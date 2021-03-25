<?php

namespace Kws3\ApiCore\Responses;

use Kws3\ApiCore\Framework;
use Kws3\ApiCore\Loader;

class JSONResponse extends Base
{

  public function send($records, $error = false)
  {

    $message = $this->getOutput($records, $error);

    if (!$this->head) {
      $response = json_encode($message);
      if (!defined('AUTOMATED_TESTING')) {

        echo $response;

        if (Framework::isClockworkEnabled()) {
          if ($error) {
            dbg()->error($message['records']);
          } else {
            $c = dbg()->userData("Response")->title("Response");
            $c->table("Response Object", [
              ['Key' => '_meta', 'Data' => $message['_meta']],
              ['Key' => 'records', 'Data' => $message['records']],
            ]);
          }
        }
      } else {
        Loader::set('APP_RESPONSE', $response);
      }
    }

    dbg()->requestProcessed();
    return true;
  }
}
