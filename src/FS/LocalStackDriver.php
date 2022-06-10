<?php

namespace Kws3\ApiCore\FS;

class LocalStackDriver extends CloudDriver
{


  public function __construct($opts = [])
  {
    if (empty($opts['region'])) {
      $opts['region'] = 'eu-west-2';
    }
    if (empty($opts['version'])) {
      $opts['version'] = '2006-03-01';
    }
    if (empty($opts['endpoint'])) {
      $opts['endpoint'] = 'http://localstack:4566';
    }
    if (empty($opts['local_endpoint'])) {
      $opts['local_endpoint'] = 'http://localhost:4566';
    }
    if (empty($opts['use_path_style_endpoint'])) {
      $opts['use_path_style_endpoint'] = true;
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
    $urlObj['url'] =  str_replace($this->opts['endpoint'], $this->opts['local_endpoint'], $urlObj['url']);
    return $urlObj;
  }
}
