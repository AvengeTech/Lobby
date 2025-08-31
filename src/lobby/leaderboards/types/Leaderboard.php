<?php namespace lobby\leaderboards\types;

use pocketmine\Server;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\{
	Entity,
	Skin
};
use pocketmine\network\mcpe\convert\{
    LegacySkinAdapter,
	TypeConverter
};
use pocketmine\network\mcpe\protocol\{
	AddPlayerPacket,
	UpdateAbilitiesPacket,
	types\AbilitiesData,
	RemoveActorPacket,
	SetActorDataPacket,
	PlayerListPacket,

	types\PlayerListEntry,
	types\inventory\ItemStackWrapper,
};
use pocketmine\network\mcpe\protocol\types\entity\{
	EntityMetadataCollection,
	EntityMetadataFlags,
	EntityMetadataProperties,
	PropertySyncData
};
use pocketmine\player\Player;
use pocketmine\world\Position;

use Ramsey\Uuid\Uuid;

use lobby\Lobby;
use lobby\leaderboards\Structure as LB;

abstract class Leaderboard extends Position{

	const RENDER_DISTANCE = 35;

	public array $texts = [];
	public int $eid;

	public array $spawnedTo = [];

	public function __construct(public int $size = 10){
		$arr = LB::POSITIONS[$this->getType()];
		parent::__construct($arr[0], $arr[1], $arr[2], Server::getInstance()->getWorldManager()->getWorldByName($arr[3]));

		$this->eid = Entity::nextRuntimeId();
		$this->calculate();
	}

	abstract public function getType() : string;

	abstract public function calculate();

	public function inLeft(Player $player) : bool{ //HACK!
		return isset(Lobby::getInstance()->getLeaderboards()->left[$player->getName()]);
	}

	public function getSize() : int{
		return $this->size;
	}

	public function isOn(Player $player) : bool{
		return isset($this->texts[$player->getName()]);
	}

	public function isSpawnedTo(Player $player) : bool{
		return isset($this->spawnedTo[$player->getName()]);
	}

	public function spawn(Player $player) : void{
		if($this->isSpawnedTo($player) || $this->distance($player->getPosition()) > self::RENDER_DISTANCE) return;

		$this->spawnedTo[$player->getName()] = true;

		$texts = $this->texts;
		$eid = $this->eid;
		
		$txt = "";
		foreach($texts as $text){
			$txt .= $text . PHP_EOL;
		}
		$txt = rtrim($txt);

		$uuid = Uuid::uuid4();

		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		$pk->entries = [PlayerListEntry::createAdditionEntry($uuid, $eid, $txt, (new LegacySkinAdapter)->toSkinData(new Skin("Standard_Custom", str_repeat("\x00", 8192), "", "geometry.humanoid.custom")))];
		$player->getNetworkSession()->sendDataPacket($pk);

		$pk = new AddPlayerPacket();
		$pk->uuid = $uuid;
		$pk->username = "leaderboard";
		$pk->actorRuntimeId = $eid;
		$pk->gameMode = 0;
		$pk->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(VanillaBlocks::AIR()->asItem()));
		$pk->position = $this->asPosition();
		$flags = (
			1 << EntityMetadataFlags::IMMOBILE
		);
		$pk->abilitiesPacket = UpdateAbilitiesPacket::create(new AbilitiesData(0, 0, $eid, []));

		$collection = new EntityMetadataCollection();
		$collection->setLong(EntityMetadataProperties::FLAGS, $flags);
		$collection->setString(EntityMetadataProperties::NAMETAG, $txt);
		$collection->setFloat(EntityMetadataProperties::SCALE, 0.01);
		$pk->metadata = $collection->getAll();

		$pk->syncedProperties = new PropertySyncData([], []);

		$player->getNetworkSession()->sendDataPacket($pk);

		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_REMOVE;
		$pk->entries = [PlayerListEntry::createRemovalEntry($uuid)];
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function doRenderCheck(Player $player) : void{
		if($this->isSpawnedTo($player)){
			if($this->distance($player->getPosition()) > self::RENDER_DISTANCE){
				$this->despawn($player);
			}
		}else{
			if(
				$player->getWorld() === $this->getWorld() &&
				$this->distance($player->getPosition()) <= self::RENDER_DISTANCE
			){
				$this->spawn($player);
			}
		}
	}

	public function update(Player $player) : void{
		$texts = $this->texts;
		$eid = $this->eid;

		$txt = "";
		foreach($texts as $text){
			$txt .= $text . PHP_EOL;
		}
		$txt = rtrim($txt);

		$pk = new SetActorDataPacket();
		$pk->actorRuntimeId = $eid;
		$flags = (
			1 << EntityMetadataFlags::IMMOBILE
		);
		$collection = new EntityMetadataCollection();
		$collection->setLong(EntityMetadataProperties::FLAGS, $flags);
		$collection->setString(EntityMetadataProperties::NAMETAG, $txt);
		$collection->setFloat(EntityMetadataProperties::SCALE, 0.01);
		$pk->metadata = $collection->getAll();

		$pk->syncedProperties = new PropertySyncData([], []);

		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function despawn(Player $player) : void{
		unset($this->spawnedTo[$player->getName()]);

		$pk = new RemoveActorPacket();
		$pk->actorUniqueId = $this->eid;
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function updateSpawnedTo() : void{
		foreach($this->spawnedTo as $name => $extra){
			$player = Server::getInstance()->getPlayerExact($name);
			if($player instanceof Player){
				$this->update($player);
			}else{
				unset($this->spawnedTo[$name]);
			}
		}
	}

	public function changeLevel(Player $player, string $newlevel){
		if($newlevel == $this->getWorld()->getDisplayName()){
			$this->spawn($player);
		}else{
			if($this->isSpawnedTo($player)){
				$this->despawn($player);
			}
		}
	}

}