<?php namespace lobby\parkour;

class Structure{
	
	const COURSE_GREEN = 0;
	const COURSE_RED = 1;
	const COURSE_ISLAND = 2;
	
	const COURSES = [
		self::COURSE_GREEN => [
			"name" => "Green",
			"speed" => 1,
			"jump" => 1,
			"world" => "sn3ak",
			"beginning" => [1946, 68, 820],
			"start" => [1946, 68, 822],
			"checkpoints" => [
				[1953, 80, 876]
			],
			"end" => [1964, 78, 927]
		],
		self::COURSE_RED => [
			"name" => "Red",
			"speed" => 1,
			"jump" => 0,
			"world" => "sn3ak",
			"beginning" => [1946, 68, 748],
			"start" => [1946, 68, 746],
			"checkpoints" => [
				[1943, 76, 696],
				[1979, 97, 605],
			],
			"end" => [1920, 111, 616]
		],
		self::COURSE_ISLAND => [
			"name" => "Island",
			"speed" => 1,
			"jump" => 1,
			"world" => "sn3ak",
			"beginning" => [2036, 47, 690],
			"start" => [2039, 47, 690],
			"checkpoints" => [
				[2084, 46, 690],
				[2112, 48, 672],
				[2156, 47, 757],
				[2131, 49, 836],
				[2128, 49, 877],
				[2090, 47, 890],
			],
			"end" => [2033, 49, 895]
		]
	];
	
}