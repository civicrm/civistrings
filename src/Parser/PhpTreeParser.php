<?php
namespace Civi\Strings\Parser;

use Civi\Strings\Pot;


/**
 * ts() calls extractor
 *
 * New extractor implementation based on https://github.com/nikic/PHP-Parser
 *
 * @author Björn Endres <endres [at] systopia.de>
 * @copyright 2015
 * @license http://www.gnu.org/licenses/gpl.html  GNU General Public License
 */
class PhpTreeParser implements ParserInterface {

  /**
   * @param string $file
   * @param string $code
   * @param Pot $pot
   */
  public function parse($file, $code, Pot $pot) {
    // Extract raw tokens
    $lexer = new \PhpParser\Lexer\Emulative();
    $parser = (new \PhpParser\ParserFactory)->create(\PhpParser\ParserFactory::PREFER_PHP7, $lexer);
    try {
      $stmts = $parser->parse($code);
      $this->extractStrings($stmts, $pot, $file);
    } catch (PhpParser\Error $e) {
      $this->reportError("Couldn't parse file: " . $e->getMessage(), 'error', $file);
    }
  }

  /**
   * Recursively searches the tree for ts()-calls
   *
   * @param node the AST (abstract syntax tree) node to look into
   * @param pot  the output POT file object
   * @param file the filename context
   */
  protected function extractStrings(&$node, Pot $pot, &$file) {
    if (is_array($node)) {
      foreach ($node as &$single_node) {
        $this->extractStrings($single_node, $pot, $file);
      }
    } elseif (is_scalar($node)) {
      return;
    } elseif (is_object($node)) {
      if (get_class($node) == "PhpParser\Node\Expr\FuncCall" && $node->name->parts[0] == 'ts') {
        // this is a 'ts' function call
        $this->createPOTEntry($node, $pot, $file);
      } elseif (get_class($node) == "PhpParser\Node\Expr\StaticCall" && $node->name == 'ts' && $node->class->parts[0] == 'E') {
        // detects the new E::ts() style translations
        $this->createPOTEntry($node, $pot, $file);
      }
      // in any way: descend into subtree
      foreach ($node as $key => &$value) {
        $this->extractStrings($value, $pot, $file);
      }
    } elseif ($node==NULL) {
      return;
    } else {
      $this->reportError("Unknown node type. PhpTreeParser needs to be fixed.", 'warn', $file);
    }
  }

  /**
   * Create a POT entry for the ts()-call AST node
   *
   * @param node the AST (abstract syntax tree) node that represents a ts()-call
   * @param pot  the output POT file object
   * @param file the filename context
   */
  protected function createPOTEntry(&$node, Pot $pot, &$file) {
    $pot_entry = array(
      'file'   => $file,
      'msgstr' => ''
    );

    // verify the call:
    if (!isset($node->args[0])) {
      $this->reportError("ts() call has no arguments.", 'error',  $file, $node->getLine());
      return;
    }

    if (get_class($node->args[0]->value) != 'PhpParser\Node\Scalar\String_') {
      $this->reportError("first argument of ts() call is no string.", 'warn',  $file, $node->getLine());
      return;
    }

    // set the string
    $pot_entry['msgid'] = $node->args[0]->value->value;


    // process the arguments
    if (isset($node->args[1])) {
      if (get_class($node->args[1]->value) != 'PhpParser\Node\Expr\Array_') {
        $this->reportError("second argument of ts() call is not an array.", 'warn', $file, $node->getLine());
      } else {
        foreach ($node->args[1]->value->items as &$ts_argument) {
          switch ($ts_argument->key->value) {
            case 'plural':
              // FIXME: Is this really correct or just a workaround? (copied from old implementation)
              unset($pot_entry['msgstr']);
              $pot_entry['msgid_plural'] = $ts_argument->value->value;
              $pot_entry['msgstr[0]'] = '';
              $pot_entry['msgstr[1]'] = '';
              break;

            case 'context':
              $pot_entry['msgctxt'] = $ts_argument->value->value;
              break;
            
            default:
            case 'domain':
            case 'count':
            case 'escape':
              break;
          }
        }
      }
    }

    $pot->add($pot_entry);
  }

  /**
   * Simple wrapper to report error messages
   *
   * @param string message    the error message
   * @param string level      severity, on of 'error', 'warn', 'info'
   * @param string reference  location of the error, e.g. the file
   */
  protected function reportError($message, $level = 'error', $file = NULL, $line = 'n/a') {
    // TODO: find proper sink for error messages
    if ($file) {
      fwrite(STDERR, "[$level] $message ($file:$line)\n");
    } else {
      fwrite(STDERR, "[$level] $message\n");
    }
  }
}
