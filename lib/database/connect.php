<?php
if($CONF['database']['driver'] == 'mysql'){
	$mysql = mysql_connect($CONF['hostname'], $CONF['database']['username'], $CONF['database']['password'])
		or fallout("Error connecting to MySQL");

	mysql_select_db($CONF['database']['database'], $mysql) or fallout('Could not select database.');

}else if($CONF['database']['driver'] == 'mysqli'){
	$mysql = mysqli_connect($CONF['hostname'], $CONF['database']['username'], $CONF['database']['password'], $CONF['database']['database'])
		or fallout("Error connecting to MySQL");
}else{
	fallout("Unknown database driver selected.");
}



function db_close()
{
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);

	if($CONF['database']['driver'] == 'mysql'){
		mysql_close($mysql);
	}else if($CONF['database']['driver'] == 'mysqli'){

	}


}
