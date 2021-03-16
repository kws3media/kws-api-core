<?php

namespace Generators;


class ConfigureDBInfo extends Base
{

  private $config_path = '';
  private $arg = [];

  public function __construct($arg, $config_path, $template_path)
  {
    $this->arg = $arg;
    $this->config_path = $config_path;
    $this->template_path = $template_path;

  }

  public function fillDBInfo()
  {
    $_args = $this->flagify($this->arg);
    $temp = file_get_contents($this->template_path);
    $arg_map =[
      'HOST' => isset($_args['host']) ? $_args['host'] : '',
      'USERNAME' => isset($_args['username']) ? $_args['username'] : '',
      'PASSWORD' => isset($_args['password']) ? $_args['password'] : '',
      'DBNAME' => isset($_args['dbname']) ? $_args['dbname'] : '',
    ];
    $temp = str_replace(array_keys($arg_map), array_values($arg_map), $temp);

    if(file_put_contents($this->config_path, $temp)){
      $this->output("DB config updated");
    }else{
      $this->output("failed to update DB config");
    }
  }
}