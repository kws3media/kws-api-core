<?php

namespace Kws3\ApiCore\Runners;

class Base
{
  protected $config = [];

  public function __construct($config = [])
  {
    ob_implicit_flush(true);
    $this->config = $config;
  }

  public function help()
  {
    //to be implemented by child classes
  }

  public function run($params)
  {
    //to be implemented by child classes
  }

  protected function output($msg, $err = null)
  {
    if ($err == true) {
      echo "\033[1;97;41m " . $msg . " \e[0m" . "\n";
    } elseif ($err === false) {
      echo "\033[1;97;42m " . $msg . " \e[0m" . "\n";
    } else {
      echo $msg . "\n";
    }
    ob_flush();
    ob_end_flush();
  }

  protected function startsWith($haystack, $needle)
  {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  protected function endsWith($haystack, $needle)
  {
    $length = strlen($needle);
    if ($length == 0) {
      return true;
    }

    return (substr($haystack, -$length) === $needle);
  }

  protected function contains($haystack, $needle)
  {
    return strpos($haystack, $needle) !== false;
  }

  protected function fixPath($path)
  {
    $ds = DIRECTORY_SEPARATOR;
    return str_replace(['/', '\\'], $ds, $path);
  }
}
