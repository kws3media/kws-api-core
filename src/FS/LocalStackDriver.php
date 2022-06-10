<?php

namespace Kws3\ApiCore\FS;

class LocalStackDriver extends CloudDriver
{


  public function __construct($opts = [])
  {
    if (empty($this->opts['region'])) {
      $this->opts['region'] = 'eu-west-2';
    }
    if (empty($this->opts['version'])) {
      $this->opts['version'] = '2006-03-01';
    }
    if (empty($this->opts['endpoint'])) {
      $this->opts['endpoint'] = 'http://localstack:4566';
    }
    if (empty($this->opts['local_endpoint'])) {
      $this->opts['local_endpoint'] = 'http://localhost:4566';
    }
    if (empty($this->opts['use_path_style_endpoint'])) {
      $this->opts['use_path_style_endpoint'] = true;
    }
    parent::__construct($opts);
  }

  public function getUrl($fileObject)
  {
    return $this->opts['local_endpoint'] . '/' . implode('/', array_filter([$fileObject->bucket, $fileObject->folder, $fileObject->name]));
  }

  public function getFriendlyUrl($fileObject)
  {
    return $this->getUrl($fileObject);
  }

  public function getUploadPresignedUrl($folder, $originalName, $expires = 3600, $acl = self::ACL_PUBLIC)
  {
    $urlObj = $this->createPresignedUrl($folder, $originalName, $expires, $acl);
    $urlObj['url'] =  str_replace($this->opts['local_endpoint'], $this->opts['endpoint'], $urlObj['url']);
    return $urlObj;
  }
}
