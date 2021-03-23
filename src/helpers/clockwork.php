<?php

/**
 * @return \Clockwork\Clockwork|\Clockwork\Support\Vanilla\Clockwork
 */
function dbg(){
  if(!\Kws3\ApiCore\Framework::isClockworkEnabled()){
    return \Kws3\ApiCore\Utils\ClockworkProductionShim::instance();
  }
  $arguments = func_get_args();

  if (empty($arguments)) {
    return \Clockwork\Support\Vanilla\Clockwork::instance();
  }

  foreach ($arguments as $argument) {
    \Clockwork\Support\Vanilla\Clockwork::debug($argument);
  }

  return reset($arguments);
}