<?php namespace lobby\leaderboards\ui;

use core\ui\windows\SimpleForm;
use core\utils\TextFormat;

class LeaderboardPrizesUi extends SimpleForm{

	public function __construct(){
		parent::__construct(
			"Parkour Leaderboard Prizes",
			"Our parkour completion leaderboards reset on the 1st of each month. There are 3 total courses for you to compete on (Green, Red, Island)." . PHP_EOL . PHP_EOL .
			"Complete each course as many times as you can and stay on the leaderboard for a prize at the end of the month!" . PHP_EOL . PHP_EOL .

				"1st. " . TextFormat::YELLOW . "1 rank upgrade OR 1 month of Warden " . TextFormat::ICON_WARDEN . TextFormat::WHITE . " + " . TextFormat::AQUA . "5,000 shards" . TextFormat::WHITE . PHP_EOL .
			"2nd. " . TextFormat::AQUA . "5,000 shards" . TextFormat::WHITE . PHP_EOL .
			"3rd. " . TextFormat::AQUA . "4,000 shards" . TextFormat::WHITE . PHP_EOL .
			"4th. " . TextFormat::AQUA . "3,000 shards" . TextFormat::WHITE . PHP_EOL .
			"5th. " . TextFormat::AQUA . "2,000 shards" . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			"All prizes will automatically be sent to your inbox."
		);
	}
	
}