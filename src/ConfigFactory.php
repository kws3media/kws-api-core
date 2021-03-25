<?php

namespace Kws3\ApiCore;

use \Kws3\ApiCore\Loader;

class ConfigFactory extends \Prefab
{
  protected $app;
  public $apiVersions;
  public $CLIRoutes;
  public $defaultDefinition;

  public function __construct()
  {
    $this->app = \Base::instance();
    $this->defaultDefinition = [
      //The main endpoint that will be hit
      "endpoint" => "[url endpoint]",
      //The controller that will handle this endpoint
      "controller" => "[name of controller, no namespaces]",
      //For normal routes
      //First parameter is the route
      //Second parameter is the function name of the Controller.
      "routes" => [
        // array keys define the HTTP verb
        'head' => [
          '/' => 'get',
          '/@id' => 'getOne'
        ],
        'get' => [
          '/' => 'get',
          '/@id' => 'getOne'
        ],
        'post' => [
          '/' => 'post'
        ],
        'put' => [
          '/@id' => 'put'
        ],
        'delete' => [
          '/@id' => 'delete'
        ]
      ]
    ];
  }

  public function setOptions($options)
  {
    foreach($options as $key=>$value){
      Loader::set($key, $value);
    }
  }

  public function registerRoutes($routesPath)
  {
    $this->apiVersions = $routesPath;
    $this->generateRoutes();
  }

  public function registerCLIRoutes($cliRoutesPath)
  {
    $this->CLIRoutes = $cliRoutesPath;
    $this->generateCLIRoutes();
  }

  protected function generateRoutes()
  {
    if($this->apiVersions)
    {
      foreach ($this->apiVersions as $av) {
        $definitionFiles = scandir($av);
        $_p = explode(DIRECTORY_SEPARATOR, $av);
        $version = end($_p);

        foreach ($definitionFiles as $definitionFile) {
          $pathinfo = pathinfo($definitionFile);
          //Only include php files that don't start with a .
          if ($pathinfo['extension'] === 'php' && substr($definitionFile, 0, 1) !== ".") {
            $definition = include($av . DIRECTORY_SEPARATOR . $definitionFile);
            $_d = explode(".", $definitionFile);
            $stub = reset($_d);

            //fill in controller and endpoints
            $generatedDefinition = array_replace_recursive($this->defaultDefinition, [
              "endpoint" => $stub,
              "controller" => ucfirst($stub)
            ]);

            //sanity check
            if (!is_array($definition)) {
              $definition = [];
            }

            //extend default route definition
            $def = array_replace_recursive($generatedDefinition, $definition);

            //set the routes based on definition
            if (isset($def["routes"]) && count($def["routes"]) > 0) {
              foreach ($def["routes"] as $verb => $routes) {

                $_prefix = '/' . $version . '/' . $def["endpoint"];
                $_handler = 'Controllers\\' . str_replace('.', '_', $version) . '\\' . $def["controller"];

                if (is_array($routes) && count($routes) > 0) {
                  foreach ($routes as $k => $v) {
                    if ($v) {
                      $_route = strtoupper($verb) . ' ' . $_prefix . ($k == '/' ? '' : $k);
                      $_routeHandler = $_handler . '->' . $v;
                      $this->app->route($_route, $_routeHandler);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  protected function generateCLIRoutes()
  {
    if($this->CLIRoutes)
    {
      foreach ($this->CLIRoutes as $CLIR) {
        $definitionFiles = scandir($CLIR);
        foreach ($definitionFiles as $definitionFile) {
          $pathinfo = pathinfo($definitionFile);
          //Only include php files that don't start with a .
          if ($pathinfo['extension'] === 'php' && substr($definitionFile, 0, 1) !== ".") {
            $definition = include($CLIR . DIRECTORY_SEPARATOR . $definitionFile);
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

}