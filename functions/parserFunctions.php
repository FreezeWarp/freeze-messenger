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

/**
 * Parses text from defined BBCode.
 *
 * @param string $text - Text to format with HTML.
 * @param integer $bbcodeLevel - The level of bbcode to parse. See documentation for values.
 * @return string - Parsed text.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 * @todo Port to new engine.

 * Some notes: The reworked BBCode engine removes all predefined BBcode, and replaces it with a broad anything-goes regex system. It will allow for nearly anything (including advanced hacks), but also means the system will simply not play nice with anything else. As such, a few things to note: smilies and valid links will be parsed solely for the HTML field, directly converted to proper IMG tags; [we're not sure what else yet].
*/

function fimParse_htmlParse($text) {
  global $user, $loginConfig, $slaveDatabase, $sqlPrefix;

  $search2 = array(
    '/\[noparse\](.*?)\[\/noparse\]/is',
  );

  $replace2 = array(
    '$1',
  );

  // Parse BB Code
  $text = preg_replace($search2, $replace2, $text);

  $bbcode = $slaveDatabase->select(
    array(
      "{$sqlPrefix}bbcode" => array(
        'options' => 'options',
        'searchRegex' => 'searchRegex',
        'replacement' => 'replacement',
      ),
    )
  );
  $bbcode = $bbcode->getAsArray(true);

  foreach ($bbcode AS $code) {
    $search3[] = $code['searchRegex'];
    $replace3[] = $code['replacement'];
  }

//  $text = preg_replace($search3, $replace3, $text);

  return $text;
}



/**
* Parses and censors phrases. Requires an active MySQL connection.
*
* @param string $text - The text to censor.
* @param int $roomId - The ID of the room's censors. Uses general censors if not available (thus not using any black/white lists).
* @return string - Censored text.
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function fimParse_censorParse($text, $roomId = 0) {
  global $sqlPrefix, $slaveDatabase;

/*  $words = dbRows("SELECT w.word, w.severity, w.param, l.listId AS listId
FROM {$sqlPrefix}censorLists AS l, {$sqlPrefix}censorWords AS w
WHERE w.listId = l.listId AND w.severity = 'replace'",'word');*/

  $words = $slaveDatabase->select(
    array(
      "{$sqlPrefix}censorLists" => array(
        'listId' => 'llistId',
      ),
      "{$sqlPrefix}censorWords" => array(
        'listId' => 'listId',
        'word' => 'word',
        'severity' => 'severity',
        'param' => 'param',
      ),
    ),
    array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'listId',
          ),
          'right' => array(
            'type' => 'column',
            'value' => 'llistId',
          ),
        ),
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'severity',
          ),
          'right' => array(
            'type' => 'string',
            'value' => 'replace',
          ),
        ),
      ),
    )
  );
  $words = $words->getAsArray('word');


  if ($roomId > 0) {
    $listsActive = $slaveDatabase->select(
      array(
        "{$sqlPrefix}censorBlackWhiteLists" => array(
          'status' => 'status',
          'roomId' => 'roomId',
          'listId' => 'listId',
        ),
      ),
      array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'roomId',
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $roomId,
            ),
          ),
        ),
      )
    );
    $listsActive = $listsActive->getAsArray();


    if (is_array($listsActive)) {
      if (count($listsActive) > 0) {
        foreach ($listsActive AS $active) {
          if ($active['status'] == 'unblock') {
            $noBlock[] = $active['listId'];
          }
        }
      }
    }
  }

  if (!$words) {
    return $text;
  }


  foreach ($words AS $word) {
    if ($noBlock) {
      if (in_array($word['listId'], $noBlock)) continue;
    }

    $words2[strtolower($word['word'])] = $word['param'];
    $searchText[] = addcslashes(strtolower($word['word']),'^&|!$?()[]<>\\/.+*');
  }

  $searchText2 = implode('|', $searchText);

  return preg_replace("/(?<!(\[noparse\]))(?<!(\quot))($searchText2)(?!\[\/noparse\])/ie","indexValue(\$words2,strtolower('\\3'))", $text);

  return $text;
}



/**
* Parsers database-stored smilies.
*
* @param string $text - The text to parse.
* @return string - Parsed text.
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/
function fimParse_smilieParse($text) {
  global $room, $forumTablePrefix, $slaveDatabase, $integrationDatabase, $loginConfig;

  switch($loginConfig['method']) {
    case 'vbulletin3':
    case 'vbulletin4':
    $smilies = $integrationDatabase->select(
      array(
        "{$forumTablePrefix}smilie" => array(
          'smilietext' => 'emoticonText',
          'smiliepath' => 'emoticonFile',
        ),
      )
    );
    $smilies = $smilies->getAsArray(true);
    break;

    case 'phpbb':
    $smilies = $integrationDatabase->select(
      array(
        "{$forumTablePrefix}smilies" => array(
          'code' => 'emoticonText',
          'smiley_url' => 'emoticonFile',
        ),
      )
    );
    $smilies = $smilies->getAsArray(true);
    break;

    case 'vanilla':
    $smilies = $slaveDatabase->select(
      array(
        "{$sqlPrefix}emoticons" => array(
          'emoticonText' => 'emoticonText',
          'emoticonFile' => 'emoticonFile',
          'context' => 'context',
        ),
      )
    );
    $smilies = $smilies->getAsArray(true);
    break;

    default:
    $smilies = false;
    break;
  }



  if (!is_array($smilies)) {
    return $text;
  }
  elseif (count($smilies) == 0) {
    return $text;
  }
  else {
    foreach ($smilies AS $smilie) {
      $smilies2[strtolower($smilie['emoticonText'])] = $smilie['emoticonFile'];
      $searchText[] = addcslashes(strtolower($smilie['emoticonText']),'^&|!$?()[]<>\\/.+*');
    }

    switch ($loginConfig['method']) {
      case 'phpbb':
      $forumUrlS = $loginConfig['url'] . 'images/smilies/';
      break;

      case 'vanilla':
      case 'vbulletin3':
      case 'vbulletin4':
      $forumUrlS = $loginConfig['url'];
      break;
    }
  }

  $searchText2 = implode('|', $searchText);

  $text = preg_replace("/(?<!(\[noparse\]))(?<!(quot))(?<!(gt))(?<!(lt))(?<!(apos))(?<!(amp))($searchText2)(?!\[\/noparse\])/ie","'<img src=\"$forumUrlS' . indexValue(\$smilies2,strtolower('\\7')) . '\" alt=\"\\7\" />'", $text);

  return $text;
}



/**
 * NEEDS DOCUMENTATION
 */
function indexValue($array, $index) {
  return $array[$index];
}



/**
* Wraps HTML with specific support for UTF-8 and URLs.
*
* @param string $html - HTML text
* @param integer $maxLength - Length after which to wrap.
* @param string $chat - String to wrap with.
* @return string - Formatted data.
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
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
   $mb = mb_substr($html, $i,1,'UTF-8');
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
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function fim3parse_keyWords($string, $messageId) {
  global $config, $sqlPrefix, $database;

  foreach ($config['searchWordPunctuation'] AS $punc) {
    $puncList[] = addcslashes($punc,'"\'|(){}[]<>.,~-?!@#$%^&*/\\'); // Dunno if this is the best approach.
  }

  $string = preg_replace('/(' . implode('|', $puncList) . ')/is',' ', $string);

  while (strpos($string,'  ') !== false) {
    $string = str_replace('  ',' ', $string);
  }

  $string = strtolower($string);


  $stringPieces = array_unique(explode(' ', $string));
  $stringPiecesAdd = array();

  foreach ($stringPieces AS $piece) {
    if (strlen($piece) >= $config['searchWordMinimum'] && !in_array($piece, $config['searchWordOmissions'])) {
      $stringPiecesAdd[] = str_replace(array_keys($config['searchWordConverts']),array_values($config['searchWordConverts']), $piece);
    }
  }

  if (count($stringPiecesAdd) > 0) {
    sort($stringPiecesAdd);


    $phraseData = $database->select(
      array(
        "{$sqlPrefix}searchPhrases" => array(
          'phraseName' => 'phraseName',
          'phraseId' => 'phraseId',
        ),
      )
    );
    $phraseData = $phraseData->getAsArray('phraseName');


    foreach ($stringPiecesAdd AS $piece) {
      if (!isset($phraseData[$piece])) {
        $database->insert(array(
          'phraseName' => $piece,
        ),
        "{$sqlPrefix}searchPhrases");
        $phraseId = $database->insertId;
      }
      else {
        $phraseId = $phraseData[$piece]['phraseId'];
      }

      $database->insert(array(
        'phraseId' => (int) $phraseId,
        'messageId' => (int) $messageId,
      ),"{$sqlPrefix}searchMessages");
    }
  }
}



/**
* Container for all above parsers, formatting different values (html, raw, api).
*
* @param string $messageText - Message string.
* @return array - $messageRaw, $messageHtml, and $messageApi strings
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function fimParse_finalParse($messageText) {
  global $room;

  $messageRaw = $messageText; // Parses the sources for MySQL.
  $messageHtml = nl2br(fimParse_smilieParse(fimParse_htmlWrap(fimParse_htmlParse(fimParse_censorParse($messageText, $room['roomId']), $room['options']),80,' '))); // Parses for browser or HTML rendering.
  $messageApi = fimParse_censorParse($messageText); // Not yet coded, you see.

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
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function fim_sendMessage($messageText, $user, $room, $flag = '') {
  global $sqlPrefix, $parseFlags, $salts, $encrypt, $loginMethod, $sqlUserGroupTableCols, $sqlUserGroupTable, $database;



  // Flags allow for less hassle on some communications.
  // Supported flags: image, video, link, email
  // Other flags that won't be parsed here: me, topic

  if (in_array($flag,array('image','video','link','email','youtube','html','audio','text'))) {
    $messageRaw = $messageText;
    $messageApi = $messageText;
    $messageHtml = $messageText;
  }
  else {
    $message = fimParse_finalParse($messageText);
    list($messageRaw, $messageHtml, $messageApi) = $message;
  }



  $messageHtmlCache = $messageHtml; // Store so we don't encrypt cache
  $messageApiCache = $messageApi; // Store so we don't encrypt cache
  $messageRawCache = $messageRaw; // Store so we don't encrypt cache



  // Encrypt Message Data

  if ($salts && $encrypt) { // Only encrypt if we have both salts and encrypt is enabled.
    $salt = end($salts); // Get the last salt stored.
    $saltNum = key($salts); // Key the key of the salt.

    $iv_size = mcrypt_get_iv_size(MCRYPT_3DES,MCRYPT_MODE_CBC); // Get random IV size (CBC mode)
    $iv = base64_encode(mcrypt_create_iv($iv_size, MCRYPT_RAND)); // Get random IV; base64 it

    list($messages, $iv, $saltNum) = fim_encrypt(array($messageRaw, $messageHtml, $messageApi)); // Get messages array, IV, salt num
    list($messageRaw, $messageHtml, $messageApi) = $messages; // Get messages from messages array
  }
  else {
    $saltNum = 0;
  }



  // Insert into archive then cache storage.

  $database->insert(array(
    'roomId' => (int) $room['roomId'],
    'userId' => (int) $user['userId'],
    'rawText' => $messageRaw,
    'htmlText' => $messageHtml,
    'apiText' => $messageApi,
    'salt' => $saltNum,
    'iv' => (isset($iv) ? $iv : ''),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'flag' => $flag,
  ),"{$sqlPrefix}messages");
  $messageId = $database->insertId;

  $database->insert(array(
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
    'apiText' => $messageApiCache,
    'flag' => $flag,
  ),"{$sqlPrefix}messagesCached");

  $messageId2 = $database->insertId;



  // Delete old messages from the cache; do so depending on the cache limit set in config.php, or default to 100.
  $cacheTableLimit = (isset($config['cacheTableMaxRows']) ? $config['cacheTableMaxRows'] : 100);
  if ($messageId2 > $cacheTableLimit) {
    $database->delete("{$sqlPrefix}messagesCached",
      array('id' => array(
        'cond' => 'lte',
        'type' => 'raw',
        'value' => ($messageId2 - $cacheTableLimit)
      )
    ));
  }

  // Add message to archive search store.
  fim3parse_keyWords($messageRawCache, $messageId);



  // Update room caches.
  $database->update(array(
    'lastMessageTime' => array(
      'type' => 'raw',
      'value' => 'NOW()',
    ),
    'lastMessageId' => $messageId2
  ),
  "{$sqlPrefix}rooms",
  array(
     'roomId' => $room['roomId'],
  ));



  // Insert or update a user's room stats.
  $database->insert(array(
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