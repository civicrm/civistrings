<?php
namespace Civi\Strings\Command;

use Civi\Strings\Parser\JsParser;
use Civi\Strings\Parser\PhpTreeParser;
use Civi\Strings\Parser\SmartyParser;
use Civi\Strings\Parser\SettingParser;
use Civi\Strings\Pot;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extract strings from a list of files.
 *
 * @package Civi\Strings\Command
 */
class ExtractCommand extends Command {

  /**
   * @var array
   *   Array(string $name => ParserInterface $parser)
   */
  protected $parsers;

  /**
   * @var \Civi\Strings\Pot
   */
  protected $pot;

  /**
   * @var null|resource
   */
  protected $stdin;

  public function __construct($name = NULL, $stdin = NULL) {
    parent::__construct($name);
    $this->stdin = $stdin ? $stdin : STDIN;
  }

  protected function configure() {
    $this
      ->setName('civistrings')
      ->setDescription('Extract strings')
      ->setHelp('Extract strings from any mix of PHP, Smarty, JS, HTML files.')
      ->addArgument('files', InputArgument::IS_ARRAY, 'Files from which to extract strings. Use "-" to accept file names from STDIN')
      ->addOption('append', 'a', InputOption::VALUE_NONE, 'Append to file. (Use with --out. Implies --no-header.)')
      ->addOption('base', 'b', InputOption::VALUE_REQUIRED, 'Base directory name (for constructing relative paths)', realpath(getcwd()))
      ->addOption('header', NULL, InputOption::VALUE_REQUIRED, 'Header file to prepend to output.')
      ->addOption('no-header', 'N', InputOption::VALUE_NONE, 'Do not output any header.')
      ->addOption('default-header', 'H', InputOption::VALUE_NONE, 'Generate a default header')
      ->addOption('msgctxt', NULL, InputOption::VALUE_REQUIRED, 'Set default msgctxt for all strings')
      ->addOption('out', 'o', InputOption::VALUE_REQUIRED, 'Output file. (Default: stdout)');
  }

  protected function initialize(InputInterface $input, OutputInterface $output) {
    $this->parsers = array();
    $this->parsers['js'] = new JsParser();
    $this->parsers['html'] = new JsParser();
    $this->parsers['php'] = new PhpTreeParser();
    $this->parsers['smarty'] = new SmartyParser($this->parsers['php']);
    $this->parsers['setting'] = new SettingParser();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $defaults = array();
    if ($input->getOption('msgctxt')) {
      $defaults['msgctxt'] = $input->getOption('msgctxt');
    }
    $this->pot = new Pot($input->getOption('base'), array(), $defaults);

    $files = $input->getArgument('files');

    if (in_array('-', $files)) {
      $files = array_merge(
        $files,
        explode("\n", stream_get_contents($this->stdin))
      );
    }

    $actualFiles = $this->findFiles($files);

    if ($input->getOption('no-header') || $input->getOption('append')) {
      $header = NULL;
    }
    elseif ($input->getOption('header')) {
      $header = file_get_contents($input->getOption('header'));
    }
    elseif ($input->getOption('default-header')) {
      $header = "msgid \"\"\nmsgstr \"\"\n\"Content-Type: text/plain; charset=UTF-8\\n\"\n\n";
    }
    else {
      $error = is_callable([$output, 'getErrorOutput']) ? $output->getErrorOutput() : $output;
      $error->writeln("<error>WARNING</error>: Please specify a header option, such as <info>-H</info> (<info>--default-header</info>) or <info>-N</info> (<info>--no-header</info>) or <info>--header=FILE</info>.");
      $header = NULL;
    }

    if (!$input->getOption('out')) {
      foreach ($actualFiles as $file) {
        $this->extractFile($file);
      }
      if ($header !== NULL) {
        $output->write($header);
      }
      $output->write($this->pot->toString($input));
    }
    else {
      $progress = new ProgressBar($output);
      $progress->start(1 + count($actualFiles));
      $progress->advance();
      foreach ($actualFiles as $file) {
        $this->extractFile($file);
        $progress->advance();
      }
      $content = '';
      ## If header is supplied and if we're starting a new file.
      if ($header !== NULL && !file_exists($input->getOption('out'))) {
        $content .= file_get_contents($input->getOption('header'));
      }

      $content .= $this->pot->toString($input);
      file_put_contents($input->getOption('out'), $content, $input->getOption('append') ? FILE_APPEND : 0);
      $progress->finish();
    }

    return 0;
  }

  protected function findFiles($paths) {
    $actualFiles = array();

    $exclude_dirs = ['vendor', 'node_modules'];
    sort($paths);
    $paths = array_unique($paths);

    foreach ($paths as $path) {
      if (is_dir($path)) {
        if (!in_array(basename($path), $exclude_dirs)) {
          $children = array();

          $d = dir($path);
          while (FALSE !== ($entry = $d->read())) {
            if ($entry == '.' || $entry == '..') {
              continue;
            }
            $children[] = rtrim($path, '/') . '/' . $entry;
          }
          $d->close();

          $actualFiles = array_merge($actualFiles, $this->findFiles($children));
        }
      }
      elseif (file_exists($path)) {
        // civix files will throw warnings and should not have any strings
        $filename = basename($path);
        if (preg_match('/^.+\.civix.php$/', $filename)) {
          continue;
        }
        $actualFiles[] = $path;
      }
    }

    return $actualFiles;
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
    elseif (preg_match('/\.html$/', $file)) {
      $parser = 'html';
    }
    elseif (preg_match('/\.setting.php$/', $file)) {
      $parser = 'setting';
    }
    elseif (preg_match('/\.(tpl|hlp)$/', $file)) {
      $parser = 'smarty';
    }
    elseif (preg_match('/\.php$/', $file) || preg_match(':^<\?php:', $content) || preg_match(':^#![^\n]+php:', $content)) {
      $parser = 'php';
    }

    return $parser ? $this->parsers[$parser] : NULL;
  }

}
