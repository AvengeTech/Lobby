<?php

namespace lobby\block;

use pocketmine\block\WeightedPressurePlate;
use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\world\sound\{
	ClickSound,
	XpLevelUpSound
};

use lobby\Lobby;
use lobby\LobbyPlayer;

use core\utils\TextFormat;

class IronPressurePlate extends WeightedPressurePlate {

	protected function recalculateCollisionBoxes(): array {
		return [AxisAlignedBB::one()];
	}

	public function hasEntityCollision(): bool {
		return true;
	}

	public function onEntityInside(Entity $player): bool {
		/** @var LobbyPlayer $player */
		if ($player instanceof Player && $player->isLoaded()) {
			if (!$player->canActivatePressurePlate()) return true;
			$player->setLastPressurePlateActivation();

			$session = $player->getGameSession()->getParkour();
			if ($session->hasCourseAttempt()) {
				$attempt = $session->getCourseAttempt();
				if (($current = $attempt->getCurrentCheckpoint()) !== null && $current->equals($this->getPosition())) {
					$attempt->addCurrentCheckpoint();
					$player->sendMessage(TextFormat::GI . "Reached checkpoint " . TextFormat::YELLOW . $attempt->getCurrentCheckpointId());

					$this->click();
				} elseif ($current === null && $attempt->getCourse()->getEndPosition()->equals($this->getPosition())) {
					$lastScore = $session->getCourseScore($attempt->getCourse())->getFastestTime();
					$player->getSession()->getLootBoxes()->addShards($shards = 5);
					$player->sendMessage(TextFormat::GI . "You beat the parkour in " . TextFormat::YELLOW . $attempt->getTimeElapsed() . " seconds" . TextFormat::GRAY . " and earned " . TextFormat::AQUA . $shards . " shard" . ($shards > 1 ? "s" : "") . "!");
					if ($attempt->complete($session)) {
						$this->getPosition()->getWorld()->addSound($this->getPosition(), new XpLevelUpSound(5));
						$player->sendMessage(TextFormat::GI . "You beat your highscore of " . TextFormat::YELLOW . $lastScore . " seconds");
					}
					$completions = $session->getCourseScore($attempt->getCourse())->getTotalCompletions();
					$bonus = match (true) {
						($completions % 250) => 1500,
						($completions % 100) => 500,
						($completions % 25) => 50,
						($completions % 10) => 25,
						default => 0
					};
					$session->setCourseAttempt();
					$this->click();
				}
			} else {
				foreach (Lobby::getInstance()->getParkour()->getCourses() as $course) {
					//var_dump($course->getStartPosition());
					//var_dump($this->getPosition());
					if ($course->getStartPosition()->equals($this->getPosition())) {
						$session->setCourseAttempt($course);
						$player->sendMessage(TextFormat::GI . "Started parkour course " . TextFormat::YELLOW . $course->getName() . TextFormat::GRAY . ", good luck!");
						$this->click();
						break;
					}
				}
			}
		}
		return true;
	}

	public function click(): void {
		$this->getPosition()->getWorld()->addSound($this->getPosition(), new ClickSound());
	}
}
