<?php

namespace Civi\Strings\Parser;

use Civi\Strings\Pot;

// avoid errors
function ts($string) {}

class MgdPhpParser extends PhpTreeParser implements ParserInterface {

  /**
   * On top of regular ts parsing, auto-add strings that are assumed to be multilingual
   * similar to ext/search_kit/Civi/Search/Translator.php::updateSearchDisplaySources
   *
   * @param string $file
   * @param string $content
   * @param Pot $pot
   */
  public function parse($file, $code, Pot $pot) {

    // Extract raw tokens
    $lexer = new \PhpParser\Lexer\Emulative();
    $parser = (new \PhpParser\ParserFactory)->create(\PhpParser\ParserFactory::PREFER_PHP7, $lexer);
    try {
      // match all calls to ts()
      $stmts = $parser->parse($code);
      $this->extractStrings($stmts, $pot, $file);

      $keys = ['label', 'title', 'description', 'text', 'empty_value', 'rewrite'];
      $this->extractMgdStrings($stmts, $keys, $pot, $file);
    }
    catch (\PhpParser\Error $e) {
      $this->reportError("Couldn't parse file: " . $e->getMessage(), 'error', $file);
    }

  }

  /**
   * Extract string from mgd by going through the array recursively
   * Similar to ext/search_kit/Civi/Search/Translator.php::extractStrings
   */
  protected function extractMgdStrings($node, array $keys, Pot $pot, string &$file) {
    if (is_array($node)) {
      foreach ($node as &$single_node) {
        $this->extractMgdStrings($single_node, $keys, $pot, $file);
      }
    }
    elseif (is_scalar($node)) {
      return;
    }
    elseif (is_object($node)) {
      if (get_class($node) == "PhpParser\Node\Expr\ArrayItem") {
        if (!empty($node->key->value) && !empty($node->value->value) 
          && is_scalar($node->key->value) && in_array($node->key->value, $keys) 
          && is_scalar($node->value->value) && $this->isWorthy($node->value->value)) {
          $pot->add([
            'file' => $file,
            'msgid' => stripcslashes($node->value->value),
            'msgstr' => '',
          ]);
        }
      }

      foreach ($node as $key => &$value) {
        $this->extractMgdStrings($value, $keys, $pot, $file);
      }
    }
    elseif ($node == NULL) {
      return;
    }
  }

  /**
   * Taken from ext/search_kit/Civi/Search/Translator.php::isWorthy
   */
  protected function isWorthy(?string $value): bool {
    return !empty($value)
      // ignore value with smarty
      && !(str_contains($value, '{') && str_contains($value, '}'))
      // ignore value with custom translation
      && (!str_contains($value, 'ts('))
      // ignore value that are a simple field token
      && !preg_match('/^\[[A-Za-z0-9._-]+\]$/', trim($value));
  }

}