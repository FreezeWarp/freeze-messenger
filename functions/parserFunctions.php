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
    $text = preg_replace($code['searchRegex'], $code['replacement'], $text);
  }

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
function fimParse_emotiParse($text) {
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
  $noparseTags = array('img', 'a');

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

function fim3parse_keyWords($string, $messageId, $roomId) {
  global $config, $sqlPrefix, $database, $user;

  foreach ($config['searchWordPunctuation'] AS $punc) {
    $puncList[] = addcslashes($punc,'"\'|(){}[]<>.,~-?!@#$%^&*/\\'); // Dunno if this is the best approach.
  }

  $string = preg_replace('/(' . implode('|', $puncList) . ')/is', ' ', $string);

  while (strpos($string,'  ') !== false) {
    $string = str_replace('  ', ' ', $string);
  }

  $string = strtolower($string);


  $stringPieces = array_unique(explode(' ', $string));
  $stringPiecesAdd = array();

  foreach ($stringPieces AS $piece) {
    if (strlen($piece) >= $config['searchWordMinimum'] && !in_array($piece, $config['searchWordOmissions'])) {
      $stringPiecesAdd[] = str_replace($config['searchWordConvertsFind'], $config['searchWordConvertsReplace'], $piece);
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
        'userId' => (int) $user['userId'],
        'roomId' => (int) $roomId,
      ),"{$sqlPrefix}searchMessages");
    }
  }
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

function fim_sendMessage($messageText, $userData, $roomData, $flag = '') {
  global $sqlPrefix, $parseFlags, $salts, $encrypt, $loginMethod, $sqlUserGroupTableCols, $sqlUserGroupTable, $database;



  // Flags allow for less hassle on some communications.
  // Supported flags: image, video, link, email
  // Other flags that won't be parsed here: me, topic

  if (in_array($flag, array('image', 'video', 'link', 'email', 'youtube', 'html', 'audio', 'text'))) {
    $messageData = array(
      'rawText' => $messageText,
      'apiText' => $messageText,
      'htmlText' => $messageText,
    );
  }
  else {
    $messageData = array(
      'rawText' => $messageText, // Parses the sources for MySQL.
      'htmlText' => nl2br( // Converts \n characters to HTML <br />s.
        fimParse_emotiParse( // Converts emoticons (e.g. ":D", ":P", "o.O") to HTML <img /> tags based on database-stored conversions.
          fimParse_htmlWrap( // Forces a space to be placed every 80 non-breaking characters, in order to prevent HTML stretching.
            fimParse_htmlParse( // Parses database-stored BBCode (e.g. "[b]Hello[/b]") to their HTML equivilents (e.g. "<b>Hello</b>").
              fimParse_censorParse($messageText, $roomData['roomId']), // Censors text based on database-stored filters, which may be activated or deactivted by the room itself.
              $roomData['options']
            ), 80, ' '
          )
        )
      ),
      'apiText' => fimParse_censorParse($messageText), // Censors text (see above).
    );
  }



  // Encrypt Message Data
  if ($salts && $encrypt) { // Only encrypt if we have both set salts and encrypt is enabled.
    list($messageDataEncrypted, $iv, $saltNum) = fim_encrypt( // Encrypt the values and return the new data, IV, and saltNum.
      array('messageRaw' => $messageData['rawText'],
            'messageHtml' => $messageData['htmlText'],
            'messageApi' => $messageData['apiText'],
      )
    );

    $messageDataEncrypted['iv'] = $iv; // Append the base64-encoded IV to the encrypted message data array.
    $messageDataEncrypted['saltNum'] = $saltNum; // Append the salt referrence to the encrypted message data array.
  }
  else { // No encyption
    $messageDataEncrypted = $messageData;
    $messageDataEncrypted['iv'] = ''; // Use an empty IV - it will be ignored by the decryptor.
    $messageDataEncrypted['saltNum'] = 0; // Same as with the IV, salt keys of "0" are ignored.
  }



  // Add the data to the datastore.
  $database->storeMessage($userData, $roomData, $messageData, $messageDataEncrypted, $flag);



  // Add message to archive search store.
  fim3parse_keyWords($messageRawCache, $messageId, $roomData['roomId']);
}
?>