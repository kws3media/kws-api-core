<?php
namespace Kws3\ApiCore\Utils;

class Log extends \Log
{
    protected $filename = 'application.log';

    // whether to rotate or not
    private $rotate = true;
    // maximum file size before rotating
    private $maxFileSize = 1024; //in KB
    // number of old log files to keep during rotation
    private $maxLogFiles = 5;

    public function __construct($options = array())
    {
        //additional setters
        if (isset($options["filename"])) {
            $this->setFilename($options["filename"]);
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
        if ($this->rotate) {
            $this->rotateLogs();
        }

        parent::__construct($this->filename);
    }

    private function rotateLogs()
    {
        $app = \Base::instance();
        $dir = realpath($app->get('LOGS'));
        $logFile = $dir . DIRECTORY_SEPARATOR . $this->filename;

        if ($this->getRotate()===true && @filesize($logFile) > $this->getMaxFileSize()*1024) {

            $max=$this->getMaxLogFiles();

            //rotate already rotated files
            for ($i=$max;$i>0;--$i) {
                $rotateFile=$logFile.'.'.$i;
                if (is_file($rotateFile)) {
                    // suppress errors because it's possible multiple processes enter into this section
                    if ($i===$max) {
                        @unlink($rotateFile);
                    } else {
                        @rename($rotateFile,$logFile.'.'.($i+1));
                    }
                }
            }

            //rotate current file
            if (is_file($logFile)) {
                // suppress errors because it's possible multiple processes enter into this section
                @rename($logFile,$logFile.'.1');
            }
        }
    }

    public function getFilename()
    {
        return $this->filename;
    }
    public function setFilename($filename)
    {
        $this->filename = $filename;
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

}
