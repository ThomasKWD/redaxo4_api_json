<?php
use PHPUnit\Framework\TestCase;

require_once('../classes/kwd_jsonapi.php');

// need an extra derived class
class kwd_jsonapi_test extends kwd_jsonapi {
	function __construct() {
		$this->init(
			'GET',
			'http',
			'api=articles/4',
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
		$this->assertSame($conf['apiString'],'api=articles/4','queryString must have "api="...');
    }

	public function testCorrectBaseUrl() {
		// testing the sub function directly
		$this->assertSame($this->jsonApiObject->buildBaseUrl('http','localhost/tk/kwd_website'),'http://localhost/tk/kwd_website/api/');
		$this->assertSame($this->jsonApiObject->buildBaseUrl('http','localhost/tk/kwd_website/'),'http://localhost/tk/kwd_website/api/');
		$this->assertSame($this->jsonApiObject->buildBaseUrl('http','localhost/tk/kwd_website//'),'http://localhost/tk/kwd_website/api/');
		$this->assertSame($this->jsonApiObject->buildBaseUrl('http','localhost/tk/kwd_website///'),'http://localhost/tk/kwd_website/api/');
	}

	public function testReInit() {
		$this->jsonApiObject->init('PUT','https','api=','popelhost');
		$conf = $this->jsonApiObject->getConfiguration();
		$this->assertSame($conf['requestMethod'],'get','request method must be reset to "get" when invalid');
		$this->assertSame($conf['baseUrl'],'https://popelhost/api/','baseUrl must be correct URL path including api/');
		$this->assertSame($conf['apiString'],'api=','queryString must have "api="...');
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

	public function testApiRootResponse() {
		$this->jsonApiObject->init('GET','http','api=','localhost');
		// $ret = $this->jsonApiObject->buildResponse(); // better name
		$this->markTestIncomplete();
	}

	public function testCollectingHeadersSeparately() {
		$accesControlOrigin = 'Access-Control-Allow-Origin: *';
		// ??? add additional init because otherwise the array already filled
		$this->assertSame($this->jsonApiObject->getHeaders(),array(),'must be empty array because no response built');
		$headers = $this->jsonApiObject->addHeader('HTTP/1.0 403 Forbidden');
		$headers = $this->jsonApiObject->addHeader($accesControlOrigin);
		$headers = $this->jsonApiObject->addHeader('Content-Type: application/json; charset=UTF-8');
		$this->assertEquals(count($this->jsonApiObject->getHeaders()),3,'should insert 3 entries');
		$this->assertEquals($this->jsonApiObject->getHeaders()[0],'HTTP/1.0 403 Forbidden','sample check index 0');
		$this->assertEquals($this->jsonApiObject->getHeaders()[1],$accesControlOrigin,'sample check index 1');
	}

	public function testSendResponse() {
		// ??? how to test successful send
		$this->markTestIncomplete();
	}
}
