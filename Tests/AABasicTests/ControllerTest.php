<?php

namespace Kws3\ApiCore\Tests\AABasicTests;

use \Kws3\ApiCore\Loader;

class ControllerTest extends \Kws3\ApiCore\Test\Base
{

  public $oldIdentity = null;

  function testOffsetLimit()
  {

    $refl = new \ReflectionClass('\Kws3\ApiCore\Controllers\Controller');

    $prop = $refl->getProperty('offset');
    $prop->setAccessible(true);
    $this->assertEquals(
      $prop->getValue(new \Kws3\ApiCore\Controllers\Controller($this->app)),
      0,
      'offset is defaulted to 0'
    );

    $prop = $refl->getProperty('limit');
    $prop->setAccessible(true);
    $this->assertEquals(
      $prop->getValue(new \Kws3\ApiCore\Controllers\Controller($this->app)),
      20,
      'limit is defaulted to 20'
    );


    Loader::set('GET.offset', 100);
    Loader::set('GET.limit', 60);

    $method = $refl->getMethod('parseRequest');
    $method->setAccessible(true);
    $instance = new \Kws3\ApiCore\Controllers\Controller($this->app);
    $method->invoke($instance);

    $prop = $refl->getProperty('offset');
    $prop->setAccessible(true);

    $this->assertEquals(
      $prop->getValue($instance),
      100,
      'offset is obeyed from GET params'
    );

    $prop = $refl->getProperty('limit');
    $prop->setAccessible(true);
    $this->assertEquals(
      $prop->getValue($instance),
      60,
      'limit is obeyed from GET params'
    );
  }
  function testGetModel()
  {

    //set identity for this test case only
    $identity = new \stdClass;
    $identity->context = 'X';
    $identity->user = null;

    $this->oldIdentity = Loader::getIdentity();
    Loader::set('IDENTITY', $identity);

    $refl = new \ReflectionClass('\Kws3\ApiCore\Controllers\Controller');
    $prop = $refl->getProperty('modelsMap');
    $prop->setAccessible(true);
    $method = $refl->getMethod('getModel');
    $method->setAccessible(true);

    $instance = new \Kws3\ApiCore\Controllers\Controller($this->app);

    $prop->setValue($instance, [
      'default' => 'Hello'
    ]);
    $return = $method->invoke($instance);
    $this->assertEquals(
      $return,
      "Hello",
      'getModel fallsback to "default"'
    );

    $prop->setValue($instance, [
      'default' => 'Hello',
      'X' => 'XInstance',
      'Y' => 'YInstance'
    ]);
    $return = $method->invoke($instance);
    $this->assertEquals(
      $return,
      "XInstance",
      'getModel returns the right invocation as per Identity->context'
    );

    $return = $method->invoke($instance, 'Y');
    $this->assertEquals(
      $return,
      "YInstance",
      'getModel returns the right invocation when a key exists'
    );

    $x = "xxxxxx";
    try {
      $x = $method->invoke($instance, 'Z');
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $this->assertEquals(
        $ex->getMessage(),
        'Unable to resolve model',
        'getModel throws an error when specified key does not exist'
      );
    }
    $this->assertEquals(
      $x,
      'xxxxxx',
      'getModel did not throw an error when specified key does not exist'
    );


    //reset Identity back to what it was
    Loader::set('IDENTITY', $this->oldIdentity);
  }

  function testSearch()
  {
    Loader::set('GET.offset', null);
    Loader::set('GET.limit', null);

    $refl = new \ReflectionClass('\Kws3\ApiCore\Controllers\Controller');
    $method = $refl->getMethod('parseRequest');
    $method->setAccessible(true);

    $prop = $refl->getProperty('isSearch');
    $prop->setAccessible(true);
    $this->assertEquals(
      $prop->getValue(new \Kws3\ApiCore\Controllers\Controller($this->app)),
      false,
      'isSearch is defaulted to false'
    );

    $prop = $refl->getProperty('filters');
    $prop->setAccessible(true);
    $this->assertEquals(
      $prop->getValue(new \Kws3\ApiCore\Controllers\Controller($this->app)),
      [],
      'filters is defaulted to empty array'
    );

    $ex_message = '';
    try {
      Loader::set('GET.q', '(field1:1,field2[gt]:2, field3[xx]: 3, field4[eq]: 4)');
      $prop = $refl->getProperty('filters');
      $prop->setAccessible(true);

      $instance = new \Kws3\ApiCore\Controllers\Controller($this->app);
      $method->invoke($instance);

      $filters = $prop->getValue($instance);
    } catch (\Kws3\ApiCore\Exceptions\HTTPException $ex) {
      $ex_message = $ex->getMessage();
      $this->assertEquals(
        $ex->getMessage(),
        'The fields you specified cannot be searched.',
        'Unspecified search params throw an exception'
      );
    }
    $this->assertNotEquals(
      $ex_message,
      '',
      'Exception was thrown in last test as expected'
    );

    Loader::set('GET.q', '');
    $instance = new \Kws3\ApiCore\Controllers\Controller($this->app);

    $prop = $refl->getProperty('allowedSearchFields');
    $prop->setAccessible(true);
    $prop->setValue($instance, ['field1', 'field2', 'field3', 'field4', 'field5']);

    Loader::set('GET.q', '(field1:1,field2[gt]:2, field3[xx]|: 3, field4[eq]|: 4, field5[eq;g1]|: 5)');

    $method->invoke($instance);

    $prop = $refl->getProperty('filters');
    $prop->setAccessible(true);
    $filters = $prop->getValue($instance);

    $this->assertEquals(
      $filters[0],
      [
        "field" => "field1",
        "value" => "1",
        "condition" => "eq",
        "jointype" => "AND",
        "group" => null
      ],
      'filters values are set properly'
    );
    $this->assertEquals(
      $filters[1],
      [
        "field" => "field2",
        "value" => "2",
        "condition" => "gt",
        "jointype" => "AND",
        "group" => null
      ],
      'filters values are set properly'
    );
    $this->assertEquals(
      $filters[2],
      [
        "field" => "field3",
        "value" => "3",
        "condition" => "xx",
        "jointype" => "OR",
        "group" => null
      ],
      'filters values are set properly'
    );
    $this->assertEquals(
      $filters[3],
      [
        "field" => "field4",
        "value" => "4",
        "condition" => "eq",
        "jointype" => "OR",
        "group" => null
      ],
      'filters values are set properly'
    );

    $this->assertEquals(
      $filters[4],
      [
        "field" => "field5",
        "value" => "5",
        "condition" => "eq",
        "jointype" => "OR",
        "group" => "g1"
      ],
      'filters group values are set properly'
    );
  }
}
