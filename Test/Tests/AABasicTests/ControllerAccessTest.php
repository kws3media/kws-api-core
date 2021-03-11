<?php
namespace Tests\AABasicTests;

class ControllerAccessTest extends \Tests\TestBase{

    protected $oldIdentity;

    function beforeEach(){
        $identity = new \stdClass;
        $identity->context = null;
        $identity->user = null;

        $this->oldIdentity = $this->app->get('IDENTITY');
        $this->app->set('IDENTITY', $identity);
    }

    function afterEach(){
        $this->app->set('IDENTITY', $this->oldIdentity);
    }

    function testControllerAccessUnAuthedUser(){

        $controller = new \Kws3\ApiCore\Controllers\BaseController($this->app);

        $refl = new \ReflectionClass('\Controllers\Base');
        $accessList = $refl->getProperty('accessList');
        $accessList->setAccessible(true);
        $accessList->setValue($controller, [
            'get' => true,
            'aMethod' => ['U', 'T'],
            'anotherMethod' => ['G'],
            'thirdMethod' => 'V'
        ]);

        $currentAction = $refl->getProperty('currentAction');
        $currentAction->setAccessible(true);
        $currentAction->setValue($controller, 'get');

        $beforeroute = $refl->getMethod('beforeroute');
        $beforeroute->setAccessible(true);

        try{
            $this->test->expect(
                $beforeroute->invoke($controller) === true,
                'Unauthenticated user on TRUE marked method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                false,
                'Unauthenticated user on TRUE marked method is allowed'
            );
        }


        try{
            $currentAction->setValue($controller, 'aMethod');
            $ret1 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Unauthenticated user on array marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                isset($ret1) === false,
                'Unauthenticated user on array marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'anotherMethod');
            $ret2 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Unauthenticated user on single-item array marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret2) === false,
                'Unauthenticated user on single-item array marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'thirdMethod');
            $ret3 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Unauthenticated user on string marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret3) === false,
                'Unauthenticated user on string marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'unspecifiedMethod');
            $ret3 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Unauthenticated user on unspecified method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret3) === false,
                'Unauthenticated user on unspecified method is not allowed'
            );
        }

    }

    function testControllerAccessAuthedUserUnknownContext(){

        $this->app->get('IDENTITY')->user = [];

        $controller = new \Kws3\ApiCore\Controllers\BaseController($this->app);

        $refl = new \ReflectionClass('\Controllers\Base');
        $accessList = $refl->getProperty('accessList');
        $accessList->setAccessible(true);
        $accessList->setValue($controller, [
            'get' => true,
            'aMethod' => ['U', 'T'],
            'anotherMethod' => ['G'],
            'thirdMethod' => 'V'
        ]);

        $currentAction = $refl->getProperty('currentAction');
        $currentAction->setAccessible(true);
        $currentAction->setValue($controller, 'get');

        $beforeroute = $refl->getMethod('beforeroute');
        $beforeroute->setAccessible(true);

        try{
            $this->test->expect(
                $beforeroute->invoke($controller) === true,
                'Uncontexted user on TRUE marked method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                false,
                'Uncontexted user on TRUE marked method is allowed'
            );
        }


        try{
            $currentAction->setValue($controller, 'aMethod');
            $ret1 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Uncontexted user on array marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                isset($ret1) === false,
                'Uncontexted user on array marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'anotherMethod');
            $ret2 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Uncontexted user on single-item array marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret2) === false,
                'Uncontexted user on single-item array marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'thirdMethod');
            $ret3 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Uncontexted user on string marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret3) === false,
                'Uncontexted user on string marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'unspecifiedMethod');
            $ret3 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Uncontexted user on unspecified method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret3) === false,
                'Uncontexted user on unspecified method is not allowed'
            );
        }

    }

    function testControllerAccessAuthedUser(){

        $this->app->get('IDENTITY')->user = [];
        $this->app->get('IDENTITY')->context = 'G';

        $controller = new \Kws3\ApiCore\Controllers\BaseController($this->app);

        $refl = new \ReflectionClass('\Controllers\Base');
        $accessList = $refl->getProperty('accessList');
        $accessList->setAccessible(true);
        $accessList->setValue($controller, [
            'get' => true,
            'aMethod' => ['U', 'T'],
            'anotherMethod' => ['G'],
            'thirdMethod' => 'V'
        ]);

        $currentAction = $refl->getProperty('currentAction');
        $currentAction->setAccessible(true);
        $currentAction->setValue($controller, 'anotherMethod');

        $beforeroute = $refl->getMethod('beforeroute');
        $beforeroute->setAccessible(true);

        try{
            $this->test->expect(
                $beforeroute->invoke($controller) === true,
                'Authed user on array marked method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                false,
                'Authed user on array marked method is allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'get');
            $this->test->expect(
                $beforeroute->invoke($controller) === true,
                'Authed user on TRUE marked method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                false,
                'Authed user on TRUE marked method is allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'aMethod');
            $ret1 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on array marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                isset($ret1) === false,
                'Authed user on array marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'thirdMethod');
            $ret3 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on string marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret3) === false,
                'Authed user on string marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'unspecifiedMethod');
            $ret3 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on unspecified method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret3) === false,
                'Authed user on unspecified method is allowed'
            );
        }

    }

    function testControllerAccessAuthedUser2(){

        $this->app->get('IDENTITY')->user = [];
        $this->app->get('IDENTITY')->context = 'U';

        $controller = new \Kws3\ApiCore\Controllers\BaseController($this->app);

        $refl = new \ReflectionClass('\Controllers\Base');
        $accessList = $refl->getProperty('accessList');
        $accessList->setAccessible(true);
        $accessList->setValue($controller, [
            'get' => true,
            'aMethod' => ['U', 'T'],
            'anotherMethod' => ['G'],
            'thirdMethod' => 'V'
        ]);

        $currentAction = $refl->getProperty('currentAction');
        $currentAction->setAccessible(true);
        $currentAction->setValue($controller, 'aMethod');

        $beforeroute = $refl->getMethod('beforeroute');
        $beforeroute->setAccessible(true);

        try{
            $this->test->expect(
                $beforeroute->invoke($controller) === true,
                'Authed user on array marked method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                false,
                'Authed user on array marked method is allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'get');
            $this->test->expect(
                $beforeroute->invoke($controller) === true,
                'Authed user on TRUE marked method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                false,
                'Authed user on TRUE marked method is allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'anotherMethod');
            $ret1 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on array marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                isset($ret1) === false,
                'Authed user on array marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'thirdMethod');
            $ret3 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on string marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret3) === false,
                'Authed user on string marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'unspecifiedMethod');
            $ret3 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on unspecified method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret3) === false,
                'Authed user on unspecified method is allowed'
            );
        }

    }

    function testControllerAccessAuthedUser3(){

        $this->app->get('IDENTITY')->user = [];
        $this->app->get('IDENTITY')->context = 'V';

        $controller = new \Kws3\ApiCore\Controllers\BaseController($this->app);

        $refl = new \ReflectionClass('\Controllers\Base');
        $accessList = $refl->getProperty('accessList');
        $accessList->setAccessible(true);
        $accessList->setValue($controller, [
            'get' => true,
            'aMethod' => ['U', 'T'],
            'anotherMethod' => ['G'],
            'thirdMethod' => 'V'
        ]);

        $currentAction = $refl->getProperty('currentAction');
        $currentAction->setAccessible(true);
        $currentAction->setValue($controller, 'thirdMethod');

        $beforeroute = $refl->getMethod('beforeroute');
        $beforeroute->setAccessible(true);

        try{
            $this->test->expect(
                $beforeroute->invoke($controller) === true,
                'Authed user on array marked method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                false,
                'Authed user on array marked method is allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'get');
            $this->test->expect(
                $beforeroute->invoke($controller) === true,
                'Authed user on TRUE marked method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                false,
                'Authed user on TRUE marked method is allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'anotherMethod');
            $ret1 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on array marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                isset($ret1) === false,
                'Authed user on array marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'aMethod');
            $ret3 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on string marked method is not allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret3) === false,
                'Authed user on string marked method is not allowed'
            );
        }

        try{
            $currentAction->setValue($controller, 'unspecifiedMethod');
            $ret3 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on unspecified method is allowed'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 isset($ret3) === false,
                'Authed user on unspecified method is allowed'
            );
        }


        //when using inactive api key

        $this->app->get('IDENTITY')->inactiveKey = true;


        try{
            $currentAction->setValue($controller, 'unspecifiedMethod');
            $ret4 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on unspecified method is not allowed if inactive key'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 ((isset($ret4) === false) && $ex->getMessage() == 'Conflict.'),
                'Authed user on unspecified method is not allowed if inactive key'
            );
        }

        try{
            $currentAction->setValue($controller, 'thirdMethod');
            $ret5 = $beforeroute->invoke($controller);
            $this->test->expect(
                false,
                'Authed user on allowed method is not allowed if inactive key'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                 ((isset($ret5) === false) && $ex->getMessage() == 'Conflict.'),
                'Authed user on allowed method is not allowed if inactive key'
            );
        }

        try{
            $currentAction->setValue($controller, 'get');
            $this->test->expect(
                $beforeroute->invoke($controller) === true,
                'Authed user on TRUE marked method is allowed if inactive key'
            );
        }catch(\Kws3\ApiCore\Exceptions\BaseHTTPException $ex){
            $this->test->expect(
                false,
                'Authed user on TRUE marked method is allowed if inactive key'
            );
        }

    }



}