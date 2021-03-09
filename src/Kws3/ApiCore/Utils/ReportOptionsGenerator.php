<?php
namespace Kws3\ApiCore\Utils;

use \Kws3\ApiCore\Utils\Identity;

class ReportOptionsGenerator extends \Models\Base
{
  public static function augment(&$model)
  {
    if($model->configurable_fields){
      $configurable_options = json_decode($model->configurable_fields, true);
      $model->configurable_fields = json_encode(self::injectOptions($configurable_options));
    }
  }

  public static function injectOptions($configurable_options){
    foreach($configurable_options as &$config_option)
    {
      if(isset($config_option['is_dynamic']) && $config_option['is_dynamic'])
      {
        $methodName = $config_option['dynamic_method'];
        if($methodName && method_exists(__CLASS__, $methodName))
        {
          $config_option['options'] = self::$methodName();
        }
      }
    }
    return $configurable_options;
  }

  protected static function buildOptionArray($data)
  {
    $option = [];
    foreach($data as $key=>$value){
      $option[] = [$key=>$value];
    }
    return $option;
  }

  protected static function getUserRoles(){
    $_map = Identity::$identityDescriptions;
    $_map = array_flip($_map);
    return self::buildOptionArray($_map);
  }


}