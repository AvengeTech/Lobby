<?php namespace lobby\entity;

use pocketmine\Server;
use pocketmine\entity\{
	Entity,
	EntitySizeInfo,
	Location
};
use pocketmine\event\entity\{
	EntityDamageEvent,
	EntityDamageByEntityEvent
};
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\{
	EntityLink,
	EntityMetadataFlags,
	EntityMetadataProperties
};
use pocketmine\player\Player;
use pocketmine\world\{
	World,
	ChunkLoader,
	format\Chunk,
	particle\SplashParticle
};

use core\utils\TextFormat;
use lobby\LobbyPlayer;

class Jetski extends Entity implements ChunkLoader{

	const IDLE_TIMER = 1200;

	const COLOR_RED = 0;
	
	public Location $spawnpoint;

	public int $aliveTicks = 0;

	public int $idleTicks = 0;
	public bool $hasMoved = false;

	public int $loaderId = 0;
	public int $lastChunkHash;
	public array $loadedChunks = [];
	
	public ?Player $firstSeat = null;
	public ?Player $secondSeat = null;

	public int $boostTimer = 0;

	public float $shiftYaw = 0;

	public static function getNetworkTypeId() : string{
		return "lobby:jetski";
	}

	protected function getInitialDragMultiplier(): float
	{
		return 0.0;
	}

	protected function getInitialGravity(): float
	{
		return 0.0;
	}

	public function __construct(Location $location, ?CompoundTag $nbt = null, int $color = self::COLOR_RED){
		parent::__construct($location, $nbt);
		$this->spawnpoint = $location;

		$this->loaderId = $this->getId();

		$this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::WASD_CONTROLLED, true);
		$this->getNetworkProperties()->setByte(EntityMetadataProperties::CONTROLLING_RIDER_SEAT_NUMBER, 0);
		$this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SADDLED, true);

		$this->getNetworkProperties()->setInt(EntityMetadataProperties::VARIANT, $color);
	}

	public function getSpawnpoint() : Location{
		return $this->spawnpoint;
	}

	public function getColor() : int{
		return $this->getNetworkProperties()->getAll()[EntityMetadataProperties::VARIANT]->getValue();
	}

	public function setColor(int $color) : void{
		$this->getNetworkProperties()->setInt(EntityMetadataProperties::VARIANT, $color);
	}

	public function getFirstSeat() : ?Player{
		return $this->firstSeat;
	}

	public function firstSeatOccupied() : bool{
		return ($player = $this->getFirstSeat()) !== null && $player->isConnected();
	}

	public function inFirstSeat(Player $player) : bool{
		return $this->firstSeatOccupied() && $this->getFirstSeat()->getName() == $player->getName();
	}

	public function setFirstSeat(?Player $player = null) : void{
		$this->firstSeat = $player;
	}

	public function getSecondSeat() : ?Player{
		return $this->secondSeat;
	}

	public function secondSeatOccupied() : bool{
		return ($player = $this->getSecondSeat()) !== null && $player->isConnected();
	}

	public function inSecondSeat(Player $player) : bool{
		return $this->secondSeatOccupied() && $this->getSecondSeat()->getName() == $player->getName();
	}

	public function setSecondSeat(?Player $player = null) : void{
		$this->secondSeat = $player;
	}

	public function sitDown(Player $player) : bool{
		/** @var LobbyPlayer $player */
		if(!$this->firstSeatOccupied()){
			$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, true);
			$player->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0, 2.1, -0.6));

			$link = new SetActorLinkPacket();
			$link->link = new EntityLink($this->getId(), $player->getId(), EntityLink::TYPE_RIDER, true,true, 0);
			foreach($this->getViewers() as $p) $p->getNetworkSession()->sendDataPacket($link);

			$player->setJetski($this);
			$this->setFirstSeat($player);

			$this->setOwningEntity($player);
			return true;
		}
		if(!$this->secondSeatOccupied()){
			$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, true);
			$player->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0, 2.3, -1.4));

			$link = new SetActorLinkPacket();
			$link->link = new EntityLink($this->getId(), $player->getId(), EntityLink::TYPE_RIDER, true,true, 0);
			foreach ($this->getViewers() as $p) $p->getNetworkSession()->sendDataPacket($link);

			$player->setJetski($this);
			$this->setSecondSeat($player);
			return true;
		}
		return false;
	}

	public function sendLinksTo(Player $player) : void{
		$packets = [];
		if($this->firstSeatOccupied()){
			$link = new SetActorLinkPacket();
			$link->link = new EntityLink($this->getId(), $this->getFirstSeat()->getId(), EntityLink::TYPE_RIDER, true,true, 0);
			$packets[] = $link;
		}
		if($this->secondSeatOccupied()){
			$link = new SetActorLinkPacket();
			$link->link = new EntityLink($this->getId(), $this->getSecondSeat()->getId(), EntityLink::TYPE_RIDER, true,true, 0);
			$packets[] = $link;
		}
		if(count($packets) > 0) foreach($packets as $pk) $player->getNetworkSession()->sendDataPacket($pk);
	}

	public function getUp(Player $player) : bool{
		/** @var LobbyPlayer $player */
		if(
			($fs = $this->inFirstSeat($player)) ||
			($ss = $this->inSecondSeat($player))
		){
			$link = new SetActorLinkPacket();
			$link->link = new EntityLink($this->getId(), $player->getId(), EntityLink::TYPE_REMOVE, true,true, 0);
			foreach (Server::getInstance()->getOnlinePlayers() as $p) $p->getNetworkSession()->sendDataPacket($link);
			
			$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, false);

			if($fs){
				$this->setFirstSeat();
				$this->setOwningEntity(null);
			}elseif($ss) $this->setSecondSeat();
			$player->setJetski();
			return true;
		}
		return false;
	}

	public function handleControls(float $motionX, float $motionZ): void {
		$speedF = 1.25;
		$maxSpeed = 1.25;
		$x = $motionX / $speedF;
		$z = $motionZ / $speedF;

		if ($x != 0 && $z != 0) {
			$this->motion->x += $x / 5;
			$this->motion->z += $z / 5;
			if ($this->motion->x > $maxSpeed) $this->motion->x = $maxSpeed;
			if ($this->motion->x < -$maxSpeed) $this->motion->x = -$maxSpeed;
			if ($this->motion->z > $maxSpeed) $this->motion->z = $maxSpeed;
			if ($this->motion->z < -$maxSpeed) $this->motion->z = -$maxSpeed;
			$this->hasMoved = true;
			$this->idleTicks = 0;
		}
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();
		if($source instanceof EntityDamageByEntityEvent){
			/** @var LobbyPlayer $player */
			$player = $source->getDamager();
			if($player instanceof Player){
				if($player->onJetski()){
					$player->sendMessage(TextFormat::RI . "You are already on a jetski!");
					return;
				}
				if($player->stackedToPlayer()) return;
				if(!$this->sitDown($player)){
					$player->sendMessage(TextFormat::RI . "This jetski is full!");
					return;
				}
			}
		}
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

		$hasUpdate = parent::entityBaseTick($tickDiff);
		$this->aliveTicks++;

		if($this->firstSeatOccupied()){
			$player = $this->getFirstSeat();
			$this->setRotation($player->getLocation()->yaw + $this->shiftYaw, 0);

			$pos = $this->getPosition()->asVector3();
			$dv = $this->getDirectionVector();
			$pos = $pos->subtract($dv->x * 1.8, -0.3, $dv->z * 1.8);
			for($i = 0; $i <= 3; $i++) $this->getPosition()->getWorld()->addParticle($pos, new SplashParticle());
		}elseif($this->hasMoved){
			$this->idleTicks++;
			if($this->idleTicks >= self::IDLE_TIMER){
				$this->teleport($this->getSpawnpoint());
			}
		}

		$this->motion->x *= 0.8;
		$this->motion->z *= 0.8;
		if (abs($this->motion->x) < 0.00025) $this->motion->x = 0;
		if (abs($this->motion->z) < 0.00025) $this->motion->z = 0;

		return true;
	}

	public function spawnTo(Player $player) : void{
		parent::spawnTo($player);
		if($this->firstSeatOccupied()){

		}
		if($this->secondSeatOccupied()){

		}
	}

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(3, 1.8, 4);
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