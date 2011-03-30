<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

function htmlParse($text,$bbcodeLevel = 1) {
  global $bbcode;

  global $user;
  global $forumpath;

  $search['shortCode'] = array(
    '/ \+([a-zA-Z0-9\ ]+)\+ /is',
    '/ \=([a-zA-Z0-9\ ]+)\= /is',
    '/ \/([a-zA-Z0-9\ ]+)\/ /is',
    '/ \_([a-zA-Z0-9\ ]+)\_ /is',
  );

  $search['buis'] = array(
    '/\[(b|strong)\](.+?)\[\/(b|strong)\]/is',
    '/\[(s|strike)\](.+?)\[\/(s|strike)\]/is',
    '/\[(i|em)\](.+?)\[\/(i|em)\]/is',
    '/\[u\](.+?)\[\/u\]/is',
  );

  $search['link'] = array(
    '/\[url=("|)(.*?)("|)\](.*?)\[\/url\]/is',
    '/\[url\](.*?)\[\/url\]/is',
    '/\[email=("|)(.*?)("|)\](.*?)\[\/email\]/is',
    '/\[email\](.*?)\[\/email\]/is',
    '/(?<!(\[noparse\]))(?<!(\[img\]))(?<!(\[url\]))((http|https|ftp|data|gopher|sftp|ssh):(\/\/|)(.+?\.|)([a-zA-Z]+)\.(com|net|org|co\.uk|co\.jp|info|us|gov)((\/)([^ ]*)|))(?!\[\/url\])(?!\[\/img\])(?!\[\/noparse\])/',
  );

  $search['colour'] = array(
    '/\[(color|colour)=("|)(.*?)("|)\](.*?)\[\/(color|colour)\]/is',
    '/\[(hl|highlight|bg|background)=("|)(.*?)("|)\](.*?)\[\/(hl|highlight|bg|background)\]/is',
  );

  $search['image'] = array(
    '/\[img\](.*?)\[\/img\]/is',
    '/\[img=("|)(.*?)("|)\](.*?)\[\/img\]/is',
  );

  $search['video'] = array(
    '/^\[youtubewide\](.*?)\[\/youtubewide\]$/is',
    '/^\[youtube\](.*?)\[\/youtube\]$/is',
  );

  $replace['buis'] = array(
    '<span style="font-weight: bold;">$1</span>',
    '<span style="text-decoration: line-through;">$1</span>',
    '<span style="font-style: oblique;">$1</span>',
    '<span style="text-decoration: underline;">$1</span>',
  );

  $replace['link'] = array(
    '<a href="$2" target="_BLANK">$4</a>',
    '<a href="$1" target="_BLANK">$1</a>',
    '<a href="mailto:$2">$4</a>',
    '<a href="mailto:$1">$1</a>',
    '<a href="$4" target="_BLANK">$4</a>',
  );

  $replace['colour'] = array(
    '<span style="color: $3;">$5</span>',
    '<span style="background-color: $3;">$5</span>',
  );

  $replace['image'] = array(
    ($bbcodeLevel <= 5 ? '<a href="$1" target="_BLANK"><img src="$1" alt="image" class="embedImage" /></a>' : ($bbcodeLevel <= 13 ? '<a href="$1" target="_BLANK">$1</a>' : '$1')),
    ($bbcodeLevel <= 13 ? '<a href="$4" target="_BLANK"><img src="$4" alt="$2" class="embedImage" /></a>' : ($bbcodeLevel <= 13 ? '<a href="$2" target="_BLANK">$4</a>' : '$2')),
  );

  $replace['video'] = array(
    ($bbcodeLevel <= 2 ? '<object width="420" height="255"><param name="movie" value="http://www.youtube.com/v/$1=en&amp;fs=1&amp;rel=0&amp;border=0"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/$1&amp;hl=en&amp;fs=1&amp;rel=0&amp;border=0" type="application/x-shockwave-flash" allowfullscreen="true" width="420" height="255"></embed></object>' : ($bbcodeLevel <= 13 ? '<a href="http://www.youtube.com/watch?v=$1" target="_BLANK">[Youtube Video]</a>' : '$1')),
    ($bbcodeLevel <= 2 ? '<object width="425" height="349"><param name="movie" value="http://www.youtube.com/v/$1=en&amp;rel=0&amp;border=0"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/$1&amp;hl=en&amp;rel=0&amp;border=0" type="application/x-shockwave-flash" allowfullscreen="true" width="310" height="255"></embed></object>' : ($bbcodeLevel <= 13 ? '<a href="http://www.youtube.com/watch?v=$1" target="_BLANK">[Youtube Video]</a>' : '$1')),
  );

  if ($bbcode['shortCode'] && $bbCodeLevel <= 16) {
    $text = preg_replace($search['shortCode'],$replace['buis'],$text);
  }
  if ($bbcode['buis'] && $bbcodeLevel <= 16) {
    $text = preg_replace($search['buis'],$replace['buis'],$text);
  }
  if ($bbcode['colour'] && $bbcodeLevel <= 11) {
    $text = preg_replace($search['colour'],$replace['colour'],$text);
  }
  if ($bbcode['link'] && $bbcodeLevel <= 9) {
    $text = preg_replace($search['link'],$replace['link'],$text);
  }
  if ($bbcode['image'] && $bbcodeLevel <= 5) {
    if ($bbcode['emoticon']) $text = smilie($text);

    $text = preg_replace($search['image'],$replace['image'],$text);
  }
  if ($bbcode['video'] && $bbcodeLevel <= 3) {
    $text = preg_replace($search['video'],$replace['video'],$text);
  }

  $search2 = array(
    '/^\/me (.+?)$/i',
    '/\[noparse\](.*?)\[\/noparse\]/is',
  );

  $replace2 = array(
    ($bbcodeLevel <= 9 ? '<span style="color: red; padding: 10px;">* ' . $user['username'] . ' $1</span>' : '* ' . $user['username'] . ' $1</span>'),
    '$1',
  );

  // Parse BB Code
  $text = preg_replace($search2,$replace2,$text);

  return $text;
}

function nl2vb($message) {
  return str_replace("\n",'{n}',$message);
}

function censor($text,$roomid = false) {
  global $sqlPrefix;

  $words = sqlArr("SELECT w.word, w.severity, w.param
FROM {$sqlPrefix}censorLists AS l, {$sqlPrefix}censorWords AS w
WHERE w.listid = l.id AND w.severity = 'replace'",'word');

  if (!$words) return $text;


  foreach ($words AS $word) {
    $words2[strtolower($word['word'])] = $word['param'];
    $searchText[] = addcslashes(strtolower($word['word']),'^&|!$?()[]<>\\/.+*');
  }

  $searchText2 = implode('|',$searchText);

  return preg_replace("/(?<!(\[noparse\]))(?<!(\quot))($searchText2)(?!\[\/noparse\])/ie","indexValue(\$words2,strtolower('\\3'))",$text);

  return $text;
}

/* The smilie functions bears some similiarites to its vBulletin equivilent because features used can ONLY be done in this certain way. The function is unique, and was not copylifted. */
function smilie($text) {
  global $room;

  $smilies = sqlArr("SELECT smilietext, smiliepath, smilieid FROM smilie",'smilieid');
  foreach ($smilies AS $id => $smilie) {
    $smilies2[strtolower($smilie['smilietext'])] = $smilie['smiliepath'];
    $searchText[] = addcslashes(strtolower($smilie['smilietext']),'^&|!$?()[]<>\\/.+*');
  }

  $searchText2 = implode('|',$searchText);

  return preg_replace("/(?<!(\[noparse\]))(?<!(\quot))($searchText2)(?!\[\/noparse\])/ie","'[img=\\3]http://www.victoryroad.net/' . indexValue(\$smilies2,strtolower('\\3')) . '[/img]'",$text);
}

function indexValue($array,$index) {
  return $array[$index];
}

function htmlwrap($str, $maxLength, $char = '<br />') { /* An adaption of a PHP.net commentor function dealing with HTML for BBCode */
  // Configuration
  $noparseTags = array('img','a','youtube');

  // Initialize Variables
  $count = 0;
  $newStr = '';
  $currentTag = '';
  $openTag = false;
  $tagParams = false;

  for ($i = 0; $i < strlen($str); $i++) {
    $newStr .= $str[$i];

    if ($str[$i] == '<') { // The character starts a BBcode tag - don't touch nothing.
      $currentTag = '';
      $openTag = true;
      continue;
    }
    elseif (($openTag) && ($str[$i] == ' ')) {
      $tagParams = true;
    }
    elseif (($openTag) && !$tagParams && ($str[$i] != '>')) {
      $currentTag .= $str[$i];
      continue;
    }
    elseif (($openTag) && ($str[$i] == '>')) { // And the BBCode tag is done again - we can touch stuffz.
      $openTag = false;
      continue;
    }

    if (!$openTag && !in_array($currentTag, $noparseTags)) {
      if ($str[$i] == ' ' || $str[$i] == "\n") { // The character is a space.
        $count = 0; // Because the character is a space, we should reset the count back to 0.
      }
      else {
        $count++; // Increment the current count.
        if ($count == $maxLength) { // We've reached the limit; add a break and reset the count back to 0.
          $newStr .= $char;
          $count = 0;
        }
      }
    }
  }

  return $newStr;
}

function finalParse($message) {
  global $room, $salts;

  $salt = end($salts);
  $saltNum = key($salts);

  $iv_size = mcrypt_get_iv_size(MCRYPT_3DES,MCRYPT_MODE_CBC);
  $iv = base64_encode(mcrypt_create_iv($iv_size, MCRYPT_RAND));

  $messageRaw = $message; // Parses the sources for MySQL.
  $messageHtml = nl2br(htmlwrap(htmlParse(censor(htmlspecialchars($message),$room['roomid']),$room['options']),30,' ')); // Parses for browser or HTML rendering.
  $messageVBnet = nl2vb(smilie($message,$room['bbcode'])); // Not yet coded, you see.

  $messageRaw = mysqlEscape(base64_encode(rtrim(mcrypt_encrypt(MCRYPT_3DES, $salt, $messageRaw, MCRYPT_MODE_CBC, base64_decode($iv)),"\0")));
  $messageHtml = mysqlEscape(base64_encode(rtrim(mcrypt_encrypt(MCRYPT_3DES, $salt, $messageHtml, MCRYPT_MODE_CBC, base64_decode($iv)),"\0")));
  $messageVBnet = mysqlEscape(base64_encode(rtrim(mcrypt_encrypt(MCRYPT_3DES, $salt, $messageVBnet, MCRYPT_MODE_CBC, base64_decode($iv)),"\0")));

  return array($messageRaw, $messageHtml, $messageVBnet, $saltNum, $iv);
}

function sendMessage($messageText,$user,$room,$flag = '') {
  global $sqlPrefix;

  $message = finalParse($messageText);
  list($messageRaw,$messageHtml,$messageVBnet,$saltNum,$iv) = $message;
  $ip = mysqlEscape($_SERVER['REMOTE_ADDR']); // Get the IP address of the user.
  $flag = mysqlEscape($flag);

  mysqlQuery("INSERT INTO {$sqlPrefix}messages (user, room, rawText, htmlText, vbText, salt, iv, microtime, ip, flag) VALUES ($user[userid], $room[id], '$messageRaw', '$messageHtml', '$messageVBnet', $saltNum, '$iv', '" . microtime(true) . "', '$ip', '$flag')");
  mysqlQuery("UPDATE {$sqlPrefix}rooms SET lastMessageTime = NOW() WHERE id = $room[id]");
}
?>