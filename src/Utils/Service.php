<?php

namespace Kws3\ApiCore\Utils;

class Service extends \Prefab
{
  public static function call($method = '', $params = [], $async = true)
  {
    $wd = getcwd();
    $ds = DIRECTORY_SEPARATOR;

    $command = 'php ' . $wd . $ds . 'index.php' . ' /cli/' . $method;
    if (is_string($params) || is_numeric($params)) {
      $command .= ' ' . escapeshellarg($params);
    } elseif (is_array($params)) {
      $command .= ' ' . implode(' ', array_map('escapeshellarg', $params));
    }

    if ($async) {
      $command = PHP_OS === 'WINNT' ? 'start /b ' . $command : $command . ' > /dev/null 2>/dev/null &';
    }

    $output = [];

    exec($command, $output);

    if (\Kws3\ApiCore\Framework::isClockworkEnabled()) {
      $c = dbg()->userData("Commands")->title("Commands");
      $_command = str_replace([
        'start /b ',
        $wd . DIRECTORY_SEPARATOR,
        ' > /dev/null 2>/dev/null &'
      ], '', $command);
      $c->table($method, [
        ['Command' => $_command, 'Params' => $params, 'Async' => $async],
      ]);
    }

    return $output;
  }
}
