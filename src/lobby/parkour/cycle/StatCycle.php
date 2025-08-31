<?php

namespace lobby\parkour\cycle;

use pocketmine\item\VanillaItems;

use lobby\Lobby;

use core\Core;
use core\discord\objects\{
	Post,
	Webhook,
	Embed,
	Footer
};
use core\inbox\object\{
	InboxInstance,
	MessageInstance
};
use core\network\protocol\ServerSubUpdatePacket;
use core\session\mysqli\data\{
	MySqlRequest,
	MySqlQuery
};
use core\utils\TextFormat;

class StatCycle {

	public function __construct() {
		foreach ([
			"CREATE TABLE IF NOT EXISTS stat_cycle(date VARCHAR(12) NOT NULL PRIMARY KEY)"
		] as $query) {
			Lobby::getInstance()->getSessionManager()->getDatabase()->query($query);
		}

		$monthlyFunc = function (bool $canReset): void {
			if ($canReset) {
				$this->runMonthlyCycle(function (): void {
					$this->resetMonthlyStats(function (): void {
					});
				});
				return;
			}
		};
		if (date("d") == 1) {
			$this->checkLastMonthlyCycle($monthlyFunc);
		}
	}

	public function checkLastMonthlyCycle(\Closure $onCompletion): void {
		Lobby::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest(
			"stat_check",
			new MySqlQuery("main", "SELECT * FROM stat_cycle WHERE date=?", [date("m/y", time())])
		), function (MySqlRequest $request) use ($onCompletion): void {
			$onCompletion(count($request->getQuery()->getResult()->getRows()) == 0);
		});
	}

	public function runMonthlyCycle(\Closure $onCompletion): void {
		Lobby::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("parkour_cycle_monthly", [
			new MySqlQuery(
				"green_totals",
				"SELECT xuid, total_monthly FROM parkour_times WHERE course='green' ORDER BY total_monthly DESC LIMIT 5"
			),
			new MySqlQuery(
				"red_totals",
				"SELECT xuid, total_monthly FROM parkour_times WHERE course='red' ORDER BY total_monthly DESC LIMIT 5"
			),
			new MySqlQuery(
				"island_totals",
				"SELECT xuid, total_monthly FROM parkour_times WHERE course='island' ORDER BY total_monthly DESC LIMIT 5"
			),
		]), function (MySqlRequest $request) use ($onCompletion): void {
			$total_green = $request->getQuery("green_totals")->getResult()->getRows();
			$total_red = $request->getQuery("red_totals")->getResult()->getRows();
			$total_island = $request->getQuery("island_totals")->getResult()->getRows();
			$xuids = [];
			foreach ($total_green as $row) $xuids[] = $row["xuid"];
			foreach ($total_red as $row) $xuids[] = $row["xuid"];
			foreach ($total_island as $row) $xuids[] = $row["xuid"];
			Core::getInstance()->getUserPool()->useUsers($xuids, function (array $users) use ($onCompletion, $total_green, $total_red, $total_island): void {
				$prizeMsg = [
					1 => "[1 rank upgrade OR 1 month of Warden, 5,000 shards]",
					2 => "[5,000 shards]",
					3 => "[4,000 shards]",
					4 => "[3,000 shards]",
					5 => "[2,000 shards]",
				];

				$totalGreen = [];
				foreach ($total_green as $key => $data) {
					$place = $key + 1;
					$totalGreen[($user = $users[$data["xuid"]])->getGamertag()] = $total = $data["total_monthly"];
					$inbox = new InboxInstance($user);
					$msg = new MessageInstance($inbox, MessageInstance::newId(), time(), 0, "Monthly Parkour leaderboard!", "You were #" . $place . " on the monthly Green parkour course leaderboard last month with " . $total . " total course completions, congratulations! " . PHP_EOL . PHP_EOL . "Your prizes: " . $prizeMsg[$place] . PHP_EOL . PHP_EOL . "Prizes are attached below!", false);
					switch ($place) {
						case 1:
							$value = 5000;
							break;
						case 2:
							$value = 5000;
							break;
						case 3:
							$value = 4000;
							break;
						case 4:
							$value = 3000;
							break;
						case 5:
							$value = 2000;
							break;
					}
					$items = [];
					$item = VanillaItems::PRISMARINE_SHARD();
					$tag = $item->getNamedTag();
					$tag->setString("command", "addshards {player} " . $value);
					$item->setNamedTag($tag);
					$item->setCustomName(TextFormat::RESET . TextFormat::AQUA . number_format($value) . " shards");
					$item->setLore([
						TextFormat::GRAY . "Tap on this item to claim",
						TextFormat::GRAY . "your shards!"
					]);
					$items[] = $item;

					if ($place == 1) {
						$i = VanillaItems::TOTEM();
						$tag = $i->getNamedTag();
						$tag->setString("command", "rankupgrade {player}");
						$i->setNamedTag($tag);
						$i->setCustomName(TextFormat::RESET . TextFormat::GOLD . "Rank upgrade");
						$i->setLore([
							TextFormat::GRAY . "Tap on this item to claim",
							TextFormat::GRAY . "your rank upgrade! If you have",
							TextFormat::GRAY . "the highest rank, you will be given",
							TextFormat::YELLOW . "30 days" . TextFormat::GRAY . " of Warden " . TextFormat::ICON_WARDEN
						]);
						$items[] = $i;
					}

					$msg->setItems($items);
					$inbox->addMessage($msg, true);
				}
				$greenText = "Green course:" . PHP_EOL;
				$place = 1;
				foreach ($totalGreen as $gamertag => $total) {
					$greenText .= $place . ". " . $gamertag . " - " . $total . " completions " . $prizeMsg[$place] . PHP_EOL;
					$place++;
				}
				$greenText = rtrim($greenText);



				$totalRed = [];
				foreach ($total_red as $key => $data) {
					$place = $key + 1;
					$totalRed[($user = $users[$data["xuid"]])->getGamertag()] = $total = $data["total_monthly"];
					$inbox = new InboxInstance($user);
					$msg = new MessageInstance($inbox, MessageInstance::newId(), time(), 0, "Monthly Parkour leaderboard!", "You were #" . $place . " on the monthly Red parkour course leaderboard last month with " . $total . " total course completions, congratulations! " . PHP_EOL . PHP_EOL . "Your prizes: " . $prizeMsg[$place] . PHP_EOL . PHP_EOL . "Prizes are attached below!", false);
					switch ($place) {
						case 1:
							$value = 5000;
							break;
						case 2:
							$value = 5000;
							break;
						case 3:
							$value = 4000;
							break;
						case 4:
							$value = 3000;
							break;
						case 5:
							$value = 2000;
							break;
					}
					$items = [];
					$item = VanillaItems::PRISMARINE_SHARD();
					$tag = $item->getNamedTag();
					$tag->setString("command", "addshards {player} " . $value);
					$item->setNamedTag($tag);
					$item->setCustomName(TextFormat::RESET . TextFormat::AQUA . number_format($value) . " shards");
					$item->setLore([
						TextFormat::GRAY . "Tap on this item to claim",
						TextFormat::GRAY . "your shards!"
					]);
					$items[] = $item;

					if ($place == 1) {
						$i = VanillaItems::TOTEM();
						$tag = $i->getNamedTag();
						$tag->setString("command", "rankupgrade {player}");
						$i->setNamedTag($tag);
						$i->setCustomName(TextFormat::RESET . TextFormat::GOLD . "Rank upgrade");
						$i->setLore([
							TextFormat::GRAY . "Tap on this item to claim",
							TextFormat::GRAY . "your rank upgrade! If you have",
							TextFormat::GRAY . "the highest rank, you will be given",
							TextFormat::YELLOW . "30 days" . TextFormat::GRAY . " of Warden " . TextFormat::ICON_WARDEN
						]);
						$items[] = $i;
					}

					$msg->setItems($items);
					$inbox->addMessage($msg, true);
				}
				$redText = "Red course:" . PHP_EOL;
				$place = 1;
				foreach ($totalRed as $gamertag => $total) {
					$redText .= $place . ". " . $gamertag . " - " . $total . " completions " . $prizeMsg[$place] . PHP_EOL;
					$place++;
				}
				$redText = rtrim($redText);



				$totalIsland = [];
				foreach ($total_island as $key => $data) {
					$place = $key + 1;
					$totalIsland[($user = $users[$data["xuid"]])->getGamertag()] = $total = $data["total_monthly"];
					$inbox = new InboxInstance($user);
					$msg = new MessageInstance($inbox, MessageInstance::newId(), time(), 0, "Monthly Parkour leaderboard!", "You were #" . $place . " on the monthly Island parkour course leaderboard last month with " . $total . " total course completions, congratulations! " . PHP_EOL . PHP_EOL . "Your prizes: " . $prizeMsg[$place] . PHP_EOL . PHP_EOL . "Prizes are attached below!", false);
					switch ($place) {
						case 1:
							$value = 5000;
							break;
						case 2:
							$value = 5000;
							break;
						case 3:
							$value = 4000;
							break;
						case 4:
							$value = 3000;
							break;
						case 5:
							$value = 2000;
							break;
					}
					$items = [];
					$item = VanillaItems::PRISMARINE_SHARD();
					$tag = $item->getNamedTag();
					$tag->setString("command", "addshards {player} " . $value);
					$item->setNamedTag($tag);
					$item->setCustomName(TextFormat::RESET . TextFormat::AQUA . number_format($value) . " shards");
					$item->setLore([
						TextFormat::GRAY . "Tap on this item to claim",
						TextFormat::GRAY . "your shards!"
					]);
					$items[] = $item;

					if ($place == 1) {
						$i = VanillaItems::TOTEM();
						$tag = $i->getNamedTag();
						$tag->setString("command", "rankupgrade {player}");
						$i->setNamedTag($tag);
						$i->setCustomName(TextFormat::RESET . TextFormat::GOLD . "Rank upgrade");
						$i->setLore([
							TextFormat::GRAY . "Tap on this item to claim",
							TextFormat::GRAY . "your rank upgrade! If you have",
							TextFormat::GRAY . "the highest rank, you will be given",
							TextFormat::YELLOW . "30 days" . TextFormat::GRAY . " of Warden " . TextFormat::ICON_WARDEN
						]);
						$items[] = $i;
					}

					$msg->setItems($items);
					$inbox->addMessage($msg, true);
				}
				$islandText = "Island course:" . PHP_EOL;
				$place = 1;
				foreach ($totalIsland as $gamertag => $total) {
					$islandText .= $place . ". " . $gamertag . " - " . $total . " completions " . $prizeMsg[$place] . PHP_EOL;
					$place++;
				}
				$islandText = rtrim($islandText);

				$server = Core::thisServer();
				if ($server->isSubServer()) {
					$server = $server->getParentServer()->getIdentifier();
				} else {
					$server = $server->getIdentifier();
				}
				$post = new Post("", "Monthly Parkour Stats - " . $server, "http://avengetech.net/pic/pfp.png", false, "", [
					new Embed(
						"",
						"rich",
						"Monthly parkour stats reset! All monthly leaderboards are now clear and ready to be filled again, get on the leaderboard for a chance to earn a prize!" . PHP_EOL . PHP_EOL .
							$greenText . PHP_EOL . PHP_EOL .
							$redText . PHP_EOL . PHP_EOL .
							$islandText,
						"",
						"ffb106",
						new Footer("Reset date: " . date("F j, Y, g:ia", time())),
						"",
						"http://avengetech.net/pic/pfp.png",
						null,
						[]
					)
				]);
				$post->setWebhook(Webhook::getWebhookByName("stats"));
				$post->send();


				Lobby::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("stat_weekly_log", new MySqlQuery(
					"main",
					"INSERT INTO stat_cycle(date) VALUES(?)",
					[date("m/y")]
				)), function (MySqlRequest $request) use ($onCompletion): void {
					$onCompletion();
				});
			});
		});
	}

	public function resetMonthlyStats(\Closure $onCompletion): void {
		Lobby::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("stat_monthly_reset", [
			new MySqlQuery("reset_parkour", "UPDATE parkour_times SET total_monthly=0"),
		]), function (MySqlRequest $request) use ($onCompletion): void {
			$servers = [];
			foreach (Core::getInstance()->getNetwork()->getServerManager()->getServersByType("lobby") as $server) {
				if ($server->getIdentifier() != Core::thisServer()->getIdentifier()) $servers[] = $server->getIdentifier();
			}
			(new ServerSubUpdatePacket([
				"server" => $servers,
				"type" => "monthly"
			]))->queue();

			$onCompletion();
		});
	}
}
