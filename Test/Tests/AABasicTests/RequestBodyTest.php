<?php
namespace Tests\AABasicTests;

class RequestBodyTest extends \Tests\TestBase{



    function testJSONparsing(){

        $JSON = '{"hello":"Hi","How":"ARE YOU"}';
        $ARR = ["hello" => "Hi", "How" => "ARE YOU"];


        $this->app->set('HEADERS.Content-Type', 'application/json');
        $this->app->set('BODY', $JSON);

        $this->app->set('VERB', 'POST');
        $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect($parsed["hello"] == $ARR['hello'], "Check JSON is parsed properly");
        $this->test->expect($parsed["How"] == $ARR['How'], "Check JSON is parsed properly");

        $this->app->set('VERB', 'PUT');
        $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect($parsed["hello"] == $ARR['hello'], "Check JSON is parsed properly");
        $this->test->expect($parsed["How"] == $ARR['How'], "Check JSON is parsed properly");

        $this->app->set('VERB', 'GET');
        $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect(!isset($parsed["hello"]), "Check JSON is parsed properly");
        $this->test->expect(!isset($parsed["How"]), "Check JSON is parsed properly");

        $this->app->set('VERB', 'DELETE');
        $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect(!isset($parsed["How"]), "Check JSON is parsed properly");
        $this->test->expect(!isset($parsed["hello"]), "Check JSON is parsed properly");

        $this->app->set('HEADERS.Content-Type', 'application/xml');
        $this->app->set('VERB', 'POST');
        $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect(!isset($parsed["How"]), "content type of application/json is only respected");
        $this->test->expect(!isset($parsed["hello"]), "content type of application/json is only respected");
    }

    function testFormDataParsing(){

        $ARR = ["hello" => "Hi", "How" => "ARE YOU"];
        $FD = "hello=Hi&How=ARE+YOU";

        $this->app->set('HEADERS.Content-Type', 'application/x-www-form-urlencoded');
        $this->app->set('BODY', $FD);
        $this->app->set('POST', null);

        $this->app->set('VERB', 'PUT');
        $rb = new \Kws3\ApiCore\Utils\RequestBody();
        $parsed = $rb->parse();
        $this->test->expect($parsed["hello"] == $ARR['hello'], "Check x-www-form-urlencoded is parsed properly");
        $this->test->expect($parsed["How"] == $ARR['How'], "Check x-www-form-urlencoded is parsed properly");
    }
}