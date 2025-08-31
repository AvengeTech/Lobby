<?php namespace lobby\hotbar\ui;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\Core;
use core\network\ui\SelectWhichUi;
use lobby\LobbyPlayer;

class SelectServerUi extends SimpleForm{

	public function __construct(){
		parent::__construct("Server Selector", "Please select which server you would like to connect to!");

		$manager = Core::getInstance()->getNetwork()->getServerManager();

		$this->addButton(new Button("Prisons" . PHP_EOL . $manager->getPlayerCountByType("prison") . " players playing!"));
		$this->addButton(new Button("SkyBlock" . PHP_EOL . $manager->getPlayerCountByType("skyblock") . " players playing!"));
	}

	public function handle($response, Player $player){
		/** @var LobbyPlayer $player */
		if($response == 0){
			$player->showModal(new SelectWhichUi($player, "prison"));
			return;
		}
		if($response == 1){
			$player->showModal(new SelectWhichUi($player, "skyblock"));
		}
	}

}
