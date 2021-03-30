<?php

namespace Kws3\ApiCore\Log;

use \Kws3\ApiCore\Log\LogWriter;

class Logger extends \Prefab
{
  protected $app;
  private $defaultCategory = 'application';

  private $loggers = [];

  // whether to rotate or not
  private $rotate = true;
  // maximum file size before rotating
  private $maxFileSize = 1024; //in KB
  // number of old log files to keep during rotation
  private $maxLogFiles = 5;
  // format of date in every log line
  private $dateFormat = 'r';

  public function __construct($options = [])
  {
    $this->app = \Base::instance();

    //additional setters
    if (isset($options["defaultCategory"])) {
      $this->setdefaultCategory($options["defaultCategory"]);
    }
    if (isset($options["maxFileSize"])) {
      $this->setMaxFileSize($options["maxFileSize"]);
    }
    if (isset($options["maxLogFiles"])) {
      $this->setMaxLogFiles($options["maxLogFiles"]);
    }
    if (isset($options["rotate"])) {
      $this->setRotate($options["rotate"]);
    }
    if (isset($options["dateFormat"])) {
      $this->setDateFormat($options["dateFormat"]);
    }
  }

  /**
   * Alias for log()
   */
  public function write($text, $category = null)
  {
    $this->log($text, $category);
  }

  /**
   * Writes to log file
   */
  public function log($text, $category = null)
  {
    $category = $this->normaliseCategory($category ?: $this->defaultCategory);
    if (!isset($this->loggers[$category])) {
      $this->createLogger($category);
    }
    $this->loggers[$category]->write($text);
  }

  public function createLogger($category, $options = [])
  {
    $category = $this->normaliseCategory($category);
    if (!isset($this->loggers[$category])) {
      $options = [
        'category' => $category,
        'maxFileSize' => $this->maxFileSize,
        'maxLogFiles' => $this->maxLogFiles,
        'dateFormat' => $this->dateFormat,
        'rotate' => $this->rotate
      ] + $options;
      $this->loggers[$category] = new LogWriter($options);
    }
  }

  private function normaliseCategory($category)
  {
    return strtolower($category);
  }

  public function getdefaultCategory()
  {
    return $this->defaultCategory;
  }
  public function setdefaultCategory($defaultCategory)
  {
    $this->defaultCategory = $defaultCategory;
  }
  public function getRotate()
  {
    return $this->rotate;
  }
  public function setRotate($flag)
  {
    $this->rotate = (bool) $flag;
  }

  public function getMaxFileSize()
  {
    return $this->maxFileSize;
  }
  public function setMaxFileSize($size)
  {
    $this->maxFileSize = $size;
  }

  public function getMaxLogFiles()
  {
    return $this->maxLogFiles;
  }
  public function setMaxLogFiles($num)
  {
    $this->maxLogFiles = $num;
  }
  public function getDateFormat()
  {
    return $this->dateFormat;
  }
  public function setDateFormat($dateFormat)
  {
    $this->dateFormat = $dateFormat;
  }
}
