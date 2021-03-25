<?php

namespace Kws3\ApiCore\Responses;

use Kws3\ApiCore\Loader;

abstract class Base extends \Prefab
{
  const SUCCESS = 'SUCCESS';
  const ERROR   = 'ERROR';

  protected $app;
  protected $head = false;
  protected $snake = true;
  protected $envelope = true;

  public function __construct()
  {
    $this->app = \Base::instance();
    if (strtolower(Loader::get('VERB')) === 'head') {
      $this->head = true;
    }
  }

  abstract public function send($records, $error = false);

  public function sendHeader($header)
  {
    if (!defined('AUTOMATED_TESTING')) {
      header($header);
    }
  }

  public function setSnake($val)
  {
    $this->snake = (bool) $val;
  }
  public function getSnake()
  {
    return $this->snake;
  }
  public function setEnvelope($val)
  {
    $this->envelope = (bool) $val;
  }
  public function getEnvelope()
  {
    return $this->envelope;
  }

  protected function getOutput($records, $error = false)
  {
    $status = $error == true ? self::ERROR : self::SUCCESS;

    $metadata = Loader::getMetaDataProvider();
    $metadata->setStatus($status);
    $metadata->setCount(count($records));

    $etag = md5(serialize($records));

    // Convert to snake_case if the flag is set
    if (!$this->snake) {
      $records = $this->arrayKeysToCamel($records);
    }

    //add envelope if set
    if ($this->envelope) {
      $message = [];
      $message['_meta'] = $metadata->getArray();

      // Handle 0 record responses, or assign the records
      if ($metadata->getCount() === 0) {
        // This is required to make the response JSON return an empty JS object.
        // Without this, the JSON return an empty array: [] instead of {}
        $message['records'] = new \stdClass();
      } else {
        $message['records'] = $records;
      }
    } else {
      $this->sendHeader('X-Record-Count: ' . $metadata->getCount());
      $this->sendHeader('X-Status: ' . $metadata->getStatus());
      $message = $records;
    }

    $etag = md5(serialize($records));
    $this->sendHeader('E-Tag: ' . $etag);

    $this->sendHeader('Content-Type: application/json; ' . 'charset=' . Loader::get('ENCODING'));

    if (!$error && Loader::get('VERB') == 'POST') {
      $this->sendHeader("HTTP/1.0 201 Created");
    }

    return $message;
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

    foreach ($snakeArray as $k => &$v) {
      if (is_array($v)) {
        $v = $this->arrayKeysToCamel($v);
      }
    }

    return $snakeArray;
  }
}
