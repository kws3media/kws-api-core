<?php

namespace Kws3\ApiCore\Models;

use \Kws3\ApiCore\Loader;

abstract class View
{
  protected $app,
    $viewName = '',
    $viewSource = '';

  public function __construct()
  {
    $this->app = \Base::instance();
  }

  public static function setdown()
  {
    $model = new static;
    Loader::getDB()->exec('DROP VIEW IF EXISTS `' . $model->viewName . '`');
  }

  public static function setup()
  {
    $model = new static;
    Loader::getDB()->exec($model->viewSource);
  }
}
