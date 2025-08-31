<?php namespace lobby\gmbots;

use lobby\gmbots\entity\{
	BattlesBot,
	PrisonBot,
	SkyBlockBot
};

class Structure{
	
	const LOCATIONS = [
		"world" => "sn3ak",
		"bots" => [
			[
				"class" => PrisonBot::class,
				"pos" => [1916.5, 63.95, 780.5]
			],
			[
				"class" => SkyBlockBot::class,
				"pos" => [1916.5, 63.95, 788.5]
			],
			/**[
				"class" => BattlesBot::class,
				"pos" => [1913.5, 63.95, 794.5]
			],*/
		]
	];
	
	const GAMEMODE_TEXT = [
		"prison" => "test",
		"skyblock" => "test",
	];
	
}