<?php

namespace Kws3\ApiCore\FS;

abstract class Driver extends \Prefab
{
  protected $app;

  public function __construct()
  {
    $this->app = \Base::instance();
  }

  abstract public function getUrl($fileObject);

  abstract public function getFriendlyUrl($fileObject);

  abstract public function create($filePath, $destinationFolder);

  public function getClassName()
  {
    $curClass = explode("\\", get_class($this));
    return array_pop($curClass);
    return substr(strrchr(__CLASS__, "\\"), 1);
  }
}
