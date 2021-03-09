<?php
namespace Kws3\ApiCore\Config;

class BaseEnv
{

  public function __construct()
  {
    //load environment variables as constants
    foreach ($_SERVER as $key => $value) {
      if (strrpos($key, 'KWS_ENV_', -strlen($key)) !== false) { //starts with KWS_ENV_
          $k = str_replace('KWS_ENV_', '', $key);
          defined($k) or define($k, $value);
      }
    }

    defined('K_ENV') or define("K_ENV", "local");

    /*
    * ^This constant K_ENV^ is set to 'development' by default,
    * This can be changed by setting environment variables in .htaccess
    */

    defined('IS_DEV') or define("IS_DEV", true);

    defined('K_ENV_PRODUCTION') or define("K_ENV_PRODUCTION", "production");
    defined('K_ENV_TESTING') or define("K_ENV_TESTING", "testing");
    defined('K_ENV_DEV') or define("K_ENV_DEV", "development");
    defined('K_ENV_LOCAL') or define("K_ENV_LOCAL", "local");

    //define the current API version in use
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $uri_parts = explode('/', $request_uri);
    if (isset($uri_parts[1])) {
      $vnum = str_ireplace('v', '', $uri_parts[1]);
      define('API_VERSION', $vnum);
    }

    //fallback in case version was not defined
    defined('API_VERSION') or define("API_VERSION", "undefined");
  }
}