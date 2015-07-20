<?php
// we use htmlLawed. Some examples here: http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/htmLawed_README.htm
require('drop/3rdparty/htmlLawed.php');


class reagent {

	public function clean_html($dirty_html){
		$config = array('safe'=>1);
    		return htmLawed($dirty_html, $config);
	}

	public function purge_html($dirty_html){
		$config = array('safe'=>1, 'elements' => '-*');
    		return htmLawed($dirty_html, $config);
	}
	
	
	// we use filter_var: http://php.net/manual/en/filter.examples.validation.php
	// the settings can be found here: http://php.net/manual/en/filter.filters.validate.php
	public function is_email($email){
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}
	
	public function is_ipaddr($ip){
		return filter_var($ip, FILTER_VALIDATE_IP);
	}
	
	public function check_length($str, $max){
		return (strlen($str) > $max);
	}
	
	public function check_length($str, $min, $max){
		return (strlen($str) > $max || strlen($str) < $min);
	}
}

$reagent = new reagent();
