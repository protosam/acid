<?php

class rehab {
	public $running = false;
	public function start(){
		$this->running = true;
	}
	
	public function profile(){
		if($this->running){
			echo '<hr>';
			echo memory_get_peak_usage();
			echo '<hr>';
			echo memory_get_peak_usage(true);
			echo '<hr>';
		}
	}
}

$rehab = new rehab();
