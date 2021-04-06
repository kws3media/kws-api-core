<?php

namespace Kws3\ApiCore\Runners;

class GenerateModels extends Base
{

  public function help()
  {
    $this->output('No help options.. just run the command');
  }

  public function run($params)
  {

    //------------Generate Models-------------
    $generator = new \Ekhaled\Generators\MySQL\Model($this->config['models']);
    $generator->generate();

    //------------Generate Views--------------
    $viewGenerator = new \Kws3\ApiCore\Generators\ViewGenerator($this->config['views']);
    $viewGenerator->generate();
  }
}
