<?php

namespace Kws3\ApiCore\FS;

use \Aws\S3\S3Client;
use \Aws\S3\Exception\S3Exception;
use \Kws3\ApiCore\Utils\Tools;

abstract class CloudDriver extends Driver
{
  public const ACL_PRIVATE               = 'private';
  public const ACL_PUBLIC                = 'public-read';
  public const ACL_OPEN                  = 'public-read-write';
  public const ACL_AUTH_READ             = 'authenticated-read';
  public const ACL_OWNER_READ            = 'bucket-owner-read';
  public const ACL_OWNER_FULL_CONTROL    = 'bucket-owner-full-control';

  public const STORAGE_CLASS_STANDARD    = 'STANDARD';
  public const STORAGE_CLASS_RRS         = 'REDUCED_REDUNDANCY';


  protected $app;
  protected $s3;
  protected $opts;
  protected $bucket;

  protected $mimeTypes = array(
    'ai' => 'application/postscript',
    'aif' => 'audio/x-aiff',
    'aifc' => 'audio/x-aiff',
    'aiff' => 'audio/x-aiff',
    'anx' => 'application/annodex',
    'asc' => 'text/plain',
    'au' => 'audio/basic',
    'avi' => 'video/x-msvideo',
    'axa' => 'audio/annodex',
    'axv' => 'video/annodex',
    'bcpio' => 'application/x-bcpio',
    'bin' => 'application/octet-stream',
    'bmp' => 'image/bmp',
    'c' => 'text/plain',
    'cc' => 'text/plain',
    'ccad' => 'application/clariscad',
    'cdf' => 'application/x-netcdf',
    'class' => 'application/octet-stream',
    'cpio' => 'application/x-cpio',
    'cpt' => 'application/mac-compactpro',
    'csh' => 'application/x-csh',
    'css' => 'text/css',
    'csv' => 'text/csv',
    'dcr' => 'application/x-director',
    'dir' => 'application/x-director',
    'dms' => 'application/octet-stream',
    'doc' => 'application/msword',
    'drw' => 'application/drafting',
    'dvi' => 'application/x-dvi',
    'dwg' => 'application/acad',
    'dxf' => 'application/dxf',
    'dxr' => 'application/x-director',
    'eps' => 'application/postscript',
    'etx' => 'text/x-setext',
    'exe' => 'application/octet-stream',
    'ez' => 'application/andrew-inset',
    'f' => 'text/plain',
    'f90' => 'text/plain',
    'flac' => 'audio/flac',
    'fli' => 'video/x-fli',
    'flv' => 'video/x-flv',
    'gif' => 'image/gif',
    'gtar' => 'application/x-gtar',
    'gz' => 'application/x-gzip',
    'h' => 'text/plain',
    'hdf' => 'application/x-hdf',
    'hh' => 'text/plain',
    'hqx' => 'application/mac-binhex40',
    'htm' => 'text/html',
    'html' => 'text/html',
    'ice' => 'x-conference/x-cooltalk',
    'ief' => 'image/ief',
    'iges' => 'model/iges',
    'igs' => 'model/iges',
    'ips' => 'application/x-ipscript',
    'ipx' => 'application/x-ipix',
    'jpe' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'js' => 'application/x-javascript',
    'kar' => 'audio/midi',
    'latex' => 'application/x-latex',
    'lha' => 'application/octet-stream',
    'lsp' => 'application/x-lisp',
    'lzh' => 'application/octet-stream',
    'm' => 'text/plain',
    'man' => 'application/x-troff-man',
    'me' => 'application/x-troff-me',
    'mesh' => 'model/mesh',
    'mid' => 'audio/midi',
    'midi' => 'audio/midi',
    'mif' => 'application/vnd.mif',
    'mime' => 'www/mime',
    'mov' => 'video/quicktime',
    'movie' => 'video/x-sgi-movie',
    'mp2' => 'audio/mpeg',
    'mp3' => 'audio/mpeg',
    'mpe' => 'video/mpeg',
    'mpeg' => 'video/mpeg',
    'mpg' => 'video/mpeg',
    'mpga' => 'audio/mpeg',
    'ms' => 'application/x-troff-ms',
    'msh' => 'model/mesh',
    'nc' => 'application/x-netcdf',
    'oga' => 'audio/ogg',
    'ogg' => 'audio/ogg',
    'ogv' => 'video/ogg',
    'ogx' => 'application/ogg',
    'oda' => 'application/oda',
    'pbm' => 'image/x-portable-bitmap',
    'pdb' => 'chemical/x-pdb',
    'pdf' => 'application/pdf',
    'pgm' => 'image/x-portable-graymap',
    'pgn' => 'application/x-chess-pgn',
    'png' => 'image/png',
    'pnm' => 'image/x-portable-anymap',
    'pot' => 'application/mspowerpoint',
    'ppm' => 'image/x-portable-pixmap',
    'pps' => 'application/mspowerpoint',
    'ppt' => 'application/mspowerpoint',
    'ppz' => 'application/mspowerpoint',
    'pre' => 'application/x-freelance',
    'prt' => 'application/pro_eng',
    'ps' => 'application/postscript',
    'qt' => 'video/quicktime',
    'ra' => 'audio/x-realaudio',
    'ram' => 'audio/x-pn-realaudio',
    'ras' => 'image/cmu-raster',
    'rgb' => 'image/x-rgb',
    'rm' => 'audio/x-pn-realaudio',
    'roff' => 'application/x-troff',
    'rpm' => 'audio/x-pn-realaudio-plugin',
    'rtf' => 'text/rtf',
    'rtx' => 'text/richtext',
    'scm' => 'application/x-lotusscreencam',
    'set' => 'application/set',
    'sgm' => 'text/sgml',
    'sgml' => 'text/sgml',
    'sh' => 'application/x-sh',
    'shar' => 'application/x-shar',
    'silo' => 'model/mesh',
    'sit' => 'application/x-stuffit',
    'skd' => 'application/x-koan',
    'skm' => 'application/x-koan',
    'skp' => 'application/x-koan',
    'skt' => 'application/x-koan',
    'smi' => 'application/smil',
    'smil' => 'application/smil',
    'snd' => 'audio/basic',
    'sol' => 'application/solids',
    'spl' => 'application/x-futuresplash',
    'spx' => 'audio/ogg',
    'src' => 'application/x-wais-source',
    'step' => 'application/STEP',
    'stl' => 'application/SLA',
    'stp' => 'application/STEP',
    'sv4cpio' => 'application/x-sv4cpio',
    'sv4crc' => 'application/x-sv4crc',
    'swf' => 'application/x-shockwave-flash',
    't' => 'application/x-troff',
    'tar' => 'application/x-tar',
    'tcl' => 'application/x-tcl',
    'tex' => 'application/x-tex',
    'texi' => 'application/x-texinfo',
    'texinfo' => 'application/x-texinfo',
    'tif' => 'image/tiff',
    'tiff' => 'image/tiff',
    'tr' => 'application/x-troff',
    'tsi' => 'audio/TSP-audio',
    'tsp' => 'application/dsptype',
    'tsv' => 'text/tab-separated-values',
    'txt' => 'text/plain',
    'unv' => 'application/i-deas',
    'ustar' => 'application/x-ustar',
    'vcd' => 'application/x-cdlink',
    'vda' => 'application/vda',
    'viv' => 'video/vnd.vivo',
    'vivo' => 'video/vnd.vivo',
    'vrml' => 'model/vrml',
    'wav' => 'audio/x-wav',
    'wrl' => 'model/vrml',
    'xbm' => 'image/x-xbitmap',
    'xlc' => 'application/vnd.ms-excel',
    'xll' => 'application/vnd.ms-excel',
    'xlm' => 'application/vnd.ms-excel',
    'xls' => 'application/vnd.ms-excel',
    'xlw' => 'application/vnd.ms-excel',
    'xml' => 'application/xml',
    'xpm' => 'image/x-xpixmap',
    'xspf' => 'application/xspf+xml',
    'xwd' => 'image/x-xwindowdump',
    'xyz' => 'chemical/x-pdb',
    'zip' => 'application/zip',
  );

  public function __construct($opts = [])
  {
    $this->opts = $opts;
    $this->bucket = $this->opts['bucket'];
  }

  abstract public function getUrl($fileObject);

  abstract public function getFriendlyUrl($fileObject);

  public function getUploadPresignedUrl($folder, $originalName, $expires = 3600, $acl = self::ACL_PUBLIC)
  {
    return $this->createUploadPresignedUrl($folder, $originalName, $expires, $acl);
  }

  public function getDownloadPresignedUrl($fileObject, $expires = 3600)
  {
    return $this->createDownloadPresignedUrl($fileObject, $expires);
  }


  public function create($filePath, $destinationFolder = '/', $opts = [])
  {

    $originalName = basename($filePath);

    $acl = $opts['acl'] ?: null;
    if ($acl === null) {
      $acl = self::ACL_PUBLIC;
    }

    $contentType = $opts['contentType'] ?: null;
    if ($contentType === null) {
      $contentType = $this->mimeByExtension($originalName);
    }

    $redundancy = $opts['redundancy'] ?: null;
    if ($redundancy === null) {
      $redundancy = self::STORAGE_CLASS_STANDARD;
    }

    $folder = Tools::trimSlash($destinationFolder, true);
    $newFilename = Tools::generateRandomFilename($filePath);

    $filename = implode('/', array_filter([$folder, $newFilename]));

    try {
      // Upload data.
      //phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
      $result = $this->getS3()->putObject(array(
        'ACL'           => $acl,
        'SourceFile'    => $filePath,
        'Bucket'        => $this->bucket,
        'Key'           => $filename,
        'ContentType'   => $contentType,
        'StorageClass'  => $redundancy
      ));

      @unlink($filePath);

      return [
        'bucket' => $this->bucket,
        'folder' => $folder,
        'name' => $newFilename,
        'original_name' => $originalName,
        'driver' => $this->getClassName(),
        'url' => '//' . implode('/', array_filter([$this->bucket, $folder, $newFilename]))
      ];
    } catch (S3Exception $e) {
      dbg($e->getMessage());
    }

    @unlink($filePath);

    return false;
  }

  public function mimeByExtension($file)
  {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if ($ext !== '') {
      $ext = strtolower($ext);
      if (isset($this->mimeTypes[$ext])) {
        return $this->mimeTypes[$ext];
      }
    }
    return null;
  }

  public function getS3()
  {
    if (!$this->s3) {

      $this->_checkOpts();

      $S3_OPTS = [
        'version' =>  $this->opts['version'],
        'region' => $this->opts['region'],
        'credentials' => [
          'key'    => $this->opts['access_key'],
          'secret' => $this->opts['secret'],
        ],
      ];

      if (isset($this->opts['endpoint'])) {
        $S3_OPTS['endpoint'] = $this->opts['endpoint'];
      };
      if (isset($this->opts['use_path_style_endpoint'])) {
        $S3_OPTS['use_path_style_endpoint'] = true;
      }


      $this->s3 = new S3Client($S3_OPTS);
    }
    return $this->s3;
  }

  protected function createUploadPresignedUrl($folder, $originalName, $expires, $acl)
  {
    $key = Tools::generateRandomFilename($originalName);
    $contentType = $this->mimeByExtension($originalName);

    $filename = implode('/', array_filter([$folder, $key]));

    if (empty($acl)) {
      $acl = self::ACL_PUBLIC;
    }

    $cmd = $this->getS3()->getCommand('PutObject', [
      'Bucket' => $this->bucket,
      'Key'    => $filename,
      'ContentType' => $contentType,
      'ACL' => $acl
    ]);

    $request = $this->getS3()->createPresignedRequest($cmd, "+$expires seconds");
    $presignedUrl = (string) $request->getUri();

    return [
      'url' => $presignedUrl,
      'original_name' => $originalName,
      'name' => $key,
      'contentType' => $contentType,
      'acl' => $acl,
      'bucket' => $this->bucket,
      'folder' => $folder,
      'region' => $this->opts['region'],
      'driver' => $this->getClassName()
    ];
  }

  protected function createDownloadPresignedUrl($fileObject, $expiry = 3600)
  {
    $cmd = $this->getS3()->getCommand('GetObject', [
      'Bucket' => $fileObject->bucket,
      'Key' => implode('/', array_filter([$fileObject->folder, $fileObject->name]))
    ]);

    $request = $this->getS3()->createPresignedRequest($cmd, "+$expiry seconds");

    return (string) $request->getUri();
  }

  protected function _checkOpts()
  {
    foreach (['bucket', 'access_key', 'secret'] as $o) {
      if (!$this->opts[$o] || empty($this->opts[$o])) {
        throw new \Exception($this->getClassName() . ": Mandatory option no set: '" . $o . "'");
      }
    }
  }
}
