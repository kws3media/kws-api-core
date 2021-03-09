<?php
namespace Kws3\ApiCore\Config;

class BaseEnv
{
  public $constents = [
    'K_ENV' => 'local',
    'IS_DEV' => true,
    'K_ENV_PRODUCTION' => 'production',
    'K_ENV_TESTING' => 'testing',
    'K_ENV_DEV' => 'development',
    'K_ENV_LOCAL' => 'local'
  ];

  public function run()
  {
    $this->setEnvionmentConstent();
    $this->overwiteConstent();
    $this->setAPIVersion();
  }

  //load environment variables as constants
  protected function setEnvionmentConstent()
  {
    foreach ($_SERVER as $key => $value) {
      if (strrpos($key, 'KWS_ENV_', -strlen($key)) !== false) { //starts with KWS_ENV_
        $k = str_replace('KWS_ENV_', '', $key);
        defined($k) or define($k, $value);
      }
    }
  }

  protected function overwiteConstent()
  {
    foreach ($this->constents as $key => $value) {
      defined($key) or define($key, $value);
    }
  }

  protected function setAPIVersion()
  {
    //define the current API version in use
    $request_uri  = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $uri_parts    = explode('/', $request_uri);

    if (isset($uri_parts[1])) {
      $vnum = str_ireplace('v', '', $uri_parts[1]);
      define('API_VERSION', $vnum);
    }

    //fallback in case version was not defined
    defined('API_VERSION') or define("API_VERSION", "undefined");
  }
}