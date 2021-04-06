<?php

namespace Kws3\ApiCore\Generators;

use \PDO;
use \RuntimeException;

class ViewGenerator extends Base
{

  private $config = array();
  private $adapter = 'mysql';
  private $DB;

  public function __construct($config = array())
  {
    $this->setConnection($config);
  }

  public function setConnection($config = array())
  {
    //declare default options
    $defaults = array(
      'output' => 'path/to/output/folder',
      'DB' => [
        "host" => '',
        "username" => '',
        "password" => '',
        "dbname" => ''
      ],
      'namespace' => 'Models\\Views',
      'extends' => '\\Models\\Views',
      'template' => '',
      'exclude' => array()
    );

    foreach ($config as $key => $value) {
      //overwrite the default value of config item if it exists
      if (array_key_exists($key, $defaults)) {
        $defaults[$key] = $value;
      }
    }

    //store the config back into the class property
    $this->config = $defaults;
    $dbConfig = $this->config['DB'];

    $dsn = $this->adapter . ':host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['dbname'];
    $this->DB = new PDO(
      $dsn,
      $dbConfig['username'],
      $dbConfig['password']
    );

    clearstatcache();
    try {
      $this->checkConditions();
    } catch (RuntimeException $ex) {
      $message = $ex->getMessage();
      $this->output($message, true);
      exit;
    }
  }

  protected function checkConditions()
  {
    $config = $this->config;

    if (empty($config['output']) || !(file_exists($config['output']) && is_dir($config['output']) && is_writable($config['output']))) {
      throw new RuntimeException('Please ensure that the output folder exists and is writable.');
    }

    if (!empty($this->config['template'])) {
      if (!(file_exists($this->config['template']) && is_file($this->config['template']))) {
        throw new RuntimeException("The specified template file does not exist.\nPlease leave the `template` option empty if you would like to use the built-in template.");
      }
    }
  }

  public function generate()
  {
    try {
      $schema = $this->getSchema();
    } catch (\PDOException $ex) {
      $message = $ex->getMessage();
      $this->output("Database connection failed with the message
>> \"" . $message . "\"
Please ensure database connection settings are correct.", true);
      exit;
    }

    $config = $this->config;

    foreach ($schema as $view) {
      if (!in_array($view['name'], $config['exclude'])) {
        $className = $this->className($view['name']);
        $h = fopen($config['output'] . $className . '.php', 'w');
        if (fwrite($h, $this->generateDBView(
          $view,
          $config['namespace'],
          $config['extends']
        ))) {
          $this->output("Generated " . $className . " View Source", false);
        } else {
          $this->output('Failed to generate ' . $className . ' View Source', true);
        }

        fclose($h);
        usleep(250000);
      }
    }
  }

  protected function generateDBView($view, $namespace = null, $extends = null, $classname = null)
  {
    $modelTemplate = $this->getTemplate();

    $viewname = strtolower($view['name']);

    $data = [
      '{{NAMESPACE}}' => '',
      '{{CLASSNAME}}' => '',
      '{{EXTENDS}}' => '',
      '{{VIEWSOURCE}}' => $view['source'],
      '{{VIEWNAME}}' => $viewname,
    ];

    if ($namespace) {
      $data['{{NAMESPACE}}'] = 'namespace ' . $namespace . ';';
    }

    if ($extends) {
      $data['{{EXTENDS}}'] = 'extends ' . $extends;
    }

    if (!$classname) {
      $classname = $this->className($viewname);
    }

    $data['{{CLASSNAME}}'] = $classname;



    $modelTemplate = str_replace(array_keys($data), array_values($data), $modelTemplate);

    return $modelTemplate;
  }

  protected function getTemplate()
  {
    if (empty($this->_template)) {
      if (!empty($this->config['template'])) {
        $this->_template = file_get_contents($this->config['template']);
      } else {
        $this->_template = <<<PHP
<?php
{{NAMESPACE}}

class {{CLASSNAME}} {{EXTENDS}}
{
    protected \$viewName = "{{VIEWNAME}}",
    \$viewSource = <<<VIEWSOURCE
{{VIEWSOURCE}}
VIEWSOURCE;

}
PHP;
      }
    }

    return $this->_template;
  }

  public function getSchema($raw = false)
  {
    $VIEWS = [];
    $tables = $this->DB->query('SHOW FULL TABLES WHERE `Table_type` = "VIEW"')->fetchAll(PDO::FETCH_NUM);

    foreach ($tables as $tableRow) {

      $view = $tableRow[0];
      $queryResult = $this->DB->query('SHOW CREATE VIEW `' . $view . '`')->fetchAll(PDO::FETCH_NUM);
      $sourceData = isset($queryResult) ? $queryResult[0] : ['name' => '', 'source' => ''];

      $VIEWS[$sourceData[0]] = [
        'name' => $sourceData[0],
        'source' => $this->normalizeSource($sourceData[1])
      ];
    }

    return $VIEWS;
  }

  protected function className($t, $ns = '')
  {
    return $ns . ucfirst(strtolower($t));
  }

  protected function normalizeSource($source)
  {
    return preg_replace("/CREATE (.*) VIEW `/", 'CREATE OR REPLACE VIEW `', $source);
  }
}
