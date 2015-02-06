<?php
namespace Civi\Strings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Pot {
  /**
   * Array(string $msgid => array $msgdef)
   *
   * @var array
   */
  protected $strings;

  /**
   * @var int
   */
  protected $nextWeight = 1;

  /**
   * Report all files as relative to $baseDir
   *
   * @var string
   */
  protected $baseDir;

  /**
   * Default properties to set on new strings.
   *
   * @var array
   */
  protected $defaults;

  public function __construct($baseDir = '', $strings = array(), $defaults = array()) {
    $this->baseDir = $baseDir;
    $this->strings = $strings;
    $this->defaults = $defaults;
  }

  public function add($string) {
    $id = $string['msgid'];

    if (is_string($string['file'])) {
      $string['files'] = array($string['file']);
      unset($string['file']);
    }

    $string = array_merge($this->defaults, $string);

    if (isset($this->strings[$id])) {
      $this->strings[$id]['files'] = array_unique(array_merge($this->strings[$id]['files'], $string['files']));
    }
    else {
      $string['weight'] = $this->nextWeight++;
      $this->strings[$id] = $string;
    }
  }

  public function getAll() {
    return $this->strings;
  }

  /**
   * @return string
   */
  public function toString() {
    uasort($this->strings, function ($a, $b) {
      if (count($a['files']) == 1 && count($a['files']) > 1) {
        return 1;
      }
      elseif (count($a['files']) > 1 && count($a['files']) == 1) {
        return -1;
      }
      else {
        return $a['weight'] - $b['weight'];
      }
    });

    //$first = TRUE;
    $buf = '';
    foreach ($this->strings as $string) {
      if (empty($string['msgid'])) {
        continue;
      }

      //if (!$first) {
      //  $buf .= "\n"
      //}

      $files = '';
      foreach ($string['files'] as $file) {
        $files .= $this->relativize($file, $this->baseDir) . ' ';
      }
      $files = trim($files);
      $buf .= "#: $files\n";

      foreach ($string as $k => $v) {
        switch ($k) {
          case 'files':
            break;

          case 'weight':
            break;

          default:
            $buf .= "$k \"$v\"\n";
        }
      }

      //$first = FALSE;
      $buf .= "\n";
    }
    return $buf;
  }

  protected static function relativize($directory, $basePath) {
    $basePath = rtrim($basePath, '/') . '/';
    if (substr($directory, 0, strlen($basePath)) == $basePath) {
      return substr($directory, strlen($basePath));
    }
    else {
      return $directory;
    }
  }

}
