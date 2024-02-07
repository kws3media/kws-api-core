<?php

namespace Kws3\ApiCore\Test;

use \Kws3\ApiCore\Loader;
use \Kws3\ApiCore\Utils\Tools;
use \Kws3\ApiCore\Utils\ConsoleColor;
use \Kws3\ApiCore\Exceptions\HTTPException;

class Base
{

  public $isolate = true;

  public $passed = 0;
  public $failed = 0;
  public $exceptions = 0;
  public $results = [];
  public $failures = [];

  protected $app;

  protected Tester $test;

  /**
   * String array of method names that are to be run exclusively.
   * When this array is filled, only the methods in the array will be run.
   */
  protected array $onlyRun = [];

  function __construct()
  {
    $this->app = \Base::instance();

    //less verbose errors in console
    Loader::set('DEBUG', 0);
    Loader::set('HIGHLIGHT', false);
  }

  function before()
  {
    return;
  }

  function after()
  {
    return;
  }

  function beforeEach()
  {
    return;
  }

  function afterEach()
  {
    return;
  }

  function setDB()
  {
    $config = Loader::get('CONFIG');
    Loader::set('DB', call_user_func(function ($config) {
      $dsn = $config['adapter'] . ':host=' . $config['host'] . ';port=' . ($config['port'] ?: '3306') . ';dbname=' . $config['dbname'];

      try {
        return \Kws3\ApiCore\Framework::createDB(
          $dsn,
          $config['username'],
          $config['password'],
          [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
      } catch (\Exception $ex) {
        $this->op('Unable to connect to the database.', 'Uncaught Exception', true);
        $this->exceptions++;
        echo "\n";
      }
    }, $config['TEST_DB']));
  }

  function run($is_forked = false, $outfile = "")
  {
    $class = get_class($this);
    $methods = get_class_methods($class);

    $exclusives = null;
    if ($this->onlyRun && is_array($this->onlyRun) && count($this->onlyRun) > 0) {
      $exclusives = $this->onlyRun;
    }

    $toRun = [];
    foreach ($methods as $meth) {
      if (stripos($meth, 'test') === 0) {
        $toRun[] = $meth;
      }
    }

    if (count($toRun) > 0) {
      $this->before();
      foreach ($toRun as $m) {
        $this->test = new Tester;
        echo "- " . $m . "\n";
        try {
          $canRunMethod = true;
          if (!is_null($exclusives)) {
            $canRunMethod = false;
            if (in_array($m, $exclusives, true)) {
              $canRunMethod = true;
            }
          }
          if ($canRunMethod) {
            $this->beforeEach();
            $this->{$m}();
            $this->afterEach();
            $this->log();
            $this->handleResults($m);
          } else {
            $this->describe('Skipping method');
          }

          echo "\n";
        } catch (\Exception $ex) {
          $this->op($ex->getMessage(), 'Uncaught Exception', true);
          $this->exceptions++;
          echo "\n";
        }
      }
      $this->after();
    }

    if ($is_forked) {
      file_put_contents($outfile, serialize([
        'passed' => $this->passed,
        'failed' => $this->failed,
        'exceptions' => $this->exceptions,
        'results' => $this->results,
        'failures' => $this->failures,
      ]));
    }
  }

  function results()
  {
    return $this->results;
  }

  function failures()
  {
    return $this->failures;
  }

  function reIdentify($key)
  {
    Loader::set('HEADERS.Api-Key', $key);
    Loader::getIdentity()->reIdentify();
  }

  function forgetIdentity()
  {
    Loader::getIdentity()->forget();
  }

  function resetDatabase()
  {
    //set db
    $this->setDB();

    //need to do this in the right order

    //1. remove views
    foreach (Loader::get('VIEWS_LIST') as $view) {
      $mdl = "\\DBViews\\" . $view;
      $mdl::setdown();
    }

    //2. remove procedure
    $PROCEDURES_LIST = Loader::get('PROCEDURES_LIST');
    if (!empty($PROCEDURES_LIST)) {
      foreach (Loader::get('PROCEDURES_LIST') as $model) {
        $mdl = "\\DBProcedures\\" . $model;
        $mdl::setdown();
      }
    }

    //3. remove tables
    foreach (Loader::get('MODELS_LIST') as $model) {
      $mdl = "\\Models\\Base\\" . $model;
      $mdl::setdown();
    }

    //4. recreate tables
    foreach (Loader::get('MODELS_LIST') as $model) {
      $mdl = "\\Models\\Base\\" . $model;
      $mdl::setup();
    }

    //5. recreate views
    foreach (Loader::get('VIEWS_LIST') as $view) {
      $mdl = "\\DBViews\\" . $view;
      $mdl::setup();
    }

    //6. recreate procedure
    if (!empty($PROCEDURES_LIST)) {
      foreach (Loader::get('PROCEDURES_LIST') as $model) {
        $mdl = "\\DBProcedures\\" . $model;
        $mdl::setup();
      }
    }
  }

  function fillData($data, $namespace = "\\Models\\")
  {
    if (is_array($data)) {

      $tally = [];

      foreach ($data as $table => $rows) {
        $mdl = $namespace . $table;
        foreach ($rows as $row) {
          $model = new $mdl;
          foreach ($row as $field => $value) {
            //handle @ pointers
            if ($this->startsWith($value, '@')) {
              preg_match("/@([^\[]*)\[(\d+)\]/", $value, $matches);
              if (isset($matches[1]) && isset($tally[$matches[1]])) {
                if (isset($matches[2]) && isset($tally[$matches[1]][$matches[2]])) {
                  $value = $tally[$matches[1]][$matches[2]];
                }
              }
            }
            //set field values
            $model->{$field} = $value;
          }
          $model->save();

          //store ids for @ pointers
          $tally[$table][] = $model->id;
        }
      }
    }
  }

  /**
   * Sets the default value of a column of a given table, to a given value
   *
   * @param
   * expected params:
   * $table_name: required (string)
   * $column_name: required (string)
   * $default_value: optional (string or integer) (defaults to 0)
   **/

  function setColumnDefaultValue($table_name, $column_name, $default_value = 0)
  {
    Loader::getDB()->exec(
      'ALTER TABLE `' . $table_name . '` ALTER COLUMN `' . $column_name . '` SET DEFAULT ?;',
      $default_value
    );
  }

  function startsWith($haystack, $needle)
  {
    return Tools::startsWith($haystack, $needle);
  }

  function endsWith($haystack, $needle)
  {
    return Tools::endsWith($haystack, $needle);
  }

  function contains($haystack, $needle)
  {
    return Tools::contains($haystack, $needle);
  }

  function notContains($haystack, $needle)
  {
    return !Tools::contains($haystack, $needle);
  }

  // assertion methods

  /**
   * Asserts isset($actual)
   * @param mixed $actual
   * @param mixed $expected
   * @param string $message
   * @return void
   */
  function assertIsSet($actual, $message = null)
  {
    $pass = isset($actual);
    $message = $pass ? $message : $message . $this->augmentErrorMessage($this->wrapType($actual), null, "to be set");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts !isset($actual)
   * @param mixed $actual
   * @param mixed $expected
   * @param string $message
   * @return void
   */
  function assertNotIsSet($actual, $message = null)
  {
    $pass = !isset($actual);
    $message = $pass ? $message : $message . $this->augmentErrorMessage($this->wrapType($actual), null, "NOT to be set");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts $actual is empty
   * @param mixed $actual
   * @param mixed $expected
   * @param string $message
   * @return void
   */
  function assertEmpty($actual, $message = null)
  {
    $pass = empty($actual);
    $message = $pass ? $message : $message . $this->augmentErrorMessage($this->wrapType($actual), null, "to be empty");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts $actual is NOT empty
   * @param mixed $actual
   * @param mixed $expected
   * @param string $message
   * @return void
   */
  function assertNotEmpty($actual, $message = null)
  {
    $pass = !empty($actual);
    $message = $pass ? $message : $message . $this->augmentErrorMessage($this->wrapType($actual), null, "NOT to be empty");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts $actual is strictly equal to $expected
   * @param mixed $actual
   * @param mixed $expected
   * @param string $message
   * @return void
   */
  function assertEquals($actual, $expected, $message = null)
  {
    $pass = $expected === $actual;
    $message = $pass ? $message : $message . $this->augmentErrorMessage($this->wrapType($actual), $this->wrapType($expected), "to be strictly equal to");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts $actual is strictly NOT equal to $expected
   * @param mixed $actual
   * @param mixed $expected
   * @param string $message
   * @return void
   */
  function assertNotEquals($actual, $expected, $message = null)
  {
    $pass = $expected !== $actual;
    $message = $pass ? $message : $message . $this->augmentErrorMessage($this->wrapType($actual), $this->wrapType($expected), "to be strictly NOT equal to");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts $actual contains the string $expected
   * @param string $actual
   * @param string $expected
   * @param string $message
   * @return void
   */
  function assertContains($actual, $expected, $message = null)
  {
    $pass = $this->contains($actual, $expected);
    $message = $pass ? $message : $message . $this->augmentErrorMessage($actual, $expected, "to contain");
    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts $actual does not contain the string $expected
   * @param string $actual
   * @param string $expected
   * @param string $message
   * @return void
   */
  function assertNotContains($actual, $expected, $message = null)
  {
    $pass = $this->notContains($actual, $expected);
    $message = $pass ? $message : $message . $this->augmentErrorMessage($actual, $expected, "to NOT contain");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts $actual starts with the string $expected
   * @param string $actual
   * @param string $expected
   * @param string $message
   * @return void
   */
  function assertStartsWith($actual, $expected, $message = null)
  {
    $pass = $this->startsWith($actual, $expected);
    $message = $pass ? $message : $message .  $this->augmentErrorMessage($actual, $expected, "to start with");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts $actual does not start with the string $expected
   * @param string $actual
   * @param string $expected
   * @param string $message
   * @return void
   */
  function assertNotStartsWith($actual, $expected, $message = null)
  {
    $pass = !$this->startsWith($actual, $expected);
    $message = $pass ? $message : $message . $this->augmentErrorMessage($actual, $expected, "to NOT start with");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts $actual ends with the string $expected
   * @param string $actual
   * @param string $expected
   * @param string $message
   * @return void
   */
  function assertEndsWith($actual, $expected, $message = null)
  {
    $pass = $this->endsWith($actual, $expected);
    $message = $pass ? $message : $message . $this->augmentErrorMessage($actual, $expected, "to end with");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Asserts $actual does not end with the string $expected
   * @param string $actual
   * @param string $expected
   * @param string $message
   * @return void
   */
  function assertNotEndsWith($actual, $expected, $message = null)
  {
    $pass = !$this->endsWith($actual, $expected);
    $message = $pass ? $message : $message . $this->augmentErrorMessage($actual, $expected, "to NOT end with");

    $this->test->expect(
      $pass,
      $message,
      1
    );
  }

  /**
   * Prints a description of the test to output console.
   * @param string $msg
   * @return void
   */
  function describe($msg)
  {
    $msgParts = preg_split("/(\r\n|\n|\r)/", trim($msg));
    foreach ($msgParts as $msgPart) {
      echo "  " . ConsoleColor::info(" " . trim($msgPart) . " ") . "\n";
    }
    $this->flush();
  }

  /**
   * Create a request expecting it to throw an exception.
   * If an exception is not thrown by the request, the test will fail implicitly.
   *
   * Expected array keys:
   *  - url: required
   *  - data: optional
   *  - headers: array of headers (optional)
   *  - identity: optional
   *
   * @param array $data
   *
   **/

  function mockException($data = [])
  {

    $ex_message = "";
    try {

      $this->mockRequest($data);
    } catch (HTTPException $ex) {
      $ex_message = $ex->getMessage();
    }
    $this->test->expect(
      ($ex_message !== ''),
      "Expected Exception has thrown in this test.",
      1
    );

    return $ex_message;
  }

  /**
   * Create a request.
   * Expected array keys:
   *  - url: required
   *  - data: optional
   *  - headers: array of headers (optional)
   *  - identity: optional,
   *
   * @param array $data
   *
   **/

  function mockRequest($data = [])
  {
    if (!isset($data['url']) || empty($data['url'])) {
      throw new \Exception('All required parameters not sent for test');
    }
    if (isset($data['identity'])) {
      $this->reIdentify($data['identity']);
    }
    if (isset($data['headers'])) {
      if (is_array($data['headers'])) {
        foreach ($data['headers'] as $key => $value) {
          Loader::set('HEADERS.' . $key, $value);
        }
      }
    }
    $mockData = [];
    if (isset($data['data'])) {
      $mockData = $data['data'];
    }

    $response = $this->childProcessRequest($data['url'], $mockData);
    return json_decode($response, true);
  }

  function log()
  {
    foreach ($this->test->results() as $result) {
      $msg = $result['text'];
      if ($result['status']) {
        $this->op($msg, 'Pass', $result["source"]);
        $this->passed++;
      } else {
        $this->op($msg, 'Fail', $result["source"], true);
        $this->failed++;
      }
      usleep(12500);
    }
  }





  function op($txt, $msg, $src, $err = false)
  {
    $processed = $this->processSourceFile($src);
    $line = $processed[1];
    if ($err) {
      echo "  -> " . ConsoleColor::error(" " . $msg . " ") . " " . ConsoleColor::info("[L" . str_pad($line, 4, '0', STR_PAD_LEFT) . "]") . " - $txt\n";
    } else {
      echo "  -> " . ConsoleColor::success(" " . $msg . " ") . " " . ConsoleColor::info("[L" . str_pad($line, 4, '0', STR_PAD_LEFT) . "]") . " - $txt\n";
    }
    $this->flush();
  }

  /**
   * Flush the output buffer
   * @return void
   */
  function flush()
  {
    if (ob_get_level() > 0) {
      ob_flush();
      ob_end_flush();
    }
    flush();
  }

  protected function childProcessRequest($url, $mockData)
  {

    $TAG_EXCEPTION = "|EXCEPTION|";
    $TAG_RESPONSE = "|RESPONSE|";

    if (!function_exists("pcntl_fork")) {
      echo "Server does not support process isolation";
      $this->flush();
      die();
    }
    if (!function_exists("socket_create_pair")) {
      echo "Server does not support sockets";
      $this->flush();
      die();
    }

    $sockets = [];
    if (socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets) === false) {
      echo "socket_create_pair() failed. Reason: " . socket_strerror(socket_last_error());
      $this->flush();
    }

    $pid = \pcntl_fork();
    if ($pid === -1) {
      echo "Could not fork child process";
      $this->flush();
      die();
    } elseif ($pid) {
      //We are in parent process
      pcntl_wait($status);
      $this->flush();

      $received = trim(socket_read($sockets[1], 65535));

      socket_close($sockets[1]);

      if ($this->startsWith($received, $TAG_RESPONSE)) {
        $omit = strlen($TAG_RESPONSE);
        return substr($received, $omit);
      }

      if ($this->startsWith($received, $TAG_EXCEPTION)) {
        $omit = strlen($TAG_EXCEPTION);
        throw new HTTPException(substr($received, $omit));
      }
    } else {
      //we are in child process

      //this shutdown function ensures
      //child processes do not close the database connection
      register_shutdown_function(function () {
        posix_kill(getmypid(), SIGKILL);
      });

      $response = null;
      $ex_message = null;
      try {
        $this->app->mock($url, $mockData);
        $response = Loader::get('RESPONSE');
      } catch (HTTPException $ex) {
        $ex_message = $ex->getMessage();
      }

      $output = "";
      if ($ex_message !== null) {
        $output .= $TAG_EXCEPTION;
        $output .= $ex_message . "\r";
      } else {
        $output .= $TAG_RESPONSE;
        $output .= $response . "\r";
      }


      if (socket_write($sockets[0], $output, strlen($output)) === false) {
        echo "socket_write() failed. Reason: " . socket_strerror(socket_last_error($sockets[0]));
        $this->flush();
      }

      socket_close($sockets[0]);
      exit;
    }
  }

  protected function handleResults($methodName)
  {
    $methodResults = $this->test->results();
    if (is_array($methodResults) && count($methodResults) > 0) {
      $this->results = array_merge($this->results, $methodResults);
      foreach ($methodResults as $res) {
        if ($res['status'] === false) {
          $this->failures[] = [
            'method' => $methodName,
            'text' => $res['text'],
            'source' => $res['source']
          ];
        }
      }
    }
  }

  protected function augmentErrorMessage($actual, $expected, $token)
  {
    return " \n     • Expected " . ConsoleColor::warning($actual) . " $token " . ConsoleColor::warning($expected);
  }

  protected function wrapType($v)
  {
    if (is_string($v)) {
      return '"' . $v . '"';
    }
    if (is_array($v)) {
      return print_r($v, true);
    }
    return $v;
  }

  protected function processSourceFile($source)
  {
    $cwd = getcwd();
    $fileLine = explode(':', str_replace($cwd, "", $source));
    return [
      $fileLine[0], $fileLine[1]
    ];
  }
}
