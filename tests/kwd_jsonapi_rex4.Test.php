<?php
// the only thing it checks is whether the globals are (still) accessed properly

use PHPUnit\Framework\TestCase;

require_once('../classes/kwd_jsonapi.php');
require_once('../classes/kwd_jsonapi_rex4.php');


class KwdJsonApiRex4TestCase extends TestCase {

    public function testInitInConstructor() {
		$this->markTestIncomplete('just need to mock global vars like $REX and $_SERVER');
    }

	//
	// public function testInitAfterConstruct() {
	//
	// }

	// - test: init with no api string then run getReponse
}
