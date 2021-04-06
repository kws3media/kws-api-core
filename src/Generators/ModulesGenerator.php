<?php

namespace Kws3\ApiCore\Generators;


class ModulesGenerator extends Base
{

  private $config = array();

  public function __construct($config = array())
  {
    $this->config = $config;
  }

  public function generate()
  {

    $config = $this->config;
    $className = $this->className($config['class_name']);

    if ($config['type'] == 'modules') {
      $files = array_map(
        function ($p) use ($className) {
          return $p . $className . '.php';
        },
        array_column($config['templates'], 'output')
      );
      $this->checkFileExists($files);
    }

    foreach ($config['templates'] as $temp) {

      $contents = file_get_contents($temp['template']);
      $contents = str_replace('%s', $className, $contents);
      $filename = $temp['output'] . $className . '.php';
      $this->checkFileExists($filename);

      if (file_put_contents($filename, $contents)) {
        $this->output("Generated " . $className . " " . $temp['name']);
      } else {
        $this->output('Failed to generate ' . $className . ' ' . $temp['output'], true);
      }
      usleep(250000);
    }
  }

  protected function className($t, $ns = '')
  {
    return $ns . ucfirst(strtolower($t));
  }

  protected function checkFileExists($filename)
  {
    $_filename = (array) $filename;

    foreach ($_filename as $fn) {
      if (file_exists($fn)) {
        $this->output("The file  $fn already exists, please try different name", true);
        exit();
      }
    }
  }
}
