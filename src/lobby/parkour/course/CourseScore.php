<?php namespace lobby\parkour\course;

use core\user\User;

class CourseScore{

	public bool $changed = false;

	public function __construct(
		public User $user,
		public Course $course,

		public float $fastestTime = 9999.99,
		public int $totalCompletions = 0,
		public int $totalMonthlyCompletions = 0,
	){}

	public function getUser() : User{
		return $this->user;
	}

	public function getCourse() : Course{
		return $this->course;
	}

	public function getFastestTime() : float{
		return $this->fastestTime;
	}

	public function setFastestTime(float $time) : void{
		$this->fastestTime = $time;
		$this->setChanged();
	}
	
	public function resetMonthlyCompletions() : void{
		$this->totalMonthlyCompletions = 0;
		$this->setChanged();
	}

	public function getTotalCompletions() : int{
		return $this->totalCompletions;
	}

	public function addTotalCompletion() : void{
		$this->totalCompletions++;
		$this->totalMonthlyCompletions++;
		$this->setChanged();
	}

	public function getTotalMonthlyCompletions() : int{
		return $this->totalMonthlyCompletions;
	}

	/**
	 * Returns whether time is faster
	 */
	public function addCompletion(float $time) : bool{
		$this->addTotalCompletion();
		if($time < $this->getFastestTime()){
			$this->setFastestTime($time);
			return true;
		}
		return false;
	}

	public function hasChanged() : bool{
		return $this->changed;
	}

	public function setChanged(bool $changed = true) : void{
		$this->changed = $changed;
	}
}