<?php

namespace Runners;

class GenerateModels extends Base{
  public function help(){
    $this->output('No help options.. just run the command');
  }

  public function run($params){

    $allConfig = require __DIR__ . "/../../../kws-api-starter/src/app/config/config.php";
    if(file_exists(__DIR__ . "/../../../kws-api-starter/src/app/config/config.php")){
      $allConfig = require __DIR__ . "/../../../kws-api-starter/src/app/config/config.local.php";
    }

    //------------Generate Models--------------
    $config = [
      'output'    => __DIR__ . '/../../../kws-api-starter/src/app/Models/Base/',
      'DB'        => $allConfig["DB"],
      'namespace' => 'Models\\Base',
      'extends'   => '\\Models\\Base',
      'relationNamespace' => '\\Models\Base\\',
      'template' => __DIR__ . '/../../../kws-api-starter/stubs/model_template.stub',
      'exclude_connectors' => false,
      'exclude' => ['migrations']
    ];

    $generator = new \Ekhaled\Generators\MySQL\Model($config);
    $generator->generate();

    //------------Generate Views--------------
    $genConfig = [
      'output' => __DIR__ . '/../../../kws-api-starter/test/DBViews/',
      'DB' => $config['DB'],
      'namespace' => 'DBViews',
      'extends' => '\\DBViews\\DBBaseView',
      'template' => __DIR__ . '/../../../kws-api-starter/stubs/db_view_template.stub',
    ];

    $viewGenerator = new \Generators\ViewGenerator($genConfig);
    $viewGenerator->generate();
  }
}