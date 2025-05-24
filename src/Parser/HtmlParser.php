<?php

namespace Civi\Strings\Parser;

use Civi\Strings\Pot;

class HtmlParser extends JsParser implements ParserInterface {

  /**
   * Rips gettext strings from $file and prints them in C format.
   *
   * @param string $file
   * @param string $content
   * @param Pot $pot
   */
  public function parse($file, $content, Pot $pot) {
    if (empty($content)) {
      return;
    }

    $doc = \phpQuery::newDocument("$content", 'text/html');
    // Match all elements with the af-text class
    $doc->find('.af-text')->each(function(\DOMElement $item) use ($pot, $file) {
      $pot->add([
        'file' => $file,
        'msgid' => stripcslashes($item->nodeValue),
        'msgstr' => '',
      ]);
    });
    // @todo Match other types

    // Match all calls to ts()
    parent::parse($file, $content, $pot);
  }

}
