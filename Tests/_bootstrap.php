<?php
define('AUTOMATED_TESTING', true);

require_once(__DIR__ . '/../vendor/autoload.php');

spl_autoload_register(function ($class) {

  $file = preg_replace('#\\\|_(?!.+\\\)#', '/', $class) . '.php';
  $file = str_replace('Kws3/ApiCore/Tests', 'Tests', $file);
  if (stream_resolve_include_path($file)) {
    require $file;
  }
});
//phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
$app = \Base::instance();
