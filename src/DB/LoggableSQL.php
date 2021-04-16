<?php

namespace Kws3\ApiCore\DB;

class LoggableSQL extends \DB\SQL
{
  function exec($cmds, $args = NULL, $ttl = 0, $log = TRUE, $stamp = false)
  {

    $result = parent::exec($cmds, $args, $ttl, $log, $stamp);

    list($duration, $cached, $query) = array_values($this->__parseLog());
    $query = $cached ? ('[CACHED] ' . $query) : $query;
    $data = $this->__getLineAttribution();
    dbg()->addDatabaseQuery($query, null, $duration, $data);


    return $result;
  }

  protected function __parseLog()
  {
    $ret = [
      'd' => null,
      'c' => false,
      'q' => null
    ];
    if ($this->log) {
      $blob = str_replace([PHP_EOL, "\r", "\n", "\r\n"], ' ', $this->log);
      $lines = preg_split('/(\D{3},\s\d{2}\s\D{3}\s\d{4}\s[\d:+\s]{14}\s)/is', $blob);
      $line = end($lines);
      preg_match('/[^\(]*(\([\d\.]*ms\))\s(\[.*\]\s)?(.*)/', trim($line), $matches);
      $ret['d'] = str_replace(['(', ')', 'ms'], '', $matches[1]);
      $ret['c'] = str_replace(['[', ']'], '', trim($matches[2])) == 'CACHED' ? true : false;
      $ret['q'] = $matches[3];
    }

    return $ret;
  }

  protected function __getLineAttribution()
  {
    $bt = debug_backtrace(0, 10);
    array_shift($bt);
    $base = str_replace(DIRECTORY_SEPARATOR . 'public', '', getcwd()) . DIRECTORY_SEPARATOR;
    $data = [
      'connection' => 'mysql',
      'model' => '',
      'file' => '',
      'line' => '',
    ];
    $line_found = false;
    $model_found = false;
    foreach ($bt as $trace) {
      $f = explode($base, $trace['file']);
      $file = isset($f[1]) ? $f[1] : $f[0];
      $parts = explode(DIRECTORY_SEPARATOR, $file);
      if ($parts[0] == 'app' && !$line_found) {
        $data['file'] = $file;
        $data['line'] = $trace['line'];
        $line_found = true;
      }
      if ($parts[0] == 'app' && $trace['class'] != 'DB\Cortex' && $trace['class'] != 'DB\CortexCollection') {
        if (empty($data['file'])) {
          $data['file'] = $file;
        };
        if (empty($data['line'])) {
          $data['line'] =  $trace['line'];
        };
        $data['model'] = $trace['class'] . "::" . $trace['function'];
        $model_found = true;
      }

      if ($line_found && $model_found) {
        break;
      }
    }

    return $data;
  }
}
