/* Text Entry Functions
 * Copyright © 2008-2011 Joseph T. Parsons
 * Licensed Under GPLv3, as per Licensing Terms of the Fliler Project
 * Original Source @ http://code.google.com/p/fliler/source/browse/edit_file.js */

function addPTag(start,end) {
  var ta = document.getElementById('messageInput');
  
  var bits = getSelection(ta);

  if (!bits) {
    alert('You haven\'t selected any text to format (or your browser does not support this function).'); 
  }
  else if (!bits[2].match(/(^[a-zA-Z0-9\ ]+)$/)) {
    alert('The selected text contains characters that can not be parsed.'); 
  }
  else {
     if (!bits[1].match(/ $/)) start = ' ' + start;
     if (!bits[3].match(/^ /)) end = end + ' ';

     ta.value = bits[1] + start + bits[2] + end + bits[3];
  }
}

function getSelection(ta) {
  var bits = [ta.value,'','',''];
  if(document.selection) {
    var vs = '#$%^%$#';
    var tr = document.selection.createRange()
    if(tr.parentElement() != ta) return false;
    bits[2] = tr.text;
    tr.text = vs;
    fb = ta.value.split(vs);
    tr.moveStart('character',-vs.length);
    tr.text = bits[2];
    bits[1] = fb[0];
    bits[3] = fb[1];
  }

  else {
    if(ta.selectionStart == ta.selectionEnd) return false;
    bits = (new RegExp('([\x00-\xff]{'+ta.selectionStart+'})([\x00-\xff]{'+(ta.selectionEnd - ta.selectionStart)+'})([\x00-\xff]*)')).exec(ta.value);
  }

  return bits;
}

/*function addPTag2(tag,att) {
  var ta = document.getElementById('messageInput');
  bits = getSelection(ta);
  if(bits) {
    ta.value = bits[1] + '[' + tag + '=' + att + ']' + bits[2] + '[/' + tag + ']' + bits[3];
  }
}

function addPTag2(tag,att) {
  var ta = document.getElementById('messageInput');
  
  if (!ta) {
    alert('You haven\'t selected any text to format.'); 
  }
  else if (!ta.match(/([a-zA-Z0-9\ ]+)/)) {
    alert('The selected text contains characters that can not be parsed.'); 
  }
  else {
    bits = getSelection(ta);
    if(bits) {
      if (!bits[1].match(/^ /)) bits[1] = ' ' + bits[1];
      if (!bits[3].match(/ $/)) bits[3] = bits[3] + ' ';
      ta.value = bits[1] + '[' + tag + '=' + att + ']' + bits[2] + '[/' + tag + ']' + bits[3];
    }
  }
}*/

/*function insertAtCursor(myValue) {
  text2 = document.getElementById('messageInput');
  if (document.selection) { // Support for IE
    text2.focus();
    sel = document.selection.createRange();
    sel.text = myValue;
  }
  else if (text2.selectionStart == '0') { // Support for Firefox
    var startPos = text2.selectionStart;
    var endPos = text2.selectionEnd;
    text2.value = text2.value.substring(0, startPos) + myValue + text2.value.substring(endPos, text2.value.length);
  }
  else {
    text2.value += myValue;
  }
}*/