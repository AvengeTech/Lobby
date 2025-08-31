<?php namespace lobby;

use pocketmine\player\Player;

use lobby\{
	hotbar\HotbarComponent,
	parkour\ParkourComponent,
	scavenger\ScavengerComponent
};
use lobby\settings\LobbySettings;

use core\session\{
	PlayerSession,
	SessionManager
};
use core\settings\SettingsComponent;
use core\user\User;
use core\utils\Version;

class LobbySession extends PlayerSession{

	public function __construct(SessionManager $sessionManager, Player|User $user){
		parent::__construct($sessionManager, $user);

		$this->addComponent(new HotbarComponent($this));
		$this->addComponent(new ParkourComponent($this));
		$this->addComponent(new ScavengerComponent($this));

		$this->addComponent(new SettingsComponent($this, Version::fromString(LobbySettings::VERSION), LobbySettings::DEFAULT_SETTINGS, LobbySettings::SETTING_UPDATES));
	}

	public function getHotbar() : HotbarComponent{
		return $this->getComponent("hotbar");
	}

	public function getParkour() : ParkourComponent{
		return $this->getComponent("parkour");
	}

	public function getScavenger() : ScavengerComponent{
		return $this->getComponent("scavenger");
	}

	public function getSettings() : ?SettingsComponent{
		return $this->getComponent("settings");
	}

}