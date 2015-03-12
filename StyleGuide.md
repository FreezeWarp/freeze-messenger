The following outlines base semantics that all submitted code should be written as, if possible:

  * Indents should be two spaces in length, and never contain \t characters
  * Start brackets should not be on their own line, while end brackets should be.
  * If statements containing a single line of code that is easy to read should be on a single line. They should still use brackets in these cases.
  * Switch statements should:
    * Have the "case" and "break" on one-line if a statement is is easy to read (usually if something is being set equal to something else).
    * Have the "case" and "break" and inner-code on separate lines if the inner-code is not easy to read or contains more than two statements.
    * Use a two linebreaks after each break if the switch-block is complicated, but otherwise use a single linebreak.
    * Use brackets.
  * Comments pertaining to one line of code should be a part of that line.
  * All parenthesis should have one space of padding on the outside, and zero on the inside.
  * All operators (e.g. "==", "+") should be padded with a single space on both sides.
  * Comma-separated arguments should contain one space following each comma.


Examples:

```
if (isset($a, $b, $c)) {
  if ($a == 'b') { // Test Something!
    $a = $a . '?';

    echo 'Hello' . $a; // Say something!
  }
}

switch ($a) {
  case 'a': echo 'Hello'; break;
  case 'b': echo 'Goodbye'; break;
  case 'c':
  if ($iloveu) { echo 'Hello?'; }
  else { echo 'Goodbye?'; }
  break;
}
```