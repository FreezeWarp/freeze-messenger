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

class messageParse {
  public function __construct($messageText = false, $messageFlag = false, $userData = false, $roomData = false) {
    $this->setMessage($messageText, $messageFlag);
    $this->setUser($userData);
    $this->setRoom($roomData);
  }

  public function setMessage($messageText, $messageFlag) {
    $this->messageText = $messageText;
    $this->messageFlag = $messageFlag;
  }

  public function setUser($userData) {
    $this->userData = $userData;
  }

  public function setRoom($roomData) {
    $this->roomData = $roomData;
  }



  /**
  * Parses and censors phrases. Requires an active MySQL connection.
  *
  * @param string $text - The text to censor.
  * @param int $roomId - The ID of the room's censors. Uses general censors if not available (thus not using any black/white lists).
  * @return string - Censored text.
  * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */

  public function censorParse($text, $roomId = 0, $roomOptions) {
    global $sqlPrefix, $slaveDatabase, $config;

    $listIds = $slaveDatabase->getRoomCensorLists($roomId, $roomOptions);

    $words = $slaveDatabase->select(
      array(
        "{$sqlPrefix}censorWords" => 'listId, word, severity, param',
      ),
      array(
        'both' => array(
          array(
            'type' => 'in',
            'left' => array(
              'type' => 'column',
              'value' => 'listId',
            ),
            'right' => array(
              'type' => 'array',
              'value' => $listIds,
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


    if (!$words) {
      return $text;
    }


    foreach ($words AS $word) {
      $words2[strtolower($word['word'])] = $word['param'];
      $searchText[] = addcslashes(strtolower($word['word']),'^&|!$?()[]<>\\/.+*');
    }
    $searchText2 = implode('|', $searchText);


    return preg_replace("/(?<!(\[noparse\]))(?<!(\quot))($searchText2)(?!\[\/noparse\])/ie","indexValue(\$words2,strtolower('\\3'))", $text);
  }



  /**
  * Parsers database-stored smilies.
  *
  * @param string $text - The text to parse.
  * @return string - Parsed text.
  * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function emotiParse($text) {
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
          "{$forumTablePrefix}emoticons" => array(
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

    $text = preg_replace("/(?<!(\[noparse\]))(?<!(quot))(?<!(gt))(?<!(lt))(?<!(apos))(?<!(amp))($searchText2)(?!\[\/noparse\])/ie","'{$forumUrlS}' . indexValue(\$smilies2,strtolower('\\7'))", $text);

    return $text;
  }



  public function getRaw() {
    return $this->messageText;
  }



  public function getEncrypted() {
    global $salts, $encrypt;

    // Encrypt Message Text
    if ($salts && $encrypt) { // Only encrypt if we have both set salts and encrypt is enabled.
      list($messageTextEncrypted, $iv, $saltNum) = fim_encrypt( // Encrypt the values and return the new data, IV, and saltNum.
        $this->messageText
      );
    }
    else { // No encyption
      $messageTextEncrypted = $this->messageText;
      $iv = ''; // Use an empty IV - it will be ignored by the decryptor.
      $saltNum = 0; // Same as with the IV, salt keys of "0" are ignored.
    }

    return array($messageTextEncrypted, $iv, $saltNum);
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

  public function getHtml() {
    global $sqlPrefix, $parseFlags, $salts, $encrypt, $loginMethod, $sqlUserGroupTableCols, $sqlUserGroupTable, $database;



    // Flags allow for less hassle on some communications.
    // Supported flags: image, video, link, email
    // Other flags that won't be parsed here: me, topic
    if (in_array($this->messageFlag, array('image', 'video', 'url', 'email', 'html', 'audio', 'text'))) {
      return $this->messageText;
    }
    else {
      return $this->emotiParse( // Converts emoticons (e.g. ":D", ":P", "o.O") to HTML <img /> tags based on database-stored conversions.
        $this->censorParse( // Censors text based on database-stored filters, which may be activated or deactivted by the room itself.
          $this->messageText,
          $this->roomData['roomId'],
          $this->roomData['options']
        )
      );
    }
  }




  /**
  * Generates keywords to enter into the archive search store.
  *
  * @param string $text - The text to generate the big keywords from.
  * @return array - The keywords found.
  * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */

  public function getKeyWords() {
    global $config, $sqlPrefix, $database, $user;

    $puncList = array();
    $string = $this->messageText;

    if (count($config['searchWordPunctuation']) > 0) {
      foreach ($config['searchWordPunctuation'] AS $punc) {
        $puncList[] = addcslashes($punc, '"\'|(){}[]<>.,~-?!@#$%^&*/\\'); // Dunno if this is the best approach.
      }

      $string = preg_replace('/(' . implode('|', $puncList) . ')/is', ' ', $string);
    }

    while (strpos($string, '  ') !== false) {
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

      return $stringPiecesAdd;
    }
    else {
      return array();
    }
  }
}
?>