<?php namespace lobby;

use core\staff\anticheat\Calculations;
use pocketmine\block\Air;
use pocketmine\event\Listener;
use pocketmine\event\player\{
	PlayerCreationEvent,
	PlayerExhaustEvent,
	PlayerJoinEvent,
	PlayerQuitEvent,
	PlayerItemUseEvent,
	PlayerInteractEvent,
	PlayerToggleFlightEvent,
	PlayerMoveEvent,
	PlayerDropItemEvent,
};
use pocketmine\event\entity\{
	EntityDamageEvent,
	EntityDamageByEntityEvent,
	EntityItemPickupEvent
};
use pocketmine\event\block\{
	BlockPlaceEvent,
	BlockBreakEvent,
	BlockUpdateEvent,
	LeavesDecayEvent
};
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\{
    InteractPacket,
    PlayerAuthInputPacket,
	types\PlayerAuthInputFlags,
	SetPlayerGameTypePacket,
};
use pocketmine\player\{
	GameMode,
	Player
};
use pocketmine\world\sound\GhastShootSound;

use lobby\settings\LobbySettings;

use core\utils\profile\ProfileUi;

class MainListener implements Listener{

	public array $dj = []; //double jump
	
	public function __construct(public Lobby $plugin){}

	/**
	 * @priority HIGHEST
	 */
	public function onCreation(PlayerCreationEvent $e){
		$e->setPlayerClass(LobbyPlayer::class);
	}

	public function onJoin(PlayerJoinEvent $e){
		$this->plugin->onPreJoin($e->getPlayer());
	}

	public function onQuit(PlayerQuitEvent $e){
		$this->plugin->onQuit($e->getPlayer());
	}

	public function onItemUse(PlayerItemUseEvent $e){
		/** @var LobbyPlayer $player */
		$player = $e->getPlayer();
		$item = $e->getItem();
		$session = $player->getGameSession()?->getHotbar();
		if($player->hasGameSession() && $session->hasHotbar()){
			$e->cancel();
			$hotbar = $session->getHotbar();
			$hotbar->handle($player, $player->getInventory()->first($item));
		}
	}

	public function onInteract(PlayerInteractEvent $e) {
		$e->cancel();
		/** @var LobbyPlayer $player */
		$player = $e->getPlayer();
		if($player->hasStack() && $player->getInventory()->getItemInHand()->getTypeId() == 0){
			$player->unstack();
		}
	}

	public function onToggleFlight(PlayerToggleFlightEvent $e) {
		/** @var LobbyPlayer $player */
		$player = $e->getPlayer();
		$flying = $e->isFlying();
		if($flying && !$player->inFlightMode()){
			$e->cancel();
			$player->setFlying(false); $player->setAllowFlight(false);
			$gm = $player->getGamemode();
			$player->getNetworkSession()->sendDataPacket(SetPlayerGameTypePacket::create(TypeConverter::getInstance()->coreGameModeToProtocol(GameMode::CREATIVE())));
			$player->getNetworkSession()->sendDataPacket(SetPlayerGameTypePacket::create(TypeConverter::getInstance()->coreGameModeToProtocol($gm)));
			
			$dv = $player->getDirectionVector();
			$player->knockback($dv->x, $dv->z, 1.5); //todo: allow double jump force to be customized
			if(!$player->isVanished() && $player->isLoaded()){
				if(($cs = $player->getSession()->getCosmetics())->hasEquippedDoubleJump()){
					$cs->getEquippedDoubleJump()->activate($player);
				}else{
					$player->getWorld()->addSound($player->getPosition(), new GhastShootSound());
				}
			}
			$this->dj[$player->getName()] = time();
		}
	}

	public function onMove(PlayerMoveEvent $e) {
		/** @var LobbyPlayer $player */
		$player = $e->getPlayer();
		if(!$player->isLoaded()) return;

		if(isset($this->dj[$player->getName()])){
			//player->getSession()->getStaff()->resetPosNoCheck();
			if($this->dj[$player->getName()] != time()){
				if(!$player->getWorld()->getBlockAt((int) $player->getPosition()->x, (int) ($player->getPosition()->y - 0.5), (int) $player->getPosition()->z) instanceof Air){
					unset($this->dj[$player->getName()]);
					if(!$player->getGameSession()->getParkour()->hasCourseAttempt()) $player->setAllowFlight(true);
				}
			}
		}

		foreach($this->plugin->getLeaderboards()->getLeaderboards() as $lb){
			$lb->doRenderCheck($player);
		}
	}

	public function onDrop(PlayerDropItemEvent $e){
		$e->cancel();
	}

	public function onPlace(BlockPlaceEvent $e){
		$e->cancel();
	}

	public function onBreak(BlockBreakEvent $e){
		$e->cancel();
	}

	public function onUpdate(BlockUpdateEvent $e){
		$e->cancel();
	}

	public function onDecay(LeavesDecayEvent $e){
		$e->cancel();
	}

	public function onDmg(EntityDamageEvent $e){
		$player = $e->getEntity();
		if($player instanceof LobbyPlayer){
			$e->cancel();
			if($e instanceof EntityDamageByEntityEvent){
				if(($killer = $e->getDamager()) instanceof LobbyPlayer && $killer->isLoaded()){
					if(
						$player->isLoaded() &&
						!$player->stackedToPlayer() &&
						!$player->onJetski() &&
						!$player->isVanished() &&
						!$player->getGameSession()->getParkour()->hasCourseAttempt()
					){
						if(
							$killer->getGameSession()->getHotbar()->getHotbar()->getName() == "spawn" &&
							$killer->getInventory()->first($killer->getInventory()->getItemInHand()) == 7
						){
							$killer->showModal(new ProfileUi($player->getSession()));
						}elseif(
							$killer->getGameSession()->getSettings()->getSetting(LobbySettings::STACKING) &&
							$player->getGameSession()->getSettings()->getSetting(LobbySettings::STACKING) &&
							!$player->stackedToPlayer() &&
							!$killer->inPlayerStack($player) &&
							count($killer->getAllStacked()) === 0 &&
							count($player->getAllStacked()) === 0
						){
							$killer->stack($player);
						}
					}
				}
			}
		}
	}

	/**public function onEntSpawn(EntitySpawnEvent $e){
		$entity = $e->getEntity();
		var_dump(get_class($entity));
		$player = \pocketmine\Server::getInstance()->getPlayerExact("sn3akrr");
		if($player instanceof Player) $player->teleport($entity->getPosition());
	}*/

	public function onItemPickup(EntityItemPickupEvent $e){
		$e->cancel();
		if(!$e->getOrigin()->isFlaggedForDespawn())
			$e->getOrigin()->flagForDespawn();
	}

	public function onExhaust(PlayerExhaustEvent $e){
		$e->cancel();
	}

	public function onInv(InventoryTransactionEvent $e){
		//if($e->getTransaction()->getSource()->getName() != "Sn3akPeak")
			$e->cancel();
	}

	public function onDpr(DataPacketReceiveEvent $e){
		$pk = $e->getPacket();
		/** @var LobbyPlayer $player */
		$player = $e->getOrigin()->getPlayer();
		if($pk instanceof InteractPacket && $pk->action === InteractPacket::ACTION_LEAVE_VEHICLE){
			if($player->stackedToPlayer()){
				$player->getStackedTo()->unstack(false);
			}elseif($player->onJetski()){
				$player->getJetski()->getUp($player);
			}
		}
		if($pk instanceof PlayerAuthInputPacket){
			if($player->onJetski() && ($jetski = $player->getJetski())->inFirstSeat($player)){
				$pLocation = $player->getLocation();
				$pLocation->pitch = 0;
				if ($pk->getInputFlags()->get(PlayerAuthInputFlags::UP)) {
					$jetski->shiftYaw = 0;
					$dir = Calculations::locationToDirectionVector($pLocation);
					if ($pk->getInputFlags()->get(PlayerAuthInputFlags::RIGHT)) {
						$dir = Calculations::locationToDirectionVector(Calculations::fakeRotate(0, 45, $pLocation));
						$jetski->shiftYaw = 45;
					} elseif ($pk->getInputFlags()->get(PlayerAuthInputFlags::LEFT)) {
						$dir = Calculations::locationToDirectionVector(Calculations::fakeRotate(0, -45, $pLocation));
						$jetski->shiftYaw = -45;
					}
					$jetski->handleControls($dir->x, $dir->z);
				} elseif ($pk->getInputFlags()->get(PlayerAuthInputFlags::UP_LEFT)) {
					$dir = Calculations::locationToDirectionVector(Calculations::fakeRotate(0, -45, $pLocation));
					$jetski->handleControls($dir->x, $dir->z);
					$jetski->shiftYaw = -45;
				} elseif ($pk->getInputFlags()->get(PlayerAuthInputFlags::UP_RIGHT)) {
					$dir = Calculations::locationToDirectionVector(Calculations::fakeRotate(0, 45, $pLocation));
					$jetski->handleControls($dir->x, $dir->z);
					$jetski->shiftYaw = 45;
				}
			}elseif($pk->getInputFlags()->get(PlayerAuthInputFlags::MISSED_SWING) && $player->hasStack()){
				$player->unstack(true);
			}
		}
	}

}