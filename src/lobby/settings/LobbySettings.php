<?php namespace lobby\settings;

interface LobbySettings{

	const VERSION = "1.0.0";

	//Normal
	const STACKING = 1;

	//Premium

	const DEFAULT_SETTINGS = [
		self::STACKING => true
	];

	const SETTING_UPDATES = [

	];

}