<?php

namespace Kws3\ApiCore\FS;

use \Aws\S3\S3Client;

class DOSpacesDriver extends S3Driver
{

  public function __construct($opts = [])
  {
    $this->opts = $opts;
    $this->bucket = $this->opts['bucket'];
    if (empty($this->opts['region'])) {
      $this->opts['region'] = 'fra1';
    }
  }

  public function getUrl($fileObject)
  {
    return 'https://' . $fileObject->bucket . '.' . $this->opts['region'] . '.digitaloceanspaces.com/' . implode('/', array_filter([$fileObject->folder, $fileObject->name]));
  }

  public function getFriendlyUrl($fileObject)
  {
    return 'https://' . $fileObject->bucket . '.' . $this->opts['region'] . '.digitaloceanspaces.com/' . implode('/', array_filter([$fileObject->folder, $fileObject->name]));
  }

  public function getS3()
  {
    if (!$this->s3) {

      $this->_checkOpts();

      $this->s3 = new S3Client([
        'version' => 'latest',
        'region' => $this->opts['region'],
        'endpoint' => 'https://' . $this->opts['region'] . '.digitaloceanspaces.com',
        'credentials' => [
          'key'    => $this->opts['access_key'],
          'secret' => $this->opts['secret'],
        ]
      ]);
    }
    return $this->s3;
  }
}
