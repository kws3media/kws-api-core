<?php

namespace Kws3\ApiCore\Utils;

/**
 * @method static string success()
 * @method static string error()
 * @method static string warning()
 * @method static string format(string $msg, array $codes)
 */

class ConsoleColor extends \Prefab
{
  const OFF        = 0;
  const BOLD       = 1;
  const ITALIC     = 3;
  const UNDERLINE  = 4;
  const BLINK      = 5;
  const INVERSE    = 7;
  const HIDDEN     = 8;
  const F_BLACK    = 90;
  const F_RED      = 91;
  const F_GREEN    = 92;
  const F_YELLOW   = 93;
  const F_BLUE     = 94;
  const F_MAGENTA  = 95;
  const F_CYAN     = 96;
  const F_WHITE    = 97;
  const B_BLACK    = 100;
  const B_RED      = 101;
  const B_GREEN    = 102;
  const B_YELLOW   = 103;
  const B_BLUE     = 104;
  const B_MAGENTA  = 105;
  const B_CYAN     = 106;
  const B_WHITE    = 107;

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
    $ansi_str = "[" . implode(';', $codes) . "m" . $str . "[" . self::OFF . "m";
    return $ansi_str;
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
