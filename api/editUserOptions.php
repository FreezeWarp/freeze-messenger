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
$request = fim_sanitizeGPC(array(
  'get' => array(
    'defaultRoomId' => array(
      'type' => 'string',
      'require' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'defaultFontface' => array(
      'type' => 'string',
      'require' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'defaultColor' => array(
      'type' => 'string',
      'require' => false,
    ),

    'defaultHighlight' => array(
      'type' => 'string',
      'require' => false,
    ),

    'watchRooms' => array(
      'type' => 'string',
      'require' => false,
      'context' => array(
        'type' => 'csv',
        'filter' => 'int',
        'evaltrue' => true,
      ),
    ),

    'favRooms' => array(
      'type' => 'string',
      'require' => false,
      'context' => array(
        'type' => 'csv',
        'filter' => 'int',
        'evaltrue' => true,
      ),
    ),

    'ignoreList' => array(
      'type' => 'string',
      'require' => false,
      'context' => array(
        'type' => 'csv',
        'filter' => 'int',
        'evaltrue' => true,
      ),
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
if ($request['defaultRoomId'] > 0) {
  $defaultRoomData = $slaveDatabase->getRoom($request['defaultRoomId']);

  if (fim_hasPermission($defaultRoomData,$user,'view')) {
    $updateArray['defaultRoom'] = (int) $_POST['defaultRoomId'];

    $xmlData['editUserOptions']['response']['defaultRoom']['status'] = true;
    $xmlData['editUserOptions']['response']['defaultRoom']['newValue'] = (int) $_POST['defaultRoomId'];
  }
  else {
    $xmlData['editUserOptions']['response']['defaultRoom']['status'] = false;
    $xmlData['editUserOptions']['response']['defaultRoom']['errStr'] = 'outofrange1';
    $xmlData['editUserOptions']['response']['defaultRoom']['errDesc'] = 'The first value ("red") was out of range.';
  }
}

if (count($request['favRooms']) > 0) {
  $favRooms = fim_arrayValidate(explode(',',$_POST['favRooms']),'int',false);
  $updateArray['favRooms'] = (string) implode(',',$favRooms);

  $xmlData['editUserOptions']['response']['favRooms']['status'] = true;
  $xmlData['editUserOptions']['response']['favRooms']['newValue'] = (string) implode(',',$favRooms);
}

if (count($request['watchRooms']) > 0) {
  $watchRooms = fim_arrayValidate(explode(',',$_POST['watchRooms']),'int',false);
  $updateArray['watchRooms'] = (string) implode(',',$watchRooms);

  $xmlData['editUserOptions']['response']['watchRooms']['status'] = true;
  $xmlData['editUserOptions']['response']['watchRooms']['newValue'] = (string) implode(',',$watchRooms);
}

if (count($request['ignoreList']) > 0) {
  $ignoreList = fim_arrayValidate(explode(',',$_POST['ignoreList']),'int',false);
  $updateArray['ignoreList'] = (string) implode(',',$ignoreList);

  $xmlData['editUserOptions']['response']['ignoreList']['status'] = true;
  $xmlData['editUserOptions']['response']['ignoreList']['newValue'] = (string) implode(',',$ignoreList);
}

if (isset($request['defaultFormatting'])) {
  $updateArray['defaultFormatting'] = (int) $_POST['defaultFormatting'];

  $xmlData['editUserOptions']['response']['defaultFormatting']['status'] = true;
  $xmlData['editUserOptions']['response']['defaultFormatting']['newValue'] = (string) implode(',',$defaultFormatting);
}

foreach (array('defaultHighlight','defaultColor') AS $value) {
  if (isset($request[$value])) {
    $rgb = fim_arrayValidate(explode(',',$request[$value]),'int',true);

    if (count($rgb) === 3) { // Too many entries.
      if ($rgb[0] < 0 || $rgb[0] > 255) { // First val out of range.
        $xmlData['editUserOptions']['response'][$value]['status'] = false;
        $xmlData['editUserOptions']['response'][$value]['errStr'] = 'outofrange1';
        $xmlData['editUserOptions']['response'][$value]['errDesc'] = 'The first value ("red") was out of range.';
      }
      elseif ($rgb[1] < 0 || $rgb[1] > 255) { // Second val out of range.
        $xmlData['editUserOptions']['response'][$value]['status'] = false;
        $xmlData['editUserOptions']['response'][$value]['errStr'] = 'outofrange2';
        $xmlData['editUserOptions']['response'][$value]['errDesc'] = 'The first value ("green") was out of range.';
      }
      elseif ($rgb[2] < 0 || $rgb[2] > 255) { // Third val out of range.
        $xmlData['editUserOptions']['response'][$value]['status'] = false;
        $xmlData['editUserOptions']['response'][$value]['errStr'] = 'outofrange3';
        $xmlData['editUserOptions']['response'][$value]['errDesc'] = 'The third value ("blue") was out of range.';
      }
      else {
        $updateArray[$value] = implode(',',$rgb);

        $xmlData['editUserOptions']['response'][$value]['status'] = true;
        $xmlData['editUserOptions']['response'][$value]['newValue'] = (string) implode(',',$rgb);
      }
    }
    else {
      $xmlData['editUserOptions']['response'][$value]['status'] = false;
      $xmlData['editUserOptions']['response'][$value]['errStr'] = 'badformat';
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
    $xmlData['editUserOptions']['response']['defaultFontface']['errStr'] = 'nofont';
    $xmlData['editUserOptions']['response']['defaultFontface']['errDesc'] = 'The specified font does not exist.';
  }
}

$database->update(
  "{$sqlPrefix}users", $updateArray, array(
    'userId' => $user['userId'],
  )
);



/* Update Data for Errors */
$xmlData['editUserOptions']['errStr'] = (string) $errStr;
$xmlData['editUserOptions']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('editUserOptions_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database */
dbClose();
?>