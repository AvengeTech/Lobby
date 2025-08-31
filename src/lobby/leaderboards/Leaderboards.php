<?php namespace lobby\leaderboards;

use pocketmine\player\Player;

use lobby\Lobby;
use lobby\leaderboards\command\Prizes;
use lobby\leaderboards\types\{
	MysqlUpdate,
	RedAllTimeCompletionsLeaderboard,
	RedMonthlyCompletionsLeaderboard,
	RedBestTimeLeaderboard,
	GreenAllTimeCompletionsLeaderboard,
	GreenMonthlyCompletionsLeaderboard,
	GreenBestTimeLeaderboard,
	IslandAllTimeCompletionsLeaderboard,
	IslandMonthlyCompletionsLeaderboard,
	IslandBestTimeLeaderboard,
    Leaderboard
};

class Leaderboards{

	const UPDATE_TICKS = 600;

	public int $ticks = 0;

	public array $leaderboards = [];

	public array $left = [];

	public function __construct(public Lobby $plugin){
		$plugin->getServer()->getCommandMap()->registerAll("leaderboards", [
			new Prizes($plugin, "lbprizes", "View weekly/monthly leaderboard prizes!"),
		]);

		$this->leaderboards["red_alltime_completions"] = new RedAllTimeCompletionsLeaderboard(5);
		$this->leaderboards["red_monthly_completions"] = new RedMonthlyCompletionsLeaderboard(5);
		$this->leaderboards["red_best_time"] = new RedBestTimeLeaderboard(5);

		$this->leaderboards["green_alltime_completions"] = new GreenAllTimeCompletionsLeaderboard(5);
		$this->leaderboards["green_monthly_completions"] = new GreenMonthlyCompletionsLeaderboard(5);
		$this->leaderboards["green_best_time"] = new GreenBestTimeLeaderboard(5);

		$this->leaderboards["island_alltime_completions"] = new IslandAllTimeCompletionsLeaderboard(5);
		$this->leaderboards["island_monthly_completions"] = new IslandMonthlyCompletionsLeaderboard(5);
		$this->leaderboards["island_best_time"] = new IslandBestTimeLeaderboard(5);
	}

	public function getLeaderboards() : array{
		return $this->leaderboards;
	}

	public function tick() : void{
		$this->ticks++;
		if($this->ticks >= self::UPDATE_TICKS){
			$this->ticks = 0;
			foreach($this->getLeaderboards() as $key => $leaderboard){
				/** @var Leaderboard $leaderboard */
				if($leaderboard instanceof MysqlUpdate) $leaderboard->calculate();
			}
		}
	}

	public function changeLevel(Player $player, string $newlevel) : void{
		foreach($this->leaderboards as $leaderboard){
			$leaderboard->changeLevel($player, $newlevel);
		}
	}

	public function onJoin(Player $player) : void{
		unset($this->left[$player->getName()]);
		foreach($this->leaderboards as $leaderboard){
			if(!$leaderboard instanceof MysqlUpdate) $leaderboard->calculate();
			$leaderboard->spawn($player);
		}
	}

	public function onQuit(Player $player) : void{
		$this->left[$player->getName()] = true;
		foreach($this->leaderboards as $leaderboard){
			/** @var Leaderboard $leaderboard */
			$leaderboard->despawn($player);
			if($leaderboard->isOn($player) && $leaderboard instanceof MysqlUpdate) $leaderboard->calculate();
		}
	}

}