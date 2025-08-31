<?php namespace lobby\settings\ui;

use pocketmine\player\Player;

use lobby\settings\LobbySettings;

use core\settings\ui\SettingsUi;
use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Toggle
};
use core\utils\TextFormat;
use lobby\LobbyPlayer;

class LobbySettingsUi extends CustomForm{

	public function __construct(Player $player){
		/** @var LobbyPlayer $player */
		parent::__construct("Lobby settings");

		$settings = $player->getGameSession()->getSettings()->getSettings();
		$this->addElement(new Label("Settings"));
		$this->addElement(new Toggle("Stacking players", $settings[LobbySettings::STACKING]));
	}

	public function handle($response, Player $player) {
		/** @var LobbyPlayer $player */
		$session = $player->getGameSession()->getSettings();

		$session->setSetting(LobbySettings::STACKING, $response[1]);

		$player->showModal(new SettingsUi(TextFormat::EMOJI_CHECKMARK . TextFormat::GREEN . " Lobby settings have been updated!"));
	}

}