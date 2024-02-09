<?php
namespace Civi\Strings\Parser;

use Civi\Strings\Pot;

/**
 * Extracts translatable strings from xml files.
 */
class XmlParser implements ParserInterface {

  /**
   * Extracts <title>string</title> <label>string</label> and <comment>string</comment>
   *
   * @param string $file
   * @param string $content
   * @param Pot $pot
   */
  public function parse($file, $content, Pot $pot) {
    if (empty($content)) {
      return;
    }

    // Match all <title>string</title>
    preg_match_all('~<(title|label|comment)>([^<]+)</(title|label|comment)>~', $content, $matches);
    foreach ($matches[2] as $text) {
      $text = html_entity_decode(trim($text), ENT_XML1);
      if ($text) {
        $pot->add(array(
          'file' => $file,
          'msgid' => $text,
          'msgstr' => '',
        ));
      }
    }
  }

}
