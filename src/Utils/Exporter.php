<?php

namespace Kws3\ApiCore\Utils;

use PDO;
use \Kws3\ApiCore\Loader;

/**

 *
 * @package Utils
 */
class Exporter extends Abstracts\PaginatedIterator
{

  /** @var array */
  protected $queryObject;

  protected $config;

  protected $pdo;
  protected $headers;
  protected $fields;
  protected $fields_described = false;

  protected $response_headers = [
    'Expires' => '0',
    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
    'Cache-Control' => 'private',
    'Content-Type' => 'application/octet-stream',
    'Content-Disposition' => 'attachment; filename="export.csv"',
    'Content-Transfer-Encoding' => 'binary'
  ];

  public function __construct($queryObject, $perPage = 20, $config = [])
  {
    $this->config = $config;

    $this->queryObject = $queryObject;

    $this->fields_described = !empty($this->config['fields']) && is_array($this->config['fields']) ? true : false;

    $this->setHeaders();
    $this->setConnection();

    if ($this->fields_described) {
      $this->setFieldsAndMap();
    }

    $this->setItemsPerPage($perPage);

    $totalItems = $this->execQuery($this->queryObject, [], true);

    $this->setTotalItems($totalItems);
  }
  protected function setHeaders()
  {
    $new_response_headers = $this->config['response_headers'];

    if (!empty($new_response_headers)) {
      $this->response_headers = array_merge($this->response_headers, $new_response_headers);
    }

    foreach ($this->response_headers as $type => $value) {
      header($type . ': ' . $value);
    }
  }

  protected function setFieldsAndMap()
  {
    $fields_map = $this->config['fields_map'];
    $this->fields = $this->config['fields'];
    $this->headers = array_map(function ($field) use ($fields_map) {
      return isset($fields_map[$field]) ? $fields_map[$field] : ucfirst(str_replace('_', ' ', $field));
    }, $this->config['fields']);
  }

  protected function setConnection()
  {
    $config = isset($this->config['db']) ? $this->config['db'] : Loader::get('CONFIG')['DB'];
    $dsn = $config['adapter'] . ':host=' . $config['host'] . ';dbname=' . $config['dbname'];
    $this->pdo = new PDO($dsn, $config['username'], $config['password']);
    $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, FALSE);
  }

  public function getPage($pageNumber)
  {
    $itemsPerPage = $this->getItemsPerPage();
    $options = [
      'LIMIT' => $itemsPerPage,
      'OFFSET' => ($pageNumber * $itemsPerPage)
    ];

    $results = $this->execQuery($this->queryObject, $options);
    return empty($results) ? [] : $results;
  }

  protected function execQuery($qobj, $options = [], $countOnly = false)
  {
    $main_query = $qobj[0];
    $option_string = '';
    $bindings =  array_slice($qobj, 1, NULL, true);

    if (!empty($options)) {
      foreach ($options as $key => $value) {
        $option_string .= ' ' . $key . ' ' . $value;
      }
    }

    $query = "{$this->buildQueryPrefix()} $main_query  $option_string";

    if ($countOnly) {
      $query = "SELECT COUNT(*) FROM ($query) AS count_query";
    }
    // tweak to convert positional parameters into temporary named parameters.
    if (strpos($query, ":")) {
      $i = 0;
      while (strpos($query, "?") && isset($bindings[++$i])) {
        $bindings[":__tmp_bind_$i"] = $bindings[$i];
        unset($bindings[$i]);
        $query = preg_replace("/[?]/", ":__tmp_bind_$i", $query, 1);
      }
    }

    $statement = $this->pdo->prepare($query);


    foreach ($bindings as $key => $value) {
      $param = false;
      switch ($value) {
        case is_int($value):
          $param = PDO::PARAM_INT;
          break;
        case is_bool($value):
          $param = PDO::PARAM_BOOL;
          break;
        case is_null($value):
          $param = PDO::PARAM_NULL;
          break;
        case is_string($value):
          $param = PDO::PARAM_STR;
          break;
      }

      if ($param) $statement->bindValue($key, $value, $param);
    }

    $statement->execute();
    return $countOnly ? $statement->fetchColumn() : $statement->fetchAll(PDO::FETCH_ASSOC);
  }
  protected function generateFields()
  {
    $fields = $this->fields;
    return '`' . implode('`, `', $fields) . '`';
  }

  protected function buildQueryPrefix()
  {
    if ($this->fields_described && isset($this->config['table'])) {
      return "SELECT {$this->generateFields()} FROM `{$this->config['table']}`";
    }
    return "SELECT";
  }

  public function export()
  {
    $headers_filled = false;
    ob_start('ob_gzhandler');
    $fp = fopen('php://output', 'w');


    if ($this->fields_described) {
      if (!$headers_filled) {
        fputcsv($fp, $this->headers);
        $headers_filled = true;
      }
    }


    foreach ($this as $page) {

      if (!$headers_filled) {
        if (isset($page[0])) {
          $headers = array_map(function ($item) {
            return ucfirst(str_replace('_', ' ', $item));
          }, array_keys($page[0]));
          fputcsv($fp, $headers);
          $headers_filled = true;
        }
      }

      foreach ($page as $row) {
        fputcsv($fp, $row);
      }
      if (ob_get_level() > 0) {
        ob_flush();
        ob_end_flush();
      }
      flush();
    }
    fclose($fp);
    die;
  }
}
