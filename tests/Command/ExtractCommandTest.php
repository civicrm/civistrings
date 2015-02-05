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
    $cases = array(); // array(array $inputFiles, string $expectedOutputFile)

    $cases[] = array(__DIR__, array("examples/ex1.php"), "examples/ex1.pot");
    $cases[] = array(__DIR__, array("examples/ex2.js"), "examples/ex2.pot");
    $cases[] = array(__DIR__, array("examples/ex3.tpl"), "examples/ex3.pot");
    $cases[] = array(__DIR__, array("examples/ex4.cmd", "examples/ex4.install", "examples/ex4.module", "examples/ex4.tpl", "examples/ex4.js", "examples/ex4.cmd2",), "examples/ex4.pot");

    return $cases;
  }

  /**
   * @param array $inputFiles
   * @param string $expectedOutputFile
   * @dataProvider examples
   */
  public function testExecute($baseDir, $inputFiles, $expectedOutputFile) {
    chdir($baseDir);
    $commandTester = $this->createCommandTester(new ExtractCommand());
    $commandTester->execute(array(
      'command' => self::COMMAND,
      'files' => $inputFiles,
    ));
    $expectedOutput = file_get_contents($expectedOutputFile);
    $this->assertEquals($expectedOutput, $commandTester->getDisplay());
  }

}
