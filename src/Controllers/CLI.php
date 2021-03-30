<?php

namespace Kws3\ApiCore\Controllers;

use \Kws3\ApiCore\Loader;

abstract class CLI
{

  protected static $defaultLogCategory = 'cli';

  //Route params
  protected $params = [];

  public function __construct(\Base $app)
  {
    if (php_sapi_name() !== 'cli') {
      $this->log('Only available via CLI', true);
      exit;
    }

    $this->params = $_SERVER['argv'];
    array_shift($this->params);
    array_shift($this->params);
  }

  protected function log($msg, $err = null)
  {
    if ($err == true) {
      echo "\033[1;97;41m " . $msg . " \e[0m" . "\n";
      dbg()->error($msg);
    } elseif ($err === false) {
      echo "\033[1;97;42m " . $msg . " \e[0m" . "\n";
      dbg()->notice($msg);
    } else {
      echo $msg . "\n";
      dbg()->info($msg);
    }
    ob_flush();
    flush();
    Loader::getLogger()->log($msg, static::$defaultLogCategory);
  }

  public function __destruct()
  {
    dbg()->commandExecuted($_SERVER['argv'][1], 0, $this->params);
  }
}
