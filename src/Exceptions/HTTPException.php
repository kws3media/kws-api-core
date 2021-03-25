<?php

namespace Kws3\ApiCore\Exceptions;

use Kws3\ApiCore\JSONResponse;
use Kws3\ApiCore\Loader;

class HTTPException extends Base
{

  public function setResponder()
  {
    $responder = Loader::getResponder();
    if (empty($reponder)) {
      //fallback to JSONResponder
      $responder = JSONResponse::instance();
    }
    $this->responder = $responder;
  }
}
