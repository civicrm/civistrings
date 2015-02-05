<?php
namespace Civi\Strings;

use Symfony\Component\Console\Input\InputInterface;

/**
 * String-extracting application.
 *
 * This is a single-command application. In the future, when we revise our approach
 * to CLI commands, the one command might be subsumed into some bigger framework.
 *
 * @link http://symfony.com/doc/current/components/console/single_command_tool.html
 * @package Civi\Strings
 */
class Application extends \Symfony\Component\Console\Application {

  /**
   * Primary entry point for execution of the standalone command.
   *
   * @param string $binDir
   *   Path to the main application binary.
   */
  public static function main($binDir) {
    $application = new Application('git-scan', '@package_version@');
    $application->run();
  }

  public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN') {
    parent::__construct($name, $version);
    $this->setCatchExceptions(TRUE);
  }

  protected function getCommandName(InputInterface $input) {
    return 'civistrings';
  }

  protected function getDefaultCommands() {
    $defaultCommands = parent::getDefaultCommands();
    $defaultCommands[] = new Command\ExtractCommand();
    return $defaultCommands;
  }

  public function getDefinition() {
    $inputDefinition = parent::getDefinition();
    // clear out the normal first argument, which is the command name
    $inputDefinition->setArguments();

    return $inputDefinition;
  }
}