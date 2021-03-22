<?php
namespace Kws3\ApiCore\Traits;

use \Kws3\ApiCore\Framework;

trait DynamicLoader
{

    public static function getLoader()
    {
        return Framework::getLoader();
    }
}
