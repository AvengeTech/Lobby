<?php namespace lobby;

use pocketmine\scheduler\Task;

use core\Core;

class MainTask extends Task{

	public int $runs = 0;

	public function __construct(public Lobby $plugin){}

	public function onRun() : void{
		$this->runs++;
		if($this->runs %5 == 0){
			$this->plugin->getSessionManager()->tick();
		}
		
		if($this->runs %20 == 0){
			$this->plugin->getLeaderboards()->tick();
		}

		if($this->runs %100 === 0){
			$ft = Core::getInstance()->getEntities()->getFloatingText();
			$ft->getText("spawn-2")?->update();
			$ft->getText("welcome-2")?->update();
			$ft->getText("prison-count")?->update();
			$ft->getText("skyblock-count")?->update();
		}
	}

}