<?php
namespace Kws3\ApiCore\Test\Tests\AABasicTests;

class ControllerTest extends \Kws3\Apicore\Test\Tests\TestBase{

    function testOffsetLimit(){

        $refl = new \ReflectionClass('\Kws3\ApiCore\BaseController');

        $prop = $refl->getProperty('offset');
        $prop->setAccessible(true);
        $this->test->expect(
            $prop->getValue(new \Kws3\ApiCore\BaseController($this->app)) == 0,
            'offset is defaulted to 0'
        );

        $prop = $refl->getProperty('limit');
        $prop->setAccessible(true);
        $this->test->expect(
            $prop->getValue(new \Kws3\ApiCore\BaseController($this->app)) == 20,
            'limit is defaulted to 20'
        );


        $this->app->set('GET.offset', 100);
        $this->app->set('GET.limit', 60);

        $method = $refl->getMethod('parseRequest');
        $method->setAccessible(true);
        $instance = new \Kws3\ApiCore\BaseController($this->app);
        $method->invoke($instance);

        $prop = $refl->getProperty('offset');
        $prop->setAccessible(true);

        $this->test->expect(
            $prop->getValue($instance) == 100,
            'offset is obeyed from GET params'
        );

        $prop = $refl->getProperty('limit');
        $prop->setAccessible(true);
        $this->test->expect(
            $prop->getValue($instance) == 60,
            'limit is obeyed from GET params'
        );

    }
    function testGetModel(){

        //set identity for this test case only
        $identity = new \stdClass;
        $identity->context = 'X';
        $identity->user = null;

        $this->oldIdentity = $this->app->get('IDENTITY');
        $this->app->set('IDENTITY', $identity);

        $refl = new \ReflectionClass('\Kws3\ApiCore\BaseController');
        $prop = $refl->getProperty('modelsMap');
        $prop->setAccessible(true);
        $method = $refl->getMethod('getModel');
        $method->setAccessible(true);

        $instance = new \Kws3\ApiCore\BaseController($this->app);

        $prop->setValue($instance, [
            'default' => 'Hello'
        ]);
        $return = $method->invoke($instance);
        $this->test->expect(
            $return == "Hello",
            'getModel fallsback to "default"'
        );

        $prop->setValue($instance, [
            'default' => 'Hello',
            'X' => 'XInstance',
            'Y' => 'YInstance'
        ]);
        $return = $method->invoke($instance);
        $this->test->expect(
            $return == "XInstance",
            'getModel returns the right invocation as per Identity->context'
        );

        $return = $method->invoke($instance, 'Y');
        $this->test->expect(
            $return == "YInstance",
            'getModel returns the right invocation when a key exists'
        );

        $x = "xxxxxx";
        try{
            $x = $method->invoke($instance, 'Z');
        }catch(\Kws3\ApiCore\BaseHTTPException $ex){
            $this->test->expect(
                 ($ex->getMessage() == 'Unable to resolve model'),
                'getModel throws an error when specified key does not exist'
            );
        }
        $this->test->expect(
             ($x == 'xxxxxx'),
            'getModel did not throw an error when specified key does not exist'
        );


        //reset Identity back to what it was
        $this->app->set('IDENTITY', $this->oldIdentity);
    }

    function testSearch(){
        $this->app->set('GET.offset', null);
        $this->app->set('GET.limit', null);

        $refl = new \ReflectionClass('\Kws3\ApiCore\BaseController');
        $method = $refl->getMethod('parseRequest');
        $method->setAccessible(true);

        $prop = $refl->getProperty('isSearch');
        $prop->setAccessible(true);
        $this->test->expect(
            $prop->getValue(new \Kws3\ApiCore\BaseController($this->app)) == false,
            'isSearch is defaulted to false'
        );

        $prop = $refl->getProperty('filters');
        $prop->setAccessible(true);
        $this->test->expect(
            $prop->getValue(new \Kws3\ApiCore\BaseController($this->app)) === [],
            'filters is defaulted to empty array'
        );

        $ex_message = '';
        try{
            $this->app->set('GET.q', '(field1:1,field2[gt]:2, field3[xx]: 3, field4[eq]: 4)');
            $prop = $refl->getProperty('filters');
            $prop->setAccessible(true);

            $instance = new \Kws3\ApiCore\BaseController($this->app);
            $method->invoke($instance);

            $filters = $prop->getValue($instance);
        }catch(\Kws3\ApiCore\BaseHTTPException $ex){
            $ex_message = $ex->getMessage();
            $this->test->expect(
                 ($ex->getMessage() == 'The fields you specified cannot be searched.'),
                'Unspecified search params throw an exception'
            );
        }
        $this->test->expect(
             ($ex_message != ''),
            'Excpetion was thrown in last test as expected'
        );

        $this->app->set('GET.q', '');
        $instance = new \Kws3\ApiCore\BaseController($this->app);

        $prop = $refl->getProperty('allowedSearchFields');
        $prop->setAccessible(true);
        $prop->setValue($instance, ['field1', 'field2', 'field3', 'field4']);

        $this->app->set('GET.q', '(field1:1,field2[gt]:2, field3[xx]|: 3, field4[eq]|: 4)');

        $method->invoke($instance);

        $prop = $refl->getProperty('filters');
        $prop->setAccessible(true);
        $filters = $prop->getValue($instance);


        $this->test->expect(
            ($filters[0]['field'] == 'field1' && $filters[0]['value'] == 1 && $filters[0]['condition'] == 'eq' && $filters[0]['jointype'] == 'AND'),
            'filters values are set properly'
        );
        $this->test->expect(
            ($filters[1]['field'] == 'field2' && $filters[1]['value'] == 2 && $filters[1]['condition'] == 'gt' && $filters[1]['jointype'] == 'AND'),
            'filters values are set properly'
        );
        $this->test->expect(
            ($filters[2]['field'] == 'field3' && $filters[2]['value'] == 3 && $filters[2]['condition'] == 'xx' && $filters[2]['jointype'] == 'OR'),
            'filters values are set properly'
        );
        $this->test->expect(
            ($filters[3]['field'] == 'field4' && $filters[3]['value'] == 4 && $filters[3]['condition'] == 'eq' && $filters[3]['jointype'] == 'OR'),
            'filters values are set properly'
        );

    }

}