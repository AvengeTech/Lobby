<?php

namespace lobby\parkour;

use lobby\Lobby;
use lobby\LobbyPlayer;
use lobby\parkour\course\{
	Course,
	CourseScore,
	CourseAttempt
};
use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;
use poggit\libasynql\result\SqlSelectResult;

/**
 * @method LobbyPlayer getPlayer()
 */
class ParkourComponent extends SaveableComponent {

	public ?CourseAttempt $attempt = null;

	public array $courseScores = [];

	public function getName(): string {
		return "parkour";
	}

	public function tick(): void {
		$this->getCourseAttempt()?->updateScoreboardLines();
	}

	public function getCourseAttempt(): ?CourseAttempt {
		return $this->attempt;
	}

	public function hasCourseAttempt(): bool {
		return $this->attempt !== null;
	}

	public function setCourseAttempt(?Course $course = null): void {
		$this->attempt = $course === null ? $course : new CourseAttempt($this->getUser(), $course);
		if ($course !== null) {
			$this->getPlayer()?->setHotbar("parkour");
			$this->getPlayer()?->setFlightMode(false);
			$this->getPlayer()?->setAllowFlight(false);
		} else {
			$this->getPlayer()?->setHotbar("spawn");
			$this->getPlayer()?->setAllowFlight(true);
		}
	}

	public function getCourseScores(): array {
		return $this->courseScores;
	}

	public function getCourseScore(Course $course): ?CourseScore {
		return $this->courseScores[$course->getName()] ?? null;
	}

	public function createTables(): void {
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach ([
			"CREATE TABLE IF NOT EXISTS parkour_times(
				xuid BIGINT(16) NOT NULL,
				course VARCHAR(32) NOT NULL,
				fastest FLOAT(6, 2) NOT NULL DEFAULT '9999.99',
				total INT NOT NULL DEFAULT 0,
				total_monthly INT NOT NULL DEFAULT 0,
				PRIMARY KEY(xuid, course)
			)",
		] as $query) $db->query($query);
	}

	public function loadAsync(): void {
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT * FROM parkour_times WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null): void {
		/** @var SqlSelectResult $result */
		$result = $request->getQuery()->getResult();
		$rows = $result->getRows();
		foreach ($rows as $row) {
			$course = Lobby::getInstance()->getParkour()->getCourse($row["course"]);
			if ($course !== null) {
				$this->courseScores[$course->getName()] = new CourseScore($this->getUser(), $course, $row["fastest"], $row["total"], $row["total_monthly"]);
			}
		}
		foreach (Lobby::getInstance()->getParkour()->getCourses() as $course) {
			if ($this->getCourseScore($course) === null) {
				$this->courseScores[$course->getName()] = new CourseScore($this->getUser(), $course);
			}
		}

		parent::finishLoadAsync($request);
	}

	public function saveAsync(): void {
		if (!$this->isLoaded()) return;

		$request = new ComponentRequest($this->getXuid(), $this->getName(), []);
		foreach ($this->getCourseScores() as $score) {
			if ($score->hasChanged()) {
				$request->addQuery(new MySqlQuery(
					$score->getCourse()->getName() . "_" . $this->getXuid(),
					"INSERT INTO parkour_times(xuid, course, fastest, total, total_monthly) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE fastest=VALUES(fastest), total=VALUES(total), total_monthly=VALUES(total_monthly)",
					[
						$this->getXuid(),
						$score->getCourse()->getName(),
						$score->getFastestTime(),
						$score->getTotalCompletions(),
						$score->getTotalMonthlyCompletions(),
					]
				));
			}
		}
		if (count($request->getQueries()) > 0) {
			$this->newRequest($request, ComponentRequest::TYPE_SAVE);
			parent::saveAsync();
		}
	}

	public function save(): bool {
		if (!$this->isLoaded()) return false;

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$xuid = $this->getXuid();
		$stmt = $db->prepare("INSERT INTO parkour_times(xuid, course, fastest, total, total_monthly) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE fastest=VALUES(fastest), total=VALUES(total), total_monthly=VALUES(total_monthly)");
		foreach ($this->getCourseScores() as $score) {
			if ($score->hasChanged()) {
				$course = $score->getCourse()->getName();
				$fastest = $score->getFastestTime();
				$total = $score->getTotalCompletions();
				$totalMonthly = $score->getTotalMonthlyCompletions();
				$stmt->bind_param("isdii", $xuid, $course, $fastest, $total, $totalMonthly);
				$stmt->execute();
				$score->setChanged(false);
			}
		}
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		$courses = [];
		foreach ($this->getCourseScores() as $score) {
			$course = $score->getCourse()->getName();
			$fastest = $score->getFastestTime();
			$total = $score->getTotalCompletions();
			$totalMonthly = $score->getTotalMonthlyCompletions();
			$courses[] = [
				"course" => $course,
				"fastest" => $fastest,
				"total" => $total,
				"total_monthly" => $totalMonthly
			];
		}
		return [
			"courses" => $courses
		];
	}

	public function applySerializedData(array $data): void {
		foreach ($data["courses"] as $cdata) {
			$course = Lobby::getInstance()->getParkour()->getCourse($cdata["course"]);
			if ($course !== null) {
				$this->courseScores[$course->getName()] = new CourseScore($this->getUser(), $course, $cdata["fastest"], $cdata["total"], $cdata["total_monthly"]);
			}
		}
		foreach (Lobby::getInstance()->getParkour()->getCourses() as $course) {
			if ($this->getCourseScore($course) === null) {
				$this->courseScores[$course->getName()] = new CourseScore($this->getUser(), $course);
			}
		}
	}
}
