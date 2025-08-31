<?php namespace lobby\entity;

use pocketmine\entity\{
	animation\ArmSwingAnimation,
	Human,
	Location,
	Skin
};
use pocketmine\world\{
	World,
	ChunkLoader,
	format\Chunk
};
use pocketmine\event\entity\{
	EntityDamageEvent,
	EntityDamageByEntityEvent
};
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use core\chat\Chat;
use core\utils\{
	CapeData,
	GenericSound,
	PlaySound,
	TextFormat
};
use lobby\LobbyPlayer as Player;

class DjSheep extends Human implements ChunkLoader{

	const DIALOGUE = [
		"Dj Sammy Sheep in da house baybee! :cool:",
		"Yuhhhh turn up",
		"Ayo it's lit in here :fire::fire:",
		"You hear dat new Sn3ak? FIRE. :fire::fire:",
		"Check out my mixtape Sheeptape :wow::devil:",
		"We party day n' nite here :sun: :moon: :fire:",
		"Y'allz got any shards? :eyes::eyes:"
	];

	const FIND_DISTANCE = 10;
	const LOSE_DISTANCE = 15;
	
	public ?string $lookingAt = "";
	public int $lookingTicks = 0;
	
	public int $aliveTicks = 0;

	public int $musicTick = 0;
	public array $musicNotes = [
		4 => ["note.hat", "81:0"],
		8 => "note.hat",
		12 => ["note.snare", "81:24"],

		20 => ["note.hat", "81:4", "81:24"],
		24 => "note.hat",
		28 => ["note.snare", ["lol.bruh", "vine.boom", "reverb.fart", "reverb.fart.long"]],

		36 => ["note.hat", "81:5"],
		40 => "note.hat",
		44 => "note.snare",
		48 => ["note.hat", "81:6"],

		56 => "note.hat",
		60 => ["note.snare", "81:7"],


		68 => ["note.hat", "81:7"],
		72 => "note.hat",
		76 => ["note.snare", "81:24"],

		84 => ["note.hat", "81:6", "81:24"],
		88 => "note.hat",
		92 => ["note.snare", ["lol.bruh", "vine.boom", "reverb.fart", "reverb.fart.long"]],

		100 => ["note.hat", "81:5"],
		104 => "note.hat",
		108 => "note.snare",
		112 => ["note.hat", "81:4"],

		120 => "note.hat",
		124 => ["note.snare", "81:2"],


		128 => "",
	];

	public int $swingCooldown = 0;
	public int $jumpCooldown = 0;


	public int $loaderId = 0;
	public int $lastChunkHash;
	public array $loadedChunks = [];

	public function __construct(Location $position, Skin $skin){
		parent::__construct($position, $skin);

		$this->setNameTagAlwaysVisible(true);
		$this->setNametag(TextFormat::YELLOW . TextFormat::BOLD . "DJ Sheep");
		$this->setMaxHealth(10000);
		$this->setHealth(10000);

		$this->setSkin((new CapeData())->getSkinWithCape($this, "atmc"));
		$this->sendSkin();

		$this->loaderId = $this->getId();
	}

	public function swing() : void{
		if($this->swingCooldown > 0){
			$this->swingCooldown--;
		}else{
			$this->swingCooldown = 2;
			$this->broadcastAnimation(new ArmSwingAnimation($this));
		}
	}

	public function jump() : void{
		if($this->jumpCooldown > 0){
			$this->jumpCooldown--;
		}else{
			$this->jumpCooldown = 10;
			$this->motion->y = $this->gravity * 4;
		}
	}

	public function doAnimation() : void{
		/**if($this->aliveTicks %10 == 0){
			$this->setSneaking(!$this->isSneaking());
		}*/

		$this->jump();
		$this->swing();
		
		if(!$this->hasLookingAt()){
			if($this->ticksLived % 40 == 0) $this->findLookingAt();
			return;
		}
		if($this->lookingTicks >= 600){
			$this->findLookingAt();
			return;
		}

		$looking = $this->getLookingAt();
		if($this->lookingTicks % 2 == 0){
			$x = $looking->getLocation()->x - $this->getLocation()->x;
			$y = $looking->getLocation()->y - $this->getLocation()->y;
			$z = $looking->getLocation()->z - $this->getLocation()->z;
			$this->setRotation(rad2deg(atan2(-$x, $z)), rad2deg(-atan2($y, sqrt($x * $x + $z * $z))));
		}

		$this->lookingTicks++;
	}

	public function doMusic() : void{
		$this->musicTick++;
		if($this->musicTick > array_key_last($this->musicNotes)){
			$this->musicTick = 0;
		}
		if(isset($this->musicNotes[$this->musicTick])){
			$notes = $this->musicNotes[$this->musicTick];
			if(!is_array($notes)) $notes = [$notes];
			foreach($notes as $note){
				if(is_array($note)){
					$note = $note[array_rand($note)];
				}
				if(strpos($note, ":") !== false){
					$note = explode(":", $note);
					$this->getPosition()->getWorld()->addSound($this->getPosition(), new GenericSound($this->getPosition(), $note[0], 2, $note[1]));
				}else{
					$this->getPosition()->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), $note));
				}
			}
		}
	}

	public function getLookingAt() : ?Player{
		return Server::getInstance()->getPlayerExact($this->lookingAt);
	}

	public function hasLookingAt() : bool{
		return $this->getLookingAt() != null && $this->getLookingAt()->getPosition()->distance($this->getPosition()) <= self::LOSE_DISTANCE && !$this->getLookingAt()->isVanished();
	}

	public function findLookingAt() : void{
		$this->lookingTicks = 0;
		/** @var Player|null $nearest */
		$nearest = $this->getWorld()->getNearestEntity($this->getPosition(), self::FIND_DISTANCE, Player::class);
		if($nearest !== null){
			$this->lookingAt = $nearest->getName();
		}
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();
		if($source instanceof EntityDamageByEntityEvent){
			$player = $source->getDamager();
			if($player instanceof Player){
				$player->sendMessage($this->getRandomDialogue());
			}
		}
	}

	public function getRandomDialogue() : string{
		return TextFormat::YELLOW . "DJ Sheep> " . TextFormat::GOLD . Chat::convertWithEmojis(self::DIALOGUE[array_rand(self::DIALOGUE)]);
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->getWorld()->registerChunkLoader($this, (int) $this->getPosition()->x >> 4, (int) $this->getPosition()->z >> 4);
		$this->lastChunkHash = World::chunkHash((int) $this->getPosition()->x >> 4, (int) $this->getPosition()->z >> 4);
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->lastChunkHash !== ($hash = World::chunkHash($x = (int) $this->getPosition()->x >> 4, $z = (int) $this->getPosition()->z >> 4))){
			$this->registerToChunk($x, $z);

			World::getXZ($this->lastChunkHash, $oldX, $oldZ);
			$this->unregisterFromChunk($oldX, $oldZ);

			$this->lastChunkHash = $hash;
		}

		$this->aliveTicks++;

		$this->doAnimation();
		$this->doMusic();

		return $this->isAlive();
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function canPickupXp() : bool{
		return false;
	}

	public function registerToChunk(int $chunkX, int $chunkZ){
		if(!isset($this->loadedChunks[World::chunkHash($chunkX, $chunkZ)])){
			$this->loadedChunks[World::chunkHash($chunkX, $chunkZ)] = true;
			$this->getWorld()->registerChunkLoader($this, $chunkX, $chunkZ);
		}
	}

	public function unregisterFromChunk(int $chunkX, int $chunkZ){
		if(isset($this->loadedChunks[World::chunkHash($chunkX, $chunkZ)])){
			unset($this->loadedChunks[World::chunkHash($chunkX, $chunkZ)]);
			$this->getWorld()->unregisterChunkLoader($this, $chunkX, $chunkZ);
		}
	}

	public function onChunkChanged(Chunk $chunk){

	}

	public function onChunkLoaded(Chunk $chunk){

	}

	public function onChunkUnloaded(Chunk $chunk){

	}

	public function onChunkPopulated(Chunk $chunk){

	}

	public function onBlockChanged(Vector3 $block){

	}

	public function getLoaderId() : int{
		return $this->loaderId;
	}

	public function isLoaderActive() : bool{
		return !$this->isFlaggedForDespawn() && !$this->closed;
	}

}