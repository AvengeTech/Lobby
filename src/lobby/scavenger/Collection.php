<?php namespace lobby\scavenger;

use pocketmine\Server;
use pocketmine\entity\Location;
use pocketmine\world\World;

class Collection{

	public string $name;
	public string $class;
	public string $world;

	public array $entities = [];

	public function __construct(public int $id){
		$data = Structure::SCAVENGER_SETS[$id];
		$this->name = $data["name"];
		$class = $this->class = $data["class"];
		$this->world = $data["world"];
		$world = $this->getWorld();
		if($world !== null){
			foreach($data["locations"] as $key => $loc){
				$location = new Location($loc[0], $loc[1], $loc[2], $world, $loc[3], 0);
				$entity = $this->entities[$key] = new $class($location, null, $this, $key);
				$entity->spawnToAll();
			}
		}
	}

	public function getId() : int{
		return $this->id;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getClass() : string{
		return $this->class;
	}

	public function getWorld() : World{
		return Server::getInstance()->getWorldManager()->getWorldByName($this->world);
	}

	public function getEntities() : array{
		return $this->entities;
	}

}