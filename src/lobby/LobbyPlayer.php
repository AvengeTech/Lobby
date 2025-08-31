<?php

namespace lobby;

use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\{
	SetActorLinkPacket,
};
use pocketmine\network\mcpe\protocol\types\entity\{
	EntityLink,
	EntityMetadataFlags,
	EntityMetadataProperties,
};
use pocketmine\player\{
	GameMode,
	Player
};
use pocketmine\world\sound\{
	PopSound,
	ExplodeSound,
};

use lobby\entity\Jetski;

use core\{
	AtPlayer
};
use core\utils\{
	PlaySound,
	TextFormat
};
use lobby\hotbar\utils\HotbarHandler;

class LobbyPlayer extends AtPlayer {

	public bool $vanishMode = false;

	public ?Player $stackedTo = null;
	public ?Player $stack = null;

	public ?Jetski $jetski = null;

	public int $lastPressurePlateActivation = 0;

	public function getGameSession(): ?LobbySession {
		return Lobby::getInstance()->getSessionManager()->getSession($this);
	}

	public function hasGameSession(): bool {
		return $this->getGameSession() !== null;
	}

	public function hasHotbar(): bool {
		return $this->getGameSession()->getHotbar()->hasHotbar();
	}

	public function getHotbar(): ?HotbarHandler {
		return $this->getGameSession()->getHotbar()->getHotbar();
	}

	public function setHotbar(string $name, bool $clear = true): void {
		$hotbar = Lobby::getInstance()->getHotbar();
		$this->getGameSession()->getHotbar()->setHotbar($hotbar->getHotbar($name), $clear);
	}

	public function inVanishMode(): bool {
		return $this->vanishMode;
	}

	public function toggleVanishMode(): bool {
		$this->setVanishMode(!$this->inVanishMode());
		return $this->inVanishMode();
	}

	public function setVanishMode(bool $vanish = true): void {
		$this->vanishMode = $vanish;
		if ($vanish) {
			foreach ($this->getServer()->getOnlinePlayers() as $player) {
				if ($player !== $this) {
					$player->despawnFrom($this);
				}
			}
		} else {
			foreach ($this->getServer()->getOnlinePlayers() as $player) {
				if ($player !== $this) {
					$player->spawnTo($this);
				}
			}
		}
	}

	public function canFly(): string|bool {
		if ($this->getGameSession()->getParkour()->hasCourseAttempt()) {
			return "You cannot fly during a parkour attempt!";
		}
		if (!$this->hasRank()) {
			return "You must have a rank to fly in the lobby! Purchase one at " . TextFormat::YELLOW . "store.avengetech.net";
		}
		return true;
	}

	public function setFlightMode(bool $mode = true, ?GameMode $gamemode = null, bool $doubleJumpEnabled = false): void {
		parent::setFlightMode($mode);
		if (!$mode && !$this->getGameSession()->getParkour()->hasCourseAttempt()) { //todo: check if doing parkour
			$this->setAllowFlight(true);
		}
	}

	public function stackedToPlayer(): bool {
		return $this->stackedTo instanceof LobbyPlayer &&
			$this->stackedTo->isConnected();
	}

	public function getStackedTo(): ?LobbyPlayer {
		return $this->stackedTo;
	}

	public function inPlayerStack(LobbyPlayer $player): bool {
		foreach ($player->getAllStacked() as $stacked) {
			if ($stacked->getName() === $player->getName()) {
				return true;
			}
		}
		return false;
	}

	public function setStackedTo(?LobbyPlayer $player): void {
		$stackedTo = $this->stackedTo;
		$this->stackedTo = $player;
		if ($player === null) {
			if ($stackedTo instanceof LobbyPlayer) {
				$link = new SetActorLinkPacket();
				$link->link = new EntityLink($stackedTo->getId(), $this->getId(), EntityLink::TYPE_REMOVE, true,true, 0);
				foreach (Server::getInstance()->getOnlinePlayers() as $p) $p->getNetworkSession()->sendDataPacket($link);
			}
			$this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, false);
		} else {
			$this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, true);
			$this->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0, 1.3, 0));

			$link = new SetActorLinkPacket();
			$link->link = new EntityLink($player->getId(), $this->getId(), EntityLink::TYPE_RIDER, true,true, 0);
			foreach (array_merge([$this], $this->getViewers()) as $p) $p->getNetworkSession()->sendDataPacket($link);
		}
	}

	public function hasStack(): bool {
		return $this->stack instanceof Player &&
			$this->stack->isConnected();
	}

	public function stack(LobbyPlayer $player): void {
		if ($this->hasStack()) {
			$this->getTopStacked()->stack($player);
		} else {
			$this->stack = $player;
			$player->setStackedTo($this);
		}
		$this->getWorld()->addSound($this->getPosition(), new PopSound());
	}

	public function unstack(bool $throw = true, bool $breakLink = false): void {
		if ($this->hasStack()) {
			if ($breakLink) {
				foreach ($this->getAllStacked() as $stacked) {
					$stacked->setStackedTo(null);
					$dv = new Vector3(mt_rand(-150, 150) / 100, 0.75, mt_rand(-150, 150) / 100);
					$stacked->setMotion($dv);
				}
				$this->getWorld()->addSound($this->getPosition(), new ExplodeSound());
			} else {
				$stack = $this->getFirstStacked();
				$stack->setStackedTo(null);
				if ($throw) {
					$stack->teleport($this->getPosition()->add(0, 1.5, 0));
					$dv = $this->getDirectionVector()->normalize()->multiply(1.75);
					$stack->setMotion(new Vector3($dv->x, 0.75, $dv->z));
					$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "firework.launch"));
				}
			}
			$this->stack = null;
		}
	}

	public function getFirstStacked(): ?LobbyPlayer {
		return $this->stack;
	}

	public function getAllStacked(): array {
		$players = [];
		if (
			$this->hasStack()
		) {
			$players[] = $next = $this->getFirstStacked();
			while ($next !== null && $next->isConnected()) {
				$next = $next->getFirstStacked();
				if ($next !== null && $next->isConnected()) $players[] = $next;
			}
		}
		return $players;
	}

	public function getTopStacked(): ?LobbyPlayer {
		$player = null;
		if (
			$this->hasStack()
		) {
			$player = $next = $this->getFirstStacked();
			while ($next !== null && $next->isConnected()) {
				$next = $player->getFirstStacked();
				if ($next !== null && $next->isConnected()) $player = $next;
			}
		}
		return $player;
	}

	public function getBottomStacked(): ?LobbyPlayer {
		$player = $this;
		if ($this->stackedToPlayer()) {
			$player = $next = $this->getStackedTo();
			while ($next !== null && $next->isConnected()) {
				$next = $player->getStackedTo();
				if ($next !== null && $next->isConnected()) $player = $next;
			}
		}
		return $player;
	}

	public function getJetski(): ?Jetski {
		return $this->jetski;
	}

	public function setJetski(?Jetski $jetski = null): void {
		$this->jetski = $jetski;
	}

	public function onJetski(): bool {
		return $this->getJetski() !== null;
	}

	public function getLastPressurePlateActivation(): int {
		return $this->lastPressurePlateActivation;
	}

	public function setLastPressurePlateActivation(): void {
		$this->lastPressurePlateActivation = time();
	}

	public function canActivatePressurePlate(): bool {
		return $this->getLastPressurePlateActivation() !== time();
	}

	public function spawnTo(Player $player): void {
		/** @var self $player */
		if (!$player->isVanished()) {
			parent::spawnTo($player);
		}
	}

	public function getLinks(): array {
		$links = parent::getLinks();
		if ($this->stackedToPlayer()) {
			$links[] = new EntityLink($this->getStackedTo()->getId(), $this->getId(), EntityLink::TYPE_RIDER, true,true, 0);
		} elseif ($this->onJetski()) {
			$links[] = new EntityLink($this->getJetski()->getId(), $this->getId(), EntityLink::TYPE_RIDER, true,true, 0);
		}
		return $links;
	}
}
