<?php

namespace Kws3\ApiCore\Runners;

class GenerateModules extends Base
{
  public function help()
  {
    $this->output("\n");
    $this->output("Command example:", false);
    $this->output("> composer generate typename classname");
    $this->output("\n");
    $this->output("Route creation:", false);
    $this->output("> composer generate route user");
    $this->output("\n");
    $this->output("Controller creation:", false);
    $this->output("> composer generate controller user");
    $this->output("\n");
    $this->output("Model creation:", false);
    $this->output("> composer generate model user");
    $this->output("\n");
    $this->output("All Modules creation:", false);
    $this->output("> composer generate module user");
    $this->output("\n");
  }

  public function run($arg = [])
  {
    if (!isset($arg[0])) {
      $this->output('Arguments typename is missing, for help \'composer generate help\'', true);
      exit();
    }

    if (!isset($arg[1]) && $arg[0] != 'module') {
      $this->output('Arguments classname is missing, for help \'composer generate help\'', true);
      exit();
    }


    $typeName = $arg[0];

    $templates = $this->config;

    if ($typeName != 'module' && !isset($templates[$typeName])) {
      $this->output('You may have misspelt something, for help \'composer generate help\'', true);
      exit();
    }

    $config = [
      'class_name' => $arg[1],
      'type' => $typeName,
      'templates' => $typeName == 'module' ? $templates : [
        $typeName => $templates[$typeName]
      ]
    ];

    $MVCGenerator = new \Kws3\ApiCore\Generators\ModulesGenerator($config);
    $MVCGenerator->generate();
  }
}
