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

require_once('generalFunctions.php');

function htmlParse($text,$bbcodeLevel = 1) {
  global $bbcode;

  global $user;
  global $forumpath;

  $search['shortCode'] = array(
    '/\+([a-zA-Z0-9\ ]+)\+/is',
    '/\=([a-zA-Z0-9\ ]+)\=/is',
    '/\/([a-zA-Z0-9\ ]+)\//is',
    '/\_([a-zA-Z0-9\ ]+)\_/is',
  );

  $search['buis'] = array(
    '/\[(b|strong)\](.+?)\[\/(b|strong)\]/is',
    '/\[(s|strike)\](.+?)\[\/(s|strike)\]/is',
    '/\[(i|em)\](.+?)\[\/(i|em)\]/is',
    '/\[(u)\](.+?)\[\/(u)\]/is',
  );

  $search['link'] = array(
    "/(?<!(\[noparse\]))(?<!(\[img\]))(?<!(\[url\]))((http|https|ftp|data|gopher|sftp|ssh):(\/\/|)(.+?\.|)([a-zA-Z\-]+)\.(com|net|org|co\.uk|co\.jp|info|us|gov)((\/)([^ \n]*)([^\?\.\! \n])|))(?!\[\/url\])(?!\[\/img\])(?!\[\/noparse\])/", // The regex is naturally selective; it improves slightly with each FIM version, but I don't really know how to do it, so I only add to it piece by piece to prevent regressions.
    '/\[url=("|)(.*?)("|)\](.*?)\[\/url\]/is',
    '/\[url\](.*?)\[\/url\]/is',
    '/\[email=("|)(.*?)("|)\](.*?)\[\/email\]/is',
    '/\[email\](.*?)\[\/email\]/is',
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
    '/\[youtubewide\](.*?)\[\/youtubewide\]/is',
    '/\[youtube\](.*?)\[\/youtube\]/is',
  );

  $replace['buis'] = array(
    '<span style="font-weight: bold;">$2</span>',
    '<span style="text-decoration: line-through;">$2</span>',
    '<span style="font-style: oblique;">$2</span>',
    '<span style="text-decoration: underline;">$2</span>',
  );

  $replace['link'] = array(
    '<a href="$4" target="_BLANK">$4</a>',
    '<a href="$2" target="_BLANK">$4</a>',
    '<a href="$1" target="_BLANK">$1</a>',
    '<a href="mailto:$2">$4</a>',
    '<a href="mailto:$1">$1</a>',
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
//    ($bbcodeLevel <= 3 ? '<iframe class="youtube-player" type="text/html" width="420" height="255" src="http://www.youtube.com/embed/$1" frameborder="0"></iframe>' : ($bbcodeLevel <= 13 ? '<a href="http://www.youtube.com/watch?v=$1" target="_BLANK">[Youtube Video]</a>' : '$1')),
//    ($bbcodeLevel <= 3 ? '<iframe class="youtube-player" type="text/html" width="420" height="310" src="http://www.youtube.com/embed/$1" frameborder="0"></iframe>' : ($bbcodeLevel <= 13 ? '<a href="http://www.youtube.com/watch?v=$1" target="_BLANK">[Youtube Video]</a>' : '$1')),
    ($bbcode <= 3 ? '<object width="420" height="255" wmode="opaque"><param name="movie" value="http://www.youtube.com/v/$1=en&amp;fs=1&amp;rel=0&amp;border=0"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/$1&amp;hl=en&amp;fs=1&amp;rel=0&amp;border=0" type="application/x-shockwave-flash" allowfullscreen="true" width="420" height="255" wmode="opaque"></embed></object>' : ($bbcode <= 13 ? '<a href="http://www.youtube.com/watch?v=$1" target="_BLANK">[Youtube Video]</a>' : '$1')),
    ($bbcode <= 3 ? '<object width="425" height="349" wmode="opaque"><param name="movie" value="http://www.youtube.com/v/$1=en&amp;rel=0&amp;border=0"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/$1&amp;hl=en&amp;rel=0&amp;border=0" type="application/x-shockwave-flash" allowfullscreen="true" width="310" height="255" wmode="opaque"></embed></object>' : ($bbcode <= 13 ? '<a href="http://www.youtube.com/watch?v=$1" target="_BLANK">[Youtube Video]</a>' : '$1')),
  );

  if ($bbcode['shortCode'] && $bbcodeLevel <= 16) {
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
    '/\[room\]([0-9]+?)\[\/room\]/is',
  );

  $replace2 = array(
    ($bbcodeLevel <= 9 ? '<span style="color: red; padding: 10px;">* ' . $user['userName'] . ' $1</span>' : '<span>* ' . $user['userName'] . ' $1</span>'),
    '$1',
    '<a href="http://vrim.victoryroad.net/?room=$1">Room $1</a>',
  );

  // Parse BB Code
  $text = preg_replace($search2,$replace2,$text);

  return $text;
}

function nl2vb($message) {
  return str_replace("\n",'{n}',$message);
}

function censor($text,$roomId = false) {
  global $sqlPrefix;

  $words = sqlArr("SELECT w.word, w.severity, w.param, l.id AS listId
FROM {$sqlPrefix}censorLists AS l, {$sqlPrefix}censorWords AS w
WHERE w.listId = l.id AND w.severity = 'replace'",'word');

  if ($roomId) {
    $listsActive = sqlArr("SELECT * FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomId = $roomId",'id');

    if ($listsActive) {
      foreach ($listsActive AS $active) {
        if ($active['status'] == 'unblock') {
          $noBlock[] = $active['listId'];
        }
      }
    }
  }

  if (!$words) return $text;


  foreach ($words AS $word) {
    if ($noBlock) {
      if (in_array($word['listId'],$noBlock)) continue;
    }

    $words2[strtolower($word['word'])] = $word['param'];
    $searchText[] = addcslashes(strtolower($word['word']),'^&|!$?()[]<>\\/.+*');
  }

  $searchText2 = implode('|',$searchText);

  return preg_replace("/(?<!(\[noparse\]))(?<!(\quot))($searchText2)(?!\[\/noparse\])/ie","indexValue(\$words2,strtolower('\\3'))",$text);

  return $text;
}

/* The smilie functions bears some similiarites to its vBulletin equivilent because features used can ONLY be done in this certain way. The function is unique, and was not copylifted.
 * Also, this function sadly doesn't integrate very well into... anything. */
function smilie($text) {
  global $room, $loginMethod, $forumPrefix, $forumUrl;

  switch($loginMethod) {
    case 'vbulletin':
    $smilies = sqlArr("SELECT smilietext, smiliepath, smilieid FROM {$forumPrefix}smilie",'smilieid');

    if (!$smilies) return $text;

    foreach ($smilies AS $id => $smilie) {
      $smilies2[strtolower($smilie['smilietext'])] = $smilie['smiliepath'];
      $searchText[] = addcslashes(strtolower($smilie['smilietext']),'^&|!$?()[]<>\\/.+*');
    }

     $forumUrlS = $forumUrl;
     break;

    case 'phpbb':
    $smilies = sqlArr("SELECT code, smiley_url, smiley_id FROM {$forumPrefix}smilies",'smiley_id');

    if (!$smilies) return $text;

    foreach ($smilies AS $id => $smilie) {
      $smilies2[strtolower($smilie['code'])] = $smilie['smiley_url'];
      $searchText[] = addcslashes(strtolower($smilie['code']),'^&|!$?()[]<>\\/.+*');
    }

    $forumUrlS = $forumUrl . 'images/smilies/';
    break;

    default:
    return $text;
    break;
  }

  $searchText2 = implode('|',$searchText);

  return preg_replace("/(?<!(\[noparse\]))(?<!(quot))(?<!(gt))(?<!(lt))(?<!(apos))(?<!(amp))($searchText2)(?!\[\/noparse\])/ie","'[img=\\3]$forumUrlS' . indexValue(\$smilies2,strtolower('\\7')) . '[/img]'",$text);
}

function indexValue($array,$index) {
  return $array[$index];
}

function htmlwrap($str, $maxLength = 40, $char = '<br />') { /* An adaption of a PHP.net commentor function dealing with HTML for BBCode */
  // Configuration
  $noparseTags = array('img','a');

  // Initialize Variables
  $count = 0;
  $newStr = '';
  $currentTag = '';
  $openTag = false;
  $tagParams = false;

  for ($i = 0; $i < mb_strlen($str,'UTF-8'); $i++) {
   $mb = mb_substr($str,$i,1,'UTF-8'); 
   $noAppend = false;

    if ($mb == '<') { // The character starts a BBcode tag - don't touch nothing.
      $currentTag = '';
      $openTag = true;
    }
    elseif ($mb == '/' && $openTag) {
      $endTag = true;
    }
    elseif (($openTag) && ($mb == ' ')) {
      $tagParams = true;
    }
    elseif (($openTag) && (!$endTag) && ($tagParams == false) && ($mb != '>')) {
      $currentTag .= $mb;
    }
    elseif (($openTag) && ($mb == '>')) { // And the BBCode tag is done again - we can touch stuffz.
      $endTag = false;
      $openTag = false;
      $tagParams = false;
    }
    else {
      if ($currentTag == 'a' && $count >= ($maxLength - 1)) {
        $noAppend = true;
        if (!$elipse) {
          $newStr .= '...';
        }
        $elipse = true;
      }
      elseif (!$openTag) {
        if ($mb == ' ' || $mb == "\n") { // The character is a space.
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

    if (!$noAppend) {
      $newStr .= $mb;
    }

  }

  return $newStr;
}

function finalParse($message) {
  global $room;

  $messageRaw = $message; // Parses the sources for MySQL.
  $messageHtml = nl2br(htmlwrap(htmlParse(censor(vrim_encodeXML($message),$room['id']),$room['options']),30,' ')); // Parses for browser or HTML rendering.
  $messageApi = nl2vb(smilie($message,$room['bbcode'])); // Not yet coded, you see.

  return array($messageRaw, $messageHtml, $messageApi);
}


function sendMessage($messageText,$user,$room,$flag = '') {
  global $sqlPrefix, $parseFlags, $salts, $encrypt, $loginMethod, $sqlUserGroupTableCols, $sqlUserGroupTable;

  $user['userName'] = mysqlEscape($user['userName']);

  $ip = mysqlEscape($_SERVER['REMOTE_ADDR']); // Get the IP address of the user.
  $flag = mysqlEscape($flag);

  if ($parseFlags && in_array($flag,array('image','video','link','email'))) {
    $messageRaw = $messageText;
    $messageApi = $messageText;

    switch ($flag) {
      case 'image':
      $messageHtml = "<a href=\"$messageText\"><img src=\"$messageText\" alt=\"\" style=\"max-height: 300px; max-width: 300px;\" /></a>";
      break;

      case 'video':
      $messageHtml = preg_replace('/^http\:\/\/(www\.|)youtube\.com\/(.*?)?v=(.+)(&|)(.*?)$/',($bbcode <= 3 ? '<object width="420" height="255" wmode="opaque"><param name="movie" value="http://www.youtube.com/v/$3=en&amp;fs=1&amp;rel=0&amp;border=0"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/$3&amp;hl=en&amp;fs=1&amp;rel=0&amp;border=0" type="application/x-shockwave-flash" allowfullscreen="true" width="420" height="255" wmode="opaque"></embed></object>' : ($bbcode <= 13 ? '<a href="http://www.youtube.com/watch?v=$3" target="_BLANK">[Youtube Video]</a>' : '$3')),$messageText);
      break;

      case 'link':
      $messageHtml = "<a href=\"$messageText\">$messageText</a>";
      break;

      case 'email':
      $messageHtml = "<a href=\"mailto:$messageText\">$messageText</a>";
      break;
    }
  }
  else {
    $message = finalParse($messageText);
    list($messageRaw,$messageHtml,$messageApi) = $message;
  }

  if ($loginMethod == 'vbulletin' && $user['displaygroupid']) {
    $group = sqlArr("SELECT * FROM {$sqlUserGroupTable} AS g WHERE g.{$sqlUserGroupTableCols[groupid]} = $user[displaygroupid]");
  }
  elseif ($loginMethod == 'phpbb') {
    $group['opentag'] = "<span style=\"color: #$user[colour];\">";
    $group['closetag'] = "</span>";
  }

  $messageHtmlCache = $messageHtml;

  if ($salts && $encrypt) {
    $salt = end($salts);
    $saltNum = key($salts);

    $iv_size = mcrypt_get_iv_size(MCRYPT_3DES,MCRYPT_MODE_CBC);
    $iv = base64_encode(mcrypt_create_iv($iv_size, MCRYPT_RAND));

    list($messages,$iv,$saltNum) = vrim_encrypt(array($messageRaw,$messageHtml,$messageApi));
    list($messageRaw,$messageHtml,$messageApi) = $messages;
  }

  $messageRaw = mysqlEscape($messageRaw);
  $messageHtml = mysqlEscape($messageHtml);
  $messageHtmlCache = mysqlEscape($messageHtmlCache);
  $messageApi = mysqlEscape($messageApi);


  mysqlQuery("INSERT INTO {$sqlPrefix}messages (user, room, rawText, htmlText, apiText, salt, iv, microtime, ip, flag) VALUES ($user[userId], $room[id], '$messageRaw', '$messageHtml', '$messageApi', '$saltNum', '$iv', '" . microtime(true) . "', '$ip', '$flag')");
  $messageid = mysqlInsertId();

  mysqlQuery("INSERT INTO {$sqlPrefix}messagesCached (messageid, roomId, userId, userName, userGroup, groupFormatStart, groupFormatEnd, time, htmlText, flag) VALUES ($messageid, $room[id], $user[userId], '$user[userName]', $user[displaygroupid], '$group[opentag]', '$group[closetag]', NOW(), '$messageHtmlCache', '$flag')");
  $messageid2 = mysqlInsertId();

  if ($messageid2 > 100) {
    mysqlQuery("DELETE FROM {$sqlPrefix}messagesCached WHERE id <= " . ($messageid2 - 100));
  }

  mysqlQuery("UPDATE {$sqlPrefix}rooms SET lastMessageTime = NOW(), lastMessageId = $messageid WHERE id = $room[id]");
  mysqlQuery("INSERT INTO {$sqlPrefix}roomStats (userId, roomId, messages) VALUES ($user[userId], $room[id], 1) ON DUPLICATE KEY UPDATE messages = messages + 1");
}
?>