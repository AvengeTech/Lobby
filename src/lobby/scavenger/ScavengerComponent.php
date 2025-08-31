<?php namespace lobby\scavenger;

use lobby\Lobby;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class ScavengerComponent extends SaveableComponent{

	public array $collectionSets = [];

	public function getName() : string{
		return "scavenger";
	}

	public function getCollectionSets() : array{
		return $this->collectionSets;
	}

	public function getCollectionSet(Collection $collection) : ?CollectionSet{
		return $this->collectionSets[$collection->getId()] ?? null;
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			//"DROP TABLE IF EXISTS scavenger",
			"CREATE TABLE IF NOT EXISTS scavenger(
				xuid BIGINT(16) NOT NULL,
				collection INT NOT NULL,
				found VARCHAR(10000) NOT NULL DEFAULT '{}',
				completed INT NOT NULL DEFAULT -1,
				PRIMARY KEY(xuid, collection)
			)",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT * FROM scavenger WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = $result->getRows();
		foreach($rows as $row){
			$collection = Lobby::getInstance()->getScavenger()->getCollection($row["collection"]);
			if($collection !== null){
				$this->collectionSets[$collection->getId()] = new CollectionSet($collection, json_decode($row["found"]), $row["completed"]);
			}
		}
		foreach(Lobby::getInstance()->getScavenger()->getCollections() as $collection){
			if(!isset($this->collectionSets[$collection->getId()])){
				$this->collectionSets[$collection->getId()] = new CollectionSet($collection);
			}
		}

		parent::finishLoadAsync($request);
	}

	public function saveAsync() : void{
		if(!$this->isLoaded()) return;

		$request = new ComponentRequest($this->getXuid(), $this->getName(), []);
		foreach($this->getCollectionSets() as $set){
			if($set->hasChanged()){
				$request->addQuery(new MySqlQuery($this->getXuid() . "_" . $set->getCollection()->getName(),
					"INSERT INTO scavenger(xuid, collection, found, completed) VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE found=VALUES(found), completed=VALUES(completed)",
					[
						$this->getXuid(),
						$set->getCollection()->getId(),
						json_encode($set->getFound()),
						$set->getCompleted()
					]
				));
			}
		}
		if(count($request->getQueries()) > 0){
			$this->newRequest($request, ComponentRequest::TYPE_SAVE);
			parent::saveAsync();
		}
	}

	public function save() : bool{
		if(!$this->isLoaded()) return false;

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$xuid = $this->getXuid();
		$stmt = $db->prepare("INSERT INTO scavenger(xuid, collection, found, completed) VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE found=VALUES(found), completed=VALUES(completed)");
		foreach($this->getCollectionSets() as $set){
			if($set->hasChanged()){
				$id = $set->getCollection()->getId();
				$found = json_encode($set->getFound());
				$completed = $set->getCompleted();
				$stmt->bind_param("iisi", $xuid, $id, $found, $completed);
				$stmt->execute();
				$set->setChanged(false);
			}
		}
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		$collections = [];
		foreach ($this->getCollectionSets() as $set) {
			$id = $set->getCollection()->getId();
			$found = json_encode($set->getFound());
			$completed = $set->getCompleted();
			$collections[] = [
				"collection" => $id,
				"found" => $found,
				"completed" => $completed,
			];
		}
		return [
			"collections" => $collections
		];
	}

	public function applySerializedData(array $data): void {
		foreach ($data["collections"] as $set) {
			$collection = Lobby::getInstance()->getScavenger()->getCollection($set["collection"]);
			if ($collection !== null) {
				$this->collectionSets[$collection->getId()] = new CollectionSet($collection, json_decode($set["found"]), $set["completed"]);
			}
		}
		foreach (Lobby::getInstance()->getScavenger()->getCollections() as $collection) {
			if (!isset($this->collectionSets[$collection->getId()])) {
				$this->collectionSets[$collection->getId()] = new CollectionSet($collection);
			}
		}
	}

}