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
    $parser = new \PhpParser\Parser($lexer);
    try {
      $stmts = $parser->parse($code);
      $this->extractStrings(&$stmts, $pot, $file);
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
    } elseif (is_object($node)) {
      if (isset($node->expr)) {
        $this->extractStrings($node->expr, $pot, $file);
      }
      if (isset($node->exprs)) {
        $this->extractStrings($node->exprs, $pot, $file);
      }
      if (isset($node->args)) {
        $this->extractStrings($node->exprs, $pot, $file);
      }
      if (get_class($node) == "PhpParser\Node\Expr\FuncCall" && $node->name->parts[0] == 'ts') {
        // this is a 'ts' function call
        $this->createPOTEntry($node, $pot, $file);
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
      $this->reportError("ts() call has no arguments.", 'error',  $file);
      return;
    }

    if (get_class($node->args[0]->value) != 'PhpParser\Node\Scalar\String_') {
      $this->reportError("first argument of ts() call is no string.", 'warn',  $file);
      return;
    }

    // set the string
    $pot_entry['msgid'] = $node->args[0]->value->value;


    // process the arguments
    if (isset($node->args[1])) {
      if (get_class($node->args[1]->value) != 'PhpParser\Node\Expr\Array_') {
        $this->reportError("second argument of ts() call is not an array.", 'warn',  $file);
      } else {
        foreach ($node->args[1]->value->items as &$ts_argument) {
          switch ($ts_argument->key->value) {
            case 'domain':
              $pot_entry['domain'] = $ts_argument->value->value;
              break;

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
  protected function reportError($message, $level = 'error', $reference = NULL) {
    // TODO: find proper sink for error messages
    if ($reference) {
      fwrite(STDERR, "[$level] $message ($reference)");
    } else {
      fwrite(STDERR, "[$level] $message");
    }
  }
}
