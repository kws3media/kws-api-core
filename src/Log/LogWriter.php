<?php

namespace Kws3\ApiCore\Log;

use \Kws3\ApiCore\Loader;

class LogWriter
{
  protected $app;
  protected $category = 'application';

  private $file;

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

    if (!is_dir($dir = Loader::get('LOGS'))) {
      mkdir($dir, \Base::MODE, TRUE);
    }

    //additional setters
    if (isset($options["category"])) {
      $this->setCategory($options["category"]);
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

    $this->file = $dir . $this->category . '.log';

    if ($this->rotate) {
      $this->rotateLogs();
    }
  }

  private function rotateLogs()
  {

    $logFile = $this->file;

    if ($this->getRotate() === true && @filesize($logFile) > $this->getMaxFileSize() * 1024) {

      $max = $this->getMaxLogFiles();

      //rotate already rotated files
      for ($i = $max; $i > 0; --$i) {
        $rotateFile = $logFile . '.' . $i;
        if (is_file($rotateFile)) {
          // suppress errors because it's possible multiple processes enter into this section
          if ($i === $max) {
            @unlink($rotateFile);
          } else {
            @rename($rotateFile, $logFile . '.' . ($i + 1));
          }
        }
      }

      //rotate current file
      if (is_file($logFile)) {
        // suppress errors because it's possible multiple processes enter into this section
        @rename($logFile, $logFile . '.1');
      }
    }
  }

  public function write($text)
  {
    $this->app->write(
      $this->file,
      date($this->dateFormat) .
        (isset($_SERVER['REMOTE_ADDR']) ?
          (' [' . $_SERVER['REMOTE_ADDR'] . ']') : '') . ' ' .
        trim($text) . PHP_EOL,
      TRUE
    );
  }

  public function getCategory()
  {
    return $this->category;
  }
  public function setCategory($category)
  {
    $this->category = $category;
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
