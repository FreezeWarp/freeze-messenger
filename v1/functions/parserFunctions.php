<?php
function htmlParse($text,$bbcode = 1) {
  global $user;
  global $forumpath;

  $search1 = array(
    '/ \+([a-zA-Z0-9\ ]+)\+ /is',
    '/ \=([a-zA-Z0-9\ ]+)\= /is',
    '/ \/([a-zA-Z0-9\ ]+)\/ /is',
    '/ \_([a-zA-Z0-9\ ]+)\_ /is',
  );

  $replace1 = array(
    ($bbcode <= 16 ? ' <span style="font-weight: bold;">$1</span> ' : ' $1 '),
    ($bbcode <= 16 ? ' <span style="text-decoration: line-through;">$1</span> ' : ' $1 '),
    ($bbcode <= 16 ? ' <span style="font-style: oblique;">$1</span> ' : ' $1 '),
    ($bbcode <= 16 ? ' <span style="text-decoration: underline;">$1</span> ' : ' $1 '),
  );

  $search2 = array(
    '/^\/me (.+?)$/i',
    '/\[url=("|)(.*?)("|)\](.*?)\[\/url\]/is',
    '/\[url\](.*?)\[\/url\]/is',
    '/\[email=("|)(.*?)("|)\](.*?)\[\/email\]/is',
    '/\[email\](.*?)\[\/email\]/is',
    '/\[img\](.*?)\[\/img\]/is',
    '/\[img=("|)(.*?)("|)\](.*?)\[\/img\]/is',
    '/^\[youtubewide\](.*?)\[\/youtubewide\]$/is',
    '/^\[youtube\](.*?)\[\/youtube\]$/is',
    '/\[noparse\](.*?)\[\/noparse\]/is',
  );

  $replace2 = array(
    ($bbcode <= 9 ? '<span style="color: red; padding: 10px;">* ' . $user['username'] . ' $1</span>' : '* ' . $user['username'] . ' $1</span>'),
    ($bbcode <= 13 ? '<a href="$2" target="_BLANK">$4</a>' : '$4'),
    ($bbcode <= 13 ? '<a href="$1" target="_BLANK">$1</a>' : '$1'),
    ($bbcode <= 13 ? '<a href="mailto:$2">$4</a>' : '$4'),
    ($bbcode <= 13 ? '<a href="mailto:$1">$1</a>' : '$1'),
    ($bbcode <= 5 ? '<a href="$1" target="_BLANK"><img src="$1" alt="image" class="embedImage" /></a>' : ($bbcode <= 13 ? '<a href="$1" target="_BLANK">$1</a>' : '$1')),
    ($bbcode <= 13 ? '<a href="$4" target="_BLANK"><img src="$4" alt="$2" class="embedImage" /></a>' : ($bbcode <= 13 ? '<a href="$4" target="_BLANK">$2</a>' : '$4')),
    ($bbcode <= 2 ? '<object width="420" height="255"><param name="movie" value="http://www.youtube.com/v/$1=en&amp;fs=1&amp;rel=0&amp;border=0"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/$1&amp;hl=en&amp;fs=1&amp;rel=0&amp;border=0" type="application/x-shockwave-flash" allowfullscreen="true" width="420" height="255"></embed></object>' : ($bbcode <= 13 ? '<a href="http://www.youtube.com/watch?v=$1" target="_BLANK">[Youtube Video]</a>' : '$1')),
    ($bbcode <= 2 ? '<object width="425" height="349"><param name="movie" value="http://www.youtube.com/v/$1=en&amp;rel=0&amp;border=0"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/$1&amp;hl=en&amp;rel=0&amp;border=0" type="application/x-shockwave-flash" allowfullscreen="true" width="310" height="255"></embed></object>' : ($bbcode <= 13 ? '<a href="http://www.youtube.com/watch?v=$1" target="_BLANK">[Youtube Video]</a>' : '$1')),
    '$1',
  );

  $text = preg_replace($search1,$replace1,$text);

  // Parse BB Code
  foreach ($search2 as $key => $val) {
    if (preg_match($val,$text)) {
      $text = preg_replace($val,$replace2[$key],$text);
    }
  }

  return $text;
}

function nl2vb($message) {
  return str_replace("\n",'{n}',$message);
}

function censor($text,$settings = 2) {
//  return str_ireplace(array('fuck','faggot','cunt','damn','cock','shit','douche','f*** you','hammer time','learn to spell'),array('f***','f*****','c**t','hoover dam','c**k','$h*!','d*****','f*** me','[youtube]otCpCn0l4Wo[/youtube]','[youtube]UHysmKGGLA8[/youtube]'),$text);
  if (($settings & 2) == false) {
    $text = str_ireplace(
              array('i blame freeze',
                    'fuck',
                    'faggot',
                    'cunt',
                    'damn',
                    'bitch',
                    'cock',
                    'shit',
                    'douche',
                    'porn',
                    '  '),
              array(':ibf:',
                    'f***',
                    'f*****',
                    'c**t',
                    'd@&#',
                    'b!&¢@',
                    'c**k',
                    '$h*!',
                    'd*****',
                    'pr0n',
                    ' &nbsp;'),
                         $text);
  }
  return $text;
}

/* The smilie functions bears some similiarites to its vBulletin equivilent because features used can ONLY be done in this certain way. The function is unique, and was not copylifted. */
function smilie($text,$bbcode = 1) {
  global $room;

  if ($bbcode <= 5) {
    $smilies = sqlArr("SELECT smilietext, smiliepath, smilieid FROM smilie",'smilieid');
    foreach ($smilies AS $id => $smilie) {
      $smilies2[strtolower($smilie['smilietext'])] = $smilie['smiliepath'];
      $searchText[] = addcslashes(strtolower($smilie['smilietext']),'^&|!$?()[]<>\\/.+*');
    }
  }

  $searchText2 = implode('|',$searchText);

  return preg_replace("/(?<!(\[noparse\]))(?<!(\quot))($searchText2)(?!\[\/noparse\])/ie","'[img=\\3]http://www.victoryroad.net/' . indexValue(\$smilies2,strtolower('\\3')) . '[/img]'",$text);

}

function indexValue($array,$index) {
  return $array[$index];
}

function htmlwrap($str, $maxLength, $char = '<br />') { /* An adaption of a PHP.net commentor function dealing with HTML for BBCode */
  // Configuration
  $noparseTags = array('img','url');

  // Initialize Variables
  $count = 0;
  $newStr = '';
  $currentTag = '';
  $openTag = false;

  for ($i = 0; $i < strlen($str); $i++) {
    $newStr .= $str[$i];
    if ($str[$i] == '['){ // The character starts a BBcode tag - don't touch nothing.
      $openTag = true;
      $currentTag = '';
      continue;
    }
    elseif (($openTag) && ($str[$i] != ']')) {
      $currentTag .= $str[$i];
    }
    elseif (($openTag) && ($str[$i] == ']')) { // And the BBCode tag is done again - we can touch stuffz.
      $openTag = false;
      continue;
    }

    if (!$openTag && !in_array($currentTag,$noparseTags)) {
      if ($str[$i] == ' ' || $str[$i] == "\n"){ // The character is a space.
        if ($count != 0) {
          $count = 0; // Because the character is a space, we should reset the count back to 0.
          $lastspace = $count + 1;
        }
      }
      else {
        $count++; // Increment the current count.
        if($count == $maxLength) { // We've reached the limit; add a break and reset the count back to 0.
          $newStr .= $char;
          $count = 0;
        }
      }
    }
  }

  return $newStr;
}

function urlParse($a) {
  return preg_replace('/^((http|https|ftp|data|gopher|sftp|ssh):(\/\/|)(.+?\.|)([a-zA-Z]+)\.(com|net|org|co\.uk|co\.jp|info|us|gov)((\/)([^ ]+?)))$/','[url]$1[/url]',$a);
}

function finalParse($message) {
  global $room;

  $salt = 'Fr33d0m*';
  $saltNum = 101;

  $iv_size = mcrypt_get_iv_size(MCRYPT_3DES,MCRYPT_MODE_CBC);
  $iv = base64_encode(mcrypt_create_iv($iv_size, MCRYPT_RAND));

  $messageRaw = $message; // Parses the sources for MySQL.
  $messageHtml = nl2br(htmlParse(smilie(htmlwrap(urlParse(censor(htmlspecialchars($message),$room['options'])),30,' '),$room['bbcode']),$room['bbcode'])); // Parses for browser or HTML rendering.
  $messageVBnet = nl2vb(smilie($message,$room['bbcode'])); // Not yet coded, you see.

  $messageRaw = mysqlEscape(base64_encode(rtrim(mcrypt_encrypt(MCRYPT_3DES, $salt, $messageRaw, MCRYPT_MODE_CBC, base64_decode($iv)),"\0")));
  $messageHtml = mysqlEscape(base64_encode(rtrim(mcrypt_encrypt(MCRYPT_3DES, $salt, $messageHtml, MCRYPT_MODE_CBC, base64_decode($iv)),"\0")));
  $messageVBnet = mysqlEscape(base64_encode(rtrim(mcrypt_encrypt(MCRYPT_3DES, $salt, $messageVBnet, MCRYPT_MODE_CBC, base64_decode($iv)),"\0")));

  return array($messageRaw,$messageHtml,$messageVBnet,$saltNum,$iv);
}
?>