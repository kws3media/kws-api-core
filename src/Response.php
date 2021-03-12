<?php

namespace Kws3\ApiCore;

class Response extends \Prefab
{
    const SUCCESS = 'SUCCESS';
    const ERROR   = 'ERROR';

    protected $app;
    protected $head = false;

    public function __construct()
    {
        $this->app = \Base::instance();
        if (strtolower($this->app->get('VERB')) === 'head') {
            $this->head = true;
        }
    }

    public function send($records, $error = false)
    {
        //implemented in child class
    }

    public function sendHeader($header)
    {
        if (!defined('AUTOMATED_TESTING')) {
            header($header);
        }
    }

    /**
     * In-Place, recursive conversion of array keys in snake_Case to camelCase
     * @param  array $snakeArray Array with snake_keys
     * @return no    return value, array is edited in place
     */
    protected function arrayKeysToCamel($snakeArray)
    {
        $keys = array_keys($snakeArray);
        foreach ($keys as &$key) {
            $key = $this->app->camelcase($key);
        }

        $snakeArray = array_combine($keys, $snakeArray);

        foreach ($snakeArray as $k=>&$v) {
            if (is_array($v)) {
                $v = $this->arrayKeysToCamel($v);
            }
        }

        return $snakeArray;
    }
}
