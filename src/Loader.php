<?php

namespace Kws3\ApiCore;

/**
 * @method static \Kws3\ApiCore\Utils\Identity getIdentity()
 * @method static \Kws3\ApiCore\Utils\RequestBody getRequestBody()
 * @method static \Kws3\ApiCore\Utils\JSONResponse getResponder()
 * @method static \Kws3\ApiCore\Utils\MetadataProvider getMetaDataProvider()
 * @method static \DB\SQL getDB()
 * @method static \DB\SQL getDb()
 */

class Loader extends \Prefab
{


  protected static $dependencyMap = [
    'IDENTITY' => '\\Kws3\\ApiCore\\Utils\\Identity',
    'RESPONDER' => '\\Kws3\\ApiCore\\JSONResponse',
    'REQUESTBODY' => '\\Kws3\\ApiCore\\Utils\\RequestBody',
    'METADATAPROVIDER' => '\\Kws3\\ApiCore\\Utils\\MetadataProvider'
  ];

  public static function __callStatic($name, $arguments)
  {
    $name = preg_replace("/^(get|load)(.*)/", '$2', $name);
    return self::get($name);
  }

  public static function get($name)
  {

    $name = self::_standardiseName($name);
    $f3 = \Base::instance();

    if (!$f3->exists($name)) {
      if (isset(self::$dependencyMap[$name])) {
        self::set($name, call_user_func(function ($class) {
          return $class::instance();
        }, self::$dependencyMap[$name]));
      }
    }

    return $f3->get($name);
  }

  public static function set($name, $val)
  {
    $name = self::_standardiseName($name);
    return \Base::instance()->set($name, $val);
  }

  protected static function _standardiseName($name)
  {
    //capitalise string before "."
    $np = explode('.', $name);
    $np[0] = strtoupper($np[0]);
    return implode(".", $np);
  }
}
