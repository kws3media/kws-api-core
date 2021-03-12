<?php
namespace ApiCore\Utils;

class Identity extends \Prefab
{
  protected $keyLifetime = 7200; //seconds

  protected $app;

  public $user = null;
  public $context = null;
  public $inactiveKey = false;
  public $expiredKey = false;

  public function getKeyLifeTime()
  {
    return $this->keyLifetime;
  }

  public function forget()
  {
    $this->user = null;
    $this->context = null;
  }

  public function reIdentify($token)
  {
    $this->identify($token);
  }

  private function identify($token)
  {
    //check config flag
    $rotateKeys = $this->app->get('CONFIG')['ROTATE_KEYS'];

    $created_on = strtotime($token->created);
    if($rotateKeys && (time() - $created_on) > $this->keyLifetime){
      $this->expiredKey = true;
      return;
    }

  }

}
