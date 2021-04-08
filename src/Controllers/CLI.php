<?php

namespace Kws3\ApiCore\Controllers;

use \Kws3\ApiCore\Loader;
use \Kws3\ApiCore\Utils\ConsoleColor;

abstract class CLI
{

  protected static $defaultLogCategory = 'cli';

  protected $app;

  //Route params
  protected $params = [];

  public function __construct(\Base $app)
  {
    if (php_sapi_name() !== 'cli') {
      $this->log('Only available via CLI', true);
      exit;
    }

    $this->app = \Base::instance();

    $this->params = $_SERVER['argv'];
    array_shift($this->params);
    array_shift($this->params);
  }

  protected function log($msg, $err = null)
  {
    if ($err == true) {
      echo ConsoleColor::error(" " . $msg . " ") . "\n";
      dbg()->error($msg);
    } elseif ($err === false) {
      echo ConsoleColor::success(" " . $msg . " ") . "\n";
      dbg()->notice($msg);
    } else {
      echo $msg . "\n";
      dbg()->info($msg);
    }
    if (ob_get_level() > 0) {
      ob_flush();
      ob_end_flush();
    }
    flush();
    Loader::getLogger()->log($msg, static::$defaultLogCategory);
  }

  public function __destruct()
  {
    dbg()->commandExecuted($_SERVER['argv'][1], 0, $this->params);
  }
}
