<?php
namespace Civi\Strings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Pot {
  protected $strings;
  protected $nextWeight = 1;

  public function __construct($strings = array()) {
    $this->strings = $strings;
  }

  public function add($string) {
    $id = $string['msgid'];

    if (is_string($string['file'])) {
      $string['files'] = array($string['file']);
      unset($string['file']);
    }

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
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  public function printAll(InputInterface $input, OutputInterface $output) {
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
    foreach ($this->strings as $string) {
      //if (!$first) {
      //  $output->writeln('');
      //}

      $files = '';
      foreach ($string['files'] as $file) {
        $files .= $this->relativize($file, $input->getOption('base')) . ' ';
      }
      $files = trim($files);
      $output->writeln("#: $files");

      foreach ($string as $k => $v) {
        switch ($k) {
          case 'files':
            break;

          case 'weight':
            break;

          default:
            $output->writeln("$k \"$v\"");
        }
      }

      //$first = FALSE;
      $output->writeln('');
    }
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
