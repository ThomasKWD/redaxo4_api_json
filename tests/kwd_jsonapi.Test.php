<?php
use PHPUnit\Framework\TestCase;

require_once('../classes/kwd_jsonapi.php');


class mockRexArticle {
	private $id;
	private $name;
	private $catname;
	private $isStartArticle;
	private $content;

	function __construct($id, $catname = '', $isStartArticle = false, $content = '') {
		$this->id = $id;
		$this->name = $catname .'_article';
		$this->catname = $catname;
		$this->$isStartArticle = $isStartArticle ? true : false;
		$this->content = $content;
	}

	function getId() {
		return $this->id;
	}

	function getName() {
		return $this->name;
	}
}

class mockRexCategory {

	private $id;
	private $name;
	private $articles = [];

	public function _addArticle($id,$name,$isStartArticle,$content) {
		$this->articles[] = new mockRexArticle($id,$name,$isStartArticle,$content);
	}

	function __construct($id,$name) {
		$this->id = $id;
		$this->name = $name;
		$this->articles[] = $this->_addArticle($id,$name,true,'<p>voll der Start-Artikel Content</p>');
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getStartArticle() {
		return $this->articles[0];
	}

	public function getChildren() {
		$myCats = array();

		$myCats[] = new self(12,'Shuri Ryu Berlin');
		$myCats[] = new self(7,'Tangará Berlin');
		$myCats[] = new self(13,'Moldt Events');

		return $myCats;
	}

	public function getArticles() {
		return $articles;
	}
}

// need an extra derived class
class kwd_jsonapi_test extends kwd_jsonapi {

	public function getRootCategories($ignore_offlines = false, $clang = 0) {
		// to mock the root categories we just generate objects from a json
		$rootCats = array();
		$rootCats[] = new mockRexCategory(1,'Start');
		$rootCats[] = new mockRexCategory(21,'News');
		$rootCats[] = new mockRexCategory(3,'Referenzen');

		return $rootCats;
	}

	public function getCategoryById($id, $clang = 0) {
		// mock clang > 0 not found == null
		if ($clang > 0) return null;
		if ($id !== 3) return null;
		// tODO: mock $d ===0
		return new mockRexCategory(3,'Referenzen');
	}

	public function getArticleById($id, $clang = 0) {
		return null;
	}

	function __construct($method = 'GET', $scheme = 'http', $serverPath = 'localhost/tk/kwd_website', $query = 'api=') {
		parent::__construct(
			$method,
			$scheme,
			$serverPath,
			$query
		);
	}
}

class KwdJsonApiTestCase extends TestCase {

	private function getResponseFromNew($queryString,$returnString = false) {
		$jao = new kwd_jsonapi_test();
		$jao->setApiQueryString(str_replace('/api/','api=',$queryString));
		$response = $jao->buildResponse();
		$json = json_decode($response);

		if ($returnString) return $response;
		return $json;
	}

	private function getHeadersForResponseFromNew($queryString,$search = '') {
		$jao = new kwd_jsonapi_test();
		$jao->setApiQueryString(str_replace('/api/','api=',$queryString));
		$jao->buildResponse();

		return $jao->getHeaders();
	}

    public function testInitInConstructor() {
		$jao = new kwd_jsonapi_test(); // default settings see test class declaration
		// test the construct ... getConfiguration path
		$conf = $jao->getConfiguration();
		$this->assertArrayHasKey('requestMethod',$conf); // redundant because array indices used below
		$this->assertSame($conf['requestMethod'],'get','request method must be valid case insensitive');
		$this->assertSame($conf['baseUrl'],'http://localhost/tk/kwd_website/api/','baseUrl must eliminate >1 trailing slashes, but add if none');
		$this->assertSame($conf['apiString'],'api=','queryString must have "api="...');
    }

	public function testCorrectBaseUrl() {
		$jao = new kwd_jsonapi_test('get','http','localhost/tk/kwd_website','api=');
		$this->assertSame($jao->getConfiguration()['baseUrl'],'http://localhost/tk/kwd_website/api/');
		$jao = new kwd_jsonapi_test('get','http','localhost2/tk/kwd_website/');
		$this->assertSame($jao->getConfiguration()['baseUrl'],'http://localhost2/tk/kwd_website/api/');
		$jao = new kwd_jsonapi_test('get','http','localhost3/tk/kwd_website//');
		$this->assertSame($jao->getConfiguration()['baseUrl'],'http://localhost3/tk/kwd_website/api/');

		// ... hope GC frees mem of all the 3 objects
	}

	public function testNoApiRequest() {
		$jao = new kwd_jsonapi_test('get','http','localhost/tk/kwd_website///','article_id=1&amp;clang=1');
		$this->assertEquals($jao->buildResponse(),'','empty string is ok here');

		$jao = new kwd_jsonapi_test('get','http','localhost/tk/kwd_website','/');
		$this->assertEquals($jao->buildResponse(),'','empty string is ok here');

		$jao = new kwd_jsonapi_test('get','http','localhost/tk/kwd_website','');
		$this->assertEquals($jao->buildResponse(),'','empty string is ok here');
	}

	public function testCollectingHeadersSeparately() {
		$jao = new kwd_jsonapi_test(); // default init
		$accesControlOrigin = 'Access-Control-Allow-Origin: *';
		// ??? add additional init because otherwise the array already filled
		$this->assertSame($jao->getHeaders(),array(),'must be empty array because no response built');
		$headers = $jao->addHeader('HTTP/1.0 403 Forbidden');
		$headers = $jao->addHeader($accesControlOrigin);
		$headers = $jao->addHeader('Content-Type: application/json; charset=UTF-8');
		$this->assertCount(3,$jao->getHeaders(),'should insert 3 entries');
		$this->assertEquals($jao->getHeaders()[0],'HTTP/1.0 403 Forbidden','sample check index 0');
		$this->assertEquals($jao->getHeaders()[1],$accesControlOrigin,'sample check index 1');
	}

	public function testApiRootResponse() {
		$jao = new kwd_jsonapi_test('GET','http','localhost','api=');
		$ret = $jao->buildResponse(); // better name
		$this->assertTrue(is_string($ret));
		// how assert error/no error ???
		$retJSON = json_decode($ret); // makes object!
		// $this->assertJsonStringEqualsJsonString($ret,'API request must lead to sensible json string');
		$this->assertSame($retJSON->request,'api/','checking json entry');
	}

	public function testIgnoreApiOnBuildResponse() {
		$jao = new kwd_jsonapi_test('GET','http','article_id=23','localhost');
		$ret = $jao->buildResponse();
		$this->assertTrue(is_string($ret));
		$this->assertSame($ret,'','no API request must lead to empty string');
	}

	// ! helper used 2x
	private function runCodeForTestingRootCats($jao) {

		$response = $jao->buildResponse();
		$this->assertInternalType('string',$response);
		$this->assertGreaterThan(3,strlen($response)); // should not be empty (which is for rejected request)

		// the cool thing: expected field names in json are object and array identifiers here ;-)
		$json = json_decode($response);

		$this->assertNotEquals($json,null,'must be != null indicating correct JSON'); // should not be empty (which is for rejected request)
		$this->assertEquals($json->help->info,'Check out the help section too!','help text should be under help > info');
		$this->assertTrue(is_array($json->help->links),'help links section should be array');
		$this->assertEquals($json->help->links[0],'http://localhost/tk/kwd_website/api/help','help link should contain reasonable api link path');

		// print_r($json);

		$this->assertTrue(strncmp('api/',$json->request,4) === 0,$json->request.' must start with "api/"');
		$this->assertSame(0,$json->clang_id,'must provide langauge clang_id');

		// check categories, then 1 in detail, then inner article of the first
		// /api/categories
		$this->assertTrue(isset($json->categories),'No entry "categories"');
		$this->assertTrue(is_array($json->categories),'"categories" is not an array');
		$this->assertEquals(3,count($json->categories),'defined 3 test root categories');
		// first cat
		$cat1 = $json->categories[0];
		$this->assertEquals($cat1->name,'Start','Name of first root category'); // ! now name, not title
		$this->assertEquals($cat1->id,1,'id of first root category');
		$this->assertTrue(!isset($cat1->prior),'!isset: we want NOT prior defined because root categories always given by prio');

		// ! first article should NOT have article
		$this->assertTrue(!isset($cat1->articles),'field "articles"(array) must NOT be there');
	}

	// /api
	public function testGenerateResponseForEntryPoint() {
		$jao = new kwd_jsonapi_test();
		$this->runCodeForTestingRootCats($jao);
	}

	// /api/0/ must be invalid
	public function testLanguage0OnEntryPoint() {
		$json = $this->getResponseFromNew('/api/0');
		$this->assertTrue(isset($json->error),'error entry must be existent');
	}

	// /api/1/ must be invalid
	public function testLanguage1OnEntryPoint() {
		$json = $this->getResponseFromNew('/api/1');
		$this->assertTrue(is_object($json->error));
	}


	// /api/categories
	public function testGenerateResponseForRootCategories() {
		$jao = new kwd_jsonapi_test();
		$jao->setApiQueryString('api=categories');
		$this->runCodeForTestingRootCats($jao);
	}

	// ! now with trailing slash
	// /api/categories/
	public function testGenerateResponseForRootCategoriesWithTrailingSlash() {
		$jao = new kwd_jsonapi_test();
		$jao->setApiQueryString('api=categories/');
		$this->runCodeForTestingRootCats($jao);
	}


	// ! following tests are reduced thus rely on more detailed checks above

	// /api/categories/3
	public function testGenerateResponseForCertainCategory() {

		// TODO: make helper function for this 4 lines:
		$jao = new kwd_jsonapi_test();
		$jao->setApiQueryString('api=categories/3');
		$response = $jao->buildResponse();
		$json = json_decode($response);

		$this->assertSame($json->name,'Referenzen');
		$this->assertSame($json->id,3);
		// ! categories now contains sub catgeories of category 3
		$this->assertTrue(isset($json->categories),'No entry "categories"');
		$this->assertTrue(is_array($json->categories),'"categories" is not an array');
		$definedKids = 3;
		$this->assertEquals($definedKids,count($json->categories),"defined $definedKids sub cats online (cat id == 3)");

		// sample: 3rd kid
		$cat1 = $json->categories[2];
		$this->assertEquals($cat1->name,'Moldt Events','Name of 3rd sub category'); // ! now name, not title
		$this->assertEquals($cat1->id,13,'id of 3rd sub category');
		$this->assertTrue(!isset($cat1->prior),'!isset: we want NOT prior defined because categories always given by prio');

		// // first article of 2nd cat
		$this->assertTrue(is_array($cat1->articles),'field "articles" must be there and be an array');
		$this->assertEquals(1,count($cat1->articles),'field "articles" must contain 1 element');
		$art1 = $cat1->articles[0];
		$this->assertTrue($art1 !== null);
		$this->assertEquals('Moldt Events_article',$art1->name);
		$this->assertEquals(13,$art1->id);
		$this->assertTrue($art1->is_start_article);
	}

	// /api/categories/3/0/ must be valid and equal to /api/categories/3
	public function testLanguage0EqualsLanguageDefault() {
		$res1 = $this->getResponseFromNew('/api/categories/3',true); // ! get plain string
		$json1 = json_decode($res1);
		$this->assertEquals('Referenzen',$json1->name, 'selected my test cat.');
		$res2 = $this->getResponseFromNew('/api/categories/3/0',true);
		$this->assertTrue(strstr($res2,'"request":"api\/categories\/3\/0"') !== false,'should contain "request":"api\/categories\/3\/0"');

		// ! equalizes the request to quickly compare the rest:
		$res2 = str_replace('"request":"api\/categories\/3\/0"','"request":"api\/categories\/3"',$res2);

		$this->assertEquals($res1,$res2, 'response should be the same.');
	}

	// /api/categories/3/1 must be valid but not found
	public function testLanguage1NotFound() {
		$json = $this->getResponseFromNew('/api/categories/3/1');
		// TODO: how to test received headers?
		$this->assertTrue(isset($json->error),'should have error element');
		$headers = $this->getHeadersForResponseFromNew('/api/categories/3/1');
		$this->assertContains('HTTP/1.1 404 Not Found',$headers,'must contain HTTP 404 header');
	}

	// don't test because its internal behaviour of Redaxo:
	// /api/categories/0/0 must be valid and equals to root cats
	// /api/categories/0/1 must be valid but not found

	// /api/categories/1234
	function testRequestUnknownCategory() {
		$json = $this->getResponseFromNew('/api/categories/1234');
		$this->assertTrue(isset($json->error),'should have error element');
		$this->assertSame('Resource for this request not found.',$json->error->message,'should have "not found" message');
	}

	// /api/content
	// ??? content only when .../articles


	// ??? don't use ".../content" at all
	// /api/categories/3/articles/content

	// /api/categories/3/content
	// /api/categories/3/contentandmetainfo

	// IDEA: /api/categories traverses *entire structure*
	// ??? /api/categories/3/articles ??

	// pubclic function testRequestSingleArticle
	// - actually you could send metadata+content always
	// /api/articles/12
	// /api/articles/12/metadata
	// /api/articles/12/content
	// /api/articles/12/[data|slices]

	// /api/help
	// - should also suggest "/api/categories/0/content"

	public function testSendResponse() {
		// ??? how to test successful send
		$this->markTestIncomplete();
	}
}
