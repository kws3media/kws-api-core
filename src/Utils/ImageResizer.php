<?php

namespace Kws3\ApiCore\Utils;

class ImageResizer extends \Prefab
{
    public static function resize($filePath, $width = 128, $height = 128)
    {
        $img = \Intervention\Image\ImageManagerStatic::make($filePath)->orientate();
        $h = $img->height();
        $w = $img->width();
        if($h == $height && $w == $width){
            return;
        }
        $background = \Intervention\Image\ImageManagerStatic::canvas($width, $height);
        $img->resize($width, $height, function($c){
            $c->aspectRatio();
            $c->upsize();
        });
        $background->insert($img, 'center');
        $background->save($filePath, 100);
    }

    public static function resizeNoFill($filePath, $width = 128, $height = 128)
    {
        $img = \Intervention\Image\ImageManagerStatic::make($filePath);
        $h = $img->height();
        $w = $img->width();
        if ($h == $height && $w == $width) {
            return;
        }
        $img->resize($width, $height, function ($c) {
            $c->aspectRatio();
            $c->upsize();
        });
        $img->save($filePath, 100);
    }

    public static function toWhiteish($filePath)
    {
        $img = \Intervention\Image\ImageManagerStatic::make($filePath);
        $h = $img->height();
        $w = $img->width();
        $mask = \Intervention\Image\ImageManagerStatic::canvas($w, $h, '#fff');
        $mask->insert($img, 'center');
        $mask->greyscale()->invert();

        $final = \Intervention\Image\ImageManagerStatic::canvas($w, $h, '#000000');
        $final->mask($mask);
        $final->invert();
        $final->brightness(100)->contrast(100);
        $final->save($filePath, 100);
    }
}
