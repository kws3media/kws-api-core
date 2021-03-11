<?php
namespace ApiCore\Utils;

class ClockworkProductionShim extends \Prefab
{
    public function __call($name, $arguments)
    {
        //do nothing
        return $this;
    }
}
