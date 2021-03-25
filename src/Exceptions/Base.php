<?php

namespace Kws3\ApiCore\Exceptions;

abstract class Base extends \Exception
{

  public $devMessage;
  public $errorCode;
  public $response;
  public $additionalInfo;

  protected $responder;

  public function __construct(
    $message,
    $code = 500,
    $errorArray = array(
      'errorCode' => 500,
      'userMessage' => '',
      'dev' => '',
      'more' => '',
      'internalCode' => '',
    )
  ) {

    $this->message = $message;
    $this->devMessage = @$errorArray['dev'];
    $this->errorCode = @$errorArray['internalCode'];
    $this->code = $code;
    $this->additionalInfo = @$errorArray['more'];

    $this->setResponder();
  }

  abstract public function setResponder();

  public function send()
  {

    $code = $this->getCode();

    $reason = @constant('Base::HTTP_' . $code);
    if (empty($reason)) {
      $reason = $this->getMessage();
    }
    if (PHP_SAPI != 'cli' && !headers_sent()) {
      header($_SERVER['SERVER_PROTOCOL'] . ' ' . $code . ' ' . $reason);
    }

    $error = array(
      'errorCode' => $this->getCode(),
      'userMessage' => $this->getMessage(),
      'devMessage' => $this->devMessage,
      'more' => $this->additionalInfo,
      'applicationCode' => $this->errorCode,
    );

    $responder = $this->responder;
    $responder->send($error, true);

    return;
  }
}
