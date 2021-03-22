<?php
namespace Kws3\ApiCore;

/**
 * @method static \Kws3\ApiCore\Utils\Identity getIdentity()
 * @method static \Kws3\ApiCore\Utils\RequestBody getRequestBody()
 * @method static \Kws3\ApiCore\Utils\JSONResponse getResponder()
 * @method static \Kws3\ApiCore\Utils\MetadataProvider getMetaDataProvider()
 */

class Loader extends \Prefab{


  protected static $dependencyMap = [
    'IDENTITY' => '\\Kws3\\ApiCore\\Utils\\Identity',
    'RESPONDER' => '\\Kws3\\ApiCore\\JSONResponse',
    'REQUESTBODY' => '\\Kws3\\ApiCore\\Utils\\RequestBody',
    'METADATAPROVIDER' => '\\Kws3\\ApiCore\\Utils\\MetadataProvider'
  ];

  public static function __callStatic($name, $arguments)
  {
    $name = strtoupper(preg_replace("/(^get)(.*)/i", '$2', $name));
    $f3 = \Base::instance();

    if(!$f3->exists($name)){
      if(isset(self::$dependencyMap[$name])){
        self::set($name, call_user_func(function ($class) {
          return $class::instance();
        }, self::$dependencyMap[$name]));
      }
    }

    return $f3->get($name);
  }

  public static function set($name, $val)
  {
    $name = strtoupper($name);
    return \Base::instance()->set($name, $val);
  }
}