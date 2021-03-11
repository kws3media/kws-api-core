<?php

namespace ApiCore\Controllers\CLI;

class CLIBase
{
    protected $app;

    //Route params
    protected $params = [];

    public function __construct(\Base $app)
    {
        if (php_sapi_name() !== 'cli'){
            echo 'Na\'ah no go here!!!';
            exit;
        }

        $this->app = \Base::instance();

        $this->params = $_SERVER['argv'];
        array_shift($this->params);
        array_shift($this->params);
    }

    protected function log($msg, $err = null)
    {
        if ($err == true) {
        echo "\033[1;97;41m " . $msg . " \e[0m" . "\n";
        dbg()->error($msg);
        } elseif ($err === false) {
        echo "\033[1;97;42m " . $msg . " \e[0m" . "\n";
        dbg()->notice($msg);
        } else {
        echo $msg . "\n";
        dbg()->info($msg);
        }
        ob_flush();
        flush();
    }

    public function __destruct()
    {
        dbg()->commandExecuted($_SERVER['argv'][1], 0, $this->params);
    }
}