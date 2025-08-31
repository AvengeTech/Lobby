<?php

namespace lobby;

use pocketmine\Server;
use pocketmine\entity\{
	Location,
	Skin
};
use pocketmine\entity\effect\{
	EffectInstance,
	VanillaEffects
};
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

use lobby\{
	LobbyPlayer as Player,

	entity\EntityRegistry,
	gmbots\GmBots,
	hotbar\Hotbar,
	leaderboards\Leaderboards,
	parkour\Parkour,
	scavenger\Scavenger
};
use lobby\command\{
	SpawnEntity,
	SpawnCommand
};
use lobby\leaderboards\ui\LeaderboardPrizesUi;

use core\Core;
use core\session\SessionManager;
use core\utils\{
	TextFormat
};
use core\utils\entity\{
	AtIcon,
	Trophy
};

class Lobby extends PluginBase {

	public static ?self $instance = null;
	public SessionManager $sessionManager;

	public GmBots $gmBots;
	public Hotbar $hotbar;
	public Leaderboards $leaderboards;
	public Parkour $parkour;
	public Scavenger $scavenger;

	public static Position $spawn;

	public function onEnable(): void {
		self::$instance = $this;
		$this->sessionManager = new SessionManager($this, LobbySession::class, "lobby");

		$this->gmBots = new GmBots($this);
		$this->hotbar = new Hotbar($this);
		$this->leaderboards = new Leaderboards($this);
		$this->parkour = new Parkour($this);
		$this->scavenger = new Scavenger($this);

		EntityRegistry::register();

		$this->getServer()->getPluginManager()->registerEvents(new MainListener($this), $this);
		$this->getScheduler()->scheduleRepeatingTask(new MainTask($this), 1);
		$this->getServer()->getCommandMap()->registerAll("lobby", [
			new SpawnEntity($this, "se", "Spawn gamemode entity"),
			new SpawnCommand($this, "spawn", "Teleport back to spawn"),
		]);

		Core::getInstance()->getEntities()->getCustomEntityRegistry()->registerEntities([
			"lobby:battles",
			"lobby:prison",
			"lobby:skyblock",

			"lobby:cheese",
			"lobby:cheeseburger",
			"lobby:hotdog",
			"lobby:shoes",
			"lobby:skull",

			"lobby:jetski"
		]);

		self::$spawn = new Position(1903.5, 65, 784.5, $world = $this->getServer()->getWorldManager()->getWorldByName("sn3ak"));
		//$world->setTime(23500);
		//$world->stopTime();
		$this->spawnIcons();

		Core::getInstance()->getNetwork()->getServerManager()->addSubUpdateHandler(function (string $server, string $type, array $data): void {
			switch ($type) {
				case "monthly":
					foreach (Server::getInstance()->getOnlinePlayers() as $player) {
						/** @var LobbyPlayer $player */
						if ($player->isLoaded()) {
							$session = $player->getGameSession()->getParkour();
							foreach ($session->getCourseScores() as $score) $score->resetMonthlyCompletions();
						}
					}
					break;
			}
		});
	}

	public function onDisable(): void {
		$this->getSessionManager()->close();
	}

	public static function getInstance(): self {
		return self::$instance;
	}

	public function getSessionManager(): SessionManager {
		return $this->sessionManager;
	}

	public function getGmBots(): GmBots {
		return $this->gmBots;
	}

	public function getHotbar(): Hotbar {
		return $this->hotbar;
	}

	public function getLeaderboards(): Leaderboards {
		return $this->leaderboards;
	}

	public function getParkour(): Parkour {
		return $this->parkour;
	}

	public function getScavenger(): Scavenger {
		return $this->scavenger;
	}

	public static function getSpawn(): Position {
		return self::$spawn;
	}

	public function spawnIcons(): void {
		$pos = [
			//main
			["x" => 1945.5, "y" => 79, "z" => 784.5, "size" => 6],

			//red course
			[
				"name" => TextFormat::BOLD . TextFormat::AQUA . "Return to spawn",
				"x" => 1929.5,
				"y" => 111,
				"z" => 622.5,
				"func" => function (Player $player): void {
					$player->teleport(Location::fromObject(self::getSpawn(), null, -90));
				}
			],
			[
				"name" => TextFormat::BOLD . TextFormat::AQUA . "Go to beginning",
				"x" => 1925.5,
				"y" => 111,
				"z" => 626.5,
				"func" => function (Player $player): void {
					$player->teleport(new Location(1944.5, 68, 748.5, $player->getPosition()->getWorld(), -135, 0));
				}
			],

			//green course
			[
				"name" => TextFormat::BOLD . TextFormat::AQUA . "Return to spawn",
				"x" => 1967.5,
				"y" => 78,
				"z" => 934.5,
				"func" => function (Player $player): void {
					$player->teleport(Location::fromObject(self::getSpawn(), null, -90));
				}
			],
			[
				"name" => TextFormat::BOLD . TextFormat::AQUA . "Go to beginning",
				"x" => 1961.5,
				"y" => 78,
				"z" => 934.5,
				"func" => function (Player $player): void {
					$player->teleport(new Location(1944.5, 68, 820.5, $player->getPosition()->getWorld(), -45, 0));
				}
			],

			//island course
			[
				"name" => TextFormat::BOLD . TextFormat::AQUA . "Return to spawn",
				"x" => 2027.5,
				"y" => 47,
				"z" => 898.5,
				"func" => function (Player $player): void {
					$player->teleport(Location::fromObject(self::getSpawn(), null, -90));
				}
			],
			[
				"name" => TextFormat::BOLD . TextFormat::AQUA . "Go to beginning",
				"x" => 2027.5,
				"y" => 47,
				"z" => 891.5,
				"func" => function (Player $player): void {
					$player->teleport(new Location(2036.5, 47, 690.5, $player->getPosition()->getWorld(), -90, 0));
				}
			],
		];
		$world = $this->getServer()->getWorldManager()->getDefaultWorld();
		if ($world !== null) { //double check
			foreach ($pos as $key => $xyz) {
				$chunk = $world->getChunk((int) $xyz["x"] >> 4, (int) $xyz["z"] >> 4);
				if ($chunk === null) {
					$world->loadChunk((int) $xyz["x"] >> 4, (int) $xyz["z"] >> 4);
				}

				$icon = new AtIcon(new Location($xyz["x"], $xyz["y"], $xyz["z"], $world, 140, 0), new Skin("Standard_Custom", file_get_contents("/[REDACTED]/skins/techie.dat"), "", "geometry.humanoid.custom"), $xyz["name"] ?? "", $xyz["func"] ?? null, $xyz["size"] ?? 1);
				$icon->spawnToAll();
			}
		}

		$pos = [
			[
				"x" => 2038.5,
				"y" => 46.95,
				"z" => 674.5,
				"func" => function (Player $player): void {
					$player->showModal(new LeaderboardPrizesUi());
				}
			],
			[
				"x" => 1949.5,
				"y" => 59.95,
				"z" => 764,
				"func" => function (Player $player): void {
					$player->showModal(new LeaderboardPrizesUi());
				}
			],
			[
				"x" => 1949.5,
				"y" => 59.95,
				"z" => 805,
				"func" => function (Player $player): void {
					$player->showModal(new LeaderboardPrizesUi());
				}
			],
		];
		if ($world !== null) { //double check
			foreach ($pos as $key => $xyz) {
				$chunk = $world->getChunk((int) $xyz["x"] >> 4, (int) $xyz["z"] >> 4);
				if ($chunk === null) {
					$world->loadChunk((int) $xyz["x"] >> 4, (int) $xyz["z"] >> 4);
				}

				$trophy = new Trophy(new Location($xyz["x"], $xyz["y"], $xyz["z"], $world, 140, 0), null, $xyz["func"] ?? null);
				$trophy->spawnToAll();
			}
		}
	}

	public function onPreJoin(Player $player): void {
		$player->setAllowFlight(true);
		$player->teleport(self::getSpawn(), 270, 0);

		$player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 0x7fffffff, 0, false));
		$player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 0x7fffffff, 0, false));
		$player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 0x7fffffff, 1, false));
	}

	public function onJoin(Player $player): void {
	}

	public function onQuit(Player $player): void {
		$player->unstack(true, true);
		if ($player->onJetski()) {
			$player->getJetski()->getUp($player);
		}
	}
}
