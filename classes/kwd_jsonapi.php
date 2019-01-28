<?php

abstract class kwd_jsonapi {

	protected const APIMARKER = 'api=';
	protected const REQUEST_START = '/api/'; // to generate correct links to api resources in response
	protected const SERVER_QUERY_STRING = 'QUERY_STRING';
	protected const SERVER_REQUEST_SCHEME = 'REQUEST_SCHEME';
	// const SERVER_REQUEST_METHOD = 'REQUEST_METHOD';

	protected const HELP = 'help';
	protected const CATEGORIES = 'categories';
	protected const ARTICLES = 'articles';
	protected const CONTENTS = 'contents';

	protected $requestMethod = '';
	protected $baseUrl = '';
	protected $api = '';

	protected $headers = array(); // indexed array

	abstract protected function getRootCategories($ignore_offlines = false,$clang = 0);
	abstract protected function getCategoryById($id, $clang = 0);
	abstract protected function getRootArticles($ignore_offlines = false,$clang = 0);
	abstract protected function getArticleById($id,$clang = 0);
	abstract protected function getArticleContent($article_id,$clang_id = 0,$ctype = 1);

	function __construct($requestMethod = 'get', $requestScheme = 'http', $queryString = '', $serverPath) {
		$this->init($requestMethod, $requestScheme, $queryString, $serverPath);
	}

	/** adds header to list
	*	- will be written as http header (see function send())
	*	! always replace (does not support replace=false for header(),
	*		??? just pass and save flag if needed)
	*	@param string $headerString must contain valid HTTP header directive
	*/
	public function addHeader($headerString) {
		array_push($this->headers,$headerString);
	}

	/** Inits instance var $baseUrl
	* sub classes should call super->init()
	* - helper function, does NOT modify state of object
	*/
	protected function buildBaseUrl($requestScheme,$serverPath) {

		// rex_server is assumed existent in redaxo 4 and 5
		// TODO: but you should extract it, it could be change in redaxo 6
		$baseUrl = $requestScheme .'://'.$serverPath;

		// check for trailing '/'
		// - for the case we have *1 or more* trailing slashes
		// ! endless loop if substr not working correctly
		while (substr($baseUrl,-1) == '/') {
			$baseUrl = substr($baseUrl,0,strlen($baseUrl) - 1);
		}
		$baseUrl  .= self::REQUEST_START;

		return $baseUrl;
	}

	/** check valid requestMethod
	* - current code seems useless but considered prepared for more methods allowed later
	* - helper function, does NOT modify state of object
	*/
	protected function buildRequestMethod($requestMethod) {

		$requestMethod = strtolower($requestMethod);

		// add more allowed methos here, maybe use reg exp
		if ($requestMethod !== 'get') $requestMethod = 'get';

		return $requestMethod;
	}

	/** reset query string
	*	- simple setter to easily change config after init
	*	! modifies object property
	*   @return string newly set string
	*/
	public function setApiQueryString($queryString) {
		$this->api = $queryString;

		return $this->api;
	}

	protected function init ($requestMethod = 'get', $requestScheme = 'http', $serverPath = '/', $queryString = '') {
		$this->requestMethod = $this->buildRequestMethod($requestMethod);
		$this->baseUrl = $this->buildBaseUrl($requestScheme,$serverPath);
		$this->setApiQueryString($queryString);
	}

	/** reads current configuration.
	*	Returns all relevant object properties as associated array
	* - getter; does NOT modify state of object
	*/
	public function getConfiguration() {
		return array(
			'requestMethod' => $this->requestMethod,
			'baseUrl' => $this->baseUrl,
			'apiString' => $this->api
		);
	}

	public function getHeaders() {
		// ! PHP always returns copy of array
		return $this->headers;
	}

	public function setHeaders($headersArray) {
		// ! PHP always assigns copy of array
		return $this->headers = $headersArray;
	}

	// ??? better naming!
	protected function getSubLink($id,$name = '') {
		$entry['id'] = $id;
		if ($name) $entry['name'] = $name;
		$entry['link'] = $this->articleLink($id);
		// return array
		return $entry;
	}

	protected function getCategoryFields($id,$name = '',$clang_id = 0, $articles = false, $content = false) {
		$entry['id'] = $id;
		if ($name) $entry['name'] = $name;
		$entry['link'] = $this->categoryLink($id,$clang_id,$articles,$content);
		// return array
		return $entry;
	}

	// get data from OOarticle object
	protected function addArticle($art, $content = false, $ctype_id) {
		$res = array();
		// order matters:
		$res['id'] = $art->getId();
		$res['name'] = $art->getName();
		$res['is_start_article'] = $art->isStartArticle() ? true : false;
		if ($content) $res['body'] = $this->getArticleContent($art->getId(), $art->getClang(), $ctype_id);

		return $res;
	}

	protected function addAllArticlesOfCategory($cat, $content = false, $ctype_id) {
		$ret = [];

		foreach($cat->getArticles(true) as $art) {
			$ret[] = $this->addArticle($art,$content, $ctype_id);
		}

		return $ret;
	}

	/// ??? make var for server name
	protected function apiLink($queryString) {
		return $this->baseUrl .$queryString;
	}

	protected function articleLink($article_id,$clang_id = 0,$showContent = false) {
		return $this->apiLink(self::ARTICLES.'/'.$article_id.'/'.$clang_id.($showContent ? '/'.self::CONTENTS : ''));
	}

	protected function categoryLink($category_id, $clang_id = 0, $showArticles = false, $showContent = false) {
		return $this->apiLink(self::CATEGORIES.'/'.$category_id.'/'.$clang_id.($showArticles ? '/'.self::ARTICLES : '').($showContent ? '/'.self::CONTENTS : ''));
	}

	//  --- API DATA GENERATION

	// always returns array,
	// - was more complicated and now remains function
	protected function splitTrimmed($string) {
		return explode('/',$string);
	}

	protected function generateSyntaxError($apiString) {

		$response = [];

		// articles/categories not found
		$this->addHeader('HTTP/1.1 400 Bad Request');
		$response['request']= 'api/'.$apiString;
		$response['error']['message'] = 'Syntax error or unknown request component';
		$response['error']['help']['info'] = 'See links for entry point or help.';
		$response['error']['help']['links'][] = $this->apiLink('');
		$response['error']['help']['links'][] = $this->apiLink('help');

		return $response;
	}

	protected function generateResourceNotFound($apiString) {
		$response;

		$this->addHeader("HTTP/1.1 404 Not Found");

		$response['request'] = 'api/'.$apiString;
		$response['error']['message'] = 'Resource for this request not found.';
		$response['error']['help']['info'] = 'Start with /api or /api/'.self::CATEGORIES;
		$response['error']['help']['links'][] = $this->apiLink('');
		$response['error']['help']['links'][] = $this->apiLink(self::CATEGORIES);
		$response['error']['help']['links'][] = $this->apiLink('help');
		return $response;
	}


	public function buildResponse() {

		$api = $this->api;
		$response = array();
		// the substr AND strlen construct is assumed to be more efficient than reg exp
		// - just avoid reg exp when possible
		// if ($api && preg_match('/^api=/Ui',$api)) {
		if (substr($api,0,strlen(self::APIMARKER)) === self::APIMARKER) {

			// START
			// ob_end_clean(); // - not needed: remove caching and thus prevent changes by extension_point "OUTPUT_BUFFER"
			// can be a problem/limitation because output buffer operations ar not done

			// immediately stop on PUT/DELETE/POST commands
			// if (strtolower($_SERVER[self::SERVER_REQUEST_METHOD]) != 'get')  {
			if ($this->requestMethod !== 'get') {
				$this->addHeader('HTTP/1.1 403 Forbidden');
				$response['error']['message'] = 'You can only GET data.';
			}
			else {
				$this->addHeader('Access-Control-Allow-Origin: *');

				// used to make api links clickable
				$host = $this->baseUrl; // ???: check id SERVER var correct in all cases!

				$api = strtolower($api);
				$api = str_replace(self::APIMARKER,'',$api);
				$api = trim($api," /\t\r\n\0\x0B"); // remove multiple slashes as well

				if (strstr($api,'//')) {
					$response = generateSyntaxError($api);
				}
				else {
					// request string as array:
					$request = $this->splitTrimmed($api);

					// first remove entries which may come from leading/trailing slashes "/":

					$response['request'] = 'api/'.$api;
					$response['debug']['queryString'] = $api;
					$response['debug']['host'] = $host;

					$content = false;
					$continue = true;
					$showArticlesOfCategory = false;
					$selectedCtype = 1;

					if ($request[count($request) - 1] === self::CONTENTS) {
						$content = true;
						array_pop($request);
					}
					// with ctype def
					else if (count($request) >= 3 && $request[count($request) - 2] === self::CONTENTS) {
						$c = $request[count($request) - 1];
						if (is_numeric($c)) {
							$selectedCtype = intval($c);
							$content = true;
							array_pop($request);
							array_pop($request);
						}
						else {
							// should send bad request here
							// ??? maybe flag for bad request, then continue not needed below!
						}
					}

					if (count($request) > 1 && $request[count($request) - 1] === self::ARTICLES) {
						$showArticlesOfCategory = true;
						array_pop($request);
					}
					else {
						// ! convention no content allowed when no articles requested
						if ($content) {
							$content = false;
							$continue = false;
							$response = $this->generateSyntaxError($api);
							$response['error']['message'] = 'Semantic error. You cannot request "contents" without requesting "articles".';
						}
					}

					if ($continue) {
						//$response['debug']['explode'] = $request;
						if ($request[0] == 'help') {
							$response['info'] = 'The project consists of "articles". Each article has an id. You always have to add "/contents" to get article content. Articles representing a collection provide a field "list". This is the way to see the hiarchical structure. For examples see the "examples" entries here.';
							$response['examples'] = array(
								array('info' => 'Entry point', 'link' => $this->apiLink('')),
								array('info' => 'Alternative entry point because no id specified', 'link' => $this->apiLink(self::ARTICLES)),
								array('info' => 'Certain article (default language)', 'link' => $this->apiLink(self::CATEGORIES.'/48'))
								// array('info' => 'Certain article with certain language ', 'link' => $this->apiLink(self::ARTICLES/48/1')),
								// array('info' => 'Certain article with content body', 'link' => $this->apiLink('articles/48/content')),
								// array('info' => 'Certain article with content body and certain language', 'link' => $this->apiLink('articles/48/1/content')),
								// array('info' => 'If the article contains sub articles it provides the bodies in the entries of "list".', 'link' => $this->apiLink('articles/3/content'))
							);
							$response['external'] = array(
								array('info' => 'Understand basic concept of categories and articles:', 'link' => 'https://redaxo.org')
							);
						}
						// TODO: This must become output of single article
						// IDEA: This automatically generates more detailed output
						// ! currently disabled
						else if ($request[0] == 'articlejgluglgs') {

							if (!isset($request[1])) {

								// tests to get ALL articles
								// $articles = OOArticle::getAll();//???
								// !!! make a nested loop for all because there should not be an article outside the structure
								// getAllSubArticles(rootCategories)

								$this->addHeader('HTTP/1.1 404 Resource not found');
								$response['error']['message'] = 'Listing all articles is not *yet* supported. You can specify an id or use the entry point "/api".';
								$response['error']['links'][] = $this->apiLink('');
								$response['error']['links'][] = $this->apiLink('help');
							}
							else {
								$article_id = intval($request[1]);
								if ($article_id) {
									if (isset($request[2])) {
										if (is_numeric($request[2])) { // also handles empty string
											$clang_id = intval($request[2]);
										}
										else if ($request[2]) {
											// error because omitted language or large number already checked
											// TODO: how to unset id, title etc.
											// $this->addHeader('HTTP/1.1 400 Syntax Error');
											$response['warning']['message'] = 'Invalid argument for language. You may provide number and/or "contents". Note that your request is still processed';
										}
									}
									else $clang_id = 0;

									$article = $this->getArticleById($article_id,$clang_id);


									if ($article) {
										// TODO: check for null!!!, clang_id can be wrong or invalid!, $article_id can be wrong
										$response['id'] =  $article_id;
										$response['category_id'] = $article->getValue('category_id');
										$response['clang_id'] =  $clang_id; // ! language id counting from 1
										$response['name'] = $article->getValue('name');
										$response['info'] = ''; // now we can concat text

										// check for "content article", by metainfo?? by checking slices??
										// ! not distinguishing between types anymore
										// $artType = $article->getValue('art_type_id');

										//try to provide link list
										$response['sub_articles'] = array();
										// get my category
										// - if there are sub categories assume one child for each sub cat
										// ??? why not pass $clang_id
										$cat = $this->getCategoryById($article->getValue('category_id'));
										$kids = $cat->getChildren(true); // true means only "onlines"
										if (count($kids)) {
											$i=0;
											foreach($kids as $k) {
												$response['sub_articles'][$i] = $this->getSubLink($k->getId(),$k->getName());
												$kidArticleId = $k->getStartArticle()->getId();
												$response['debug']['sub_articles'][$i]['body'] = $kidArticleId;
												// ! commented out since altered
												// $this->addContent($response['sub_articles'][$i],$content,$kidArticleId,$clang_id);
												$i++;
											}
											if (count($response['sub_articles'])) {
												$response['info'] .= ' List of start articles of sub categories.';
											}
											else {
												unset($response['sub_articles']);
												$response['info'] .= ' No articles in  subcategories.';
											}
										}
										else {
											// try to get articles in cat itself
											// - ignore start article
											$kids = $cat->getArticles(true);

											$i = 0;
											foreach($kids as $k) {
												if ($k->getId() !== $cat->getId()) {
													$response['sub_articles'][$i] = $this->getSubLink($k->getId(),$k->getName());
													// ! commented out since altered
													// $this->addContent($response['sub_articles'][$i],$content,$article_id,$clang_id);
													$i++;
												}
											}
											if (count($response['sub_articles'])) {
												$response['info'] .= 'List of sub articles. Startarticle of category is ignored';
											}
											else {
												$response['info'] .= ' No subarticles online.';
												unset($response['sub_articles']);
											}
											// ! you can't use count($kids) directly, since check for startarticle inside loop
										}

										// always add own content
										// ! commented out since altered
										// $this->addContent($response,$content,$article_id,$clang_id);
										if (!$content) {
											$response['help']['info'] = 'You may add "/'.self::CONTENTS.'" to get the body of the article.';
											// TODO: make convention to present links always the same
											$output_clang = $clang_id + 1;
											$response['help']['links'] = [];
											$response['help']['links'][] = $this->articleLink($article_id,$clang_id,true);
										}
										else {
											$response['warning'] = 'Content may contain links to web pages (no API links)!';
										}
									}
									else {
										$this->addHeader("HTTP/1.1 404 Not Found");
										// no $article
										// - $article_id and $clang_id already checked whether invalid
										// - so we assume non existing article
										// TODO: throw 404 with description
										$response['error']['message'] = 'Resource for this request not found.';
										// $response['help'] = 'See list of links for information how to start.';
										$response['error']['help']['info'] = 'Start with /api/'.self::CATEGORIES;
										$response['error']['help']['links'][] = $this->apiLink(self::ARTICLES);
									}
								}
								else {
									// TODO: make sub function!
									$this->addHeader("HTTP/1.1 400 Bad Request");
									$response['error']['message'] = 'Invalid parameter for "articles".';
									$response['error']['help']['info'] = 'Start with /api/ or see "links" for other examples';
									$response['error']['help']['links'][] = $this->apiLink('');
									$response['error']['help']['links'][] = $this->apiLink(self::ARTICLES);
									$response['error']['help']['links'][] = $this->apiLink('help');
								}
							}
						}
						else if ($request[0] === '' || $request[0] === self::CATEGORIES) {
							$response['debug'][self::CONTENTS] = $content;
							// set start cat id
							$startCat = 0;
							$clang_id = 0;
							$kids = null;

							if (isset($request[1])) {
							 	$startCat = intval($request[1]);
							}
							if (isset($request[2])) {
								$clang_id = intval($request[2]);
							}

							// ! we assume rqeuesting cat id == 0 means rootCategories!!
							$cat = $this->getCategoryById($startCat,$clang_id);

							if ($cat) {
								$kids = $cat->getChildren(true);

								$response['id'] = $cat->getId();
								$response['name'] = $cat->getName();
							}
							else if (!$startCat) {
								$kids = $this->getRootCategories(true,$clang_id);

								$response['info'] = 'You can use the ids or links in the list of root "categories".';
								$response['help']['info'] = 'Check out the help section too!';
								$response['help']['links'][] = $this->apiLink('help');
							}
							else {
								$response = $this->generateResourceNotFound($api);
							}

							$response['clang_id'] = $clang_id;

							if ($kids && count($kids)) {
								foreach($kids as $k) {
									$catResponse = $this->getCategoryFields($k->getId(),$k->getName(),$clang_id,$showArticlesOfCategory,$content);

									if ($showArticlesOfCategory) {
										$catResponse[self::ARTICLES] = $this->addAllArticlesOfCategory($k,$content,$selectedCtype);
									}
									$response[self::CATEGORIES][] = $catResponse;
								}
							}
							else if (!$startCat){
								// IDEA: check if better to have an empty array "categories[]" to indicate there usually are some
								$response['warning'] = 'Currently no root "categories" online.';
							}

							// my own content
							// ??? sub function
							// ??? must be loop for all in cat!!!!!!
							if ($showArticlesOfCategory) {
								if ($cat)  {
									$response[self::ARTICLES] = $this->addAllArticlesOfCategory($cat,$content,$selectedCtype);
								}
								else if (!$startCat) {
									$artRes = [];
									$arts = $this->getRootArticles(true,$clang_id);
									foreach($arts as $art) {
										$artRes[] = $this->addArticle($art,$content,$selectedCtype); // TODO: wrong usage of $content
									}
									// - could insert if to prevent empty array
									// ! can be empty array because *all* root articles could be offline (not depending on cat)
									$response[self::ARTICLES] = $artRes;
								}
							}
						}
						else {
							$response = $this->generateSyntaxError($api);
							// // articles/categories not found
							// $this->addHeader('HTTP/1.1 400 Bad Request');
							// $response['error']['message'] = 'Syntax error or unknown request component';
							// $response['error']['help']['info'] = 'See links for entry point or help.';
							// $response['error']['help']['links'][] = $this->apiLink('');
							// $response['error']['help']['links'][] = $this->apiLink('help');
						}
					}
				}
			}

			// ! comment line if you need debug
			// DEBUG:
			unset($response['debug']);
			$this->addHeader('Content-Type: application/json; charset=UTF-8',false);

			// ! we don't exit if response could not been build
			// ! usually this allows to show normal start page of Redaxo project.
		}
		else {
			// do nothing
			// ??? maybe log attempt
		}

		// ??? include headers in return value?
		if (count($response)) return json_encode($response);
		return '';
	}

	public function sendHeaders() {
		foreach($this->getHeaders() as $h) {
			// ! only supports replace = true
			header($h);
		}
	}

	public function send($responseString) {
		if ($responseString) {
			// send headers!!
			ob_end_clean();
			$this->sendHeaders();
			echo $responseString;
			return true;
		}

		return false;
	}
}
