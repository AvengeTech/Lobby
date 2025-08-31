<?php namespace lobby\scavenger\entity;

class Shoes extends ScavengerEntity{

	protected function getInitialDragMultiplier(): float {
		return 0.0;
	}

	protected function getInitialGravity(): float {
		return 0.0;
	}
	
	public static function getNetworkTypeId() : string{
		return "lobby:shoes";
	}

}