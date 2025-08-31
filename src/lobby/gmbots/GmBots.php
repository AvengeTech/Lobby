<?php namespace lobby\gmbots;

use pocketmine\Server;
use pocketmine\entity\Location;

use lobby\Lobby;

class GmBots{

	public function __construct(public Lobby $plugin){
		$this->spawn();
	}

	public function spawn() : void{
		$world = Server::getInstance()->getWorldManager()->getWorldByName(Structure::LOCATIONS["world"]);
		if($world === null) return;
		foreach(Structure::LOCATIONS["bots"] as $data){
			$class = $data["class"];
			$pos = $data["pos"];
			$entity = new $class(new Location(array_shift($pos), array_shift($pos), array_shift($pos), $world, 90, 0));
			$entity->spawnToAll();
		}
	}

}