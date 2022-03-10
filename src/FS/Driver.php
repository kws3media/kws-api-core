<?php

namespace Kws3\ApiCore\FS;

abstract class Driver extends \Prefab
{
  protected $app;

  abstract public function getUrl($fileObject);

  abstract public function getFriendlyUrl($fileObject);

  abstract public function create($filePath, $destinationFolder, $opts = []);

  public function __construct()
  {
    $this->app = \Base::instance();
  }

  public function getClassName()
  {
    $curClass = explode("\\", get_class($this));
    return array_pop($curClass);
    return substr(strrchr(__CLASS__, "\\"), 1);
  }
}
