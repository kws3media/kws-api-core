<?php

namespace Kws3\ApiCore\Runners;

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
    if (!$this->endsWith($className, 'test')) {
      $className = $className . 'Test';
    } else {
      $className = substr($className, 0, -4) . 'Test';
    }
    $f .= $this->fixPath('/' . $className . '.php');

    if (file_exists($f)) {
      $this->output('Failed to generate ' . ($dir ? $dir . "/" : '') . $className . ', file already exists', true);
      return;
    }

    $h = fopen($f, 'w');
    if (fwrite($h, "<?php
namespace " . $this->config['namespace'] . ($dir ? "\\" . $dir : '') . ";

class $className extends " . $this->config['base_namespace'] . "{

    /**
     * List of tests
     * ================
     * [ ] test description 1
     * [ ] test description 2
     *
     */

    function before(){
        //runs before running ANY test method
    }

    function after(){
        //runs after running ALL test methods
    }

    function beforeEach(){
        //runs before EACH test method
    }

    function afterEach(){
        //runs after EACH test method
    }

}")) {
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
      }
    }

    $endTime = microtime(true);


    $this->output("\n\n");
    $this->output("=====================================================");
    $this->output(
      ($passed + $failed) . ' Assertions, ' .
        "\033[1;97;42m " . $passed . ' Passed' . " \e[0m, " .
        ($failed ? "\033[1;97;41m " . $failed . ' Failed' . " \e[0m, " : $failed . ' Failed, ') .
        ($exceptions ? "\033[1;97;41m " . $exceptions . ' Exceptions' . " \e[0m" : $exceptions . ' Exceptions')
    );

    $_diff = \DateTime::createFromFormat('U.u', number_format(($endTime - $startTime), 6, '.', ''));

    $this->output("Total time taken: " . $_diff->format("H:i:s.u"));
    $this->output("=====================================================");
  }

  protected function runFiles($obj, $fileName = null)
  {

    $passed = 0;
    $failed = 0;
    $exceptions = 0;

    $this->output("");
    foreach ($obj['files'] as $tf) {
      $f = explode(DIRECTORY_SEPARATOR, $tf);
      $f = array_pop($f);
      $f = str_replace('.php', '', $f);
      if (($fileName && $f == $fileName) || !$fileName) {
        $this->output("..................... " . $obj['namespace'] . '\\' . $f . " ...............");
        $class = $this->config['namespace'] . "\\" . ($obj['namespace'] ? $obj['namespace'] . "\\" : "") . $f;
        $class = new $class;
        $class->run();

        $passed += $class->passed;
        $failed += $class->failed;
        $exceptions += $class->exceptions;
      }
    }

    return [$passed, $failed, $exceptions];
  }
}
