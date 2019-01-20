<?php

/** Provides Redaxo 4.x specific init code.
*	JSON api logic see base class.
*/
class kwd_jsonapi_rex4 extends kwd_jsonapi {

	public function getRootCategories($ignore_offlines = false, $clang = 0) {
		return OOCategory::getRootCategories($ignore_offlines,$clang);
	}

	public function getCategoryById($id, $clang = 0) {
		return OOCategory::getCategoryById($id, $clang);
	}

	public function getArticleById($id, $clang = 0) {
		return OOArticle::getArticleById($id,$clang);
	}

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
