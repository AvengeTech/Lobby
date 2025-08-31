<?php namespace lobby\hotbar\utils;

use pocketmine\player\Player;

class HotbarHandler{

	public int $ticks = 0;

	public function __construct(
		public string $name,
		public int $defaultSlot,
		
		public array $items,
		public \Closure $actions,
		
		public ?\Closure $tick = null,
		public ?\Closure $afterSetup = null
	){}

	public function getName() : string{
		return $this->name;
	}

	public function getDefaultSlot() : int{
		return $this->defaultSlot;
	}

	public function getItems() : array{
		return $this->items;
	}

	public function getActions() : \Closure{
		return $this->actions;
	}

	public function setup(Player $player, bool $clear = true){
		if($clear){
			$player->getArmorInventory()->clearAll();
			$player->getCursorInventory()->clearAll();
			$player->getInventory()->clearAll();
		}

		$player->getInventory()->setHeldItemIndex($this->getDefaultSlot());
		foreach($this->getItems() as $slot => $item){
			$player->getInventory()->setItem($slot, $item);
		}

		if($this->hasAfterSetupAction()){
			($this->getAfterSetupAction())($player);
		}
	}

	public function handle(Player $player, int $slot) : void{
		$this->getActions()($player, $slot);
	}

	public function ticks() : bool{
		return $this->getTickAction() !== null;
	}

	public function getTickAction() : ?callable{
		return $this->tick;
	}

	public function tick(Player $player) : void{
		$this->ticks++;
		$this->getTickAction()($player, $this->ticks);
	}

	public function hasAfterSetupAction() : bool{
		return $this->afterSetup !== null;
	}

	public function getAfterSetupAction() : ?\Closure{
		return $this->afterSetup;
	}
	
}