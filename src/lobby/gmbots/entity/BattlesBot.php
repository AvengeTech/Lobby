<?php namespace lobby\gmbots\entity;

use core\utils\TextFormat;

class BattlesBot extends GamemodeBot{
	
	public function getServerType() : string{
		return "pvp";
	}

	public function getFormattedServerName() : string{
		return TextFormat::BOLD . TextFormat::RED . "PvP";
	}

	public static function getNetworkTypeId() : string{
		return "lobby:battles";
	}

	//public function onCollideWithPlayer(Player $player) : void{
	//	$player->setMotion($player->getPosition()->subtractVector($this->getPosition())->add(0, 1, 0)->normalize()->multiply(0.4));
	//	$player->sendTip(TextFormat::RI . "This gamemode is in development");
	//}

	//public function attack(EntityDamageEvent $source) : void{
	//	$source->cancel();
	//	if($source instanceof EntityDamageByEntityEvent){
	//		$player = $source->getDamager();
	//		$player->sendMessage(TextFormat::RI . "This gamemode is in development");
	//	}
	//}

}