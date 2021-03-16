<?php

namespace Generators;

class Base{

  protected function output($msg, $err = null){
    if($err == true){
        echo "\033[1;97;41m " .$msg." \e[0m" . "\n";
    }elseif($err === false){
        echo "\033[1;97;42m " .$msg." \e[0m" . "\n";
    }else{
        echo $msg . "\n";
    }
    ob_flush();
    ob_end_flush();
  }

  protected function flagify($args){
    $_args = [];
    if(!empty($args)){
      foreach ($args as $arg) {
        list($k, $v) = explode("=", $arg);
        $_args[$k] = $v;
      }
    }
    return $_args;
  }

}