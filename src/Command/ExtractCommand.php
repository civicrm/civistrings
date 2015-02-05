<?php
namespace Civi\Strings\Command;

use Civi\Strings\Parser\JsParser;
use Civi\Strings\Parser\PhpParser;
use Civi\Strings\Parser\SmartyParser;
use Civi\Strings\Pot;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExtractCommand extends Command {

  protected $parsers;

  protected $pot;

  protected function configure() {
    $this
      ->setName('civistrings')
      ->setDescription('Extract strings from the given files')
      ->setHelp('Compare the commits/revisions in different source trees')
      ->addArgument('files', InputArgument::IS_ARRAY, 'Files from which to extract strings')
      ->addOption('base', 'b', InputOption::VALUE_REQUIRED, 'Base directory name (for constructing relative paths)', realpath(getcwd()));
  }

  protected function initialize(InputInterface $input, OutputInterface $output) {
    $this->parsers = array();
    $this->parsers['js'] = new JsParser();
    $this->parsers['php'] = new PhpParser();
    $this->parsers['smarty'] = new SmartyParser();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->pot = new Pot();
    $this->extractFiles($input, $output, $input->getArgument('files'));
    $this->pot->printAll($input, $output);
  }

  protected function extractFiles(InputInterface $input, OutputInterface $output, $paths) {
    sort($paths);
    foreach ($paths as $path) {
      if ($path == '-') {
        $files = explode("\n", file_get_contents('php://stdin'));
        $this->extractFiles($input, $output, $files);
      }
      elseif (is_dir($path)) {
        $children = array();

        $d = dir($path);
        while (FALSE !== ($entry = $d->read())) {
          if ($entry == '.' || $entry == '..') {
            continue;
          }
          $children[] = $path . '/' . $entry;
        }
        $d->close();

        $this->extractFiles($input, $output, $children);
      }
      elseif (file_exists($path)) {
        $this->extractFile($path);
      }
    }
  }

  /**
   * @param string $file
   */
  protected function extractFile($file) {
    $content = @file_get_contents($file);

    $parser = $this->pickParser($file, $content);
    if (!$parser) {
      return;
    }

    $parser->parse($file, $content, $this->pot);
  }

  /**
   * @param string $file
   * @param string $content
   * @return Object|NULL
   */
  protected function pickParser($file, $content) {
    $file = realpath($file);

    $parser = NULL;
    if (preg_match('/~$/', $file)) {
      // skip
    }
    elseif (preg_match('/\.js$/', $file)) {
      $parser = 'js';
    }
    elseif (preg_match('/\.tpl$/', $file)) {
      $parser = 'smarty';
    }
    elseif (preg_match('/\.php$/', $file) || preg_match(':^<\?php:', $content) || preg_match(':^#![^\n]+php:', $content)) {
      $parser = 'php';
    }

    return $parser ? $this->parsers[$parser] : NULL;
  }

}
