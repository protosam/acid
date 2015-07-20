<?php
// we use htmlLawed. Some examples here: http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/htmLawed_README.htm
require('drop/3rdparty/htmlLawed.php');


class reagent {

	public function limit_html($dirty_heml, $tags_allowed){
		$config = array('safe'=>1, 'elements' => '-*, '.$tags_allowed);
    		return htmLawed($dirty_html, $config);
	}

	public function clean_html($dirty_html){
		$config = array('safe'=>1);
    		return htmLawed($dirty_html, $config);
	}

	public function purge_html($dirty_html){
		$config = array('safe'=>1, 'elements' => '-*');
    		return htmLawed($dirty_html, $config);
	}
}

$reagent = new reagent();
