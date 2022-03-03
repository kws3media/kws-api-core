<?php

namespace Kws3\ApiCore\Reports;

class QueryOptions
{
  public static function getAliases($config)
  {
    $field = [];
    $opts = explode(' ', $config);
    $default_alias = explode('.', $opts[0]);
    $field['alias'] = isset($opts[2]) ? $opts[2] : (isset($default_alias[1]) ? $default_alias[1] : $default_alias[0]);
    $field['name'] = $opts[0];
    return $field;
  }

  public static function buildFieldMap($mapData, $field)
  {
    $_field = self::getAliases($field);
    $case_string = ' (CASE ';
    foreach ($mapData as $key => $value) {
      $case_string .= "WHEN {$_field['name']}='$key' THEN '$value' ";
    }

    $case_string .= "END) AS {$_field['alias']}";
    return $case_string;
  }

  public static function buildFields($fields)
  {
    preg_match_all('/\[\[([^\]]+)\]\]/', $fields, $matches);

    foreach ($matches[1] as $key => $value) {
      list($methodName, $parameter) = explode(':', $value);

      if (method_exists(static::class, $methodName)) {
        $case_string = static::{$methodName}($parameter);
        $fields = str_replace($matches[0][$key], $case_string, $fields);
      }
    }

    return str_ireplace([';'], '', $fields);
  }


  public static function getFieldMaps()
  {
    $all_methods = get_class_methods(static::class);

    $excluding_methods = [
      'getAliases', 'buildFields',
      'buildFieldMap', 'getFieldMaps'
    ];

    return array_values(
      array_diff(
        $all_methods,
        $excluding_methods
      )
    );
  }

  public static function formatDateDMY($field)
  {
    $_field = self::getAliases($field);
    return "DATE_FORMAT({$_field['name']}, '%d/%m/%Y') AS {$_field['alias']}";
  }

  public static function formatDateDMYH($field)
  {
    $_field = self::getAliases($field);
    return "DATE_FORMAT({$_field['name']}, '%d/%m/%Y %H:%i:%s') AS {$_field['alias']}";
  }

  public static function boolMap($field)
  {
    $_map = [0 => 'No', 1 => 'Yes', 'NULL' => 'No'];
    return self::buildFieldMap($_map, $field);
  }
}
