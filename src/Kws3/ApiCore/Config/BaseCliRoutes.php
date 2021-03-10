<?php

namespace Kws3\ApiCore\Config;

class BaseCliRoutes
{
  public $CLIRoutes;
  public $DS;
  public $app;

  public function setupCliRoutes($config)
  {
    $this->app = $config['app'];
    $this->CLIRoutes = $config['CLIRoutes'];
    $this->DS = $config['DS'];
    $this->generateRoute();
  }

  protected function generateRoute(){
    foreach ($this->CLIRoutes as $CLIR) {
      $definitionFiles = scandir($CLIR);
      foreach ($definitionFiles as $definitionFile) {
        $pathinfo = pathinfo($definitionFile);
        //Only include php files that don't start with a .
        if ($pathinfo['extension'] === 'php' && substr($definitionFile, 0, 1) !== ".") {
          $definition = include($CLIR . $this->DS . $definitionFile);
          $_d = explode(".", $definitionFile);
          $className  = reset($_d);

          //set the routes based on definition
          $routesArray = $definition["routes"];
          if (isset($routesArray) && count($routesArray) > 0) {
            $cli_namespace = 'Controllers\\CLI\\';
            foreach ($routesArray as $endpoint => $resolution) {
              if (is_numeric($endpoint)) {
                $endpoint = $resolution;
              }
              $_handler = $cli_namespace . $className . '->' . $resolution;
              $this->app->route('GET /cli/' . $endpoint, $_handler);
            }
          }
        }
      }
    }
  }
}
