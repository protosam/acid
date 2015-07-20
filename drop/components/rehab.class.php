<?php

class rehab {
	public $running = false;
	public function start(){
		$this->running = true;
	}
	
	public function profile(){
		if($this->running){
		}
	}
}

$rehab = new rehab();
