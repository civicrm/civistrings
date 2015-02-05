<?php
namespace Civi\Strings\Parser;

use Civi\Strings\Pot;

/**
 * smarty-extractor.php - rips gettext strings from Smarty {ts} calls
 *
 * ------------------------------------------------------------------------- *
 * This library is free software; you can redistribute it and/or             *
 * modify it under the terms of the GNU Lesser General Public                *
 * License as published by the Free Software Foundation; either              *
 * version 2.1 of the License, or (at your option) any later version.        *
 *                                                                           *
 * This library is distributed in the hope that it will be useful,           *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of            *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU         *
 * Lesser General Public License for more details.                           *
 *                                                                           *
 * You should have received a copy of the GNU Lesser General Public          *
 * License along with this library; if not, write to the Free Software       *
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA *
 * ------------------------------------------------------------------------- *
 *
 * This command line script rips gettext strings from smarty file, and prints
 * them to stdout; this can later be used with the standard gettext tools.
 *
 * Usage:
 * ./smarty-extractor.php <filename or directory> [file2, ...]
 *
 * If a parameter is a directory, the template files within will be parsed.
 *
 * @version   $Id$
 * @link      http://smarty-gettext.sf.net/
 * @author    Sagi Bashari <sagi@boom.org.il>
 * @author    Piotr Szotkowski <shot@civicrm.org>
 * @author    Tim Otten <info [at] civicrm.org>
 * @copyright 2004 Sagi Bashari
 * @license   http://www.gnu.org/licenses/lgpl.html  GNU Lesser General Public License
 */
class SmartyParser {

  /**
   * Rips gettext strings from $file and prints them in C format.
   *
   * @param string $file
   * @param Pot $pot
   */
  public function parse($file, $pot) {
    $content = @file_get_contents($file);

    if (empty($content)) {
      return;
    }

    // smarty open tag
    $ldq = preg_quote('{');

    // smarty close tag
    $rdq = preg_quote('}');

    // smarty command
    $cmd = preg_quote('ts');

    // if there’s a {php} tag, fetch its contents into a file and parse it with php-extractor.php
    $phpTagMatches = array();
    preg_match_all("/{$ldq}\s*(php)\s*([^{$rdq}]*){$rdq}([^{$ldq}]*){$ldq}\/\\1{$rdq}/", $content, $phpTagMatches);
    if (!empty($phpTagMatches[3][0])) {
      throw new \Exception("Not implemented: {php} parsing");
      // we want to create a file with the same path and name, but under the
      // tempdir; this allows the php-extractor.php to add the right comment
      /*
      $phpCode = $phpTagMatches[3][0];
      $tempdir = sys_get_temp_dir();
      $filedir = substr(dirname($file), strlen($root) + 1);
      $filename = basename($file);
      @mkdir("$tempdir/$filedir", 0777, TRUE);
      file_put_contents("$tempdir/$filedir/$filename", "<?php $phpCode ?>");
      passthru("bin/php-extractor.php $tempdir $tempdir/$filedir/$filename");
       */
    }

    preg_match_all("/{$ldq}\s*({$cmd})\s*([^{$rdq}]*){$rdq}([^{$ldq}]*){$ldq}\/\\1{$rdq}/", $content, $matches);

    for ($i = 0; $i < count($matches[0]); $i++) {
      if (preg_match('/plural\s*=\s*["\']?\s*(.[^\"\']*)\s*["\']?/', $matches[2][$i], $match)) {
        $pot->add(array(
          'file' => $file,
          'msgid' => self::fs($matches[3][$i]),
          'msgid_plural' => self::fs($match[1]),
          'msgstr[0]' => '',
          'msgstr[1]' => '',
        ));

      }
      else {
        $pot->add(array(
          'file' => $file,
          'msgid' => self::fs($matches[3][$i]),
          'msgstr' => '',
        ));

      }
    }

    preg_match_all("/{$ldq}\s*(docURL)\s*([^{$rdq}]*){$rdq}/", $content, $matches);

    for ($i = 0; $i < count($matches[0]); $i++) {
      if (preg_match('/text\s*=\s*["\']?\s*(.[^\"\']*)\s*["\']?/', $matches[2][$i], $match)) {
        $pot->add(array(
          'file' => $file,
          'msgid' => self::fs($match[1]),
          'msgstr' => '',
        ));
      }
      if (preg_match('/title\s*=\s*["\']?\s*(.[^\"\']*)\s*["\']?/', $matches[2][$i], $match)) {
        $pot->add(array(
          'file' => $file,
          'msgid' => self::fs($match[1]),
          'msgstr' => '',
        ));
      }
    }
  }

  /**
   * "fix" string - strip slashes, escape and convert new lines to \n
   */
  public static function fs($str) {
    $str = stripslashes($str);
    $str = str_replace('"', '\"', $str);
    $str = str_replace("\n", '\n', $str);
    return $str;
  }

}
