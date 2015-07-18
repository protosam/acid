<?php
/* catalyst
 *
 * mysqli wrapper with CRUD
 */
 
 class catalyst {
	public static $_LINK = 'nope';
	public $link = 'nope';

	public function __construct(){
		$this->link = self::$_LINK;
	}

	public function setlink($lnk){
		self::$_LINK = $lnk;
	}
}
