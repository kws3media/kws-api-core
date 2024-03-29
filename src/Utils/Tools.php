<?php

namespace Kws3\ApiCore\Utils;

use \Kws3\ApiCore\Exceptions\HTTPException;

class Tools extends \Prefab
{

  public static function emailsFromTextField($string)
  {
    $emails = explode(',', $string);
    return array_filter(array_map('trim', $emails));
  }

  public static function toFixed($num)
  {
    return number_format($num, 2, '.', '');
  }

  public static function startsWith($haystack, $needle)
  {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  public static function endsWith($haystack, $needle)
  {
    $length = strlen($needle);
    if ($length === 0) {
      return true;
    }

    return (substr($haystack, -$length) === $needle);
  }

  public static function contains($haystack, $needle)
  {
    return strpos($haystack, $needle) !== false;
  }

  public static function clamp($value, $min, $max)
  {
    return min(max($value, $min), $max);
  }

  public static function validate_password($password)
  {
    // Validate password strength
    $uppercase    = preg_match('@[A-Z]@', $password);
    $lowercase    = preg_match('@[a-z]@', $password);
    $number       = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^A-Za-z0-9]@', $password);
    if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
      throw new HTTPException(
        'Password should be a strong one.',
        406,
        array(
          'dev' => 'Should be at least 8 characters in length and include at least one lower-case letter, one upper-case letter, one number, and one special character.',
          'internalCode' => '',
          'more' => '',
        )
      );
    } else {
      return true;
    }
  }

  public static function trimSlash($str, $both = false)
  {
    $str = rtrim($str, '/\\');
    if ($both) {
      $str = ltrim($str, '/\\');
    }

    return $str;
  }

  public static function generateRandomFilename($originalFilename)
  {
    $name = basename($originalFilename);
    $parts = explode(".", $name);
    $ext = array_pop($parts);
    $fileNoExtension = implode(".", $parts);
    return md5(uniqid(mt_rand(), true) . $fileNoExtension) . '.' . strtolower($ext);
  }

  //Random pronounceable ref key
  public static function randomPronounceableRefKey($syllables = 3, $strong = false)
  {
    $pw = '';
    $c = 'bcdfghjklmnprstvwz'; // consonants except hard to speak ones
    $v = 'aeiou';              // vowels
    $a = $c . $v;              // all

    //Append Caps & Special Chars
    if ($strong) {
      $caps = 'AEIOQUXY';
      $special = '@#!$&()/^';
      $cpsRandom = $caps[rand(0, strlen($caps) - 1)];
      $splRandom = $special[rand(0, strlen($special) - 1)];
      $pw .= $cpsRandom;
      $pw .= $splRandom;
    }

    //iterate till number of syllables reached
    for ($i = 0; $i < $syllables; $i++) {
      $pw .= $c[rand(0, strlen($c) - 1)];
      $pw .= $v[rand(0, strlen($v) - 1)];
      $pw .= $a[rand(0, strlen($a) - 1)];
    }

    //... and add a nice number
    $pw .= rand(10, 99);

    return $pw;
  }

  /**
   * Parses out the search parameters from a request.
   * And populates $this->searchFields and $this->filters
   * $this->searchFields object is used to check whether a field searched on is actually searchable or not
   * Unparsed, they will look like this:
   *    (name:Benjamin Franklin,location:Philadelphia,age[gt]:12)
   * searchFields object:
   *     array('name'=>'Benjamin Franklin', 'location'=>'Philadelphia', 'age'=>12)
   * filters object:
   *    array('field'=>'name', 'value'=>'Benjamin Franklin', 'condition'=>'eq'),
   *    array('field'=>'location', 'value'=>'Philadelphia', 'condition'=>'eq'),
   *    array('field'=>'age', 'value'=>'12', 'condition'=>'gt')
   * @param string $unparsed Unparsed search string
   */
  public static function parseSearchParameters($unparsed)
  {
    // Strip parens that come with the request string
    $unparsed = trim($unparsed, '()');

    // Now we have an array of "key:value" strings.
    $splitFields = array_map('trim', explode(',', $unparsed));
    $mapped = array();
    $filters = array();

    // Split the strings at their colon, set left to key, and right to value.
    foreach ($splitFields as $field) {
      if (trim($field) !== "") {
        $splitField = array_map('trim', explode(':', $field));
        //filter out the condition
        preg_match("/([^\[\]]*)(\[([^\[\]]+)\])?([|])?/", $splitField[0], $matches);
        if (isset($matches[1])) {
          $mapped[$matches[1]] = isset($splitField[1]) ? $splitField[1] : "";
          $filter = array(
            'field' => $matches[1],
            'value' => isset($splitField[1]) ? $splitField[1] : "",
            'condition' => 'eq',
            'jointype' => 'AND',
            'group' => null
          );
          if (isset($matches[3])) {
            $condition_parts = explode(';', $matches[3]);
            $filter['condition'] = $condition_parts[0];
            if (isset($condition_parts[1])) {
              $filter['group'] = $condition_parts[1];
            }
          }
          if (isset($matches[4])) {
            $filter['jointype'] = 'OR';
          }
          $filters[] = $filter;
        }
      }
    }

    return ['searchFields' => $mapped, 'filters' => $filters];
  }

  /**
   * Removes indentation from multiline strings. Works with both tabs and spaces.
   * @param string $str
   * @return string
   */
  public static function dedent($str)
  {
    // 1. Remove leading/trailing whitespace.
    $parts = array_map(function ($part) {
      return trim($part);
    }, explode("\n", $str));

    // 2. Find all line breaks to determine the highest common indentation level.
    $spaces = min(array_map(function ($part) {
      preg_match('#^\s*#', $part, $matches);
      return strlen($matches[0]);
    }, $parts));

    // 3. Remove the common indentation from all strings.
    $parts = array_map(function ($part) use ($spaces) {
      return substr($part, $spaces);
    }, $parts);

    return implode("\n", $parts);
  }
}
