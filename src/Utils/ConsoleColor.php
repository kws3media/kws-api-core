<?php

namespace Kws3\ApiCore\Utils;

/**
 * @method static string success()
 * @method static string error()
 * @method static string info()
 * @method static string warning()
 * @method static string format(string $msg, array $codes)
 */

class ConsoleColor extends \Prefab
{
  public const OFF        = 0;
  public const BOLD       = 1;
  public const ITALIC     = 3;
  public const UNDERLINE  = 4;
  public const BLINK      = 5;
  public const INVERSE    = 7;
  public const HIDDEN     = 8;
  public const F_BLACK    = 90;
  public const F_RED      = 91;
  public const F_GREEN    = 92;
  public const F_YELLOW   = 93;
  public const F_BLUE     = 94;
  public const F_MAGENTA  = 95;
  public const F_CYAN     = 96;
  public const F_WHITE    = 97;
  public const B_BLACK    = 100;
  public const B_RED      = 101;
  public const B_GREEN    = 102;
  public const B_YELLOW   = 103;
  public const B_BLUE     = 104;
  public const B_MAGENTA  = 105;
  public const B_CYAN     = 106;
  public const B_WHITE    = 107;

  protected $themes = [];

  public function __construct()
  {
    //set some base themes
    $this->setTheme('success', [
      self::BOLD,
      self::F_WHITE,
      self::B_GREEN,
    ]);
    $this->setTheme('error', [
      self::BOLD,
      self::F_WHITE,
      self::B_RED,
    ]);
    $this->setTheme('info', [
      self::BOLD,
      self::F_BLUE,
      self::B_WHITE,
    ]);
    $this->setTheme('warning', [
      self::BOLD,
      self::F_RED,
      self::B_YELLOW,
    ]);
  }

  /**
   * Actual formatter for console output
   * @param mixed $str
   * @param array $codes
   * @return string
   */
  public function format($str, $codes = [])
  {
    return "[" . implode(';', $codes) . "m" . $str . "[" . self::OFF . "m";
  }

  /**
   * Creates themes that can be called using magic functions
   * @param mixed $name
   * @param array $codes
   * @return void
   */
  public function setTheme($name, $codes = [])
  {
    $this->themes[$name] = $codes;
  }


  public function __call($name, $arguments)
  {
    if (array_key_exists($name, $this->themes)) {
      return $this->format($arguments[0], $this->themes[$name]);
    }
    return $arguments[0];
  }

  public static function __callStatic($name, $arguments)
  {
    $self = self::instance();
    if (array_key_exists($name, $self->themes)) {
      return $self->format($arguments[0], $self->themes[$name]);
    }
    return $arguments[0];
  }
}
