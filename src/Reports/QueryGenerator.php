<?php

namespace Kws3\ApiCore\Reports;

use \Kws3\ApiCore\Models\Model;
use \Kws3\ApiCore\Loader;
use \Kws3\ApiCore\Utils\Tools;
use \Kws3\ApiCore\Exceptions\HTTPException;

class QueryGenerator extends Model
{
  public const TYPE_INT          = 'Int';
  public const TYPE_TEXT         = 'Text';
  public const TYPE_BOOL         = 'Bool';
  public const TYPE_DATE         = 'Date';
  public const TYPE_ENUM         = 'Enum';
  public const TYPE_NULLABLEENUM = 'NullEnum';

  public const QUERY_TYPE_LINK   = 'L';
  public const QUERY_TYPE_RAW    = 'R';

  public const IGNORABLE = 'Ignorable';

  protected $model;
  protected $DB;

  /** EXAMPLES OF configurable fields
   *
   * INT: {
   *  "name"    : "Validation Attempts",
   *  "binding" : "auto_compliance_attempts",
   *  "type"    : "Int",
   *  "max"     : 100, (optional)
   *  "step"    : 2, (optional)
   *  "min"     : 0 (optional)
   * }
   *
   * TEXT: {
   *  "name"    : "Email Address",
   *  "binding" : "email",
   *  "type"    : "Text"
   * }
   *
   * BOOL: {
   *  "name"    : "Email Sent",
   *  "binding" : "email_sent",
   *  "type"    : "Bool"
   * }
   *
   * DATE: {
   *  "name"    : "Callback date",
   *  "binding" : "callback_date",
   *  "type"    : "Date"
   * }
   *
   * ENUM: {
   *  "name"    : "Is Verified",
   *  "binding" : "is_verfied",
   *  "type"    : "Enum",
   *  "options" : [{"Yes": "Y"}, {"No": "N"}, {"Unknown": "U"}]
   * }
   *
   */

  public function __construct(&$model)
  {
    $this->model = $model;
    $this->DB = Loader::getDB();
  }

  public function mainQuery($filters, $fields, $offset = null, $limit = null)
  {
    $query = $this->getQuery($filters);

    //Sanitize limit and offset
    $offset = filter_var($offset, FILTER_SANITIZE_NUMBER_INT);
    $limit = filter_var($limit, FILTER_SANITIZE_NUMBER_INT);

    $limit_query = $limit ? " LIMIT $offset, $limit;" : ";";
    $main_query = 'SELECT ' . $fields . $query['query'] . $limit_query;

    $results = $this->DB->exec($main_query, $query['bindings']);
    return empty($results) ? [] : $results;
  }

  public function countQuery($filters, $fields)
  {
    $query = $this->getQuery($filters);

    $count_query = 'SELECT count(*) as total FROM (' . 'SELECT ' . $fields . $query['query'] . ') as count_query;';
    $count_results = $this->DB->exec($count_query, $query['bindings']);

    $total = 0;
    if (isset($count_results) && isset($count_results[0]['total'])) {
      $total = $count_results[0]['total'];
    }

    return $total;
  }

  public function getQuery($filters)
  {
    $query = $this->createQuery($filters);
    $bindings = $this->createBindings($filters);
    $query = [
      'query' => $query,
      'bindings' => $bindings
    ];
    return $query;
  }

  protected function createQuery(&$filters)
  {

    $query = "";
    $query .= !empty($this->model->table_names) ? " FROM " . $this->model->table_names : '';
    $query .= !empty($this->model->join_query) ? " " . $this->model->join_query : '';
    $query .= $this->createWhere($filters);
    $query .= !empty($this->model->group_by) ? " GROUP BY " . $this->model->group_by : '';
    $query .= !empty($this->model->order_by) ? " ORDER BY " . $this->model->order_by : '';

    return str_ireplace([';'], '', $query);
  }

  protected function createBindings($filters)
  {
    $bindings = [];
    foreach ($filters as $filter) {
      $bindings[':' . $filter['field']] = $filter['value'];
    }

    return $bindings;
  }

  protected function createWhere(&$filters)
  {
    $where = ' WHERE ';
    $configurable_options = [];
    if ($this->model->configurable_fields) {
      $configurable_options = json_decode($this->model->configurable_fields, true);
    }

    $where_clause = self::createCondition($configurable_options, $filters, $this->model->where_clause);

    return $where . $where_clause;
  }

  protected static function createCondition($options, &$filters, $where)
  {
    //fix up ignorable fields
    $where = self::fixIgnorableFields($options, $filters, $where);

    //EXAMPLE: select * from a where some_date {{Date:some_date}};
    $where = self::conditionForDate($options, $filters, $where);

    //EXAMPLE: select * from a where email {{Text:email}};
    $where = self::conditionForText($options, $filters, $where);

    //EXAMPLE: select * from a where auto_compliance_attempts {{Int:auto_compliance_attempts}};
    $where = self::conditionForInt($options, $filters, $where);

    //EXAMPLE: select * from a where auto_compliance_attempts {{NullEnum:auto_compliance_attempts}};
    $where = self::conditionForNullableEnum($options, $filters, $where);

    //checks if value sent for ENUM is actually in the ENUM
    self::checkEnums($options, $filters);

    return $where;
  }

  protected static function fixIgnorableFields($options, $filters, $where)
  {
    //first replace all fields that have been sent
    $sentFields = [];

    foreach ($filters as $filter) {
      if (Tools::endsWith($filter['field'], '_begin')) {
        //for bound dates _begin
        $sentFields[] = preg_replace('/_begin$/', '', $filter['field']);
      } elseif (Tools::endsWith($filter['field'], '_end')) {
        //for bound dates _end
        $sentFields[] = preg_replace('/_end$/', '', $filter['field']);
      } else {
        $sentFields[] = $filter['field'];
      }
    }

    foreach ($sentFields as $sentField) {
      $pattern = '/\[\[' . self::IGNORABLE . '\:' . $sentField . ':([^]]*)]]/';
      $where = preg_replace($pattern, '$1', $where);
    }

    //then replace all fields that have not been sent
    foreach ($options as $option) {
      $pattern = '/\[\[' . self::IGNORABLE . '\:' . $option['binding'] . ':([^]]*)]]/';
      $where = preg_replace($pattern, '', $where);
    }

    return $where;
  }

  protected static function conditionForDate($options, $filters, $where)
  {
    foreach ($options as $option) {
      if ($option['type'] === self::TYPE_DATE) {
        $param_type = 'single';
        $begin_found = 0;
        $end_found = 0;
        $pattern = '{{' . self::TYPE_DATE . ':' . $option['binding'] . '}}';

        foreach ($filters as $filter) {
          if ($filter['field'] === $option['binding'] . '_begin') {
            $begin_found = 1;
          }
          if ($filter['field'] === $option['binding'] . '_end') {
            $end_found = 1;
          }
        }
        if ($begin_found && $end_found) {
          $param_type = 'double';
        }

        if ($param_type === 'single') {
          $where = str_replace(
            $pattern,
            ' = :' . $option['binding'],
            $where
          );
        } elseif ($param_type === 'double') {
          $where = str_replace(
            $pattern,
            ' BETWEEN :' . $option['binding'] . '_begin AND :' . $option['binding'] . '_end',
            $where
          );
        }
      }
    }

    return $where;
  }

  protected static function conditionForText($options, &$filters, $where)
  {
    foreach ($options as $option) {
      if ($option['type'] === self::TYPE_TEXT) {
        $pattern = '{{' . self::TYPE_TEXT . ':' . $option['binding'] . '}}';
        foreach ($filters as &$filter) {
          if ($filter['field'] === $option['binding']) {
            $raw_condition = $filter['condition'];
            switch ($raw_condition) {
              case 'lk':
                $where = str_replace(
                  $pattern,
                  ' LIKE :' . $option['binding'],
                  $where
                );
                $filter['value'] = '%' . $filter['value'] . '%';
                break;
              case 'xlk':
                $where = str_replace(
                  $pattern,
                  ' NOT LIKE :' . $option['binding'],
                  $where
                );
                $filter['value'] = '%' . $filter['value'] . '%';
                break;
              default:
                $where = str_replace(
                  $pattern,
                  ' = :' . $option['binding'],
                  $where
                );
                break;
            }
          }
        }
      }
    }

    return $where;
  }

  protected static function conditionForInt($options, $filters, $where)
  {
    foreach ($options as $option) {
      if ($option['type'] === self::TYPE_INT) {
        $pattern = '{{' . self::TYPE_INT . ':' . $option['binding'] . '}}';
        foreach ($filters as $filter) {
          if ($filter['field'] === $option['binding']) {
            $raw_condition = $filter['condition'];
            switch ($raw_condition) {
              case 'gt':
                $where = str_replace(
                  $pattern,
                  ' > :' . $option['binding'],
                  $where
                );
                break;
              case 'lt':
                $where = str_replace(
                  $pattern,
                  ' < :' . $option['binding'],
                  $where
                );
                break;
              case 'xeq':
                $where = str_replace(
                  $pattern,
                  ' <> :' . $option['binding'],
                  $where
                );
                break;
              default:
                $where = str_replace(
                  $pattern,
                  ' = :' . $option['binding'],
                  $where
                );
                break;
            }
          }
        }
      }
    }

    return $where;
  }

  protected static function conditionForNullableEnum($options, &$filters, $where)
  {
    foreach ($options as $option) {
      if ($option['type'] === self::TYPE_NULLABLEENUM) {
        $pattern = '{{' . self::TYPE_NULLABLEENUM . ':' . $option['binding'] . '}}';
        foreach ($filters as $k => &$filter) {
          if ($filter['field'] === $option['binding']) {
            if (strtoupper($filter['value']) === 'NULL') {
              $where = str_replace(
                $pattern,
                ' IS NULL',
                $where
              );
              unset($filters[$k]);
            } elseif (strtoupper($filter['value']) === 'NOT NULL') {
              $where = str_replace(
                $pattern,
                ' IS NOT NULL',
                $where
              );
              unset($filters[$k]);
            } else {
              $where = str_replace(
                $pattern,
                ' = :' . $option['binding'],
                $where
              );
            }
          }
        }
      }
    }

    return $where;
  }

  protected static function checkEnums($options, $filters)
  {
    foreach ($options as $option) {
      foreach ($filters as $filter) {
        if ($filter['field'] === $option['binding']) {
          if ($option['type'] === self::TYPE_ENUM) {
            $valid_values = [];
            $value = null;
            foreach ($filters as $filter) {
              if ($filter['field'] === $option['binding']) {
                $value = $filter['value'];
                foreach ($option['options'] as $enum_option) {
                  $valid_values[] = array_values($enum_option)[0];
                }
              }
            }

            if (!in_array($value, $valid_values, true)) {
              throw new HTTPException('Precondition Failed.', 412);
            }
          }
        }
      }
    }
  }
}
