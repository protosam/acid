<?php

class rehab {
	public $running = false;
	public function start(){
		$this->running = true;
	}
	
	public function profile(){
		if($this->running){
			 $runtime = (microtime(true) - SCRIPT_START_TIME)/60;
			echo '<hr>';
			echo memory_get_peak_usage();
			echo '<hr>';
			echo $runtime.'s';
			echo '<hr>';
		}
	}
}

$rehab = new rehab();
