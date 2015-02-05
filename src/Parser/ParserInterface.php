<?php
namespace Civi\Strings\Parser;
use Civi\Strings\Pot;

interface ParserInterface {

  /**
   * Scan a document for translatable strings.
   *
   * @param string $file
   * @param string $content
   * @param Pot $pot
   */
  public function parse($file, $content, Pot $pot);

}
