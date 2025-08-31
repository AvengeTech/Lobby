<?php namespace lobby\scavenger;

class CollectionSet{
	
	public bool $changed = false;

	public function __construct(
		public Collection $collection,
		public array $found = [],
		public int $completed = -1
	){}

	public function getCollection() : Collection{
		return $this->collection;
	}
	
	public function getFound() : array{
		return $this->found;
	}

	public function hasFound(int $collectionId) : bool{
		return in_array($collectionId, $this->getFound());
	}

	public function addFound(int $collectionId) : bool{
		$this->found[] = $collectionId;
		$this->setChanged();

		if(count($this->getFound()) === count($this->getCollection()->getEntities())){
			$this->setCompleted();
			return true;
		}
		return false;
	}
	
	public function isCompleted() : bool{
		return $this->getCompleted() !== -1;
	}

	public function getCompleted() : int{
		return $this->completed;
	}
	
	public function setCompleted() : void{
		$this->completed = time();
		$this->setChanged();
	}

	public function getCompletedFormatted() : string{
		if($this->getCompleted() == -1) return "NOT YET";
		return date("m/d/y", $this->getCompleted());
	}
	
	public function hasChanged() : bool{
		return $this->changed;
	}
	
	public function setChanged(bool $changed = true) : void{
		$this->changed = $changed;
	}
	
}