<?php

namespace Kws3\ApiCore\Utils;

use PDO;
use \Exception;
use \Kws3\ApiCore\Loader;


/**
 *  Given $queryObject, prepare a paginated list of database rows based on $perPage scoped by $queryObject.
 *  Once query execution ends, it will exports the data as gzipped stream.
 *  $config takes table name, fields definition, column headers mapping, export filename and database connection details.
 *  By default it uses DB details from running app config
 *  $config also takes additional response headers.
 * @package Utils
 */
class Exporter extends Abstracts\PaginatedIterator
{


  protected array $queryObject;

  protected array $config;

  protected PDO $pdo;

  protected array $headers = [];

  protected $fields;

  protected $export_file_name = "export.csv";

  protected array $response_headers = [
    'Expires' => '0',
    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
    'Cache-Control' => 'private',
    'Content-Type' => 'application/octet-stream',
    'Content-Disposition' => 'attachment',
    'Content-Transfer-Encoding' => 'binary'
  ];

  /**
   * Constructor method
   * @param array $queryObject - object with query parameters compatible with Cortext
   * @param int $perPage - number of rows to be returned per page, default: 20
   * @param array $config - table name, fields defination, fields map, export filename and database connection details
   * Example:
   *  $config = [
   *  	'table' => 'table_name', (optional) can be pass along query
   *  	'fields' => ['id', 'name', 'email'...], or "`id`, `name`, `email`" (required)
   *  	'fields_map' => ['id' => 'ID', 'name' => 'NAME',...], (optional) (default: no map) (work if fields is array)
   *  	'filename' => 'export.csv', (optional) (default: export.csv)
   *  	'db' => [], (optional) (default: current app db config)
   *  	'response_headers' => ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="export.csv"'], (optional) (default: default headers)
   *  	];
   * @return void
   */

  public function __construct($queryObject, $perPage = 20, $config = [])
  {
    $this->config = $config;

    $this->queryObject = $queryObject;

    $this->export_file_name = isset($config['filename']) ? $config['filename'] : $this->export_file_name;

    $this->setConnection();

    $this->setFieldsAndMap();

    $this->setItemsPerPage($perPage);

    $totalItems = $this->execQuery($this->queryObject, [], true);

    $this->setTotalItems($totalItems);
  }

  public function getPage($pageNumber)
  {
    $itemsPerPage = $this->getItemsPerPage();
    $options = [
      'LIMIT' => $itemsPerPage,
      'OFFSET' => $pageNumber * $itemsPerPage
    ];

    $results = $this->execQuery($this->queryObject, $options);
    return empty($results) ? [] : $results;
  }

  public function export()
  {

    $this->setHeaders();
    dbg()->sendHeaders();


    $headers_filled = false;
    ob_start('ob_gzhandler');
    $fp = fopen('php://output', 'w');


    if (is_array($this->headers) && !empty($this->headers)) {
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
    dbg()->requestProcessed();
    die;
  }

  protected function setHeaders()
  {
    $new_response_headers = $this->config['response_headers'];

    if (!empty($new_response_headers)) {
      $this->response_headers = array_merge($this->response_headers, $new_response_headers);
    }

    foreach ($this->response_headers as $type => $value) {
      if ($type === 'Content-Disposition') {
        $value = $value . '; filename="' . $this->export_file_name . '"';
      }
      header($type . ': ' . $value);
    }
  }

  protected function setFieldsAndMap()
  {
    $fields_map = !empty($this->config['fields_map']) ? $this->config['fields_map'] : [];
    $this->fields = $this->config['fields'];
    if (is_array($this->fields)) {
      $this->headers = array_map(function ($field) use ($fields_map) {
        return isset($fields_map[$field]) ? $fields_map[$field] : ucfirst(str_replace('_', ' ', $field));
      }, $this->config['fields']);
    }
  }

  protected function setConnection()
  {
    $config = isset($this->config['db']) ? $this->config['db'] : Loader::get('CONFIG')['DB'];
    $dsn = $config['adapter'] . ':host=' . $config['host'] . ';dbname=' . $config['dbname'];
    $this->pdo = new PDO($dsn, $config['username'], $config['password']);
    $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, FALSE);
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

    $data = $this->__getLineAttribution();
    dbg()->addDatabaseQuery($query, null, 0, $data);

    return $countOnly ? $statement->fetchColumn() : $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  protected function generateFields()
  {
    $fields = $this->fields;
    return '`' . implode('`, `', $fields) . '`';
  }

  protected function buildQueryPrefix()
  {
    if (is_array($this->fields)) {
      if (isset($this->config['table']) && !empty($this->config['table'])) {
        return "SELECT {$this->generateFields()} FROM `{$this->config['table']}`";
      } else {
        throw new Exception('Table name is not set');
      }
    }

    if (is_string($this->fields)) {
      return "SELECT {$this->fields} ";
    }

    throw new Exception('Fields are not set');
  }

  protected function __getLineAttribution()
  {
    $bt = debug_backtrace(0, 10);
    array_shift($bt);
    $base = str_replace(DIRECTORY_SEPARATOR . 'public', '', getcwd()) . DIRECTORY_SEPARATOR;
    $data = [
      'connection' => 'mysql',
      'model' => '',
      'file' => '',
      'line' => '',
    ];
    $line_found = false;
    $model_found = false;
    foreach ($bt as $trace) {
      $f = explode($base, $trace['file']);
      $file = isset($f[1]) ? $f[1] : $f[0];
      $parts = explode(DIRECTORY_SEPARATOR, $file);
      if ($parts[0] === 'app' && !$line_found) {
        $data['file'] = $file;
        $data['line'] = $trace['line'];
        $line_found = true;
      }
      if ($parts[0] === 'app' && $trace['class'] !== 'DB\Cortex' && $trace['class'] !== 'DB\CortexCollection') {
        if (empty($data['file'])) {
          $data['file'] = $file;
        };
        if (empty($data['line'])) {
          $data['line'] =  $trace['line'];
        };
        $data['model'] = $trace['class'] . "::" . $trace['function'];
        $model_found = true;
      }

      if ($line_found && $model_found) {
        break;
      }
    }

    return $data;
  }
}
