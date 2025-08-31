<?php

namespace lobby\parkour\command;

use core\AtPlayer;
use core\command\type\CoreCommand;
use core\utils\TextFormat;
use lobby\Lobby;
use lobby\LobbyPlayer as Player;

class ParkourCommand extends CoreCommand {

	public function __construct(public Lobby $plugin, $name, $description){
		parent::__construct($name, $description);
		$this->setInGameOnly();
	}

	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args)
	{
		/** @var Player $sender */
		$ps = $sender->getGameSession()->getParkour();
		if($ps->hasCourseAttempt()){
			$attempt = $ps->getCourseAttempt();
			$pos = $attempt->getLastCheckpoint() ?? $attempt->getCourse()->getBeginningPosition();
			$sender->teleport($pos);
			$sender->sendMessage(TextFormat::GI . "Teleported to last checkpoint!");
			return;
		}
		$parkour = Lobby::getInstance()->getParkour();
		if(count($parkour->getCourses()) === 1){
			$courses = $parkour->getCourses();
			$course = array_shift($courses);
		}else{
			$course = $parkour->getCourse(array_shift($args) ?? "");
			if($course === null){
				$sender->sendMessage(TextFormat::RI . "Invalid course name!");
				return;
			}
		}
		$sender->teleport($course->getBeginningPosition());
		$sender->sendMessage(TextFormat::GI . "Teleported to parkour course!");
	}

	public function getPlugin() : Lobby{
		return $this->plugin;
	}
}