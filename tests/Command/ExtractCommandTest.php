<?php
namespace Civi\Strings\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ApplicationTest extends \PHPUnit_Framework_TestCase {

  const COMMAND = 'civistrings';

  public function examples() {
    $cases = array(); // array(array $inputFiles, string $expectedOutputFile)

    $cases[] = array(array("examples/ex1.php"), "examples/ex1.pot");
    $cases[] = array(array("examples/ex2.js"), "examples/ex2.pot");
    $cases[] = array(array("examples/ex3.tpl"), "examples/ex3.pot");
    $cases[] = array(array("examples/ex4.cmd", "examples/ex4.install", "examples/ex4.module", "examples/ex4.tpl", "examples/ex4.js", "examples/ex4.cmd2", "examples/ex4.txt"), "examples/ex4.pot");

    return $cases;
  }

  /**
   * @param array $inputFiles
   * @param string $expectedOutputFile
   * @dataProvider examples
   */
  public function testExecute($inputFiles, $expectedOutputFile) {
    chdir($this->getBaseDir());
    $commandTester = $this->createCommandTester(new ExtractCommand());
    $commandTester->execute(array(
      'command' => self::COMMAND,
      'files' => $inputFiles,
    ));
    $expectedOutput = file_get_contents($expectedOutputFile);
    $this->assertEquals($expectedOutput, $commandTester->getDisplay());
  }

  /**
   * This is the same as ex4 from the normal testExecute(), but the input
   * files are passed using a mix of command line arguments and STDIN.
   */
  public function testExecuteEx4ViaStdin() {
    chdir($this->getBaseDir());
    $fh = fopen('php://memory', 'w+');
    fwrite($fh, "examples/ex4.cmd\n");
    fwrite($fh, "examples/ex4.install\n");
    fwrite($fh, "examples/ex4.tpl\n");
    fwrite($fh, "examples/ex4.module\n"); // note: duplicated in 'files' below
    rewind($fh);

    $commandTester = $this->createCommandTester(new ExtractCommand(NULL, $fh));
    $commandTester->execute(array(
      'command' => self::COMMAND,
      'files' => array("-", "examples/ex4.module", "examples/ex4.js", "examples/ex4.cmd2", "examples/ex4.txt"),
    ));
    $expectedOutput = file_get_contents("examples/ex4.pot");
    $this->assertEquals($expectedOutput, $commandTester->getDisplay());

    fclose($fh);
  }

  protected function createCommandTester($command) {
    $application = new Application();
    $application->add($command);
    $commandTester = new CommandTester($command);
    return $commandTester;
  }

  /**
   * @return string
   */
  protected function getBaseDir() {
    return dirname(dirname(__DIR__));
  }

}
