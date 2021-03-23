<?php

namespace Kws3\ApiCore;

defined('K_ENV') or define("K_ENV", "local");
defined('K_ENV_PRODUCTION') or define("K_ENV_PRODUCTION", "production");
defined('K_ENV_TESTING') or define("K_ENV_TESTING", "testing");
defined('K_ENV_DEV') or define("K_ENV_DEV", "development");
defined('K_ENV_LOCAL') or define("K_ENV_LOCAL", "local");

class Framework extends \Prefab
{

  /** @var \Base */
  protected $app;

  /** @var \Loader */
  public static $loader;

  protected $defaultDefinition = [
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

  public static function init($params){

    /** @var self */
    $instance = self::instance($params);

    return $instance->app;
  }

  public static function registerOptions($params)
  {
    $instance = self::instance();
    $instance->applyOptions($params);
  }

  public static function registerRoutes($routes = [])
  {
    $instance = self::instance();
    if($routes){
      foreach ($routes as $av) {
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
            $generatedDefinition = array_replace_recursive($instance->defaultDefinition, [
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
                      $instance->app->route($_route, $_routeHandler);
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

  public static function registerCLIRoutes($routes)
  {
    $instance = self::instance();
    if($routes){
      foreach($routes as $CLIR){
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
                $instance->app->route('GET /cli/' . $endpoint, $_handler);
              }
            }
          }
        }
      }
    }
  }

  public function __construct($params = [])
  {
    $this->app = \Base::instance();
    $this->applyOptions($params);
  }

  protected function applyOptions($params){
    foreach($params as $k => $v){
      $this->app->set($k, $v);
    }
  }



}