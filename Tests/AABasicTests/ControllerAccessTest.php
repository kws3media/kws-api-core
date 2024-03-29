<?php

namespace Kws3\ApiCore\Tests\AABasicTests;

use \Kws3\ApiCore\Loader;

class ControllerAccessTest extends \Kws3\ApiCore\Test\Base
{

  protected $oldIdentity;

  function beforeEach()
  {
    $identity = new \stdClass;
    $identity->context = null;
    $identity->user = null;

    Loader::set('IDENTITY', $identity);
  }


  function testControllerAccessUnAuthedUser()
  {

    $controller = new \Kws3\ApiCore\Controllers\Controller($this->app);

    $refl = new \ReflectionClass('\Kws3\ApiCore\Controllers\Controller');
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

    try {
      $this->assertEquals(
        $beforeroute->invoke($controller),
        true,
        'Unauthenticated user on TRUE marked method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Unauthenticated user on TRUE marked method is allowed'
      );
    }


    try {
      $currentAction->setValue($controller, 'aMethod');
      $ret1 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Unauthenticated user on array marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret1),
        false,
        'Unauthenticated user on array marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'anotherMethod');
      $ret2 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Unauthenticated user on single-item array marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret2),
        false,
        'Unauthenticated user on single-item array marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'thirdMethod');
      $ret3 = $beforeroute->invoke($controller);
      $this->assertEquals(
        true,
        false,
        'Unauthenticated user on string marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret3),
        false,
        'Unauthenticated user on string marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'unspecifiedMethod');
      $ret3 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Unauthenticated user on unspecified method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret3),
        false,
        'Unauthenticated user on unspecified method is not allowed'
      );
    }
  }

  function testControllerAccessAuthedUserUnknownContext()
  {

    Loader::getIdentity()->user = [];

    $controller = new \Kws3\ApiCore\Controllers\Controller($this->app);

    $refl = new \ReflectionClass('\Kws3\ApiCore\Controllers\Controller');
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

    try {
      $this->assertEquals(
        $beforeroute->invoke($controller),
        true,
        'Uncontexted user on TRUE marked method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Uncontexted user on TRUE marked method is allowed'
      );
    }


    try {
      $currentAction->setValue($controller, 'aMethod');
      $ret1 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Uncontexted user on array marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret1),
        false,
        'Uncontexted user on array marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'anotherMethod');
      $ret2 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Uncontexted user on single-item array marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret2),
        false,
        'Uncontexted user on single-item array marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'thirdMethod');
      $ret3 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Uncontexted user on string marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        isset($ret3),
        false,
        'Uncontexted user on string marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'unspecifiedMethod');
      $ret3 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Uncontexted user on unspecified method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        isset($ret3),
        false,
        'Uncontexted user on unspecified method is not allowed'
      );
    }
  }

  function testControllerAccessAuthedUser()
  {

    Loader::getIdentity()->user = [];
    Loader::getIdentity()->context = 'G';

    $controller = new \Kws3\ApiCore\Controllers\Controller($this->app);

    $refl = new \ReflectionClass('\Kws3\ApiCore\Controllers\Controller');
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

    try {
      $this->assertEquals(
        $beforeroute->invoke($controller),
        true,
        'Authed user on array marked method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on array marked method is allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'get');
      $this->assertEquals(
        $beforeroute->invoke($controller),
        true,
        'Authed user on TRUE marked method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on TRUE marked method is allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'aMethod');
      $ret1 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on array marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret1),
        false,
        'Authed user on array marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'thirdMethod');
      $ret3 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on string marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret3),
        false,
        'Authed user on string marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'unspecifiedMethod');
      $ret3 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on unspecified method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret3),
        false,
        'Authed user on unspecified method is allowed'
      );
    }
  }

  function testControllerAccessAuthedUser2()
  {

    Loader::getIdentity()->user = [];
    Loader::getIdentity()->context = 'U';

    $controller = new \Kws3\ApiCore\Controllers\Controller($this->app);

    $refl = new \ReflectionClass('\Kws3\ApiCore\Controllers\Controller');
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

    try {
      $this->assertEquals(
        $beforeroute->invoke($controller),
        true,
        'Authed user on array marked method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on array marked method is allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'get');
      $this->assertEquals(
        $beforeroute->invoke($controller),
        true,
        'Authed user on TRUE marked method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on TRUE marked method is allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'anotherMethod');
      $ret1 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on array marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret1),
        false,
        'Authed user on array marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'thirdMethod');
      $ret3 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on string marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret3),
        false,
        'Authed user on string marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'unspecifiedMethod');
      $ret3 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on unspecified method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret3),
        false,
        'Authed user on unspecified method is allowed'
      );
    }
  }

  function testControllerAccessAuthedUser3()
  {

    Loader::getIdentity()->user = [];
    Loader::getIdentity()->context = 'V';

    $controller = new \Kws3\ApiCore\Controllers\Controller($this->app);

    $refl = new \ReflectionClass('\Kws3\ApiCore\Controllers\Controller');
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

    try {
      $this->assertEquals(
        $beforeroute->invoke($controller),
        true,
        'Authed user on array marked method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on array marked method is allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'get');
      $this->assertEquals(
        $beforeroute->invoke($controller),
        true,
        'Authed user on TRUE marked method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on TRUE marked method is allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'anotherMethod');
      $ret1 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on array marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        isset($ret1),
        false,
        'Authed user on array marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'aMethod');
      $ret3 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on string marked method is not allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret3),
        false,
        'Authed user on string marked method is not allowed'
      );
    }

    try {
      $currentAction->setValue($controller, 'unspecifiedMethod');
      $ret3 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on unspecified method is allowed'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        isset($ret3),
        false,
        'Authed user on unspecified method is allowed'
      );
    }


    //when using inactive api key

    Loader::getIdentity()->inactiveKey = true;


    try {
      $currentAction->setValue($controller, 'unspecifiedMethod');
      $ret4 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on unspecified method is not allowed if inactive key'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret4),
        false,
        'Authed user on unspecified method is not allowed if inactive key'
      );
      $this->assertEquals(
        $ex->getMessage(),
        'Conflict.',
        'Authed user on unspecified method is not allowed if inactive key'
      );
    }

    try {
      $currentAction->setValue($controller, 'thirdMethod');
      $ret5 = $beforeroute->invoke($controller);
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on allowed method is not allowed if inactive key'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        isset($ret5),
        false,
        'Authed user on allowed method is not allowed if inactive key'
      );
      $this->assertEquals(
        $ex->getMessage(),
        'Conflict.',
        'Authed user on allowed method is not allowed if inactive key'
      );
    }

    try {
      $currentAction->setValue($controller, 'get');
      $this->assertEquals(
        $beforeroute->invoke($controller),
        true,
        'Authed user on TRUE marked method is allowed if inactive key'
      );
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      //we are failing this test on purpose to hilight the problem
      $this->assertEquals(
        true,
        false,
        'Authed user on TRUE marked method is allowed if inactive key'
      );
    }
  }
}
