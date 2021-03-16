<?php

namespace Runners;

class GenerateModules extends Base
{
  public function help(){
    $this->output("\n");
    $this->output("Command example:", false);
    $this->output("> php bin/cli GenerateModules typename classname");
    $this->output("\n");
    $this->output("Route creation:", false);
    $this->output("> php bin/cli GenerateModules route user");
    $this->output("\n");
    $this->output("Controller creation:", false);
    $this->output("> php bin/cli GenerateModules controller user");
    $this->output("\n");
    $this->output("Model creation:", false);
    $this->output("> php bin/cli GenerateModules model user");
    $this->output("\n");
    $this->output("All Modules creation:", false);
    $this->output("> php bin/cli GenerateModules modules user");
    $this->output("\n");
  }

  public function run($arg = [])
  {
    if (!isset($arg[1]) && $arg[0] != 'mvc') {
      $this->output('Arguments classname is missing, for help \'php bin/cli GenerateModules help\'', true);
      exit();
    }
    if (!isset($arg[0])) {
      $this->output('Arguments typename is missing, for help \'php bin/cli GenerateModules help\'', true);
      exit();
    }

    $typeName = $arg[0];


    //------------Generate MVC--------------

    $templates = [
      'controller' => [
        'name' => 'Controller',
        'template' => __DIR__ . '/../../stubs/controller_template.stub',
        'output' => __DIR__ . '/../../src/app/Controllers/v1/',
      ],
      'model' => [
        'name' => 'Model',
        'template' => __DIR__ . '/../../stubs/mainmodel_template.stub',
        'output' => __DIR__ . '/../../src/app/Models/',
      ],
      'route' => [
        'name' => 'Routes',
        'template' => __DIR__ . '/../../stubs/route_template.stub',
        'output' => __DIR__ . '/../../src/app/routes/v1/',
      ]
    ];

    if ($typeName != 'modules' && !isset($templates[$typeName])) {
      $this->output('You may have misspelt something, for help \'php bin/cli GenerateMVC help\'', true);
      exit();
    }

    $config = [
      'class_name' => $arg[1],
      'type' => $typeName,
      'templates' => $typeName == 'modules' ? $templates : [
        $typeName => $templates[$typeName]
      ]
    ];

    $MVCGenerator = new \Generators\ModulesGenerator($config);
    $MVCGenerator->generate();
  }
}
