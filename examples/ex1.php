<?php
// These are never executed, you can run extractor.php on itself to test it
// $b, $f, $n, $s3 and $s4 should break
$a = ts("Test string 1");
//$b = ts("Test string 2 %string", array("%string" => "how do you do"));
$c = ts('Test string 3');
$d = ts("Special\ncharacters");
$e = ts('Special\ncharacters');
//$f = ts("Embedded $variable");
$g = ts('Embedded $variable');
$h = ts("more \$special characters");
$i = ts('even more \$special characters');
$j = ts("Mixed 'quote' \"marks\"");
$k = ts('Mixed "quote" \'marks\'');
$l = ts('This is some repeating text');
$m = ts("This is some repeating text");
function embedded_function_call() {
  return 12;
}
$n = ts('An example with Drupal-style trailing comma is as %1 as %2', array(
  1 => 1+2 . 'good' . ($num++),
  2 => pick(array('gold', 'chocolate', 'other')),
));

//$n = ts(embedded_function_call());
$s1 = ts('a test with a %1 variable, and %2 another one', array(1 => 'one', 2 => 'two'));
$s2 = ts('%3 – a plural test, %count frog', array(
  'count' => 7,
  "plural" => 'a plural test, %count frogs',
  3 => 'three'
));
//$s3 = ts('a test – no count', array('plural' => 'No count here'));
//$s4 = ts('a test – no plural', array('count' => 42));
$s5 = ts('a test for multitoken element value', array(1 => $c . $d));
$t1 = ts("This is some text with context", array('context' => 'testcontext'));
$t2 = ts("This is some text with %1 context", array('context' => 'other_context', 1 => 'more'));
$t3 = ts("This is some text %1 context", array(1 => 'with even more', 'context' => 'testcontext'));

$ext1 = E::ts('The string from the extension');
$ext2 = E::ts('The contextualized string from the extension', ['context' => 'testcontext']);
