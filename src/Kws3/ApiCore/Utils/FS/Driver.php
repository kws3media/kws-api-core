<?php
namespace Kws3\ApiCore\Utils\FS;

use \Kws3\ApiCore\Exceptions\HTTPException;

class Driver extends \Prefab
{
    protected $app;

    public function __construct()
    {
        $this->app = \Base::instance();
    }

    public function getUrl($fileObject)
    {
        throw new HTTPException('Not Implemented', 500);
    }

    public function getFriendlyUrl($fileObject)
    {
        throw new HTTPException('Not Implemented', 500);
    }

    public function create($filePath, $destinationFolder)
    {
        //to be implemneted by child classes
    }

    public function getClassName()
    {
        $curClass = explode("\\", get_class($this));
        return array_pop($curClass);
        return substr(strrchr(__CLASS__, "\\"), 1);
    }

    public function generateFilename($originalFilename)
    {
        $name = basename($originalFilename);
        $parts = explode(".", $name);
        $ext = array_pop($parts);
        $fileNoExtension = implode(".", $parts);
        $name = md5(uniqid(mt_rand(), true) . $fileNoExtension) . '.' . strtolower($ext);
        return $name;
    }

    public function trimSlash($str, $both = false)
    {
        $str = rtrim($str, '/\\');
        if($both){
            $str = ltrim($str, '/\\');
        }

        return $str;
    }
}