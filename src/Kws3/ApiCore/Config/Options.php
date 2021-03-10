<?php

namespace Kws3\ApiCore\Config;

class Options
{
  public $app;

  public function __construct(\Base $app)
  {
    $this->app = $app;
  }

  public function setOptions($options)
  {
    foreach($options as $key=>$value){
      $this->app->set($key, $value);
    }
  }

}