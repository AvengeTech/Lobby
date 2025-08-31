<?php

namespace lobby\leaderboards\command;

use core\AtPlayer;
use core\command\type\CoreCommand;
use core\rank\Rank;
use core\utils\TextFormat;
use lobby\Lobby;
use lobby\LobbyPlayer as Player;
use lobby\leaderboards\ui\LeaderboardPrizesUi;

class Prizes extends CoreCommand {

	public function __construct(public Lobby $plugin, $name, $description){
		parent::__construct($name, $description);
		$this->setInGameOnly();
	}

	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args)
	{
		$sender->showModal(new LeaderboardPrizesUi());
	}
}