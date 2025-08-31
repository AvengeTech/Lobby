<?php

namespace lobby\scavenger;

use pocketmine\entity\{
	EntityFactory,
	EntityDataHelper
};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

use lobby\Lobby;
use lobby\scavenger\entity\{
	Cheese,
	Cheeseburger,
	Glizzy,
	Shoes,
	Skull
};

use core\Core;

class Scavenger {

	public array $collections = [];

	public function __construct(public Lobby $plugin) {
		$this->registerEntities();
		foreach (Structure::SCAVENGER_SETS as $id => $data) {
			$this->collections[$id] = new Collection($id);
		}
	}

	public function registerEntities(): void {
		EntityFactory::getInstance()->register(Cheese::class, function (World $world, CompoundTag $nbt): Cheese {
			return new Cheese(EntityDataHelper::parseLocation($nbt, $world), $nbt, $this->getCollection(Structure::TYPE_CHEESE), Structure::TYPE_CHEESE);
		}, ["lobby:cheese"]);
		EntityFactory::getInstance()->register(Cheeseburger::class, function (World $world, CompoundTag $nbt): Cheeseburger {
			return new Cheeseburger(EntityDataHelper::parseLocation($nbt, $world), $nbt, $this->getCollection(Structure::TYPE_CHEESEBURGER), Structure::TYPE_CHEESEBURGER);
		}, ["lobby:cheeseburger"]);
		EntityFactory::getInstance()->register(Glizzy::class, function (World $world, CompoundTag $nbt): Glizzy {
			return new Glizzy(EntityDataHelper::parseLocation($nbt, $world), $nbt, $this->getCollection(Structure::TYPE_GLIZZY), Structure::TYPE_GLIZZY);
		}, ["lobby:hotdog"]);
		EntityFactory::getInstance()->register(Shoes::class, function (World $world, CompoundTag $nbt): Shoes {
			return new Shoes(EntityDataHelper::parseLocation($nbt, $world), $nbt, $this->getCollection(Structure::TYPE_SHOES), Structure::TYPE_SHOES);
		}, ["lobby:shoes"]);
		EntityFactory::getInstance()->register(Skull::class, function (World $world, CompoundTag $nbt): Skull {
			return new Skull(EntityDataHelper::parseLocation($nbt, $world), $nbt, $this->getCollection(Structure::TYPE_SKULL), Structure::TYPE_SKULL);
		}, ["lobby:skull"]);
	}

	public function getCollections(): array {
		return $this->collections;
	}

	public function getCollection(int $id): ?Collection {
		return $this->collections[$id] ?? null;
	}
}
