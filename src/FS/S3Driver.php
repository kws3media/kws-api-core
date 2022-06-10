<?php

namespace Kws3\ApiCore\FS;

class S3Driver extends CloudDriver
{


  public function __construct($opts = [])
  {
    if (empty($this->opts['region'])) {
      $this->opts['region'] = 'eu-west-2';
    }
    if (empty($this->opts['version'])) {
      $this->opts['version'] = '2006-03-01';
    }
    parent::__construct($opts);
  }

  public function getUrl($fileObject)
  {
    return 'https://s3-' . $this->opts['region'] . '.amazonaws.com/' . implode('/', array_filter([$fileObject->bucket, $fileObject->folder, $fileObject->name]));
  }

  public function getFriendlyUrl($fileObject)
  {
    return 'http://' . implode('/', array_filter([$fileObject->bucket, $fileObject->folder, $fileObject->name]));
  }
}
