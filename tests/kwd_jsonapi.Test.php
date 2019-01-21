<?php
use PHPUnit\Framework\TestCase;

require_once('../classes/kwd_jsonapi.php');


class mockRexArticle {
	private $id;
	private $name;
	private $isStartarticle;

	function __construct($id,$catname,$isStartarticle) {
		$this->id = $id;
		$name = $catname .'_article';
		$this->$isStartarticle = $isStartarticle ? true : false;
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

	function __construct($id,$name) {
		$this->id = $id;
		$this->name = $name;
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getStartArticle() {
		return new mockRexArticle($this->id,$this->name,true);
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
		return null;
	}

	public function getArticleById($id, $clang = 0) {
		return null;
	}

	function __construct() {
		parent::__construct(
			'GET',
			'http',
			'api=',
			'localhost/tk/kwd_website//'
		);
	}
}

class KwdJsonApiTestCase extends TestCase {
	protected $jsonApiObject;

	protected function setUp() {
		$this->jsonApiObject = new kwd_jsonapi_test();
	}

	protected function tearDown() {
		unset($this->jsonApiObject); // don't know if no effect
	}

    public function testInitInConstructor() {
		// test the construct ... getConfiguration path
		$conf = $this->jsonApiObject->getConfiguration();
		$this->assertArrayHasKey('requestMethod',$conf); // redundant because array indices used below
		$this->assertSame($conf['requestMethod'],'get','request method must be valid case insensitive');
		$this->assertSame($conf['baseUrl'],'http://localhost/tk/kwd_website/api/','baseUrl must eliminate >1 trailing slashes, but add if none');
		$this->assertSame($conf['apiString'],'api=','queryString must have "api="...');
    }

	public function testReInit() {
		// ??? check if ever needed
		$this->jsonApiObject->init('PUT','https','api=','popelhost');
		$conf = $this->jsonApiObject->getConfiguration();
		$this->assertSame($conf['requestMethod'],'get','request method must be reset to "get" when invalid');
		$this->assertSame($conf['baseUrl'],'https://popelhost/api/','baseUrl must be correct URL path including api/');
		$this->assertSame($conf['apiString'],'api=','queryString must have "api="...');
	}

	public function testCorrectBaseUrl() {
		$this->jsonApiObject->init('get','http','api=','localhost/tk/kwd_website');
		$this->assertSame($this->jsonApiObject->getConfiguration()['baseUrl'],'http://localhost/tk/kwd_website/api/');

		$this->jsonApiObject->init('get','http','api=','localhost2/tk/kwd_website/');
		$this->assertSame($this->jsonApiObject->getConfiguration()['baseUrl'],'http://localhost2/tk/kwd_website/api/');
		$this->jsonApiObject->init('get','http','api=','localhost3/tk/kwd_website//');
		$this->assertSame($this->jsonApiObject->getConfiguration()['baseUrl'],'http://localhost3/tk/kwd_website/api/');
	}

	public function testNoApiRequest() {
		// should not generate json because not found in query string
		$this->jsonApiObject->init('GET','http','article_id=1&amp;clang=1','localhost');
		$ret = $this->jsonApiObject->buildResponse();
		$this->assertEquals($ret,'','empty string is ok here');
		$this->jsonApiObject->init('GET','http','/','localhost');
		$ret = $this->jsonApiObject->buildResponse();
		$this->assertEquals($ret,'','empty string is ok here');
		$this->jsonApiObject->init('GET','http','','localhost');
		$ret = $this->jsonApiObject->buildResponse();
		$this->assertEquals($ret,'','empty string is ok here');
	}

	public function testCollectingHeadersSeparately() {
		$accesControlOrigin = 'Access-Control-Allow-Origin: *';
		// ??? add additional init because otherwise the array already filled
		$this->assertSame($this->jsonApiObject->getHeaders(),array(),'must be empty array because no response built');
		$headers = $this->jsonApiObject->addHeader('HTTP/1.0 403 Forbidden');
		$headers = $this->jsonApiObject->addHeader($accesControlOrigin);
		$headers = $this->jsonApiObject->addHeader('Content-Type: application/json; charset=UTF-8');
		$this->assertCount(3,$this->jsonApiObject->getHeaders(),'should insert 3 entries');
		$this->assertEquals($this->jsonApiObject->getHeaders()[0],'HTTP/1.0 403 Forbidden','sample check index 0');
		$this->assertEquals($this->jsonApiObject->getHeaders()[1],$accesControlOrigin,'sample check index 1');
	}

	public function testApiRootResponse() {
		$this->jsonApiObject->init('GET','http','api=','localhost');
		$ret = $this->jsonApiObject->buildResponse(); // better name
		$this->assertTrue(is_string($ret));
		// how assert error/no error ???
		$retJSON = json_decode($ret); // makes object!
		// $this->assertJsonStringEqualsJsonString($ret,'API request must lead to sensible json string');
		$this->assertSame($retJSON->request,'api/','checking json entry');
	}

	public function testIgnoreApiOnBuildResponse() {
		$this->jsonApiObject->init('GET','http','article_id=23','localhost');
		$ret = $this->jsonApiObject->buildResponse();
		$this->assertTrue(is_string($ret));
		$this->assertSame($ret,'','no API request must lead to empty string');
	}

	public function testGenerateResponseForEntryPoint() {
		// ??? make sure no categories yet
		$this->markTestIncomplete('// ??? IDEA: could demand "/api/categories" while /api/just gives meta infos');
	}

	public function testGenerateResponseForRootCategoriesRequest() {

		$response = $this->jsonApiObject->buildResponse();
		$this->assertInternalType('string',$response);
		$this->assertGreaterThan(3,strlen($response)); // should not be empty (which is for rejected request)

		// the cool thing: expected field names in json are object and array identifiers here ;-)
		$json = json_decode($response);

		$this->assertNotEquals($json,null,'must be != null indicating correct JSON'); // should not be empty (which is for rejected request)
		$this->assertEquals($json->help->info,'Check out the help section too!','help text should be under help > info');
		$this->assertTrue(is_array($json->help->links),'help links section should be array');
		$this->assertEquals($json->help->links[0],'http://localhost/tk/kwd_website/api/help','help link should contain reasonable api link path');

		// print_r($json);

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

		// first article of first cat
		$this->assertTrue(is_array($cat1->articles),'field "articles" must be there and be an array');
		$art1 = $cat1->articles[0];

		// $this->assertTrue(isset($json->categories),'false means: the list of "categories" not found');
	}

	// /api/categories/3
	// /api/categories/3/content
	// /api/categories/3/contentandmetainfo

	// pubclic function testRequestSingleArticle
	// - actually you could send metadata+content always
	// /api/articles/12
	// /api/articles/12/metadata
	// /api/articles/12/content
	// /api/articles/12/[data|slices]

	public function testSendResponse() {
		// ??? how to test successful send
		$this->markTestIncomplete();
	}
}
