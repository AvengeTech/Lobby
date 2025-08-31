<?php namespace lobby\entity;

use pocketmine\Server;
use pocketmine\entity\{
	Location,
	Skin
};

class EntityRegistry{

	const LOCATIONS = [
		[
			"entity" => Jetski::class,
			"pos" => [
				[2030, 42.8, 707, 270, 0], //xyz-yaw-variant
				[2017, 42.8, 721, 270, 1],
				[2007, 42.8, 735, 270, 10],
				[2000, 42.8, 746, 270, 2],
				[1997, 42.8, 765, 270, 11],

				[1999, 42.8, 817, 270, 3],
				[2009, 42.8, 843, 270, 4],
				[2023, 42.8, 854, 270, 5],
				[2034, 42.8, 873, 270, 6],
			]
		],
		[
			"entity" => DjSheep::class,
			"skin" => ["Standard_Custom", "/[REDACTED]/skins/djsheep.dat"],
			"pos" => [
				[1895, 73, 958.5, 180]
			]
		]
	];

	//registers necessary entities + spawns them
	public static function register() : void{
		$world = Server::getInstance()->getWorldManager()->getWorldByName("sn3ak");
		if($world === null) return;

		foreach(self::LOCATIONS as $type){
			$class = $type["entity"];
			$pos = $type["pos"];

			foreach($pos as $loc){
				$x = array_shift($loc);
				$y = array_shift($loc);
				$z = array_shift($loc);
				$yaw = array_shift($loc);
				$chunk = $world->getChunk((int) $x >> 4, (int) $z >> 4);
				if($chunk === null){
					$world->loadChunk((int) $x >> 4, (int) $z >> 4);
				}

				if(isset($type["skin"])){
					$entity = new $class(new Location($x, $y, $z, $world, $yaw, 0), new Skin($type["skin"][0], file_get_contents($type["skin"][1]), "", "geometry.humanoid.custom"), ...$loc);
					$entity->spawnToAll();
				}else{
					$entity = new $class(new Location($x, $y, $z, $world, $yaw, 0), null, ...$loc);
					$entity->spawnToAll();
				}
			}
		}
	}
	
}