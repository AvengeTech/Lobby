<?php namespace lobby\leaderboards\types;

use lobby\Lobby;

use core\Core;
use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\utils\TextFormat;

class RedBestTimeLeaderboard extends Leaderboard implements MysqlUpdate{

	public function getType() : string{
		return "red_best_time";
	}

	public function calculate() : void{
		Lobby::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("update_leaderboard_" . $this->getType(), new MySqlQuery(
			"main",
			"SELECT xuid, fastest FROM parkour_times WHERE course='red' ORDER BY fastest ASC LIMIT " . $this->getSize() . ";",
			[]
		)), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			$xuids = [];
			foreach($rows as $row){
				$xuids[] = $row["xuid"];
			}
			Core::getInstance()->getUserPool()->useUsers($xuids, function(array $users) use($rows) : void{
				$texts = [TextFormat::RED . TextFormat::BOLD . TextFormat::EMOJI_TROPHY . " Best Times " . TextFormat::EMOJI_TROPHY];
				$i = 1;
				foreach($rows as $row){
					$texts[($gt = $users[$row["xuid"]]->getGamertag())] =
						TextFormat::RED . $i . ". " .
						TextFormat::YELLOW . $gt . " " . TextFormat::GRAY . "- " .
						TextFormat::AQUA . $row["fastest"] . "s";
					$i++;
				}
				$this->texts = $texts;
				$this->updateSpawnedTo();
			});
		});
	}

}