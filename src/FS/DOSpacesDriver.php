<?php

namespace Kws3\ApiCore\FS;

use \Kws3\ApiCore\Utils\Tools;

class DOSpacesDriver extends CloudDriver
{

  public function __construct($opts = [])
  {
    if (empty($opts['region'])) {
      $opts['region'] = 'fra1';
    }
    if (empty($opts['version'])) {
      $opts['version'] = 'latest';
    }
    if (empty($opts['endpoint'])) {
      $opts['endpoint'] = 'https://' . $opts['region'] . '.digitaloceanspaces.com';
    }
    parent::__construct($opts);
  }

  public function getUrl($fileObject)
  {
    return 'https://' . $fileObject->bucket . '.' . $this->opts['region'] . '.digitaloceanspaces.com/' . implode('/', array_filter([$fileObject->folder, $fileObject->name]));
  }

  public function getFriendlyUrl($fileObject)
  {
    if ($this->opts['has_cdn']) {
      $cdn_url = 'https://' . $fileObject->bucket . '.' . $this->opts['region'] . '.cdn.digitaloceanspaces.com/';
      if (!empty($this->opts['cdn_url'])) {
        $cdn_url = $this->opts['cdn_url'];
      }
      return Tools::trimSlash($cdn_url) . "/" . implode('/', array_filter([$fileObject->folder, $fileObject->name]));
    }
    return $this->getUrl($fileObject);
  }
}
