<?php

namespace lobby\hotbar;

use pocketmine\item\{
	VanillaItems
};
use pocketmine\block\utils\DyeColor;
use pocketmine\player\Player;

use lobby\Lobby;
use lobby\hotbar\utils\HotbarHandler;

use core\Core;
use core\cosmetics\ui\CosmeticsUi;
use core\network\server\ui\SelectQueueUi;
use core\rules\uis\RulesUi;
use core\vote\uis\TopVotersPage;
use core\utils\{
	BlockRegistry,
	profile\ProfileUi,
	TextFormat
};
use lobby\LobbyPlayer;

class Hotbar {

	public array $hotbars = [];

	public function __construct(public Lobby $plugin) {
		$this->setup();
	}

	public function setup(): void {
		$this->hotbars["spawn"] = new HotbarHandler("spawn", 4, [
			0 => VanillaItems::DYE()->setColor(DyeColor::LIME())->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Players: " . TextFormat::GREEN . "Visible"),

			1 => BlockRegistry::CONDUIT()->asItem()->setCustomName(TextFormat::RESET . TextFormat::AQUA . "Cosmetics"),

			3 => VanillaItems::PAPER()->setCustomName(TextFormat::RESET . TextFormat::RED . "Top Voters List"),
			4 => VanillaItems::COMPASS()->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Navigator"),
			5 => VanillaItems::BOOK()->setCustomName(TextFormat::RESET . TextFormat::AQUA . "Rules" . TextFormat::GRAY . " (Please read!)"),

			7 => VanillaItems::TOTEM()->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Profile"),
			8 => VanillaItems::FEATHER()->setCustomName(TextFormat::RESET . TextFormat::GREEN . "Toggle flight"),
		], function (Player $player, int $slot) {
			/** @var LobbyPlayer $player */
			switch ($slot) {
				default:
					break;
				case 0:
					if ($player->toggleVanishMode()) {
						$i = VanillaItems::DYE()->setColor(DyeColor::GRAY());
						$i->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Players: " . TextFormat::GRAY . "Invisible");
						$player->getInventory()->setItemInHand($i);

						$player->sendMessage(TextFormat::YI . "Players are now invisible.");
					} else {
						$i = VanillaItems::DYE()->setColor(DyeColor::LIME());
						$i->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Players: " . TextFormat::GREEN . "Visible");
						$player->getInventory()->setItemInHand($i);

						$player->sendMessage(TextFormat::YI . "Players are now visible.");
					}
					break;

				case 1:
					$player->showModal(new CosmeticsUi($player));
					break;
				case 2:
					$session = $player->getSession()->getGadgets();
					if (!$session->hasDefaultGadget()) return;
					$gadget = $session->getDefaultGadget(true);
					if ($session->getTotal($gadget) <= 0) {
						$player->sendMessage(TextFormat::RI . "You are out of this gadget! Find more in " . TextFormat::LIGHT_PURPLE . "Loot Boxes");
						return;
					}
					if ($session->hasDelay($gadget)) {
						$player->sendMessage(TextFormat::RI . "You can use this gadget again in " . TextFormat::YELLOW . $session->getDelayLeft($gadget) . "s");
						return;
					}
					$gadget->onUse($player);
					break;

				case 3:
					$player->showModal(new TopVotersPage());
					break;
				case 4:
					$player->showModal(new SelectQueueUi($player));
					break;
				case 5:
					$player->showModal(new RulesUi(($pl = Core::getInstance()), $pl->getRules()->getRuleManager()));
					break;

				case 7:
					$player->showModal(new ProfileUi($player->getSession()));
					break;
				case 8:
					$canFly = $player->canFly();
					if (is_string($canFly)) {
						$player->sendMessage(TextFormat::RI . $canFly);
						return;
					}
					$player->setFlightMode(!$player->inFlightMode());
					$player->sendMessage(TextFormat::GI . "You are " . ($player->inFlightMode() ? "now" : "no longer") . " in flight mode");
					break;
			}
		}, null, function (Player $player) {
			/** @var LobbyPlayer $player */
			$session = $player->getSession()->getGadgets();
			if ($session->hasDefaultGadget()) {
				$item = clone ($dg = $session->getDefaultGadget(true))->getItem();
				$item->setCustomName($item->getCustomName() . TextFormat::GRAY . " (" . number_format($session->getTotal($dg)) . " left)");
				$player->getInventory()->setItem(2, $item);
			}
		});


		$this->hotbars["parkour"] = new HotbarHandler("parkour", 0, [
			0 => VanillaItems::PAPER()->setCustomName(TextFormat::RESET . TextFormat::GREEN . "Last checkpoint"),
			1 => VanillaItems::BONE()->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Checkpoint hint"),

			4 => VanillaItems::STICK()->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Restart"),

			8 => VanillaItems::GHAST_TEAR()->setCustomName(TextFormat::RESET . TextFormat::RED . "Quit attempt"),
		], function (Player $player, int $slot) {
			/** @var LobbyPlayer $player */
			$session = $player->getGameSession()->getParkour();
			if (!$session->hasCourseAttempt()) {
				$player->setHotbar("spawn");
				$player->sendMessage(TextFormat::RI . "No active parkour course attempt.");
				return;
			}
			$attempt = $session->getCourseAttempt();
			switch ($slot) {
				default:
					break;
				case 0:
					$player->teleport($attempt->getLastCheckpoint());
					$player->sendMessage(TextFormat::YI . "Teleported to last checkpoint");
					break;
				case 1:
					$attempt->sendCheckpointHint();
					break;

				case 4:
					$session->setCourseAttempt($attempt->getCourse());
					$player->teleport($attempt->getCourse()->getStartPosition());
					$player->sendMessage(TextFormat::YI . "Course restarted, good luck!");
					break;

				case 8:
					$session->getCourseAttempt()?->removeScoreboard();
					$session->setCourseAttempt();
					$player->teleport($attempt->getCourse()->getBeginningPosition());
					$player->sendMessage(TextFormat::RI . "Better luck next time! " . TextFormat::EMOJI_FROWN);
					break;
			}
		}, function (Player $player, int $runs) {
		});
	}

	public function getHotbar(string $name): ?HotbarHandler {
		return $this->hotbars[$name] ?? null;
	}
	
}