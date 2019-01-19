<?php
use PHPUnit\Framework\TestCase;

require('../kwd_jsonapi.php');

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

class KwdJsonApiTest extends TestCase {
	protected $jsonApiObject;

	protected function setUp() {
		$this->jsonApiObject = new kwd_jsonapi_test();
	}

	protected function tearDown() {
		unset($this->jsonApiObject);
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
		$this->assertSame($this->jsonApiObject->initBaseUrl('http','localhost/tk/kwd_website'),'http://localhost/tk/kwd_website/api/');
		$this->assertSame($this->jsonApiObject->initBaseUrl('http','localhost/tk/kwd_website/'),'http://localhost/tk/kwd_website/api/');
		$this->assertSame($this->jsonApiObject->initBaseUrl('http','localhost/tk/kwd_website//'),'http://localhost/tk/kwd_website/api/');
		$this->assertSame($this->jsonApiObject->initBaseUrl('http','localhost/tk/kwd_website///'),'http://localhost/tk/kwd_website/api/');
	}


	//
	// public function testInitAfterConstruct() {
	//
	// }

	// - test: init with no api string then run getReponse
}
