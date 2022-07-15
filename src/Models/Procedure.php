<?php

namespace Kws3\ApiCore\Models;

use \Kws3\ApiCore\Loader;

abstract class Procedure
{
  protected $app,
    $procName = '',
    $procSource = '';

  public function __construct()
  {
    $this->app = \Base::instance();
  }

  public static function setdown()
  {
    $model = new static;
    Loader::getDB()->exec('DROP PROCEDURE IF EXISTS `' . $model->procName . '`');
  }

  public static function setup()
  {
    $model = new static;
    Loader::getDB()->exec($model->procSource);
  }
}
