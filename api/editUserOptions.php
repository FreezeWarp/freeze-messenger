<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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
 * Edits the Logged-In User's Options
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 *
 * =POST Parameters=
These parameteres are, where applicable, documented in the SQL documentation.

 * @param int defaultRoomId
 * @param uri avatar
 * @param uri profile
 * @param array[r,g,b] defaultColor - A comma-seperated list of the three chroma values, corresponding to red, green, and blue. The range of these chroma values is [0,255], where 0 is no color, and 255 is full color.
 * @param array[r,g,b] defaultHighlight - Same as defaultColor.
 * @param string defaultFontface
 * @param int defaultFormatting
 * @param csv watchRooms - A comma-seperated list comma-seperated list of room IDs that will be watched. When a new message is made in these rooms, the user will be notified.
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
 *
 *
 * PUT editUserOptions.php watchRooms[]=1&watchRooms[]=2&watchRooms[]=3 == replaces the list of watch rooms with the new list, [1,2,3]
 * DELETE editUserOptions.php watchRooms[]=1&watchRooms[]=2&watchRooms[]=3 == removes rooms 1, 2, and 3 from the watch rooms list
 * POST editUserOptions.php watchRooms[]=1&watchRooms[]=2&watchRooms[]=3 == adds rooms 1, 2, and 3 from the watch rooms list
 */

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$requestHead = fim_sanitizeGPC('g', array(
    '_action' => [],
));

$request = fim_sanitizeGPC('p', array(
    'defaultRoomId' => array(
        'cast' => 'roomId',
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
        'cast' => 'list',
        'valid' => array('bold', 'italic')
    ),

    'parentalAge' => array(
        'cast' => 'int',
    ),

    'parentalFlags' => array(
        'cast' => 'list',
        'valid' => $config['parentalFlags'], // Note that values are dropped automatically if a value is not allowed. We will not tell the client this.
    ),

    'watchRooms' => array(
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),

    'favRooms' => array(
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),

    'friendsList' => array(
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),

    'ignoreList' => array(
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    )
));

/* Data Predefine */
$xmlData = array(
    'editUserOptions' => array(
    ),
);



/************************************
 **** Editable Only Properties ******
 ************************************/
if ($requestHead['_action'] === 'edit') {

    /************************************
     ***** Vanilla Only Properties ******
     ************************************/
    if ($loginConfig['method'] === 'vanilla') {

        /************************************
         ************ Avatar ****************
         ************************************/
        if (isset($request['avatar'])) { // TODO: Add regex policy.
            if ($badRegex) // TODO
                $xmlData['editUserOptions']['avatar'] = (new fimError('bannedFile', 'The avatar specified is not allowed.', null, true));

            else {
                $imageData = getimagesize($request['avatar']);

                if ($imageData[0] <= $config['avatarMinimumWidth'] || $imageData[1] <= $config['avatarMinimumHeight'])
                    $xmlData['editUserOptions']['avatar'] = (new fimError('smallSize', 'The avatar specified is too small.', null, true));

                elseif ($imageData[0] >= $config['avatarMaximumWidth'] || $imageData[1] >= $config['avatarMaximumHeight'])
                    $xmlData['editUserOptions']['avatar'] = (new fimError('bigSize', 'The avatar specified is too large.', null, true));

                elseif (!in_array($imageData[2], $config['imageType']))
                    $xmlData['editUserOptions']['avatar'] = (new fimError('badType', 'The avatar is not a valid image type.', null, true))->value();

                else
                    $updateArray['avatar'] = $request['avatar'];
            }
        }



        /************************************
         ************** Profile *************
         ************************************/
        if (isset($request['profile'])) { // TODO: Add regex policy.
            if ($request['profile'] === '') {
                // Really, do nothing for now. Could have a hook here later if we want to.
            }

            elseif (filter_var($request['profile'], FILTER_VALIDATE_URL) === FALSE) {
                $xmlData['editUserOptions']['profile'] = (new fimError('noUrl', 'The URL is not a URL.', null, true))->value();
            }

            else {
                if (($config['profileMustMatchRegex'] && !preg_match($config['profileMustMatchRegex'], $request['profile']))
                    || ($config['profileMustNotMatchRegex'] && preg_match($config['profileMustNotMatchRegex'], $request['profile'])))
                    $xmlData['editUserOptions']['profile'] = (new fimError('bannedUrl', 'The URL specified is not allowed.', null, true))->value();

                elseif (!curlRequest::exists($request['profile']))
                    $xmlData['editUserOptions']['profile'] = (new fimError('badUrl', 'The URL does not exist.', null, true))->value();

                else
                    $updateArray['profile'] = $request['profile'];
            }
        }

    }

    /************************************
     * Error for Vanilla Only Properties*
     ************************************/
    else {
        if (isset($request['avatar']))
            $xmlData['editUserOptions']['avatar'] = (new fimError('avatarDisabled', 'The avatar can not be changed through this API.', null, true))->value();

        if (isset($request['profile']))
            $xmlData['editUserOptions']['profile'] = (new fimError('profileDisabled', 'The profile can not be changed through this API.', null, true))->value();
    }

    /*** END: Vanilla-Only Properties ***/



    /************************************
     *********** Default Room ID ********
     ************************************/
    if (isset($request['defaultRoomId'])) {
        $defaultRoom = new fimRoom($request['defaultRoomId']);

        if (!$defaultRoom->roomExists())
            $xmlData['editUserOptions']['defaultRoom'] = (new fimError('invalidRoom', 'The room specified does not exist.', null, true))->value();

        elseif (!($database->hasPermission($user, $defaultRoom) & ROOM_PERMISSION_VIEW))
            $xmlData['editUserOptions']['defaultRoom'] = (new fimError('noPerm', 'You do not have permission to view the room you are trying to default to.', null, true))->value();

        else
            $updateArray['defaultRoomId'] = $defaultRoom->id;
    }



    /************************************
     ********* Default Formatting *******
     ************************************/
    if (isset($request['defaultFormatting'])) {
        if (in_array('bold', $request['defaultFormatting']) && $config['defaultFormattingBold'])
            $updateArray['messageFormatting'][] = 'font-weight:bold';

        if (in_array('italic', $request['defaultFormatting']) && $config['defaultFormattingItalics'])
            $updateArray['messageFormatting'][] = 'font-style:italic';
    }



    /************************************
     ***** Default Highlight/Color ******
     ************************************/
    foreach (array('defaultHighlight', 'defaultColor') AS $value) {
        if (isset($request[$value])) {
            $rgb = fim_arrayValidate(explode(',', $request[$value]), 'int', true);

            if (!$config['defaultFormatting' . substr($value, 7)])
                $xmlData['editUserOptions'][$value] = (new fimError('disabled', $value . ' is disabled on this server.', null, true))->value();

            elseif (count($rgb) !== 3) // Too many entries.
                $xmlData['editUserOptions'][$value] = (new fimError('badFormat', 'The ' . $value . ' value was not properly formatted.', null, true))->value();

            elseif ($rgb[0] < 0 || $rgb[0] > 255) // First val out of range.
                $xmlData['editUserOptions'][$value] = (new fimError('outOfRange1', 'The first value ("red") was out of range.', null, true))->value();

            elseif ($rgb[1] < 0 || $rgb[1] > 255) // Second val out of range.
                $xmlData['editUserOptions'][$value] = (new fimError('outOfRange2', 'The first value ("green") was out of range.', null, true))->value();

            elseif ($rgb[2] < 0 || $rgb[2] > 255) // Third val out of range.
                $xmlData['editUserOptions'][$value] = (new fimError('outOfRange3', 'The first value ("blue") was out of range.', null, true))->value();

            else {
                switch ($value) {
                    case 'defaultHighlight':
                        $updateArray['messageFormatting'][] = 'background-color:rgb(' . implode(',', $rgb) . ')';
                    break;
                    case 'defaultColor':
                        $updateArray['messageFormatting'][] = 'color:rgb(' . implode(',', $rgb) . ')';
                    break;
                }
            }
        }
    }



    /************************************
     ********* Default Fontface *********
     ************************************/
    if (isset($request['defaultFontface'])) {
        if (!$config['defaultFormattingFont'])
            $xmlData['editUserOptions']['defaultFontface'] = (new fimError('disabled', 'Defaults fonts are disabled on this server.', null, true))->value();

        else if (!isset($config['fonts'][$request['defaultFontface']]))
            $xmlData['editUserOptions']['defaultFontface'] = (new fimError('noFont', 'The specified font is not recognised. A list of recognised fonts can be obtained through the getServerStatus API.', null, true))->value();

        else
            $updateArray['messageFormatting'][] = 'font-family:' . $config['fonts'][$request['defaultFontface']];
    }



    /************************************
     *********** Parental Age ***********
     ************************************/
    if (isset($request['parentalAge'])) {
        if (!in_array($request['parentalAge'], $config['parentalAges'], true))
            $xmlData['editUserOptions']['parentalAge'] = (new fimError('badAge', 'The parental age specified is invalid. A list of valid parental ages can be obtained from the getServerStatus API.', null, true))->value();

        else
            $updateArray['userParentalAge'] = $request['parentalAge'];
    }



    /************************************
     ********** Parental Flags **********
     ************************************/
    if (isset($request['parentalFlags']))
        $updateArray['userParentalFlags'] = implode(',', $request['parentalFlags']);
}

/*** END: Editable Only Properties ***/



/************************************
 ********* Perform the Update *******
 ************************************/

if (count($updateArray) > 0) {
    $updateArray['messageFormatting'] = implode(';', $updateArray['messageFormatting']);

    $user->setDatabase($updateArray);
}




/************************************
 **** Edit/Replace/Delete Lists *****
 ************************************/

$database->autoQueue(true);

/* Watch Rooms (used for notifications of new messages, which are placed in unreadMessages) */
if (count($request['watchRooms'])) {
    $database->editRoomList('watchRooms', $user, $request['watchRooms'], $requestHead['_action']);
}



/* Fav List */
if (count($request['favRooms'])) {
    $database->editRoomList('favRooms', $user, $request['favRooms'], $requestHead['_action']);
}



/* Ignore List */
if (count($request['ignoreList'])) {
    $database->editUserLists('ignoreList', $user, $request['ignoreList'], $requestHead['_action']);
}



/* Friends List */
if (count($request['friendsList'])) {
    $database->editUserLists('friendsList', $user, $request['friendsList'], $requestHead['_action']);
}

$database->autoQueue(false);




/* Output Data */
echo new apiData($xmlData);
?>