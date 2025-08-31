<?php namespace lobby\leaderboards\types;

use lobby\Lobby;

use core\Core;
use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\utils\TextFormat;

class IslandMonthlyCompletionsLeaderboard extends Leaderboard implements MysqlUpdate{

	public function getType() : string{
		return "island_monthly_completions";
	}

	public function calculate() : void{
		Lobby::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("update_leaderboard_" . $this->getType(), new MySqlQuery(
			"main",
			"SELECT xuid, total_monthly FROM parkour_times WHERE course='island' ORDER BY total_monthly DESC LIMIT " . $this->getSize() . ";",
			[]
		)), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			$xuids = [];
			foreach($rows as $row){
				$xuids[] = $row["xuid"];
			}
			Core::getInstance()->getUserPool()->useUsers($xuids, function(array $users) use($rows) : void{
				$texts = [TextFormat::AQUA . TextFormat::BOLD . TextFormat::EMOJI_TROPHY . " Completions [MONTHLY] " . TextFormat::EMOJI_TROPHY . PHP_EOL . TextFormat::WHITE . "[Resets every month!]"];
				$i = 1;
				foreach($rows as $row){
					$texts[($gt = $users[$row["xuid"]]->getGamertag())] =
						TextFormat::RED . $i . ". " .
						TextFormat::YELLOW . $gt . " " . TextFormat::GRAY . "- " .
						TextFormat::AQUA . number_format($row["total_monthly"]);
					$i++;
				}
				$this->texts = $texts;
				$this->updateSpawnedTo();
			});
		});
	}

}