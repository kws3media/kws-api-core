<?php

namespace Kws3\ApiCore\Controllers;

use \Clockwork\Web\Web;
use \Kws3\ApiCore\Exceptions\HTTPException;
use \Kws3\ApiCore\Loader;

final class Clockwork
{

  public function getClockwork()
  {
    if (K_ENV != K_ENV_LOCAL) {
      throw new HTTPException('Not Found.', 404);
    }

    $path = Loader::get('PATH');
    $clockworkDataUri = '#/__clockwork(?:/(?<id>[0-9-]+|latest))?(?:/(?<direction>(?:previous|next)))?(?:/(?<count>\d+))?#';

    preg_match($clockworkDataUri, $path, $matches);

    if (isset($matches) && isset($matches['id'])) {
      $request = str_replace('/__clockwork', '', Loader::get('PATH')) . '?' . Loader::get('QUERY');
      dbg()->returnMetadata($request);
    } else {
      $this->getApp();
    }
  }

  public function getApp()
  {
    $path = Loader::get('PATH');
    $path = str_replace('/__clockwork', '', $path);
    if ($path == '/app') {
      $path = '/index.html';
    }

    $web = new Web;
    if ($asset = $web->asset($path)) {
      header("Content-Type: " . $asset['mime']);
      echo file_get_contents($asset['path']);
    }
  }
}
