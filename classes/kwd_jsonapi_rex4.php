<?php

/** Provides Redaxo 4.x specific init code.
*	JSON api logic see base class.
*/
class kwd_jsonapi_rex4 extends kwd_jsonapi {

	protected function getRootCategories($ignore_offlines = false, $clang = 0) {
		return OOCategory::getRootCategories($ignore_offlines,$clang);
	}

	protected function getRootArticles($ignore_offlines = false, $clang = 0) {
		return OOArticle::getRootArticles($ignore_offlines,$clang);
	}

	protected function getCategoryById($id, $clang = 0) {
		return OOCategory::getCategoryById($id, $clang);
	}

	protected function getArticleById($id, $clang = 0) {
		return OOArticle::getArticleById($id,$clang);
	}

	protected function getArticleContent($article_id,$clang_id = 0,$ctype = 1) {
		$articleContent = new rex_article();
		$articleContent->setClang($clang_id);
		$articleContent->setArticleId($article_id);
		return $articleContent->getArticle($ctype);
	}

	// ??? how works with unset $serverQueryString
	function __construct($serverQueryString = '') {
		global $REX;

		parent::__construct(
			rex_request_method(), // must pass lower case string
			rex_server(self::SERVER_REQUEST_SCHEME,'string','http'), // request_scheme
			$REX['SERVER'], // project path
			rex_server($serverQueryString ? $serverQueryString  : self::SERVER_QUERY_STRING) // query string of request url
		);
	}
}
