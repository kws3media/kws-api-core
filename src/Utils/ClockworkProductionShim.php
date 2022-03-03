<?php

namespace Kws3\ApiCore\Utils;

class ClockworkProductionShim extends \Prefab
{
    //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function __call($name, $arguments)
    {
        //do nothing
        return $this;
    }
}
