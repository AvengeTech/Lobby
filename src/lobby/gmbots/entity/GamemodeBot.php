<?php namespace lobby\gmbots\entity;

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
use pocketmine\world\{
	World,
	ChunkLoader,
	format\Chunk,
	sound\PopSound
};

use core\Core;
use core\network\server\ui\JoinQueueUi;
use core\utils\TextFormat;

use lobby\LobbyPlayer;
use pocketmine\player\Player;

abstract class GamemodeBot extends Entity implements ChunkLoader{

	public int $aliveTicks = 0;

	public int $loaderId = 0;
	public int $lastChunkHash;
	public array $loadedChunks = [];

	public array $lastPop = [];

	abstract public function getServerType() : string;

	abstract public function getFormattedServerName() : string;

	public function __construct(Location $location, ?CompoundTag $nbt = null){
		parent::__construct($location, $nbt);
		
		//$this->setNametagAlwaysVisible(true);
		//$this->updateNametag();

		$this->loaderId = $this->getId();
	}

	protected function getInitialDragMultiplier(): float
	{
		return 0.0;
	}

	protected function getInitialGravity(): float
	{
		return 0.0;
	}

	public function updateNametag() : void{
		$this->setNametag(
			$this->getFormattedServerName() . PHP_EOL . TextFormat::RESET . TextFormat::GRAY .
			Core::getInstance()->getNetwork()->getServerManager()->getPlayerCountByType($this->getServerType()) . " players"
		);
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();
		if($source instanceof EntityDamageByEntityEvent){
			$player = $source->getDamager();

			if(!($player instanceof LobbyPlayer)) return;

			$this->pop($player);
			$player->showModal(new JoinQueueUi($player, $this->getServerType()));
		}
	}

	public function onCollideWithPlayer(Player $player) : void{
	/** @var LobbyPlayer $player */
		$this->pop($player);
		$player->setMotion($player->getPosition()->subtractVector($this->getPosition())->add(0, 1, 0)->normalize()->multiply(0.4));
		$player->showModal(new JoinQueueUi($player, $this->getServerType()));
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->getWorld()->registerChunkLoader($this, (int) $this->getPosition()->x >> 4, (int) $this->getPosition()->z >> 4);
		$this->lastChunkHash = World::chunkHash((int) $this->getPosition()->x >> 4, (int) $this->getPosition()->z >> 4);
	}

	public function pop(Player $player){
		if(isset($this->lastPop[$player->getName()]) && microtime(true) - $this->lastPop[$player->getName()] < 0.5)
			return;

		$this->lastPop[$player->getName()] = microtime(true);
		$this->getPosition()->getWorld()->addSound($player->getPosition()->add(0, 1, 0), new PopSound(), [$player]);
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
		if($this->aliveTicks %100 === 0){
			//$this->updateNametag();
		}

		//$this->setRotation($this->getLocation()->getYaw() + 4, 0);

		//todo: update total players in nametag

		return true;
	}

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.5, 2, 1.5);
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