<?php namespace lobby\gmbots\entity;

use core\utils\TextFormat;

class SkyBlockBot extends GamemodeBot{

	public function getServerType() : string{
		return "skyblock";
	}

	public function getFormattedServerName() : string{
		return TextFormat::AQUA . TextFormat::BOLD . "SKYBLOCK";
	}

	public static function getNetworkTypeId() : string{
		return "lobby:skyblock";
	}

}