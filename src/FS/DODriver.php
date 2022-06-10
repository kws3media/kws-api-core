<?php

namespace Kws3\ApiCore\FS;

class DODriver extends CloudDriver
{

  public function __construct($opts = [])
  {
    if (empty($this->opts['region'])) {
      $this->opts['region'] = 'fra1';
    }
    if (empty($this->opts['version'])) {
      $this->opts['version'] = 'latest';
    }
    if (empty($this->opts['endpoint'])) {
      $this->opts['endpoint'] = 'https://' . $this->opts['region'] . '.digitaloceanspaces.com';
    }
    parent::__construct($opts);
  }

  public function getUrl($fileObject)
  {
    return 'https://' . $fileObject->bucket . '.' . $this->opts['region'] . '.digitaloceanspaces.com/' . implode('/', array_filter([$fileObject->folder, $fileObject->name]));
  }

  public function getFriendlyUrl($fileObject)
  {
    return 'https://' . $fileObject->bucket . '.' . $this->opts['region'] . '.digitaloceanspaces.com/' . implode('/', array_filter([$fileObject->folder, $fileObject->name]));
  }
}
