<?php namespace lobby\hotbar;

use pocketmine\player\Player;

use lobby\hotbar\utils\HotbarHandler;

use core\session\component\BaseComponent;

class HotbarComponent extends BaseComponent{

	const CLICK_DELAY = 0.2;

	public float $clickDelay = 0;

	public ?HotbarHandler $hotbar = null;

	public function getName() : string{
		return "hotbar";
	}

	public function tick() : void{
		$player = $this->getPlayer();
		if(
			$player !== null &&
			$player->isConnected() &&
			$this->hasHotbar() &&
			$this->getHotbar()->ticks()
		){
			$this->getHotbar()->tick($player);
		}
	}

	public function setClicked() : void{
		$this->clickDelay = microtime(true);
	}

	public function canClick() : bool{
		return $this->clickDelay + self::CLICK_DELAY < microtime(true);
	}

	public function getHotbar() : ?HotbarHandler{
		return $this->hotbar;
	}

	public function hasHotbar() : bool{
		return $this->getHotbar() !== null;
	}

	public function setHotbar(?HotbarHandler $hotbar = null, bool $clear = true) : void{
		$old = $this->getHotbar();
		$this->hotbar = $hotbar;
		if(($player = $this->getPlayer()) instanceof Player){
			if($clear){
				$player->getArmorInventory()->clearAll();
				$player->getCursorInventory()->clearAll();
				$player->getInventory()->clearAll();
			}
			if($hotbar !== null){
				$hotbar->setup($player, $clear);
			}
		}
	}

}