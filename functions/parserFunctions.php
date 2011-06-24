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



/**
* Parses text from defined BBCode.
* TODO: Support DB-BBCode (likely FIM4).
*
* @param string $text - Text to format with HTML.
* @param integer $bbcodeLevel - The level of bbcode to parse. See documentation for values.
* @return string - Parsed text.
* @author Joseph Todd Parsons
*/

function fimParse_htmlParse($text,$bbcodeLevel = 1) {
  global $bbcode, $user, $forumpath;

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
    ($bbcode <= 3 ? '<object width="420" height="255" wmode="transparent"><param name="movie" value="http://www.youtube.com/v/$1=en&amp;fs=1&amp;rel=0&amp;border=0"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/$1&amp;hl=en&amp;fs=1&amp;rel=0&amp;border=0" type="application/x-shockwave-flash" allowfullscreen="true" width="420" height="255" wmode="opaque"></embed></object>' : ($bbcode <= 13 ? '<a href="http://www.youtube.com/watch?v=$1" target="_BLANK">[Youtube Video]</a>' : '$1')),
    ($bbcode <= 3 ? '<object width="425" height="349" wmode="transparent"><param name="movie" value="http://www.youtube.com/v/$1=en&amp;rel=0&amp;border=0"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/$1&amp;hl=en&amp;rel=0&amp;border=0" type="application/x-shockwave-flash" allowfullscreen="true" width="310" height="255" wmode="opaque"></embed></object>' : ($bbcode <= 13 ? '<a href="http://www.youtube.com/watch?v=$1" target="_BLANK">[Youtube Video]</a>' : '$1')),
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
    if ($bbcode['emoticon']) $text = fimParse_smilieParse($text);

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



/**
* Parses and censors phrases. Requires an active MySQL connection.
*
* @param string $text - The text to censor.
* @param int $roomId - The ID of the room's censors. Uses general censors if not available (thus not using any black/white lists).
* @return string - Censored text.
* @author Joseph Todd Parsons
*/

function fimParse_censorParse($text,$roomId = 0) {
  global $sqlPrefix;

  $words = dbRows("SELECT w.word, w.severity, w.param, l.listId AS listId
FROM {$sqlPrefix}censorLists AS l, {$sqlPrefix}censorWords AS w
WHERE w.listId = l.listId AND w.severity = 'replace'",'word');

  if ($roomId) {
    $listsActive = dbRows("SELECT * FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomId = $roomId",'id');

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



/**
* Parsers predefined smilies on vBulletin and PHPBB systems. Support planned for Vanilla.
*
* @param string $text - The text to parse.
* @return string - Parsed text.
* @author Joseph Todd Parsons
*/
function fimParse_smilieParse($text) {
  global $room, $loginMethod, $forumPrefix, $forumUrl;

  switch($loginMethod) {
    case 'vbulletin':
    $smilies = dbRows("SELECT smilietext, smiliepath, smilieid FROM {$forumPrefix}smilie",'smilieid');

    if (!$smilies) return $text;

    foreach ($smilies AS $id => $smilie) {
      $smilies2[strtolower($smilie['smilietext'])] = $smilie['smiliepath'];
      $searchText[] = addcslashes(strtolower($smilie['smilietext']),'^&|!$?()[]<>\\/.+*');
    }

     $forumUrlS = $forumUrl;
     break;

    case 'phpbb':
    $smilies = dbRows("SELECT code, smiley_url, smiley_id FROM {$forumPrefix}smilies",'smiley_id');

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



/**
* Wraps HTML with specific support for UTF-8 and URLs.
*
* @param string $html - HTML text
* @param integer $maxLength - Length after which to wrap.
* @param string $chat - String to wrap with.
* @return string - Formatted data.
* @author Joseph Todd Parsons
*/

function fimParse_htmlWrap($html, $maxLength = 80, $char = '<br />') { /* An adaption of a PHP.net commentor function dealing with HTML for BBCode */
  // Configuration
  $noparseTags = array('img','a');

  // Initialize Variables
  $count = 0;
  $newHtml = '';
  $currentTag = '';
  $openTag = false;
  $tagParams = false;

  for ($i = 0; $i < mb_strlen($html,'UTF-8'); $i++) {
   $mb = mb_substr($html,$i,1,'UTF-8');
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
          $newHtml .= '...';
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
            $newHtml .= $char;
            $count = 0;
          }
        }
      }
    }

    if (!$noAppend) {
      $newHtml .= $mb;
    }

  }

  return $newHtml;
}


/**
* Generates keywords to enter into the archive search store.
*
* @param string $text - The text to generate the big keywords from.
* @global int
* @global
* @return array - The keywords found.
* @author Joseph Todd Parsons
*/

fim3parse_keyWords($string) {
  $string = preg_replace('/()/',' ',$string) // Replace a variety of special symbols with plain spaces.

  $string
}



/**
* Container for all above parsers, formatting different values (html, raw, api).
*
* @param string $messageText - Message string.
* @return array - $messageRaw, $messageHtml, and $messageApi strings
* @author Joseph Todd Parsons
*/

function fimParse_finalParse($messageText) {
  global $room;

  $messageRaw = $messageText; // Parses the sources for MySQL.
  $messageHtml = nl2br(fimParse_htmlWrap(fimParse_htmlParse(fimParse_censorParse(fim_encodeXml($messageText),$room['id']),$room['options']),30,' ')); // Parses for browser or HTML rendering.
  $messageApi = fimParse_smilieParse($messageText,$room['bbcode']); // Not yet coded, you see.

  return array($messageRaw, $messageHtml, $messageApi);
}



/**
* Sends a message to the database.
*
* @param string $messageText - Message to be sent.
* @param array $user - Array of user, including at least the userId index (ideally also userName, others).
* @param array $room - Room data, including at least the roomId index.
* @param string $flag - Message context flag; for instance, email, image, etc..
* @return void - true on success, false on failure
* @author Joseph Todd Parsons
*/

function fim_sendMessage($messageText,$user,$room,$flag = '') {
  global $sqlPrefix, $parseFlags, $salts, $encrypt, $loginMethod, $sqlUserGroupTableCols, $sqlUserGroupTable;

  $ip = dbEscape($_SERVER['REMOTE_ADDR']); // Get the IP address of the user.



  // Flags allow for less hassle on some communications.
  // Supported flags: image, video, link, email
  // Other flags that won't be parsed here: me, topic

  if (in_array($flag,array('image','video','link','email'))) {
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
    $message = fimParse_finalParse($messageText);
    list($messageRaw,$messageHtml,$messageApi) = $message;
  }



  $messageHtmlCache = $messageHtml; // Store so we don't encrypt cache
  $messageHtmlApi = $messageHtml; // Store so we don't encrypt cache



  // Encrypt Message Data

  if ($salts && $encrypt) { // Only encrypt if we have both salts and encrypt is enabled.
    $salt = end($salts); // Get the last salt stored.
    $saltNum = key($salts); // Key the key of the salt.

    $iv_size = mcrypt_get_iv_size(MCRYPT_3DES,MCRYPT_MODE_CBC); // Get random IV size (CBC mode)
    $iv = base64_encode(mcrypt_create_iv($iv_size, MCRYPT_RAND)); // Get random IV; base64 it

    list($messages,$iv,$saltNum) = fim_encrypt(array($messageRaw,$messageHtml,$messageApi)); // Get messages array, IV, salt num
    list($messageRaw,$messageHtml,$messageApi) = $messages; // Get messages from messages array
  }



  // Insert into archive then cache storage.

  dbInsert(array(
    'roomId' => (int) $room['roomId'],
    'userId' => (int) $user['userId'],
    'rawText' => $messageRaw,
    'htmlText' => $messageHtml,
    'apiText' => $messageApi,
    'salt' => $saltNum,
    'iv' => $iv,
    'ip' => $ip,
    'flag' => $flag,
  ),"{$sqlPrefix}messages");
  $messageId = dbInsertId();

  dbInsert(array(
    'messageId' => (int) $messageId,
    'roomId' => (int) $room['roomId'],
    'userId' => (int) $user['userId'],
    'userName' => $user['userName'],
    'userGroup' => (int) $user['userGroup'],
    'avatar' => $user['avatar'],
    'profile' => $user['profile'],
    'userFormatStart' => $user['userFormatStart'],
    'userFormatEnd' => $user['userFormatEnd'],
    'defaultFormatting' => $user['defaultFormatting'],
    'defaultColor' => $user['defaultColor'],
    'defaultHighlight' => $user['defaultHighlight'],
    'defaultFontface' => $user['defaultFontface'],
    'htmlText' => $messageHtmlCache,
    'apiText' => $messageHtmlApi,
    'flag' => $flag,
  ),"{$sqlPrefix}messagesCached");

  $messageId2 = dbInsertId();



  // Delete old messages from the cache; do so depending on the cache limit set in config.php, or default to 100.
  $cacheTableLimit = ($cacheTableLimit ? $cacheTableLimit : 100);
  if ($messageId2 > $cacheTableLimit) {
    dbDelete("{$sqlPrefix}messagesCached",
    array('id' => array(
      'cond' => 'lte',
      'type' => 'raw',
      'value' => ($messageId2 - $cacheTableLimit)
    )));
  }



  // Update room caches.
  dbUpdate(array(
    'lastMessageTime' => array(
      'type' => 'raw',
      'value' => 'NOW()',
    ),
  ),
  "{$sqlPrefix}rooms",
  array(
     'roomId' => $room['roomId'],
  ));



  // Insert or update a user's room stats.
  dbInsert(array(
    'userId' => $user['userId'],
    'roomId' => $room['roomId'],
    'messages' => 1),
   "{$sqlPrefix}roomStats",
   array(
    'messages' => array(
      'type' => 'raw',
      'value' => 'messages + 1',
    )
  ));
}
?>