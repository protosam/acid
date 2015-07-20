<?php
class bouncer {
	public function __construct(){
		// nothing here yet.
	}
	
	// returns true if the request looks "OK"
	public function csrfck($allowed_referring_pages){
		// check the referrer
		// pages okay to make request from
		// check the token
		return true;
	}
}
