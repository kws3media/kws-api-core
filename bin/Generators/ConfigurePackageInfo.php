<?php

namespace Generators;


class ConfigurePackageInfo extends Base
{

  private $package_path = '';
  private $arg = [];

  public function __construct($arg, $package_path)
  {
    $this->arg = $arg;
    $this->package_path = $package_path;

  }

  public function fillPackageInfo()
  {
    $json = json_decode(file_get_contents($this->package_path), true);
    $json['name'] =  isset($this->arg[0]) ? $this->arg[0] : $json['name'];
    $json['version'] = isset($this->arg[1]) ? $this->arg[1] : $json['version'];

    if(file_put_contents($this->package_path, json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))){
        $this->output("Now project name is '".$json['name']."' and version is ".$json['version']);
    }else{
      $this->output('failed to update package.json');
    }
  }
}
