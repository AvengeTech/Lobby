<?php namespace lobby\command;

use core\AtPlayer;
use core\command\type\CoreCommand;
use core\utils\TextFormat;
use lobby\Lobby;
use lobby\LobbyPlayer as Player;

class SpawnCommand extends CoreCommand {

	public function __construct(public Lobby $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setInGameOnly();
	}

	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args)
	{
		$sender->teleport(Lobby::getSpawn());
		$sender->sendMessage(TextFormat::GI . "Teleported to spawn!");
	}
}