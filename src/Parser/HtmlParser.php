<?php

namespace Civi\Strings\Parser;

use Civi\Afform\StringVisitor;
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

    $form = [];
    $doc = \phpQuery::newDocument($content, 'text/html');

    (new StringVisitor())->visit($form, $doc, function ($string) use ($pot, $file) {
      $pot->add([
        'file' => $file,
        'msgid' => stripcslashes($string),
        'msgstr' => '',
      ]);
      return $string;
    });

    // Match all calls to ts()
    // ASIDE: This is kind of weird. If afform's StringVisitor doesn't handle {{ts('...')}},
    // then we should probably it.
    parent::parse($file, $content, $pot);
  }

}
