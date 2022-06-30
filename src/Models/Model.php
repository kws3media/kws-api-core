<?php

namespace Kws3\ApiCore\Models;

use \Kws3\ApiCore\Loader;
use \Kws3\ApiCore\Utils\PaginatedRows;

/**
 * @method static void log($msg) logs $msg to active log file as well as clockwork (if enabled)
 * @method void log($msg) logs $msg to active log file as well as clockwork (if enabled)
 */

abstract class Model extends \DB\Cortex
{
  public const KWS_FILTER_MULTISELECT = 'multiselect';
  public const KWS_FILTER_SELECT = 'select';
  public const KWS_FILTER_DATE = 'date';
  public const KWS_FILTER_DATERANGE = 'daterange';

  protected $app,
    $fieldConf = [],
    $fluid = false,
    $db = 'DB',
    $ttl = 0,

    $autoModifiedCreated = true,
    $softDelete = true,
    $trackDeletion = true;

  protected static $defaultLogCategory = 'application';

  public function __construct()
  {
    $this->app = \Base::instance();

    if (K_ENV === K_ENV_PRODUCTION) {
      $this->ttl = 86400;
    }

    $this->fixRelations();

    parent::__construct();

    $this->beforeinsert(function ($self) {
      return self::modifiedCreated($self);
    });

    $this->beforeupdate(function ($self) {
      return self::modifiedCreated($self);
    });

    $this->beforeerase(function ($self) {
      return self::checkSoftDelete($self);
    });
  }

  /**
   * Given $queryObject, it returns a paginated list of
   * database rows based on $perPage and scoped by the $queryObject.
   *
   * This paginated list can be iterated over transparently like an array,
   * while it lazily loads the rows in each iteration.
   *
   * @param array $queryObject - object with query parameters compatible with objects used in $model->load() or $model->find()
   * @param int $perPage - number of rows to be returned per page, default: 20
   * @return Traversable - object with rows in chunks of $perPage
   */
  public function findPaginated($queryObject, $perPage = 20)
  {
    return new PaginatedRows($this, $queryObject, $perPage);
  }

  /**
   * automatically patches the modified and created dates
   * on models that have those fields
   */
  public static function modifiedCreated($instance)
  {
    if ($instance->autoModifiedCreated) {

      if (isset($instance->fieldConf['created'])) {
        if (empty($instance->created)) {
          $instance->touch('created');
        }
      }
      if (isset($instance->fieldConf['modified'])) {
        $instance->touch('modified');
      }
    }

    return true;
  }

  /**
   * Automatically applies soft-delete if model supports it
   */
  public static function checkSoftDelete($instance)
  {
    if ($instance->trackDeletion && $instance->softDelete) {

      $identity = Loader::getIdentity();
      $user     = $identity->user ? $identity->user->id : null;

      if (isset($instance->fieldConf['deleted_on'])) {
        $instance->touch('deleted_on');
      }
      if (isset($instance->fieldConf['deleted_by'])) {
        $instance->deleted_by = $user;
      }
      $instance->save();
    }

    if ($instance->softDelete && isset($instance->fieldConf['deleted'])) {
      $instance->deleted = 1;
      $instance->save();
      return false;
    }

    return true;
  }

  /**
   * Parser for filters
   * converts filters to a PDO friendly stringy thingy
   */
  public static function filteredQuery($filters, $existingQuery = '', $existingBindings = [], $tablename = NULL)
  {
    $map = array(
      'eq' => '=',
      'xeq' => '<>',
      'gt' => '>',
      'gte' => '>=',
      'lt' => '<',
      'lte' => '<='
    );
    $bind = [];
    $query = '';

    $query_groups = [];
    $first_field_found = false;

    if (is_array($filters)) {
      foreach ($filters as $filter) {

        $is_first_outer_field = false;

        if (isset($filter['field']) && isset($filter['value'])) {
          //if field does not contain a . and tablename is given, prepend it
          $field = $tablename && strpos($filter['field'], '.') === false ? '`' . $tablename . '`.`' . $filter['field'] . '`' : (isset($filter['subquery']) ? $filter['subquery'] : (strpos($filter['field'], '.') === false ? '`' . $filter['field'] . '`' : $filter['field']));

          //make the fieldname not clash with already prepared statement's named params
          $namedParam = uniqid(':fq_' . $filter['field'] . '_');
          $condition = $filter['condition'];
          $value = $filter['value'];

          $queryPart = '';

          if (isset($map[$condition])) {
            $queryPart = $field . " " . $map[$condition] . " " . $namedParam;
            $bind[$namedParam] = $value;
          } else {
            switch ($condition) {
              case 'null':
                $queryPart = $filter['value'] === 'true' ? $field . " IS NULL" : $field . " IS NOT NULL";
                break;
              case 'in':
                $queryPart = $field . " IN (" . $namedParam . ")";
                $bind[$namedParam] = explode('|', $value);
                break;
              case 'lk':
                $queryPart = $field . " LIKE " . $namedParam;
                $bind[$namedParam] = '%' . $value . '%';
                break;
              case 'bw':
                $queryPart = $field . " LIKE " . $namedParam;
                $bind[$namedParam] = $value . '%';
                break;
              case 'ew':
                $queryPart = $field . " LIKE " . $namedParam;
                $bind[$namedParam] = '%' . $value;
                break;
              case 'xlk':
                $queryPart = $field . " NOT LIKE " . $namedParam;
                $bind[$namedParam] = '%' . $value . '%';
                break;
              case 'xbw':
                $queryPart = $field . " NOT LIKE " . $namedParam;
                $bind[$namedParam] = $value . '%';
                break;
              case 'xew':
                $queryPart = $field . " NOT LIKE " . $namedParam;
                $bind[$namedParam] = '%' . $value;
                break;
              case 'dt':
                $queryPart = "DATE(" . $field . ") = " . $namedParam;
                $bind[$namedParam] = $value;
                break;
              case 'dtbw':
                $_values = explode(" ", $value);
                $v_start = reset($_values);
                $v_end = end($_values);
                $namedParam_start = $namedParam . '_start';
                $namedParam_end = $namedParam . '_end';
                $queryPart = "DATE(" . $field . ") BETWEEN " . $namedParam_start . " AND " . $namedParam_end;
                $bind[$namedParam_start] = $v_start;
                $bind[$namedParam_end] = $v_end;
                break;
            }
          }

          //work out if this the first field outside a group
          if (empty($filter['group']) && !$first_field_found) {
            $is_first_outer_field = true;
            $first_field_found = true;
          }

          //we dont care about the join type of the first field, it will be ANDed in brackets anyway
          $joinType = ($is_first_outer_field ? '' : ' ' . $filter['jointype'] . ' ');

          if (!empty($filter['group'])) {
            //separate out query groups
            if (empty($query_groups[$filter['group']])) {
              //ensure we don't get a starting "AND" inside group brackets
              $query_groups[$filter['group']] = '';
              $joinType = '';
            }
            $query_groups[$filter['group']] .= !empty($queryPart) ? $joinType . $queryPart : '';
          } else {
            $query .= !empty($queryPart) ? $joinType . $queryPart : '';
          }
        }
      }
    }

    if (!empty($existingQuery)) {
      $query = $existingQuery . (!empty($query) ? " AND (" . $query . ")" : "");
    } else {
      $query = (!empty($query) ? " " . $query . "" : "");
    }

    //add groups to query
    if (count($query_groups) > 0) {
      foreach ($query_groups as $qg) {
        $query .= (!empty($query) ? ' AND' : '') . ' (' . $qg . ')';
      }
    }

    if (empty($query)) {
      return null;
    }

    return array_merge([0 => $query], (array)$existingBindings, $bind);
  }

  public static function filteredRawQuery($filters, $existingQuery = '', $existingBindings = [], $tables)
  {
    $DB = \Base::instance()->get('DB');

    if (count($filters) > 0) {
      foreach ($filters as &$f) {
        foreach ($tables as $key => $fields) {
          if (in_array($f['field'], $fields)) {
            $f['field'] = '`' . $key . '`.`' . $f['field'] . '`';
            break;
          }
        }
      }
    }

    $filter = self::filteredQuery($filters, $existingQuery, $existingBindings);
    $sql = '';

    if (is_array($filter) && !empty($filter[0])) {
      $sql = $filter[0];

      $args = isset($filter[1]) && is_array($filter[1]) ? $filter[1] : array_slice($filter, 1, NULL, TRUE);
      $args = is_array($args) ? $args : array(1 => $args);

      if (count($args) > 0) {
        foreach ($args as $i => $v) {
          if (is_array($v)) {
            $v = implode(",", array_map(function ($s) use ($DB) {
              return $DB->quote($s);
            }, $v));
          } else {
            $v = $DB->quote($v);
          }

          $sql = str_replace($i, $v, $sql);
        }
      }
    }
    return $sql;
  }

  /**
   * Validates given properties are not empty
   */
  public static function checkMandatoryFields(&$model, $fields = [])
  {
    if (!is_array($fields) || empty($model)) {
      return false;
    }
    foreach ($fields as $field) {
      if (empty($model->{$field})) {
        return false;
      }
    }
    return true;
  }

  public static function getValidSortCriteria($value = '', $availableOptions = [], $default = 'id DESC')
  {
    if (!empty($value)) {
      foreach ($availableOptions as $option) {
        if ($option['value'] === $value) {
          return $value;
        }
      }
    }

    return $default;
  }

  protected function fixRelations()
  {
    //phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
    foreach ($this->fieldConf as $k => &$v) {
      foreach ($v as $k1 => &$v1) {
        if ($k1 === 'belongs-to-one' || $k1 === 'has-many' || $k1 === 'has-one') {
          if (is_array($v1)) {
            $v1[0] = $this->getNamespacedClass($v1[0]);
          } else {
            $v1 = $this->getNamespacedClass($v1);
          }
        }
      }
    }
  }

  protected function getNamespacedClass($fqcn)
  {
    $curClass = explode("\\", get_class($this));
    array_pop($curClass);
    $curNamespace = implode("\\", $curClass);

    $givenClass = explode("\\", $fqcn);
    $class = array_pop($givenClass);

    if ($curNamespace !== "Models\\Base" && class_exists($curNamespace . "\\" . $class)) {
      //inject current model's namespace if it isn't Base
      //the next condition will catch it and inject an extended model
      $class = $curNamespace . "\\" . $class;
    } elseif (class_exists("Models\\" . $class)) {
      //inject extended models namespace
      $class = "Models\\" . $class;
    } elseif (class_exists("Models\\Base\\" . $class)) {
      //inject base models namespace
      $class = "Models\\Base\\" . $class;
    }

    return $class;
  }


  public function __call($name, $arguments)
  {
    if ($name === 'log') {
      dbg()->info($arguments[0]);
      Loader::getLogger()->log($arguments[0], static::$defaultLogCategory);
    }
  }

  public static function __callStatic($name, $arguments)
  {
    if ($name === 'log') {
      dbg()->info($arguments[0]);
      Loader::getLogger()->log($arguments[0], static::$defaultLogCategory);
    }
  }
}
