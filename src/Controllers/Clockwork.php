<?php

namespace Kws3\ApiCore\Controllers;

use \Kws3\ApiCore\Exceptions\HTTPException;
use \Kws3\ApiCore\Loader;

final class Clockwork
{

  public function getClockwork()
  {
    if (K_ENV != K_ENV_LOCAL) {
      throw new HTTPException('Not Found.', 404);
    }
    $request = str_replace('/__clockwork', '', Loader::get('PATH')) . '?' . Loader::get('QUERY');
    dbg()->returnMetadata($request);
  }
}
