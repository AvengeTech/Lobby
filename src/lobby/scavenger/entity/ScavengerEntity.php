<?php namespace lobby\scavenger\entity;

use pocketmine\entity\{
	Entity,
	EntitySizeInfo,
	Location
};
use pocketmine\event\entity\{
	EntityDamageEvent,
	EntityDamageByEntityEvent
};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\{
	World,
	ChunkLoader,
	format\Chunk,
	sound\XpLevelUpSound
};

use lobby\scavenger\Collection;

use core\utils\{
	PlaySound,
	TextFormat
};
use lobby\LobbyPlayer;
use pocketmine\math\Vector3;

abstract class ScavengerEntity extends Entity implements ChunkLoader {

	public int $aliveTicks = 0;

	public int $loaderId = 0;
	public int $lastChunkHash;
	public array $loadedChunks = [];

	public function __construct(Location $location, ?CompoundTag $nbt, public Collection $collection, public int $collectionId) {
		parent::__construct($location, $nbt);
		$this->loaderId = $this->getId();
	}

	public function getCollection() : Collection{
		return $this->collection;
	}

	public function getCollectionId() : int{
		return $this->collectionId;
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();
		if($source instanceof EntityDamageByEntityEvent){
			/** @var LobbyPlayer $player */
			$player = $source->getDamager();
			if($player->isLoaded()){
				$session = $player->getGameSession()->getScavenger();
				$set = $session->getCollectionSet(($collection = $this->getCollection()));
				if($set !== null){
					$name = $collection->getName();
					if($set->isCompleted()){
						$player->sendMessage(TextFormat::RI . "You've already collected all of the " . $name . "!");
						$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.ghast.scream"), [$player]);
						return;
					}
					if($set->hasFound($this->getCollectionId())){
						$left = count($collection->getEntities()) - count($set->getFound());
						$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "lol.bruh"), [$player]);
						$player->sendMessage(TextFormat::RI . "You've already found this " . $name . "! There " . ($left !== 1 ? "are" : "is") . " still " . TextFormat::AQUA . $left . TextFormat::GRAY . " more to find!");
						return;
					}
					if($set->addFound($this->getCollectionId())){
						$player->sendMessage(TextFormat::GI . "You found all of the " . TextFormat::YELLOW . $name . TextFormat::GRAY . "! You won " . TextFormat::LIGHT_PURPLE . "x10 Loot Boxes");
						$this->getWorld()->addSound($this->getPosition(), new XpLevelUpSound(100), [$player]);
						$player->getSession()->getLootBoxes()->addLootBoxes(10);
					}else{
						$left = count($collection->getEntities()) - count($set->getFound());
						$player->sendMessage(TextFormat::GI . "You found " . $name . "! There " . ($left !== 1 ? "are" : "is") . " still " . TextFormat::AQUA . $left . TextFormat::GRAY . " more to find!");
						$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "random.orb"), [$player]);
					}
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

		return true;
	}

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(0.4, 0.4, 0.4);
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