<?php
namespace Civi\Strings\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ApplicationTest extends \PHPUnit_Framework_TestCase {

  const COMMAND = 'civistrings';

  protected function createCommandTester($command) {
    $application = new Application();
    $application->add($command);
    $commandTester = new CommandTester($command);
    return $commandTester;
  }

  public function examples() {
    $cases = array(); // array(string $inputFile, string $expectedOutputFile)
    $cases[] = array(__DIR__, "examples/ex1.php", "examples/ex1.pot");
    $cases[] = array(__DIR__, "examples/ex2.js", "examples/ex2.pot");
    $cases[] = array(__DIR__, "examples/ex3.tpl", "examples/ex3.pot");

    return $cases;
  }

  /**
   * @param string $inputFile
   * @param string $expectedOutputFile
   * @dataProvider examples
   */
  public function testExecute($baseDir, $inputFile, $expectedOutputFile) {
    chdir($baseDir);
    $commandTester = $this->createCommandTester(new ExtractCommand());
    $commandTester->execute(array(
      'command' => self::COMMAND,
      'files' => array($inputFile),
    ));
    $expectedOutput = file_get_contents($expectedOutputFile);
    $this->assertEquals($expectedOutput, $commandTester->getDisplay());
  }

}
