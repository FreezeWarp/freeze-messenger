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
 * Edit's the Logged-In User's Options
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'defaultRoomId' => array(
    'context' => 'int',
  ),

  'avatar' => array(),

  'profile' => array(),

  'defaultFontface' => array(
    'context' => 'int',
  ),

  'defaultColor' => array(),

  'defaultHighlight' => array(),

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

  'favRooms' => array(
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
  if (isset($request['avatar'])) { // TODO: Add regex policy.
    $imageData = getimagesize($request['avatar']);
    if ($imageData[0] <= $config['avatarMinimumWidth'] || $imageData[1] <= $config['avatarMinimumHeight']) {
      $xmlData['editUserOptions']['response']['avatar']['status'] = false;
      $xmlData['editUserOptions']['response']['avatar']['errStr'] = 'smallSize';
      $xmlData['editUserOptions']['response']['avatar']['errDesc'] = 'The avatar specified is too small.';
    }
    elseif ($imageData[0] >= $config['avatarMaximumWidth'] || $imageData[1] <= $config['avatarMaximumHeight']) {
      $xmlData['editUserOptions']['response']['avatar']['status'] = false;
      $xmlData['editUserOptions']['response']['avatar']['errStr'] = 'bigSize';
      $xmlData['editUserOptions']['response']['avatar']['errDesc'] = 'The avatar specified is too small.';
    }
    elseif (!in_array($imageData[2], array('IMAGETYPE_GIF', 'IMAGETYPE_JPEG', 'IMAGETYPE_PNG'))) {
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

  if (isset($request['profile'])) { // TODO: Add regex policy.

    if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
      $xmlData['editUserOptions']['response']['profile']['status'] = false;
      $xmlData['editUserOptions']['response']['profile']['errStr'] = 'noUrl';
      $xmlData['editUserOptions']['response']['profile']['errDesc'] = 'The URL is not a URL.';
    }
    else {
      $ch = curl_init($request['profile']);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
      curl_setopt($ch, CURLOPT_NOBODY, true);
      curl_exec($ch);
      $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($status !== 200) {
        $xmlData['editUserOptions']['response']['profile']['status'] = false;
        $xmlData['editUserOptions']['response']['profile']['errStr'] = 'badUrl';
        $xmlData['editUserOptions']['response']['profile']['errDesc'] = 'The URL does not validate.';
      }
      elseif ($badRegex) {
        $xmlData['editUserOptions']['response']['avatar']['status'] = false;
        $xmlData['editUserOptions']['response']['avatar']['errStr'] = 'bannedUrl';
        $xmlData['editUserOptions']['response']['avatar']['errDesc'] = 'The URL specified is not allowed.';
      }
      else {
        $updateArray['profile'] = $request['profile'];

        $xmlData['editUserOptions']['res zponse']['profile']['status'] = true;
        $xmlData['editUserOptions']['response']['profile']['newValue'] = (int) $request['avatar'];
      }
    }
  }
}


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

/* TODO
foreach (array('favRooms', 'watchRooms', 'ignoreList') AS $item) {
  if (isset($request[$item])) {
    $updateArray[$item] = (string) implode(',', $request[$item]);

    $xmlData['editUserOptions']['response']['favRooms']['status'] = true;
    $xmlData['editUserOptions']['response']['favRooms']['newValue'] =  $updateArray[$item];
  }
} */

if (isset($request['defaultFormatting'])) {
  $updateArray['defaultFormatting'] = (int) $request['defaultFormatting'];

  $xmlData['editUserOptions']['response']['defaultFormatting']['status'] = true;
  $xmlData['editUserOptions']['response']['defaultFormatting']['newValue'] = (string) implode(',', $defaultFormatting);
}

foreach (array('defaultHighlight','defaultColor') AS $value) {
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

if (isset($request['defaultFontface'])) {
  $fontData = $slaveDatabase->getFont((int) $request['defaultFontface']);

  if ((int) $fontData['fontId']) {
    $updateArray['defaultFontface'] = (int) $fontData['fontId'];

    $xmlData['editUserOptions']['response']['defaultFontface']['status'] = true;
    $xmlData['editUserOptions']['response']['defaultFontface']['newValue'] = (int) $fontData['fontId'];
  }
  else {
    $xmlData['editUserOptions']['response']['defaultFontface']['status'] = false;
    $xmlData['editUserOptions']['response']['defaultFontface']['errStr'] = 'noFont';
    $xmlData['editUserOptions']['response']['defaultFontface']['errDesc'] = 'The specified font does not exist.';
  }
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