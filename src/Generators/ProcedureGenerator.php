<?php

namespace Kws3\ApiCore\Generators;

use \PDO;
use \RuntimeException;

class ProcedureGenerator extends Base
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
      'namespace' => 'Models\\Procedures',
      'extends' => '\\Models\\Procedures',
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

    try {
      $this->DB = \Kws3\ApiCore\Framework::createDB(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
      );
    } catch (\Exception $ex) {
      echo "DB connection error\n";
    }

    clearstatcache();
    try {
      $this->checkConditions();
    } catch (RuntimeException $ex) {
      $message = $ex->getMessage();
      $this->output($message, true);
      exit;
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

    foreach ($schema as $proc) {
      if (!in_array($proc['name'], $config['exclude'], true)) {
        $className = $this->className($proc['name']);
        $h = fopen($config['output'] . $className . '.php', 'w');
        if (fwrite($h, $this->generateDBProcedure(
          $proc,
          $config['namespace'],
          $config['extends']
        ))) {
          $this->output("Generated " . $className . " Procedure Source " . $h, false);
        } else {
          $this->output('Failed to generate ' . $className . ' Procedure Source ', true);
        }

        fclose($h);
        usleep(250000);
      }
    }
  }

  public function getSchema()
  {
    $PROCEDURES = [];
    $procedures = $this->DB->query("SHOW PROCEDURE STATUS WHERE `Db` = '" . $this->config['DB']['dbname'] . "';")->fetchAll(PDO::FETCH_NUM);

    foreach ($procedures as $procRow) {
      $procedure = $procRow[1];
      $queryResult = $this->DB->query('SHOW CREATE PROCEDURE `' . $procedure . '`')->fetchAll(PDO::FETCH_NUM);
      $sourceData = isset($queryResult) ? $queryResult[0] : ['name' => '', 'source' => ''];

      $PROCEDURES[$sourceData[0]] = [
        'name' => $sourceData[0],
        'source' => $this->normalizeSource($sourceData[2])
      ];
    }

    return $PROCEDURES;
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

  protected function generateDBProcedure($proc, $namespace = null, $extends = null, $classname = null)
  {
    $modelTemplate = $this->getTemplate();

    $procname = strtolower($proc['name']);

    $data = [
      '{{NAMESPACE}}' => '',
      '{{CLASSNAME}}' => '',
      '{{EXTENDS}}' => '',
      '{{PROCSOURCE}}' => $proc['source'],
      '{{PROCNAME}}' => $procname,
    ];

    if ($namespace) {
      $data['{{NAMESPACE}}'] = 'namespace ' . $namespace . ';';
    }

    if ($extends) {
      $data['{{EXTENDS}}'] = 'extends ' . $extends;
    }

    if (!$classname) {
      $classname = $this->className($procname);
    }

    $data['{{CLASSNAME}}'] = $classname;



    $modelTemplate = str_replace(array_keys($data), array_values($data), $modelTemplate);

    return $modelTemplate;
  }

  protected function getTemplate()
  {
    if (empty($this->_template)) {
      $this->_template = !empty($this->config['template']) ? file_get_contents($this->config['template']) : <<<PHP
<?php
{{NAMESPACE}}

class {{CLASSNAME}} {{EXTENDS}}
{
    protected \$procName = "{{PROCNAME}}",
    \$procSource = <<<PROCSOURCE
{{PROCSOURCE}}
PROCSOURCE;

}
PHP;
    }

    return $this->_template;
  }

  protected function className($t, $ns = '')
  {
    return $ns . ucfirst(strtolower($t));
  }

  protected function normalizeSource($source)
  {
    return preg_replace("/CREATE (.*) PROCEDURE /", 'CREATE PROCEDURE ', $source);
  }
}
