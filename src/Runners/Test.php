<?php

declare(ticks=1);

namespace Kws3\ApiCore\Runners;

use \Kws3\ApiCore\Utils\ConsoleColor;
use \Kws3\ApiCore\Loader;

class Test extends Base
{
  public function help()
  {
    $this->output("\n");
    $this->output("To generate a test file:", false);
    $this->output("> composer test generate testFileName");
    $this->output("\n");
    $this->output("To generate a test file in a test group:", false);
    $this->output("> composer test generate groupName:testFileName");
    $this->output("\n");
    $this->output("To run all tests:", false);
    $this->output("> composer test start");
    $this->output("\n");
    $this->output("To run a single test group:", false);
    $this->output("> composer test start groupName");
    $this->output("\n");
    $this->output("To run a single test file:", false);
    $this->output("> composer test start groupName:testFileName");
    $this->output("\n");
    $this->output("To see a list of all available groups:", false);
    $this->output("> composer test listGroups");
    $this->output("\n");
  }

  public function run($params)
  {

    $func = array_shift($params);

    if (method_exists($this, $func)) {
      $reflection = new \ReflectionMethod($this, $func);
      if ($reflection->isPublic()) {
        $this->$func($params);
      }
    } else {
      $this->output('Error: Invalid command', true);
    }
  }

  public function listGroups()
  {
    $folder = $this->config['folder'];

    $this->output("\n");
    $this->output("List of available test groups:", false);
    $testGroupFolders = glob($this->fixPath($folder . '/*'), GLOB_ONLYDIR);
    foreach ($testGroupFolders as $dir) {
      $ex = explode(DIRECTORY_SEPARATOR, $dir);
      $namespace = end($ex);
      $this->output(" - " . $namespace);
    }
  }

  function generate($className)
  {

    $folder = $this->config['folder'];

    if (is_array($className)) {
      $className = array_shift($className);
    }

    $dir = false;
    if (strpos($className, ':') !== false) {
      list($dir, $className) = explode(':', $className);
      $dir = ucfirst(strtolower($dir));
      $f = $this->fixPath($folder . '/' . $dir);
      if (!file_exists($f) || !is_dir($f)) {
        mkdir($f);
      }
    } else {
      $f = $this->fixPath($folder);
    }

    $className = ucfirst(strtolower($className));
    $className = !$this->endsWith($className, 'test') ? $className . 'Test' : substr($className, 0, -4) . 'Test';
    $f .= $this->fixPath('/' . $className . '.php');

    if (file_exists($f)) {
      $this->output('Failed to generate ' . ($dir ? $dir . "/" : '') . $className . ', file already exists', true);
      return;
    }

    $h = fopen($f, 'w');
    if (fwrite($h, "<?php

namespace " . $this->config['namespace'] . ($dir ? "\\" . $dir : '') . ";

class $className extends " . $this->config['base_namespace'] .
      "
{

  /**
   * List of tests
   * ================
   * [ ] test description 1
   * [ ] test description 2
   *
   */

  function before()
  {
    //runs before running ANY test method
  }

  function after()
  {
    //runs after running ALL test methods
  }

  function beforeEach()
  {
    //runs before EACH test method
  }

  function afterEach()
  {
    //runs after EACH test method
  }
}
")) {
      $this->output("Generated " . $className, false);
    } else {
      $this->output('Failed to generate ' . $className, true);
    }

    fclose($h);
  }

  function start($groupName = null)
  {

    $folder = $this->fixPath($this->config['folder']);

    if (is_array($groupName)) {
      $groupName = array_shift($groupName);
    }

    require_once($this->fixPath($this->config['bootstrap_file']));

    $passed = 0;
    $failed = 0;
    $exceptions = 0;
    $failures = [];
    $startTime = microtime(true);
    $fileName = null;

    if ($this->contains($groupName, ':')) {
      $_g = explode(":", $groupName);
      $groupName = $_g[0];
      if (isset($_g[1])) {
        $fileName = $_g[1];
      }
    }

    $baseGroupTests = glob($this->fixPath($folder . '/*Test.php'));
    $testGroupFolders = glob($this->fixPath($folder . '/*'), GLOB_ONLYDIR);

    $toRun = [
      'BASE' => ['files' => $baseGroupTests, 'namespace' => null, 'name' => 'BASE']
    ];

    foreach ($testGroupFolders as $dir) {
      $ex = explode(DIRECTORY_SEPARATOR, $dir);
      $namespace = end($ex);
      $toRun[$namespace] = [
        'files' => glob($this->fixPath($folder . '/' . $namespace . '/*Test.php')),
        'namespace' => $namespace,
        'name' => $namespace
      ];
    }

    if ($groupName) {
      if (isset($toRun[$groupName])) {
        $ret = $this->runFiles($toRun[$groupName], $fileName);
        $passed += $ret[0];
        $failed += $ret[1];
        $exceptions += $ret[2];
        $failures = $ret[3];
      } else {
        $this->output("ERROR: Test group " . $groupName . " does not exist", true);
        return;
      }
    } else {
      foreach ($toRun as $run) {
        $ret = $this->runFiles($run);
        $passed += $ret[0];
        $failed += $ret[1];
        $exceptions += $ret[2];
        $failures = array_merge($failures, $ret[3]);
      }
    }

    $endTime = microtime(true);

    $this->renderFailures($failures);

    $this->output("\n\n");
    $this->output("=====================================================");
    $this->output(
      ($passed + $failed) . ' Assertions, ' .
        ConsoleColor::success(" " . $passed . ' Passed ') . ", " .
        ($failed ? ConsoleColor::error(" " . $failed . ' Failed ') . ", " : $failed . ' Failed, ') .
        ($exceptions ? ConsoleColor::warning(" " . $exceptions . ' Exceptions ') . ", " : $exceptions . ' Exceptions, ')
    );

    $_diff = \DateTime::createFromFormat('U.u', number_format($endTime - $startTime, 6, '.', ''));

    $this->output("Total time taken: " . $_diff->format("H:i:s.u"));
    $this->output("=====================================================");
  }

  protected function renderFailures($failures)
  {
    if (is_array($failures) && count($failures) > 0) {
      $this->output("\n\n");
      $this->output(ConsoleColor::error("====================================================="));
      $this->output(ConsoleColor::error("                     FAILURES                        "));
      $this->output(ConsoleColor::error("====================================================="));
      foreach ($failures as $failure) {
        $this->output(" - " . $failure['method']);
        $this->output("  -> " . ConsoleColor::error(" Fail ") . "- " . $failure['text']);
        list($file, $line) = $this->processSourceFile($failure['source']);
        $this->output("  " . $file . ' ' . ConsoleColor::info("[Line " . $line . "]"));
        $this->output("");
      }
      $this->output(ConsoleColor::error("================== END FAILURES ====================="));
    }
  }

  protected function runFiles($obj, $fileName = null)
  {

    $passed = 0;
    $failed = 0;
    $exceptions = 0;
    $failures = [];

    $this->output("");
    foreach ($obj['files'] as $tf) {
      $f = explode(DIRECTORY_SEPARATOR, $tf);
      $f = array_pop($f);
      $f = str_replace('.php', '', $f);
      if (($fileName && $f === $fileName) || !$fileName) {
        $this->output("..................... " . $obj['namespace'] . '\\' . $f . " ...............");
        $className = $this->config['namespace'] . "\\" . ($obj['namespace'] ? $obj['namespace'] . "\\" : "") . $f;

        $class = new $className;
        $class->run();
        $passed += $class->passed;
        $failed += $class->failed;
        $exceptions += $class->exceptions;
        $failures = array_merge($failures, $class->failures());
      }
    }

    return [$passed, $failed, $exceptions, $failures];
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
