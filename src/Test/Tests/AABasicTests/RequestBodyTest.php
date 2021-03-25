<?php

namespace Kws3\ApiCore\Test\Tests\AABasicTests;

use \Kws3\ApiCore\Loader;

class RequestBodyTest extends \Kws3\ApiCore\Test\Tests\TestBase
{


    function testJSONparsing(){

        $JSON = '{"hello":"Hi","How":"ARE YOU"}';
        $ARR = ["hello" => "Hi", "How" => "ARE YOU"];


        Loader::set('HEADERS.Content-Type', 'application/json');
        Loader::set('BODY', $JSON);

        Loader::set('VERB', 'POST');
    $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect($parsed["hello"] == $ARR['hello'], "Check JSON is parsed properly");
        $this->test->expect($parsed["How"] == $ARR['How'], "Check JSON is parsed properly");

        Loader::set('VERB', 'PUT');
    $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect($parsed["hello"] == $ARR['hello'], "Check JSON is parsed properly");
        $this->test->expect($parsed["How"] == $ARR['How'], "Check JSON is parsed properly");

        Loader::set('VERB', 'GET');
    $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect(!isset($parsed["hello"]), "Check JSON is parsed properly");
        $this->test->expect(!isset($parsed["How"]), "Check JSON is parsed properly");

        Loader::set('VERB', 'DELETE');
    $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect(!isset($parsed["How"]), "Check JSON is parsed properly");
        $this->test->expect(!isset($parsed["hello"]), "Check JSON is parsed properly");

        Loader::set('HEADERS.Content-Type', 'application/xml');
        Loader::set('VERB', 'POST');
    $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect(!isset($parsed["How"]), "content type of application/json is only respected");
        $this->test->expect(!isset($parsed["hello"]), "content type of application/json is only respected");
    }

    function testFormDataParsing(){

        $ARR = ["hello" => "Hi", "How" => "ARE YOU"];
        $FD = "hello=Hi&How=ARE+YOU";

        Loader::set('HEADERS.Content-Type', 'application/x-www-form-urlencoded');
        Loader::set('BODY', $FD);
        Loader::set('POST', null);

        Loader::set('VERB', 'PUT');
    $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect($parsed["hello"] == $ARR['hello'], "Check x-www-form-urlencoded is parsed properly");
        $this->test->expect($parsed["How"] == $ARR['How'], "Check x-www-form-urlencoded is parsed properly");
    }
}