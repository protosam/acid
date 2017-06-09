<?php
// A debug function that writes to dbg.log
function dbg($str){
	if(file_exists('dbg.log'))
		$current = file_get_contents('dbg.log');
	else
		$current = "";

	$current .= "\n".$str;
	file_put_contents('dbg.log', $current);
}

// start buffering
ob_start();
// start session
session_start();
// Get the directory above processing.php
$cms_dir = realpath(dirname(__FILE__).'/..').'/';
// Make our working directory the directory above drop/processing.php
chdir($cms_dir);

// in case of command line runs
if(!isset($_SERVER['REQUEST_URI'])){
	$_SERVER['REQUEST_URI'] = '/';
}

// This variable is for the special die function.
$overdose = false;

// Special die function that prevents shutdown() procedure
// It is meant for unrecoverable errors that will cause
// more problems during shutdown() procedure
function overdose($message = '')
{	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
	$overdose = true;

	// end buffering
	ob_end_flush();

	die($message);
}

// make sure the config file exists
if(!file_exists('drop/config.php'))
	die('Copy drop/config.example.php to drop/config.php and edit it!');

// Include all the things we will need.
require('drop/config.php');

// set the timezone...
date_default_timezone_set($CONF['date_default_timezone']);


// Connect to keystore
if($CONF['redis']['enabled'] == true){
	$redis = new Redis();
	try {
		$redis->connect($CONF['redis']['hostname'], $CONF['redis']['port']);
	} catch (Exception $e) {
	    overdose("Couldn't connected to Redis: ".$e->getMessage());
	}
}

// Determine if we only need redis. This may be neccessary for speed gains.
if(!isset($redis_only) || $redis_only != true){

	require('drop/components/catalyst.class.php');
	require('drop/components/reagent.class.php');
	require('drop/components/bouncer.class.php');
	require('drop/components/vision.class.php');


	// Connect to the database
	$db = new mysqli($CONF['database']['hostname'], $CONF['database']['username'], $CONF['database']['password'], $CONF['database']['database'], $CONF['database']['port'], $CONF['database']['socket']);
	if ($db->connect_error)
		overdose("Error connecting to MySQL");

	// setup catalyst to use the $db link
	catalyst::setlink($db);



	// include all the database related files
	$dbfiles = preg_grep('/.php$/', scandir('drop/database'));
	foreach($dbfiles as $k => $dbfilename){
		include('drop/database/'.$dbfilename);
	}


	// Lets figure out what template file we're using... if any.
	// Also provides footer/header logic
	require('drop/vision/controller.php');

	// initiate the variable explicitly for catch-all.
	$tpl_file = false;
	// iterate the $THEMES array, to figure out which match to use for our path.
	foreach($TEMPLATES as $dir => $file){
		if(preg_match('/^'.preg_quote($dir, '/').'/', $_SERVER['REQUEST_URI'])){
			$tpl_file = $file;
			break;
		}

	}


	// lets run the no template function if there is no template...
	if(!$tpl_file && $tpl_file == "")
		vision_no_template();

	// lets spit out a header...
	if($tpl_file && $tpl_file != "")
		vision_header();



	// Pretty much the footer code.
	function shutdown()
	{	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
		// Make our working directory the directory above drop/processing.php
		chdir($cms_dir);

		// if we used overdose() we will just do nothing futher.
		if($overdose)
			return;

		if($tpl_file && $tpl_file != "")
			vision_footer();

		// ensure db connection is closed out.
		$db->close();

		// ensure redis connection is closed
		if(isset($redis)){
			$redis->close();
		}

		// end buffering
		ob_end_flush();
	}

	// make sure the shutdown command is called before we exist our script
	register_shutdown_function('shutdown');

}
