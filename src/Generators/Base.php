<?php

namespace Kws3\ApiCore\Generators;

use \Kws3\ApiCore\Utils\ConsoleColor;

class Base
{

  protected function output($msg, $err = null)
  {
    if ($err === true) {
      echo ConsoleColor::error(" " . $msg . " ") . "\n";
    } elseif ($err === false) {
      echo ConsoleColor::success(" " . $msg . " ") . "\n";
    } else {
      echo $msg . "\n";
    }
    if (ob_get_level() > 0) {
      ob_flush();
      ob_end_flush();
    }
    flush();
  }

  protected function flagify($args)
  {
    $_args = [];
    if (!empty($args)) {
      foreach ($args as $arg) {
        list($k, $v) = explode("=", $arg);
        $_args[$k] = $v;
      }
    }
    return $_args;
  }
}
