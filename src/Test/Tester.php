<?php

namespace Kws3\ApiCore\Test;


class Tester extends \Test
{
  /**
   *  Evaluate condition and save test result
   *  @return object
   *  @param boolean $cond
   *  @param string $text
   *  @param int $traceItem
   **/
  function expect($cond, $text = NULL, $traceItem = 0)
  {
    $out = (bool)$cond;
    if ($this->level === $out || $this->level === self::FLAG_Both) {
      $data = ['status' => $out, 'text' => $text, 'source' => NULL];
      $traces = [];
      foreach (debug_backtrace() as $frame) {
        if (isset($frame['file'])) {
          $traces[] = \Base::instance()->fixslashes($frame['file']) . ':' . $frame['line'];
        }
      }
      if ($traces[$traceItem]) {
        $data["source"] = $traces[$traceItem];
      }
      $this->data[] = $data;
    }
    if (!$out && $this->passed) {
      $this->passed = FALSE;
    }
    return $this;
  }
}
