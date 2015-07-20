<?php
// we use htmlLawed. Some examples here: http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/htmLawed_README.htm
require('drop/3rdparty/htmlLawed.php');


class reagent {
	// prevents XSS from being used
	public function clean_html($dirty_html) {
		$config = array('safe'=>1);
    		return htmLawed($dirty_html, $config);
	}

	// strips all HTML from input
	public function purge_html($dirty_html) {
		$config = array('safe'=>1, 'elements' => '-*');
    		return htmLawed($dirty_html, $config);
	}
	
	// we use filter_var: http://php.net/manual/en/filter.examples.validation.php
	// the settings can be found here: http://php.net/manual/en/filter.filters.validate.php
	// returns true if email looks valid
	public function validate_email($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}
	
	// returns true is ip looks valid
	public function validate_ip($ip) {
		return filter_var($ip, FILTER_VALIDATE_IP);
	}
	
	// returns true if string is between or equal to $min and $max
	public function validate_length($str, $min, $max) {
		$len = strlen($str);
		if($len < $min || $len > $max)
			return false;
		return true;
	}
	
	
}

$reagent = new reagent();
