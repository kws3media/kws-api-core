<?php

namespace Kws3\ApiCore\Config;

class BaseRoutes
{
  public $defaultDefinition = [
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
  public $apiVersions;
  public $DS;
  public $app;

  public function setupRoutes($config)
  {
    $this->app = $config['app'];
    $this->apiVersions = $config['apiVersions'];
    $this->DS = $config['DS'];
    $this->generateRoute();
  }

  protected function generateRoute()
  {
    foreach ($this->apiVersions as $av) {
      $definitionFiles = scandir($av);
      $_p = explode($this->DS, $av);
      $version = end($_p);

      foreach ($definitionFiles as $definitionFile) {
        $pathinfo = pathinfo($definitionFile);
        //Only include php files that don't start with a .
        if ($pathinfo['extension'] === 'php' && substr($definitionFile, 0, 1) !== ".") {
          $definition = include($av . $this->DS . $definitionFile);
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