<?php
namespace Kws3\ApiCore\Utils;

class Identity extends \Prefab
{
  public static $keyLifetime = 7200; //seconds

  protected $app;

  public $user = null;
  public $context = null;
  public $inactiveKey = false;
  public $expiredKey = false;

  public function __construct()
  {
    $this->app = \Base::instance();
    $this->identify();
  }

  public function forget()
  {
    $this->user = null;
    $this->context = null;
  }

  public function reIdentify()
  {
    $this->identify();
  }

  private function identify()
  {
    $api_key = $this->app->get('HEADERS.Api-Key');

    $token = new \Models\Tokens;
    $token->load(['`access_token` = ?', $api_key]);
    if ($token->dry()) {
      return;
    }

    if ($token->user->disabled == 1 || $token->user->deleted == 1) {
      return;
    }

    if ($token->active != 1) {
      $this->inactiveKey = true;
      return;
    }
    //check config flag
    $rotateKeys = $this->app->get('CONFIG')['ROTATE_KEYS'];

    $created_on = strtotime($token->created);
    if($rotateKeys && (time() - $created_on) > self::$keyLifetime){
      $this->expiredKey = true;
      return;
    }

    if (!empty($token->user)) {
      $this->user = $token->user;
      $this->context = $token->user->role;
    }
  }

}
