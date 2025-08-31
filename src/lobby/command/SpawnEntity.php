<?php

namespace lobby\command;

use core\AtPlayer;
use core\command\type\CoreCommand;
use core\rank\Rank;
use core\utils\TextFormat;
use lobby\Lobby;
use lobby\entity\Jetski;
use lobby\gmbots\entity\BattlesBot;
use lobby\gmbots\entity\PrisonBot;
use lobby\gmbots\entity\SkyBlockBot;
use lobby\LobbyPlayer as Player;
use core\gadgets\entity\Cake;
use core\lootboxes\entity\LootBox;

class SpawnEntity extends CoreCommand {

	public function __construct(public Lobby $plugin, string $name, string $description) {
		parent::__construct($name, $description);
		$this->setInGameOnly();
		$this->setHierarchy(Rank::HIERARCHY_HEAD_MOD);
	}

	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args)
	{
		if (count($args) === 0) {
			$sender->sendMessage(TextFormat::RI . "Invalid entity (bb, crate)");
			return;
		}
		switch (array_shift($args)) {
			case "j":
				$entity = new Jetski(new \pocketmine\entity\Location($sender->getLocation()->getX(), $sender->getLocation()->getY(), $sender->getLocation()->getZ(), $sender->getWorld(), 90, 0), null, (int) (array_shift($args) ?? 0));
				break;
			case "b":
				$entity = new BattlesBot(new \pocketmine\entity\Location($sender->getLocation()->getX(), $sender->getLocation()->getY(), $sender->getLocation()->getZ(), $sender->getWorld(), 90, 0));
				break;
			case "p":
				$entity = new PrisonBot(new \pocketmine\entity\Location($sender->getLocation()->getX(), $sender->getLocation()->getY(), $sender->getLocation()->getZ(), $sender->getWorld(), 90, 0));
				break;
			case "s":
				$entity = new SkyBlockBot(new \pocketmine\entity\Location($sender->getLocation()->getX(), $sender->getLocation()->getY(), $sender->getLocation()->getZ(), $sender->getWorld(), 90, 0));
				break;
			case "c":
				$entity = new Cake(new \pocketmine\entity\Location($sender->getLocation()->getX(), $sender->getLocation()->getY(), $sender->getLocation()->getZ(), $sender->getWorld(), 90, 0));
				break;
			case "crate":
				$entity = new LootBox(new \pocketmine\entity\Location($sender->getLocation()->getX(), $sender->getLocation()->getY(), $sender->getLocation()->getZ(), $sender->getWorld(), 90, 0));
				break;
		}
		$entity->spawnToAll();
		$sender->sendMessage(TextFormat::GI . "Spawned entity!");
	}

	public function getPlugin(): Lobby {
		return $this->plugin;
	}
}
