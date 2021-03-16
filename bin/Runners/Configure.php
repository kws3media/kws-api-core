<?php

namespace Runners;

class Configure extends Base
{
  public function help()
  {
    $this->output("\n");
    $this->output("Configure Package Info:", false);
    $this->output("> php bin/cli Configure package package_name package_version");
    $this->output("\n");
    $this->output("Configure DB Info:", false);
    $this->output("> php bin/cli Configure db host=hostname username=name password=pass dbname=dev");
  }

  public function run($arg = [])
  {
    if (!isset($arg[0])) {
      echo 'option argument missing';
      exit();
    }


    if ($arg[0] == 'package') {

        if (!isset($arg[1])) {
          echo 'package name argument missing';
          exit();
        }
        $pkg_path = __DIR__ . '/../../../kws-api-starter/composer.json';
        $_arg = array_slice($arg, 1);
        $ConfigurePackageInfo = new \Generators\ConfigurePackageInfo($_arg, $pkg_path);
        $ConfigurePackageInfo->fillPackageInfo();
    }

    if ($arg[0] == 'db') {

        $template_path = __DIR__ . '/../../../kws-api-starter/stubs/config_template.stub';
        $config_path = __DIR__ . '/../../../kws-api-starter/src/app/config/config.local.php';
        $_arg = array_slice($arg, 1);
        $ConfigureDBInfo = new \Generators\ConfigureDBInfo($_arg, $config_path, $template_path);
        $ConfigureDBInfo->fillDBInfo();
    }
  }
}
