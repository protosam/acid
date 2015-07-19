<?php
// Get the directory above processing.php
$cms_dir = realpath(dirname(__FILE__).'/..').'/';
// Make our working directory the directory above lib/processing.php
chdir($cms_dir);

// This variable is for the special die function.
$fallout = false;

// Special die function that prevents shutdown() procedure
// It is meant for unrecoverable errors that will cause
// more problems during shutdown() procedure
function fallout($message = '')
{	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
	$fallout = true;
	die($message);
}

// make sure the config file exists
if(!file_exists('lib/config.php'))
	die('Copy lib/config.example.php to lib/config.php and edit it!');

// Include all the things we will need.
require('lib/config.php');
require('lib/3rdparty/catalyst.class.php');
require('lib/3rdparty/reagent.class.php');
require('lib/3rdparty/vision.class.php');


// Connect to the database
$db = new mysqli($CONF['database']['hostname'], $CONF['database']['username'], $CONF['database']['password'], $CONF['database']['database'], $CONF['database']['port'], $CONF['database']['socket']);
if ($db->linker->connect_error)
	fallout("Error connecting to MySQL");

// setup catalyst to use the $db link
catalyst::setlink($db);



// include all the database related files
//$dbfiles = preg_grep('/.db.php$/', scandir('lib/database'));
$dbfiles = scandir('lib/database');
foreach($dbfiles as $k => $dbfilename){
	include($dbfilename);
}


// Lets figure out what template file we're using... if any.
require('themes/config.php');

// initiate the variable explicitly for catch-all.
$tpl_file = false;
// iterate the $THEMES array, to figure out which match to use for our path.
foreach($THEMES as $dir => $file){
	if(preg_match('/^'.preg_quote($dir, '/').'/', $_SERVER['REQUEST_URI'])){
		$tpl_file = $file;
		break;
	}

}

// lets spit out a header...
if($tpl_file && $tpl_file != ""){
	$xtpl = new vision($tpl_file);
	$xtpl->parse('header');
	$xtpl->out('header');
}



// Pretty much the footer code.
function shutdown()
{	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
	// Make our working directory the directory above lib/processing.php
	chdir($cms_dir);

	// if we used fallout() we will just do nothing futher.
	if($fallout)
		return;

	// lets spit out a footer...
	if($tpl_file && $tpl_file != ""){
		$xtpl->restart($tpl_file);
		$xtpl->parse('footer');
		$xtpl->out('footer');
	}

	// ensure db connection is closed out.
	$db->close();
}

// make sure the shutdown command is called before we exist our script
register_shutdown_function('shutdown');
