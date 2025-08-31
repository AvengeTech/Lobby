<?php

namespace lobby\scavenger;

use lobby\scavenger\entity\{
	Cheese,
	Cheeseburger,
	Glizzy,
	Shoes,
	Skull
};

class Structure {

	const TYPE_CHEESE = 1;
	const TYPE_CHEESEBURGER = 2;
	const TYPE_GLIZZY = 3;
	const TYPE_SHOES = 4;
	const TYPE_SKULL = 5;

	const SCAVENGER_SETS = [
		//todo: figure out good structure for
		//limited time scavenger hunts
		self::TYPE_CHEESE => [
			"name" => "Cheese",
			"class" => Cheese::class,
			"world" => "sn3ak",
			"locations" => [
				[1912.5, 64, 771.5, 90], //gamemode bot

				[1936.5, 123, 729.5, 90], //left tree
				[1921.5, 83, 634.5, 0], //far left on ground

				[2036.5, 63, 781.5, 0], //inside fountain
				[2030.5, 58, 800.5, 90], //side of fountain area

				[2150.5, 63, 687.5, 0], //first island snow hill

				//rooftops
				[1817.5, 93, 814.5, 0], //above main building
				[1869.5, 78, 824.5, 90], //right small circle rooftop
				[1843.5, 90, 645.5, 90], //far left building back roof
			],
			"prize" => [], //gadgets, crates, etc
		],
		self::TYPE_CHEESEBURGER => [
			"name" => "Burger",
			"class" => Cheeseburger::class,
			"world" => "sn3ak",
			"locations" => [
				//main hub
				[1925.5, 60, 744.5, 0], //opposite of treks

				[2164.5, 50.75, 903.5, 0], //second island snow
				[2120.5, 47.75, 918.5, 0], //second island snow 2

			],
			"prize" => [], //gadgets, crates, etc
		],
		self::TYPE_GLIZZY => [
			"name" => "Glizzy",
			"class" => Glizzy::class,
			"world" => "sn3ak",
			"locations" => [
				[1878.5, 69, 786.5, 0], //xyz YAW

				[1786.5, 70, 769.5, 0], //main building plant holder thing
				[1933.5, 83, 834.5, 0], //inside tree
			],
			"prize" => [], //gadgets, crates, etc
		],
		self::TYPE_SHOES => [
			"name" => "AvengeTreks",
			"class" => Shoes::class,
			"world" => "sn3ak",
			"locations" => [
				[1872.5, 69, 785.5, 0], //xyz YAW

				[1880.5, 44, 674.5, 180], //left bottom bridge
				[2025.5, 144, 610.5, 235], //top of big structure

				//central spawn
				[1942.5, 60, 745.5, 90],
				[1932.5, 83, 841.5, 0],
				[1823.5, 71, 837.5, 180],

				//main building
				[1808.5, 92, 777.5, 90],

				[1932.5, 110, 612.5, 0], //red parkour end
			],
			"prize" => [], //gadgets, crates, etc
		],
		self::TYPE_SKULL => [
			"name" => "Skull",
			"class" => Skull::class,
			"world" => "sn3ak",
			"locations" => [
				[2019.5, 60, 937.5, 0], //cave front right
				[2033.5, 53, 943.5, 0],

				[1809.5, 54, 910.5, 180], //cave back right
				[1799.5, 56, 912.5, 180],
				[1777.5, 54, 887.5, 0],

				[1974.5, 46, 609.5, 0], //cave front left
				[1954.5, 47, 611.5, 0],

				[2168.5, 56, 677.5, 180], //left island
				[2147.5, 47, 742.5, 0],

				[2147.5, 47, 920.5, 0], //right island
				[2116.5, 60, 865.5, 0],

				[2038.5, 64, 780.5, 0], //inside fountain

			],
			"prize" => [], //gadgets, crates, etc
		],
	];
}
