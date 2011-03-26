/* PHP str_replace for Javascript
 * http://www.rdlt.com/javascript-str_replace-equivalent.html */

function str_replace (search, replace, subject) {
  var result = "";
  var oldi = 0;
  for (i = subject.indexOf (search); i > -1; i = subject.indexOf (search, i)) {
    result += subject.substring (oldi, i);
    result += replace;
    i += search.length;
    oldi = i;
  }
  return result + subject.substring (oldi, subject.length);
}