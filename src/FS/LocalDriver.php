<?php

namespace Kws3\ApiCore\FS;

use \Kws3\ApiCore\Loader;
use \Kws3\ApiCore\Utils\Tools;

class LocalDriver extends Driver
{
  protected $app;
  protected $opts;

  public function __construct($opts = [])
  {
    $_web_url = '/files/';
    $this->opts = [
      'folder'      => $_SERVER['DOCUMENT_ROOT'] . Loader::get('BASE') . $_web_url,
      'url'         => $_web_url,
      'domain_url'  => null
    ] + $opts;
  }

  public function getUrl($fileObject)
  {
    return implode('/', array_filter([$this->getDomainUrl(), Tools::trimSlash($this->opts['url'], true), $fileObject->folder, $fileObject->name]));
  }

  public function getFriendlyUrl($fileObject)
  {
    return $this->getUrl($fileObject);
  }

  public function create($filePath, $destinationFolder = '/')
  {
    $uploadsBase = Tools::trimSlash($this->opts['folder']);
    $folder = Tools::trimSlash($destinationFolder, true);
    $originalName = basename($filePath);
    $newFilename = Tools::generateRandomFilename($filePath);

    $finalFolder = implode('/', [$uploadsBase, $folder]);
    if (!is_dir($finalFolder)) {
      mkdir($finalFolder, 0777, true);
    }

    $newFilePath = $finalFolder . '/' . $newFilename;

    $success = rename($filePath, $newFilePath);

    if ($success !== false) {
      return [
        'folder' => $folder,
        'name' => $newFilename,
        'original_name' => $originalName,
        'driver' => $this->getClassName(),
        'url' => implode('/', array_filter([$this->getDomainUrl(), Tools::trimSlash($this->opts['url'], true), $folder, $newFilename]))
      ];
    } else {
      @unlink($filePath);
    }

    return $success;
  }

  protected function getDomainUrl()
  {
    if (!empty($this->opts['domain_url'])) {
      return Tools::trimSlash($this->opts['domain_url']);
    }
    //infer url if domain_url is not supplied
    $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    $url =  $scheme . "://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}";
    return Tools::trimSlash(str_replace('index.php', '', $url));
  }
}
