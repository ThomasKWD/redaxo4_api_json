<?php

/** Provides Redaxo 4.x specific init code.
*	JSON api logic see base class.
*/
class kwd_jsonapi_rex4 extends kwd_jsonapi {
	// ??? how works with unset $serverQueryString
	function __construct($serverQueryString = '') {
		global $REX;

		$this->init(
			rex_request_method(), // must pass lower case string
			rex_server(self::SERVER_REQUEST_SCHEME,'string','http'),
			rex_server($serverQueryString ? $serverQueryString  : self::SERVER_QUERY_STRING),
			$REX['SERVER']
		);
	}
}
