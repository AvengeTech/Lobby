<?php namespace lobby\parkour\course;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\{
	Entity,
	Skin
};
use pocketmine\network\mcpe\convert\{
    LegacySkinAdapter,
	TypeConverter
};
use pocketmine\network\mcpe\protocol\{
	AddPlayerPacket,
	UpdateAbilitiesPacket,
	types\AbilitiesData,
	PlayerListPacket,
	RemoveActorPacket,

	types\PlayerListEntry,
	types\entity\EntityMetadataCollection,
	types\entity\EntityMetadataFlags,
	types\entity\EntityMetadataProperties,
	types\entity\PropertySyncData,
	types\inventory\ItemStackWrapper
};
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\{
	Position,
	sound\PopSound
};

use Ramsey\Uuid\Uuid;

use lobby\{
	LobbyPlayer as Player,

	parkour\ParkourComponent
};

use core\Core;
use core\scoreboards\ScoreboardObject;
use core\user\User;
use core\utils\TextFormat;

class CourseAttempt{

	public Player $player;
	public float $started;
	
	public int $currentCheckpoint = 0;
	public float $checkpointHintCooldown = 0;

	public float $fastestCache;

	public ScoreboardObject $scoreboard;
	public array $lines = [];
	
	public function __construct(
		public User $user,
		public Course $course
	){
		/** @var Player $player */
		$player = $this->player = $user->getPlayer();
		$this->started = microtime(true);

		$score = $player->getGameSession()->getParkour()->getCourseScore($this->getCourse());
		$this->fastestCache = $fastest = $score->getFastestTime();
		Core::getInstance()->getScoreboards()->removeScoreboard($player, true);
		$scoreboard = $this->scoreboard = new ScoreboardObject($player);
		$lines = $this->lines = [
			0 => TextFormat::GRAY . "Course: " . TextFormat::YELLOW . $course->getName(),
			1 => " ",
			2 => TextFormat::GRAY . "Time: " . TextFormat::AQUA . $this->getTimeElapsed() . "s",
			3 => TextFormat::GRAY . "Checkpoint: " . TextFormat::GREEN . round($this->getCurrentCheckpoint()->distance($player->getPosition()), 2) . "m",
			4 => "  ",
			5 => TextFormat::GRAY . "High score: " . TextFormat::GREEN . $fastest . "s",
			6 => TextFormat::GRAY . "Completions: " . TextFormat::WHITE . $score->getTotalCompletions(),
			7 => "   ",
			8 => TextFormat::AQUA . "store.avengetech.net",
		];
		$scoreboard->send($lines);
	}

	public function getPlayer() : Player{
		return $this->player;
	}
	
	public function getUser() : User{
		return $this->user;
	}
	
	public function getCourse() : Course{
		return $this->course;
	}
	
	public function sendCheckpointHint() : void{
		$player = $this->getPlayer();
		if($this->checkpointHintCooldown > microtime(true)){
			$player->sendMessage(TextFormat::RI . "You can use the checkpoint hint again in " . TextFormat::YELLOW . round($this->checkpointHintCooldown - microtime(true), 2) . "s");
			return;
		}
		$this->checkpointHintCooldown = microtime(true) + 10;

		$player->sendMessage(TextFormat::GI . "Displaying checkpoint location hint!");

		$direction = ($this->getCurrentCheckpoint() ?? $this->getCourse()->getEndPosition())->subtractVector($player->getPosition()->asVector3())->normalize();
		$startPos = $player->getPosition()->asVector3();

		$max = 7;
		for($i = 0; $i <= $max; $i++){
			Core::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($player, $startPos, $direction, $i, $max) : void{
				if(!$player->isConnected()) return;

				$id = Entity::nextRuntimeId();

				$skin = (new LegacySkinAdapter)->toSkinData(new Skin("Standard_Custom", str_repeat("\x00", 8192), "", "geometry.humanoid.custom"));

				$uuid = Uuid::uuid4();
				$text = ($i == $max ? TextFormat::GREEN . "This way!" : TextFormat::EMOJI_ARROW_UP);

				$pk = new PlayerListPacket();
				$pk->type = PlayerListPacket::TYPE_ADD;
				$pk->entries = [PlayerListEntry::createAdditionEntry($uuid, $id, $text, $skin)];
				$player->getNetworkSession()->sendDataPacket($pk);

				$pk = new AddPlayerPacket();
				$pk->uuid = $uuid;
				$pk->username = $text;
				$pk->actorRuntimeId = $id;
				$pk->position = $pos = $startPos->addVector($direction->multiply($i + 1));
				$pk->gameMode = 0;
				$pk->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(VanillaBlocks::AIR()->asItem()));
				$flags = (
					1 << EntityMetadataFlags::IMMOBILE
				);

				$collection = new EntityMetadataCollection();
				$collection->setLong(EntityMetadataProperties::FLAGS, $flags);
				$collection->setString(EntityMetadataProperties::NAMETAG, $text);
				$collection->setFloat(EntityMetadataProperties::SCALE, 0.01);
				$pk->metadata = $collection->getAll();

				$pk->syncedProperties = new PropertySyncData([], []);

				$pk->abilitiesPacket = UpdateAbilitiesPacket::create(new AbilitiesData(0, 0, $id, []));

				$player->getNetworkSession()->sendDataPacket($pk);

				$pk = new PlayerListPacket();
				$pk->type = PlayerListPacket::TYPE_REMOVE;
				$pk->entries = [PlayerListEntry::createRemovalEntry($uuid)];
				$player->getNetworkSession()->sendDataPacket($pk);

				$player->getWorld()->addSound($pos, new PopSound(), [$player]);

				Core::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($player, $id) : void{
					if(!$player->isConnected()) return;
					
					$pk = new RemoveActorPacket();
					$pk->actorUniqueId = $id;
					$player->getNetworkSession()->sendDataPacket($pk);
				}), 40);
			}), $i * 5);
		}
	}
	
	public function getLastCheckpoint() : ?Position{
		return $this->getCourse()->getCheckpoints()[$this->getCurrentCheckpointId() - 1] ?? $this->getCourse()->getStartPosition();
	}
	
	public function addCurrentCheckpoint() : void{
		$this->currentCheckpoint++;
	}

	public function getCurrentCheckpointId() : int{
		return $this->currentCheckpoint;
	}
	
	public function getCurrentCheckpoint() : ?Position{
		return $this->getCourse()->getCheckpoints()[$this->getCurrentCheckpointId()] ?? null;
	}
	
	public function getStarted() : float{
		return $this->started;
	}
	
	public function getTimeElapsed() : float{
		return round(microtime(true) - $this->getStarted(), 2);
	}
	
	public function getScoreboard() : ScoreboardObject{
		return $this->scoreboard;
	}
	
	public function getLines() : array{
		return $this->lines;
	}
	
	public function updateScoreboardLines() : void{
		$this->lines[2] = TextFormat::GRAY . "Time: " . TextFormat::AQUA . ($time = $this->getTimeElapsed()) . "s";
		$end = false;
		$chP = $this->getCurrentCheckpoint();
		if($chP === null){
			$end = true;
			$chP = $this->getCourse()->getEndPosition();
		}
		$this->lines[3] = TextFormat::GRAY . ($end ? "End" : "Checkpoint") . ": " . TextFormat::GREEN . round($chP->distance($this->getPlayer()->getPosition()), 2) . "m";

		if($time > $this->fastestCache){
			$this->lines[5] = TextFormat::GRAY . "High score: " . TextFormat::RED . $this->fastestCache . "s";
		}

		ksort($this->lines);
		$this->getScoreboard()->update($this->getLines());
	}

	public function removeScoreboard() : void{
		$this->getScoreboard()->remove();
		Core::getInstance()->getScoreboards()->addScoreboard($this->getPlayer());
	}
	
	public function complete(ParkourComponent $session) : bool{
		$this->removeScoreboard();
		return $session->getCourseScore($this->getCourse())->addCompletion($this->getTimeElapsed());
	}
	
}