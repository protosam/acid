<?php
// this array controls which template file is used based on the URL path
// first match will be used.
$TEMPLATES = array(
	'/blah/stuff' => 'drop/vision/overall2.tpl',
	'/' => 'drop/vision/overall.tpl',
);


// this is logic neccessary for our headers
function vision_header()
{	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
	$tpl = new vision($tpl_file);
	$tpl->parse('header');
	$tpl->out('header');
}

// this is logic neccessary for our footers
function vision_footer()
{	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
	$tpl = new vision($tpl_file);
	$tpl->parse('footer');
	$tpl->out('footer');
}


// This function is ran in place of vision_header() if the template file is
// empty for a specified path
function vision_no_template()
{
	// Do nothing example.
}
