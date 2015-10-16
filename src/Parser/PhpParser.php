<?php
namespace Civi\Strings\Parser;

use Civi\Strings\Pot;

/**
 * tsCallType return values
 */
define('TS_CALL_TYPE_INVALID', 0);
define('TS_CALL_TYPE_SINGLE', 1);
define('TS_CALL_TYPE_PLURAL', 2);

/**
 * ts() calls extractor
 *
 * Drupal's t() extractor from http://drupal.org/project/drupal-pot
 * modified to suit CiviCRM's ts() calls
 *
 * Extracts translatable strings from specified function calls, plus adds some
 * file specific strings. Only literal strings with no embedded variables can
 * be extracted. Outputs a POT file on STDOUT, errors on STDERR
 *
 * FIXME Remove fwrite()s
 *
 * @deprecated see PhpTreeParser
 * @author Jacobo Tarrio <jtarrio [at] alfa21.com>
 * @author Gabor Hojtsy <goba [at] php.net>
 * @author Piotr Szotkowski <shot@caltha.pl>
 * @author Tim Otten <info [at] civicrm.org>
 * @copyright 2003, 2004 Alfa21 Outsourcing
 * @license http://www.gnu.org/licenses/gpl.html  GNU General Public License
 */
class PhpParser implements ParserInterface {

  /**
   * @param string $file
   * @param string $code
   * @param Pot $pot
   */
  public function parse($file, $code, Pot $pot) {
    // Extract raw tokens
    $raw_tokens = token_get_all($code);

    // Remove whitespace and HTML
    $tokens = array();
    $lineno = 1;
    foreach ($raw_tokens as $tok) {
      if ((!is_array($tok)) || (($tok[0] != T_WHITESPACE) && ($tok[0] != T_INLINE_HTML))) {
        if (is_array($tok)) {
          $tok[] = $lineno;
        }
        $tokens[] = $tok;
      }
      if (is_array($tok)) {
        $lineno += count(explode("\n", $tok[1])) - 1;
      }
      else {
        $lineno += count(explode("\n", $tok)) - 1;
      }
    }

    $this->findTsCalls($tokens, $file, $pot);
  }

  /**
   * Find all of the ts() calls
   *
   * @param array $tokens the array with tokens from token_get_all()
   * @param string $file the string containing the file name
   * @param Pot $pot
   *
   * @return void
   */
  protected function findTsCalls($tokens, $file, Pot $pot) {

    // iterate through all the tokens while there's still a chance for
    // a ts() call
    while (count($tokens) > 3) {
      list($ctok, $par, $mid) = $tokens;

      // the first token has to be a T_STRING (with a function name)
      if (!is_array($ctok)) {
        array_shift($tokens);
        continue;
      }

      // check whether we're at ts(
      list($type, $string, $line) = $ctok;
      if (($type == T_STRING) && ($string == 'ts' or $string == 't') && ($par == '(')) {

        switch ($this->tsCallType($tokens)) {
          case TS_CALL_TYPE_SINGLE:
            $pot->add(array(
              'file' => $file,
              'msgid' => $this->formatQuotedString($mid[1]),
              'msgstr' => '',
            ));
            break;

          case TS_CALL_TYPE_PLURAL:
            $pot->add(array(
              'file' => $file,
              'msgid' => $this->formatQuotedString($mid[1]),
              'msgid_plural' => $this->formatQuotedString($this->getPluralString($tokens)),
              'msgstr[0]' => '',
              'msgstr[1]' => '',
            ));
            break;

          case TS_CALL_TYPE_INVALID:
            $this->markerError($file, $line, 'ts', $tokens);
            break;

          default:
            break;

        }
      }

      array_shift($tokens);
    }
  }

  /**
   * Checks the type of the ts() call
   *
   * TS_CALL_TYPE_SINGLE  for a call resulting in calling gettext() (singular)
   * TS_CALL_TYPE_PLURAL  for a call resulting in calling ngettext() (plural)
   * TS_CALL_TYPE_INVALID for an invalid call
   *
   * @param array $tokens the array with tokens from token_get_all()
   *
   * @return int  the integer representing the type of the call
   */
  protected function tsCallType($tokens) {

    // $tokens[0] == 'ts', $tokens[1] == '('
    $mid = $tokens[2];
    $rig = $tokens[3];

    // $mid has to be a T_CONSTANT_ENCAPSED_STRING
    if (!is_array($mid) or ($mid[0] != T_CONSTANT_ENCAPSED_STRING)) {
      fwrite(STDERR, "[011] Invalid marker content");
      return TS_CALL_TYPE_INVALID;
    }

    // if $rig is a closing paren, it's a valid call with no variables,
    // else $rig has to be a comma
    if ($rig == ')') {
      return TS_CALL_TYPE_SINGLE;
    }
    elseif ($rig != ',') {
      fwrite(STDERR, "[010] Invalid marker content");
      return TS_CALL_TYPE_INVALID;
    }

    // if $rig is a comma the next token must be a T_ARRAY call
    // and the next one must be an opening paren
    if ($tokens[4][0] != T_ARRAY or $tokens[5] != '(') {
      fwrite(STDERR, "[009] Invalid marker content");
      return TS_CALL_TYPE_INVALID;
    }

    // if there's an array, it cannot be empty
    // i.e. no ts('string', array()) calls
    if ($tokens[6] == ')') {
      fwrite(STDERR, "[008] Invalid marker content");
      return TS_CALL_TYPE_INVALID;
    }

    // let's iterate through the ts()'s array(...) contents
    $i = 6;
    $haveCount = FALSE;
    $havePlural = FALSE;

    while ($i < count($tokens)) {
      $key = $tokens[$i];
      $doubleArrow = $tokens[$i + 1];
      $value = $tokens[$i + 2];

      // if it's not a => in the middle, it's not an array, really
      if ($doubleArrow[0] != T_DOUBLE_ARROW) {
        // Except for closing parenthesis, because we may have array items with a trailing comma
        // c.f. Drupal coding standards
        if ($tokens[$i + 1] == ')') {
          $i--;
        }
        else {
          fwrite(STDERR, "[007] Invalid marker content: " . $tokens[$i + 1]);
          return TS_CALL_TYPE_INVALID;
        }
      }

      if (!is_array($key)) {
        // ignore?
      }
      elseif ($key[1] == "'count'" or $key[1] == '"count"') {
        // no double count declarations
        if ($haveCount) {
          fwrite(STDERR, "[006] Invalid marker content");
          return TS_CALL_TYPE_INVALID;
        }
        $haveCount = TRUE;

      }
      elseif ($key[1] == "'plural'" or $key[1] == '"plural"') {
        // no double plural declarations
        if ($havePlural) {
          fwrite(STDERR, "[005] Invalid marker content");
          return TS_CALL_TYPE_INVALID;
        }
        $havePlural = TRUE;
        // plural value must be a string
        if ($value[0] != T_CONSTANT_ENCAPSED_STRING) {
          fwrite(STDERR, "[004] Invalid marker content");
          return TS_CALL_TYPE_INVALID;
        }

        // ‘escape’ is a valid param, so accept it
      }
      elseif ($key[1] == "'escape'" or $key[1] == '"escape"') {
        // no-op

        // ‘domain’ is a valid param for extensions, so accept it
      }
      elseif ($key[1] == "'domain'" or $key[1] == '"domain"') {
        // no-op

        // Drupal uses bang-prepended placeholders, so accept them
      }
      elseif (preg_match('/^[\'"]!\d+[\'"]$/', $key[1])) {
        // no-op

        // Drupal also uses words as placeholders, so accept them
      }
      elseif (preg_match('/^[\'"]%[a-z]+[\'"]$/', $key[1])) {
        // no-op

        // no non-number keys (except count and plural, above)
      }
      elseif ($key[0] != T_LNUMBER) {
        fwrite(STDERR, "[003] Invalid marker content");
        return TS_CALL_TYPE_INVALID;

      }

      // let's find where is the next ts()'s array(...) element
      $i += 3;
      // counter for paren pairs *inside* the element's value
      $parenCount = 0;
      while ($i < count($tokens) and ($parenCount > 0 or $tokens[$i] != ',')) {
        if ($parenCount < 1 and ($tokens[$i] == ')' or ($tokens[$i] == ',' and $tokens[$i + 1] == ')'))) {
          // we've reached the last element of the ts()'s array(...)
          break 2;
        }
        if ($tokens[$i] == '(') {
          $parenCount++;
        }
        elseif ($tokens[$i] == ')') {
          $parenCount--;
        }
        // we're still parsing the current element's value, as it can be multi-token:
        // ts('string with a %1 variable', array(1 => $object->method())
        $i++;
      }
      // let's move to the first token of the next element
      $i++;

    }

    // both present - we have a plural!
    if ($haveCount and $havePlural) {
      return TS_CALL_TYPE_PLURAL;

      // only one present - no deal
    }
    elseif ($haveCount or $havePlural) {
      fwrite(STDERR, "[002] Invalid marker content");
      return TS_CALL_TYPE_INVALID;

      // all of the array's keys are of type T_LNUMBER - it's a single call
    }
    else {
      return TS_CALL_TYPE_SINGLE;

    }
  }

  /**
   * formats a string for using it as a $strings array key
   *
   * @param string $str the string up for formatting
   *
   * @return string  the string after formatting
   */
  protected function formatQuotedString($str) {
    $quo = substr($str, 0, 1);
    $str = substr($str, 1, -1);
    if ($quo == '"') {
      $str = stripcslashes($str);
    }
    else {
      $str = strtr($str, array("\\'" => "'", "\\\\" => "\\"));
    }
    return $str;
  }

  /**
   * Gets the plural string from the ts()'s array
   *
   * @param array $tokens the array with tokens from token_get_all()
   *
   * @return string  the string containing the "plural" string from the ts()'s array
   */
  protected function getPluralString($tokens) {
    $plural = "";
    if ($this->tsCallType($tokens) == TS_CALL_TYPE_PLURAL) {
      $i = 6;
      while ($i < count($tokens)) {
        $key = $tokens[$i];
        $doubleArrow = $tokens[$i + 1];
        $value = $tokens[$i + 2];
        if (isset($key[1]) and ($key[1] == "'plural'" or $key[1] == '"plural"') and $doubleArrow[0] == T_DOUBLE_ARROW) {
          $plural = $value[1];
          break;
        }
        $i++;
      }
    }
    return $plural;
  }

  /**
   * writes an error string to STDERR
   *
   * @param string $file the string containing the file the error's in
   * @param string $line the string containing the line the error's in
   * @param string $marker the string with the erroneous function name
   * @param array $tokens the array with the function's tokens
   *
   * @return void
   */
  protected function markerError($file, $line, $marker, $tokens) {
    fwrite(STDERR, "Invalid marker content in $file:$line\n* $marker(");
    array_shift($tokens);
    array_shift($tokens);
    $expr = '';
    $par = 1;
    while (count($tokens) && $par) {
      if (is_array($tokens[0])) {
        $expr .= $tokens[0][1];
      }
      else {
        $expr .= $tokens[0];
        if ($tokens[0] == "(") {
          $par++;
        }
        if ($tokens[0] == ")") {
          $par--;
        }
      }
      array_shift($tokens);
    }
    fwrite(STDERR, $expr);
    fwrite(STDERR, "\n\n");
  }

}
