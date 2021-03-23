<?php
namespace Kws3\ApiCore\Utils\FS;

use \Kws3\ApiCore\Loader;

class LocalDriver extends Driver
{
    protected $app;
    protected $opts;

    public function __construct()
    {
        $this->opts = Loader::get('CONFIG')['LOCALFS'];
    }

    public function getUrl($fileObject)
    {
        return implode('/', array_filter([$this->getDomainUrl(), $this->trimSlash($this->opts['url'], true), $fileObject->folder, $fileObject->name]));
    }

    public function getFriendlyUrl($fileObject)
    {
        return $this->getUrl($fileObject);
    }

    public function create($filePath, $destinationFolder = '/')
    {
        $uploadsBase = $this->trimSlash($this->opts['folder']);
        $folder = $this->trimSlash($destinationFolder, true);
        $originalName = basename($filePath);
        $newFilename = $this->generateFilename($filePath);

        $finalFolder = implode('/', [$uploadsBase, $folder]);
        if(!is_dir($finalFolder)){
            mkdir($finalFolder, 0777, true);
        }

        $newFilePath = $finalFolder . '/' . $newFilename;

        $success = rename($filePath, $newFilePath);

        if($success !== false){
            return [
                'folder' => $folder,
                'name' => $newFilename,
                'original_name' => $originalName,
                'driver' => $this->getClassName(),
                'url' => implode('/', array_filter([$this->getDomainUrl(), $this->trimSlash($this->opts['url'], true), $folder, $newFilename]))
            ];
        } else {
            @unlink($filePath);
        }

        return $success;
    }

    protected function getDomainUrl()
    {
        if (!empty($this->opts['domain_url'])) {
            return $this->trimSlash($this->opts['domain_url']);
        }
        //infer url if domain_url is not supplied
        $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
        $url =  $scheme . "://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}";
        return $this->trimSlash(str_replace('index.php', '', $url));
    }
}