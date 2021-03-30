<?php

namespace Kws3\ApiCore\Utils;

use \Exception;
use Kws3\ApiCore\Exceptions\HTTPException;
use \Kws3\ApiCore\Loader;

class Identity extends \Prefab
{

  const CONTEXT_ADMIN           = 'A';
  const CONTEXT_USER            = 'U';

  public static $identityDescriptions = [
    self::CONTEXT_ADMIN         => 'Admin',
    self::CONTEXT_USER          => 'User'
  ];

  protected $rotateKeys = true; //set true to activate rotating keys
  protected $keyLifetime = 7200; //seconds

  protected $requestHeaderKey = 'Api-Key';
  protected $tokensModel = null;
  protected $accessTokenField = 'access_token';


  public $user = null;
  public $context = null;
  public $inactiveKey = false;
  public $expiredKey = false;

  public function __construct($config = [])
  {

    foreach($config as $k => $v){
      if(property_exists($this, $k)){
        $this->{$k} = $v;
      }
    }

    $this->identify();
  }

  public function getKeyLifeTime()
  {
    return $this->keyLifetime;
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

  protected function identify()
  {

    if(empty($this->tokensModel)){
      throw new HTTPException('tokensModel not defined', 500);
    }

    $api_key = Loader::get('HEADERS.' . $this->requestHeaderKey);

    $token = new $this->tokensModel;
    $token->load(['`' . $this->accessTokenField . '` = ?', $api_key]);
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

    $created_on = strtotime($token->created);
    if($this->rotateKeys && (time() - $created_on) > $this->keyLifetime){
      $this->expiredKey = true;
      return;
    }

    if (!empty($token->user)) {
      $this->user = $token->user;
      $this->context = $token->user->role;
      $this->postProcess();
    }
  }

  protected function postProcess(){
    //to be implemented by subclass
  }

}