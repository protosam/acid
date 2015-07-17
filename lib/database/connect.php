<?php

$mysql = mysqli_connect($CONF['hostname'], $CONF['database']['username'], $CONF['database']['password'], $CONF['database']['database'])
	or fallout("Error connecting to MySQL");


function db_close()
{
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);

	if($CONF['database']['driver'] == 'mysql'){
		mysql_close($mysql);
	}else if($CONF['database']['driver'] == 'mysqli'){

	}


}
