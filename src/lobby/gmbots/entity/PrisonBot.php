<?php namespace lobby\gmbots\entity;

use core\utils\TextFormat;

class PrisonBot extends GamemodeBot{

	public function getServerType() : string{
		return "prison";
	}

	public function getFormattedServerName() : string{
		return TextFormat::GOLD . TextFormat::BOLD . "PRISON";
	}

	public static function getNetworkTypeId() : string{
		return "lobby:prison";
	}

}