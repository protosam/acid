<?php

$db = new mysqli($CONF['database']['hostname'], $CONF['database']['username'], $CONF['database']['password'], $CONF['database']['database']);
if ($db->linker->connect_error)
	fallout("Error connecting to MySQL");


function db_close()
{
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
	$db->close();
}
