<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

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
 * Edit's the Logged-In User's Options
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2012
 *
 * =GET Parameters=
 These parameteres are, where applicable, documented in the SQL documentation.

 * @param int defaultRoomId
 * @param uri avatar
 * @param uri profile
 * @param array[r,g,b] defaultColor - A comma-seperated list of the three chroma values, corresponding to red, green, and blue. The range of these chroma values is [0,255], where 0 is no color, and 255 is full color.
 * @param array[r,g,b] defaultHighlight - Same as defaultColor.
 * @param string defaultFontface
 * @param int defaultFormatting
 * @param csv watchRooms - A comma-seperated list comma-seperated list of room IDs that will be watched. When a new message is made in these rooms, the user will be notified.
 * @param csv ignoreList - A comma-seperated list of user IDs that will be ignored for private messages.
 * @param int parentalAge - The parental age corresponding to the room.
 * @param csv parentalFlags - A comma-separated list of parental flags that apply to the room.
 *
 * =Errors=
 * Error codes are listed by the parent node they appear under:
 *
 * ==editUserOptions==
 * No error codes are thrown.
 *
 * ==avatar==
 * @throw smallSize - The avatar's dimensions are below the server minimum. This may also occur if a valid image was not sent.
 * @throw bigSize - The avatar's dimensions exceed the server maximum.
 * @throw badType - The avatar's filetype is not supported.
 * @throw bannedFile - The file has been been blocked by a server regex blacklist. [[TODO.]]

 * ==profile==
 * @throw noUrl - A valid URL was not provided.
 * @throw bannedUrl - The URL has been blocked by a server regex blacklist. [[TODO.]]

 * ==defaultRoom==
 * @throw noPerm - The user is not allowed to view the room specified.

 * ==defaultHighlight, defaultColor==
 * @throw outOfRange1 - The first color value, red, is out of the [0,255] range.
 * @throw outOfRange2 - The second color value, green, is out of the [0,255] range.
 * @throw outOfRange3 - The second color value, blue, is out of the [0,255] range.
 * @throw badFormat - Too few, or too many, chroma values were specified.
 
 * ==defaultFontface==
 * @throw noFont - The font specified does not exist.

 * ==parentalAge==
 * @throw badAge - The parental age specified is not valid.
 *
 * =Response=
 * @return APIOBJ
 ** editUserOptions
 *** activeUser
 **** userId
 **** userName
 *** errStr
 *** errDesc
 *** response
 **** avatar
 ***** status - true or false
 ***** newValue
 ***** errStr
 ***** errDesc
 **** profile
 ***** status - true or false
 ***** newValue
 ***** errStr
 ***** errDesc
 **** defaultRoom
 ***** status - true or false
 ***** newValue
 ***** errStr
 ***** errDesc
 **** defaultFormatting
 ***** status - true or false (always true)
 ***** newValue
 **** defaultHighlight, defaultColor
 ***** status - true or false
 ***** newValue
 ***** errStr
 ***** errDesc
 **** defaultFontface
 ***** status - true or false
 ***** newValue
 ***** errStr
 ***** errDesc
 **** parentalAge
 ***** status - true or false
 ***** newValue
 ***** errStr
 ***** errDesc
 **** parentalFlags
 ***** status - true or false
 ***** newValue
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'defaultRoomId' => array(
    'context' => 'int',
  ),

  'avatar' => array(
    'trim' => true,
  ),

  'profile' => array(
    'trim' => true,
  ),

  'defaultFontface' => array(
    'trim' => true,
  ),

  'defaultColor' => array(
    'trim' => true,
  ),

  'defaultHighlight' => array(
    'trim' => true,
  ),

  'defaultFormatting' => array(
    'context' => 'int',
  ),

  'watchRooms' => array(
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'ignoreList' => array(
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'parentalAge' => array(
    'context' => 'int',
  ),

  'parentalFlags' => array(
    'context' => array(
      'type' => 'csv',
      'allowedValues' => $config['parentalFlags'], // Note that values are dropped automatically if a value is not allowed. We will not tell the client this.
    ),
  ),
));

/* Data Predefine */
$xmlData = array(
  'editUserOptions' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'response' => array(),
  ),
);



/* Plugin Hook Start */
($hook = hook('editUserOptions_start') ? eval($hook) : '');



/* Start Processing */
if ($loginConfig['method'] === 'vanilla') {
  /* Avatar */
  if (isset($request['avatar'])) { // TODO: Add regex policy.
    $imageData = getimagesize($request['avatar']);
    if ($imageData[0] <= $config['avatarMinimumWidth'] || $imageData[1] <= $config['avatarMinimumHeight']) {
      $xmlData['editUserOptions']['response']['avatar']['status'] = false;
      $xmlData['editUserOptions']['response']['avatar']['errStr'] = 'smallSize';
      $xmlData['editUserOptions']['response']['avatar']['errDesc'] = 'The avatar specified is too small.';
    }
    elseif ($imageData[0] >= $config['avatarMaximumWidth'] || $imageData[1] >= $config['avatarMaximumHeight']) {
      $xmlData['editUserOptions']['response']['avatar']['status'] = false;
      $xmlData['editUserOptions']['response']['avatar']['errStr'] = 'bigSize';
      $xmlData['editUserOptions']['response']['avatar']['errDesc'] = 'The avatar specified is too large.';
    }
    elseif (!in_array($imageData[2], $config['imageType'])) {
      $xmlData['editUserOptions']['response']['avatar']['status'] = false;
      $xmlData['editUserOptions']['response']['avatar']['errStr'] = 'badType';
      $xmlData['editUserOptions']['response']['avatar']['errDesc'] = 'The avatar is not a valid image type.';
    }
    elseif ($badRegex) {
      $xmlData['editUserOptions']['response']['avatar']['status'] = false;
      $xmlData['editUserOptions']['response']['avatar']['errStr'] = 'bannedFile';
      $xmlData['editUserOptions']['response']['avatar']['errDesc'] = 'The avatar specified is not allowed.';
    }
    else {
      $updateArray['avatar'] = $request['avatar'];

      $xmlData['editUserOptions']['response']['avatar']['status'] = true;
      $xmlData['editUserOptions']['response']['avatar']['newValue'] = (int) $request['avatar'];
    }
  }


  /* Profile */
  if (isset($request['profile'])) { // TODO: Add regex policy.
    if (filter_var($request['profile'], FILTER_VALIDATE_URL) === FALSE) {
      $xmlData['editUserOptions']['response']['profile']['status'] = false;
      $xmlData['editUserOptions']['response']['profile']['errStr'] = 'noUrl';
      $xmlData['editUserOptions']['response']['profile']['errDesc'] = 'The URL is not a URL.';
    }
    else {
      $ch = curl_init($request['profile']);
      curl_setopt($ch, CURLOPT_USERAGENT, $config['curlUA']);
      curl_setopt($ch, CURLOPT_NOBODY, true);
      curl_exec($ch);
      $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($retcode !== 200) {
        $xmlData['editUserOptions']['response']['profile']['status'] = false;
        $xmlData['editUserOptions']['response']['profile']['errStr'] = 'badUrl';
        $xmlData['editUserOptions']['response']['profile']['errDesc'] = 'The URL does not validate.';
      }
      elseif ($badRegex) {
        $xmlData['editUserOptions']['response']['profile']['status'] = false;
        $xmlData['editUserOptions']['response']['profile']['errStr'] = 'bannedUrl';
        $xmlData['editUserOptions']['response']['profile']['errDesc'] = 'The URL specified is not allowed.';
      }
      else {
        $updateArray['profile'] = $request['profile'];

        $xmlData['editUserOptions']['response']['profile']['status'] = true;
        $xmlData['editUserOptions']['response']['profile']['newValue'] = $request['profile'];
      }
    }
  }
}


/* Default Room */
if ($request['defaultRoomId'] > 0) {
  $defaultRoomData = $slaveDatabase->getRoom($request['defaultRoomId']);

  if (fim_hasPermission($defaultRoomData,$user,'view')) {
    $updateArray['defaultRoom'] = (int) $request['defaultRoomId'];

    $xmlData['editUserOptions']['response']['defaultRoom']['status'] = true;
    $xmlData['editUserOptions']['response']['defaultRoom']['newValue'] = (int) $request['defaultRoomId'];
  }
  else {
    $xmlData['editUserOptions']['response']['defaultRoom']['status'] = false;
    $xmlData['editUserOptions']['response']['defaultRoom']['errStr'] = 'noPerm';
    $xmlData['editUserOptions']['response']['defaultRoom']['errDesc'] = 'You do not have permission to view the room you are trying to default to.';
  }
}


/* Watch Rooms (used for notifications of new messages, which are placed in unreadMessages) */
if (isset($request['watchRooms'])) {
  $database->delete("{$sqlPrefix}watchRooms", array(
    'userId' => $user['userId'],
  ));

  $queryParts['roomSelect'] = array(
    'columns' => array(
      "{$sqlPrefix}rooms" => 'roomId, roomName, roomTopic, owner, defaultPermissions, parentalFlags, parentalAge, options, lastMessageId, lastMessageTime, messageCount',
    ),
    'conditions' => array(
      'both' => array(
        array(
          'type' => 'in',
          'left' => array(
            'type' => 'column', 'value' => 'roomId',
          ),
          'right' => array(
            'type' => 'array', 'value' => $request['watchRooms'],
          ),
        ),
      ),
    )
  );

  $roomData = $database->select(
    $queryParts['roomSelect']['columns'],
    $queryParts['roomSelect']['conditions']);
  $roomData = $roomData->getAsArray('roomId');

  foreach ($request['watchRooms'] AS $watchRoomId) {
    foreach ($roomIds AS $roomId) {
      if (fim_hasPermission($roomData[$watchRoomId], $user, 'view')) {
        $this->insert("{$sqlPrefix}watchRooms", array(
          'userId' => $user['userId'],
          'roomId' => $watchRoomId,
        ));
      }
    }
  }
}


/* Default Formatting */
if (isset($request['defaultFormatting'])) {
  $updateArray['defaultFormatting'] = (int) $request['defaultFormatting'];

  $xmlData['editUserOptions']['response']['defaultFormatting']['status'] = true;
  $xmlData['editUserOptions']['response']['defaultFormatting']['newValue'] = (string) implode(',', $defaultFormatting);
}


/* Default Highlight & Default Colour */
foreach (array('defaultHighlight', 'defaultColor') AS $value) {
  if (isset($request[$value])) {
    $rgb = fim_arrayValidate(explode(',', $request[$value]), 'int', true);

    if (count($rgb) === 3) { // Too many entries.
      if ($rgb[0] < 0 || $rgb[0] > 255) { // First val out of range.
        $xmlData['editUserOptions']['response'][$value]['status'] = false;
        $xmlData['editUserOptions']['response'][$value]['errStr'] = 'outOfRange1';
        $xmlData['editUserOptions']['response'][$value]['errDesc'] = 'The first value ("red") was out of range.';
      }
      elseif ($rgb[1] < 0 || $rgb[1] > 255) { // Second val out of range.
        $xmlData['editUserOptions']['response'][$value]['status'] = false;
        $xmlData['editUserOptions']['response'][$value]['errStr'] = 'outOfRange2';
        $xmlData['editUserOptions']['response'][$value]['errDesc'] = 'The first value ("green") was out of range.';
      }
      elseif ($rgb[2] < 0 || $rgb[2] > 255) { // Third val out of range.
        $xmlData['editUserOptions']['response'][$value]['status'] = false;
        $xmlData['editUserOptions']['response'][$value]['errStr'] = 'outOfRange3';
        $xmlData['editUserOptions']['response'][$value]['errDesc'] = 'The third value ("blue") was out of range.';
      }
      else {
        $updateArray[$value] = implode(',', $rgb);

        $xmlData['editUserOptions']['response'][$value]['status'] = true;
        $xmlData['editUserOptions']['response'][$value]['newValue'] = (string) implode(',', $rgb);
      }
    }
    else {
      $xmlData['editUserOptions']['response'][$value]['status'] = false;
      $xmlData['editUserOptions']['response'][$value]['errStr'] = 'badFormat';
      $xmlData['editUserOptions']['response'][$value]['errDesc'] = 'The default highlight value was not properly formatted.';
    }
  }
}


/* Default Fontface */
if (isset($request['defaultFontface'])) {
  if (isset($config['fonts'][$request['defaultFontface']])) {
    $updateArray['defaultFontface'] = $config['fonts'][$request['defaultFontface']];

    $xmlData['editUserOptions']['response']['defaultFontface']['status'] = true;
    $xmlData['editUserOptions']['response']['defaultFontface']['newValue'] = $config['fonts'][$request['defaultFontface']];
  }
  else {
    $xmlData['editUserOptions']['response']['defaultFontface']['status'] = false;
    $xmlData['editUserOptions']['response']['defaultFontface']['errStr'] = 'noFont';
    $xmlData['editUserOptions']['response']['defaultFontface']['errDesc'] = 'The specified font does not exist.';
  }
}


/* Parental Age */
if (isset($request['parentalAge'])) {
  if (in_array($request['parentalAge'], $config['parentalAges'])) {
    $updateArray['parentalAge'] = (int) $request['parentalAge'];

    $xmlData['editUserOptions']['response']['parentalAge']['status'] = true;
    $xmlData['editUserOptions']['response']['parentalAge']['newValue'] = (int) $request['parentalAge'];
  }
  else {
    $xmlData['editUserOptions']['response']['parentalAge']['status'] = false;
    $xmlData['editUserOptions']['response']['parentalAge']['errStr'] = 'badAge';
    $xmlData['editUserOptions']['response']['parentalAge']['errDesc'] = 'The parental age specified is not valid.';
  }
}


/* Parental Flags */
if (isset($request['parentalFlags'])) {
  $updateArray['parentalFlags'] = implode(',', $request['parentalFlags']);

  $xmlData['editUserOptions']['response']['parentalFlags']['status'] = true;
  $xmlData['editUserOptions']['response']['parentalFlags']['newValue'] = $request['parentalFlags'];
}


($hook = hook('editUserOptions_preQuery') ? eval($hook) : '');

if (count($updateArray) > 0) {
  $database->update(
    "{$sqlPrefix}users", $updateArray, array(
      'userId' => $user['userId'],
    )
  );
}



/* Update Data for Errors */
$xmlData['editUserOptions']['errStr'] = (string) $errStr;
$xmlData['editUserOptions']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('editUserOptions_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>