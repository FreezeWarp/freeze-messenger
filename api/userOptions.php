<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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
 * @copyright Joseph T. Parsons 2017
 *
 * =GET Parameters=
 * @param string["edit", "delete", "create"] _action - The action parameter is used here for favRooms, friendsList, and ignoreList. If "edit," then those lists will be replaced with the list specified. If create, then those lists will be appended by the lists specified. If "delete," then those lists will have all items in the specified list deleted.
 *
 * =PUT Parameters=
These parameters are, where applicable, documented in the SQL documentation.
 * @param int defaultRoomId - The ID of the room that the user would like loaded when they login to FreezeMessenger.
 * @param uri avatar - (Vanilla logins only.) A file pointing to the user's avatar.
 * @param uri profile - (Vanilla logins only.) A url pointing to the user's profile.
 * @param csv[r,g,b] defaultColor - A comma-seperated list of the three chroma values, corresponding to red, green, and blue. The range of these chroma values is [0,255], where 0 is no color, and 255 is full color.
 * @param csv[r,g,b] defaultHighlight - Same as defaultColor.
 * @param string defaultFontface - A fontface name corresponding with the table returned by getServerStatus. It should be the name, and not the full font-family list.
 * @param list['bold', 'italic'] defaultFormatting - A list of default formatting styles to apply to all of the user's messages. Supports "bold" and "italic."
 * @param int parentalAge - The age-appropriateness of content the user has indicated they are willing to view. Increasing this will allow more mature content, and decreasing it will disallow such content.
 * @param list[parentalFlags] parentalFlags - A list of parental flags that the user has indicated they are willing to see. Valid entries are returned by getServerStatus.
 *
 * =POST/PUT/DELETE Parameters=
 * @param list[roomIds] favRooms - A list of room IDs corresponding to rooms the user has favourited. This exists to help synchronise different clients -- internally, the list is ignored.
 * @param list[userIds] friendsList - (Vanilla logins only.) A list of user IDs corresponding to users the user has friended. They may restrict private messages to this list. Additionally, future functionality is planned, but not currently supported.
 * @param list[userIds] ignoreList - A list of user IDs corresponding to users the user does not want to interact with. These users will not be able to initiate a private message with the user.
 *
 * =Errors=
 * Error codes are listed by the parent node they appear under:
 *
 * ==avatar==
 * @throw smallSize - The avatar's dimensions are below the server minimum. This may also occur if a valid image was not sent.
 * @throw bigSize - The avatar's dimensions exceed the server maximum.
 * @throw badType - The avatar's filetype is not supported.
 * @throw bannedFile - The file has been been blockzed by a server regex blacklist/whitelist.
 * @throw noUrl - The URL provided did not seem to be a URL.
 * @throw badUrl - The URL provided could not be resolved (didn't exist).
 * @throw avatarDisabled - Avatars are not supported by the FreezeMessenger server (typically because it is integrated with a seperate login system).

 * ==profile==
 * @throw noUrl - A valid URL was not provided.
 * @throw badUrl - The URL provided could not be resolved (didn't exist).
 * @throw bannedUrl - The URL has been blocked by a server regex blacklist.
 * @throw profileDisabled - Profiles are not supported by the FreezeMessenger server (typically because it is integrated with a seperate login system).

 * ==defaultRoom==
 * @throw invalidRoom - The room specified does not exist.
 * @throw noPerm - The user is not allowed to view the room specified.

 * ==defaultHighlight, defaultColor==
 * @throw disabled - Default highlight/color is disabled by the FreezeMessenger server.
 * @throw outOfRange1 - The first color value, red, is out of the [0,255] range.
 * @throw outOfRange2 - The second color value, green, is out of the [0,255] range.
 * @throw outOfRange3 - The second color value, blue, is out of the [0,255] range.
 * @throw badFormat - Too few, or too many, chroma values were specified.

 * ==defaultFontface==
 * @throw disabled - Default fonts are disabled by the FreezeMessenger server.
 * @throw noFont - The font specified does not exist.
 *
 * ==defaultFormatting==
 * No errors are thrown by defaultFormatting, however specifying an invalid value will cause an exception. If a parameter is disabled, it will simply not be applied.
 *
 * ==parentalAge==
 * @throw badAge - The parental age specified is not valid.
 *
 * ==parentalFlags==
 * No errors are thrown by parentalFlags, however specifying an invalid value will cause an exception.
 *
 *
 * =PUT/POST/DELETE Examples=
 * Note that when using PUT, every directive is supported. When using POST and DELETE, only the four lists are supported.
 *
 * PUT userOptions.php favRooms[]=1&favRooms[]=2&favRooms[]=3 == replaces the list of fav rooms with the new list, [1,2,3]
 * PUT userOptions.php favRooms[]=1&favRooms[]=2&favRooms[]=3&defaultHighlight=0,0,0 == replaces the list of fav rooms with the new list, [1,2,3]. Sets the default highlight color to black.
 * POST userOptions.php favRooms[]=1&favRooms[]=2&favRooms[]=3 == adds rooms 1, 2, and 3 from the fav rooms list
 * POST userOptions.php favRooms[]=1&favRooms[]=2&friendsLists[]=3 == adds room 1 to the fav rooms, room 2 to the favourite rooms, and user 3 to the friends list.
 * POST userOptions.php favRooms[]=1&favRooms[]=2&favRooms[]=3&defaultHighlight=0,0,0 == adds room 1 to the fav rooms, room 2 to the favourite rooms, and user 3 to the friends list. Though defaultHighlight is specified, this is a POST request, and it will thus be ignored.
 * DELETE userOptions.php favRooms[]=1&favRooms[]=2&favRooms[]=3 == removes rooms 1, 2, and 3 from the fav rooms list
 */

use Fim\Error;
use Fim\Room;
use Fim\User;

$apiRequest = true;

require('../global.php');


/* Get Request Data */
$requestHead = \Fim\Utilities::sanitizeGPC('g', array(
    '_action' => [],
));

$request = \Fim\Utilities::sanitizeGPC('p', array(
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
        'valid' => \Fim\Config::$parentalFlags,
        'evaltrue'  => true
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
    ),

    'privacyLevel' => array(
        'valid' => [User::USER_PRIVACY_ALLOWALL, User::USER_PRIVACY_BLOCKALL, User::USER_PRIVACY_FRIENDSONLY],
    )
));
\Fim\Database::instance()->accessLog('editUserOptions', $request);


/* Data Predefine */
$xmlData = array(
    'editUserOptions' => array(
    ),
);
$updateArray = [];


if ($user->isAnonymousUser())
    new \Fim\Error('anonymousUser', 'Anonymous users cannot change their settings.');


/************************************
 **** Editable Only Properties ******
 ************************************/
if ($requestHead['_action'] === 'edit' || $requestHead['_action'] === 'create') {

    /************************************
     ************ Avatar ****************
     ************************************/
    if (isset($request['avatar'])) {
        if (!$user->hasPriv('selfChangeAvatar'))
            $xmlData['editUserOptions']['avatar'] = (new \Fim\Error('noPerm', 'You cannot change your avatar.', null, true))->getArray();

        elseif ($request['avatar'] === '')
            $updateArray['avatar'] = $request['avatar'];

        elseif ((\Fim\Config::$avatarMustMatchRegex && !preg_match(\Fim\Config::$avatarMustMatchRegex, $request['avatar']))
            || (\Fim\Config::$avatarMustNotMatchRegex && preg_match(\Fim\Config::$avatarMustNotMatchRegex, $request['avatar'])))
            $xmlData['editUserOptions']['avatar'] = (new \Fim\Error('bannedFile', 'The avatar specified is not allowed.', null, true))->getArray();

        elseif (filter_var($request['avatar'], FILTER_VALIDATE_URL) === FALSE)
            $xmlData['editUserOptions']['avatar'] = (new \Fim\Error('noUrl', 'The URL is not a URL.', null, true))->getArray();

        elseif (\Fim\Config::$avatarMustExist && !Http\CurlRequest::exists($request['avatar']))
            $xmlData['editUserOptions']['avatar'] = (new \Fim\Error('badUrl', 'The URL does not exist.', null, true))->getArray();

        else {
            $imageData = getimagesize($request['avatar']);

            if ($imageData[0] <= \Fim\Config::$avatarMinimumWidth || $imageData[1] <= \Fim\Config::$avatarMinimumHeight)
                $xmlData['editUserOptions']['avatar'] = (new \Fim\Error('smallSize', 'The avatar specified is too small.', null, true))->getArray();

            elseif ($imageData[0] >= \Fim\Config::$avatarMaximumWidth || $imageData[1] >= \Fim\Config::$avatarMaximumHeight)
                $xmlData['editUserOptions']['avatar'] = (new \Fim\Error('bigSize', 'The avatar specified is too large.', null, true))->getArray();

            elseif (!in_array($imageData[2], \Fim\Config::$imageTypesAvatar))
                $xmlData['editUserOptions']['avatar'] = (new \Fim\Error('badType', 'The avatar is not a valid image type.', null, true))->getArray();

            else
                $updateArray['avatar'] = $request['avatar'];
        }
    }



    /************************************
     ************** Profile *************
     ************************************/
    if (isset($request['profile'])) {
        if (!$user->hasPriv('selfChangeProfile'))
            $xmlData['editUserOptions']['profile'] = (new \Fim\Error('noPerm', 'You cannot change your profile.', null, true))->getArray();

        elseif ($request['profile'] === '')
            $updateArray['profile'] = $request['profile'];

        elseif (filter_var($request['profile'], FILTER_VALIDATE_URL) === false)
            $xmlData['editUserOptions']['profile'] = (new \Fim\Error('noUrl', 'The URL is not a URL.', null, true))->getArray();

        elseif ((\Fim\Config::$profileMustMatchRegex && !preg_match(\Fim\Config::$profileMustMatchRegex, $request['profile']))
            || (\Fim\Config::$profileMustNotMatchRegex && preg_match(\Fim\Config::$profileMustNotMatchRegex, $request['profile'])))
            $xmlData['editUserOptions']['profile'] = (new \Fim\Error('bannedUrl', 'The URL specified is not allowed.', null, true))->getArray();

        elseif (\Fim\Config::$profileMustExist && !Http\CurlRequest::exists($request['profile']))
            $xmlData['editUserOptions']['profile'] = (new \Fim\Error('badUrl', 'The URL does not exist.', null, true))->getArray();

        else
            $updateArray['profile'] = $request['profile'];
    }



    /************************************
     *********** Default Room ID ********
     ************************************/
    if (isset($request['defaultRoomId'])) {
        $defaultRoom = new Room($request['defaultRoomId']);

        if (!$defaultRoom->exists()
            || !(\Fim\Database::instance()->hasPermission($user, $defaultRoom) & Room::ROOM_PERMISSION_VIEW))
            $xmlData['editUserOptions']['defaultRoomId'] = (new \Fim\Error('invalidRoom', 'The room specified does not exist.', null, true))->getArray();

        else
            $updateArray['defaultRoomId'] = $defaultRoom->id;
    }



    /************************************
     ********* Default Formatting *******
     ************************************/
    if (isset($request['defaultFormatting'])) {
        if (in_array('bold', $request['defaultFormatting']) && \Fim\Config::$defaultFormattingBold)
            $updateArray['messageFormatting'][] = 'font-weight:bold';

        if (in_array('italic', $request['defaultFormatting']) && \Fim\Config::$defaultFormattingItalics)
            $updateArray['messageFormatting'][] = 'font-style:italic';
    }



    /************************************
     ***** Default Highlight/Color ******
     ************************************/
    foreach (array('defaultHighlight', 'defaultColor') AS $value) {
        if (isset($request[$value])) {
            if (empty($request[$value])) {
                $updateArray['messageFormatting'][] = '';
            }
            else {
                $rgb[$value] = \Fim\Utilities::arrayValidate(explode(',', $request[$value]), 'int', true);

                if (!\Fim\Config::${'defaultFormatting' . substr($value, 7)})
                    $xmlData['editUserOptions'][$value] = (new \Fim\Error('disabled', $value . ' is disabled on this server.', null, true))->getArray();

                elseif (count($rgb[$value]) !== 3) // Too many entries.
                    $xmlData['editUserOptions'][$value] = (new \Fim\Error('badFormat', 'The ' . $value . ' value was not properly formatted.', null, true))->getArray();

                elseif ($rgb[$value][0] < 0 || $rgb[$value][0] > 255) // First val out of range.
                    $xmlData['editUserOptions'][$value] = (new \Fim\Error('outOfRange1', 'The first value ("red") was out of range.', null, true))->getArray();

                elseif ($rgb[$value][1] < 0 || $rgb[$value][1] > 255) // Second val out of range.
                    $xmlData['editUserOptions'][$value] = (new \Fim\Error('outOfRange2', 'The first value ("green") was out of range.', null, true))->getArray();

                elseif ($rgb[$value][2] < 0 || $rgb[$value][2] > 255) // Third val out of range.
                    $xmlData['editUserOptions'][$value] = (new \Fim\Error('outOfRange3', 'The first value ("blue") was out of range.', null, true))->getArray();

                else {
                    switch ($value) {
                        case 'defaultHighlight':
                            $updateArray['messageFormatting']['background-color'] = 'background-color:rgb(' . implode(',', $rgb[$value]) . ')';
                        break;
                        case 'defaultColor':
                            $updateArray['messageFormatting']['color'] = 'color:rgb(' . implode(',', $rgb[$value]) . ')';
                        break;
                    }
                }
            }
        }
    }

    if (isset($rgb['defaultHighlight'], $rgb['defaultColor'])) {
        if (\Fim\Utilities::contrast($rgb['defaultHighlight'], $rgb['defaultColor']) < \Fim\Config::$defaultFormattingMinimumContrast) {
            $xmlData['editUserOptions']['defaultHighlight'] = (new \Fim\Error('lowContrast', 'The chosen highlight and font color have too low of a contrast. The calculated contrast was ' . round(\Fim\Utilities::contrast($rgb['defaultHighlight'], $rgb['defaultColor']), 2) . '; please use a constrast of at least ' . \Fim\Config::$defaultFormattingMinimumContrast . '.', null, true))->getArray();
            unset($updateArray['messageFormatting']['background-color']);
            unset($updateArray['messageFormatting']['color']);
        }
    }
    elseif (isset($rgb['defaultHighlight']) && $rgb['defaultHighlight'] > 127) {
        $updateArray['messageFormatting'][] = 'color:rgb(25,25,25)';
    }
    elseif (isset($rgb['defaultColor']) && $rgb['defaultColor'] < 127) {
        $updateArray['messageFormatting'][] = 'background-color:rgb(225,225,255)';
    }



    /************************************
     ********* Default Fontface *********
     ************************************/
    if (isset($request['defaultFontface'])) {
        if (!\Fim\Config::$defaultFormattingFont)
            $xmlData['editUserOptions']['defaultFontface'] = (new \Fim\Error('disabled', 'Defaults fonts are disabled on this server.', null, true))->getArray();

        else if (!isset(\Fim\Config::$fonts[$request['defaultFontface']]))
            $xmlData['editUserOptions']['defaultFontface'] = (new \Fim\Error('noFont', 'The specified font is not recognised. A list of recognised fonts can be obtained through the getServerStatus API.', null, true))->getArray();

        else
            $updateArray['messageFormatting'][] = 'font-family:' . \Fim\Config::$fonts[$request['defaultFontface']];
    }



    /************************************
     *********** Parental Age ***********
     ************************************/
    if (isset($request['parentalAge'])) {
        if (!in_array($request['parentalAge'], \Fim\Config::$parentalAges, true))
            $xmlData['editUserOptions']['parentalAge'] = (new \Fim\Error('badAge', 'The parental age specified is invalid. A list of valid parental ages can be obtained from the getServerStatus API.', null, true))->getArray();

        else
            $updateArray['parentalAge'] = $request['parentalAge'];
    }



    /************************************
     ********** Parental Flags **********
     ************************************/
    if (isset($request['parentalFlags']))
        $updateArray['parentalFlags'] = $request['parentalFlags'];



    /************************************
     ********** Privacy Setting *********
     ************************************/
    if (isset($request['privacyLevel']))
        $updateArray['privacyLevel'] = $request['privacyLevel'];
}

/*** END: Editable Only Properties ***/



/************************************
 ********* Perform the Update *******
 ************************************/
if (count($updateArray) > 0) {
    if (isset($updateArray['messageFormatting'])) {
        $updateArray['messageFormatting'] = implode(';', $updateArray['messageFormatting']);
    }

    $user->setDatabase($updateArray);
}




/************************************
 **** Edit/Replace/Delete Lists *****
 ************************************/

\Fim\Database::instance()->autoQueue(true);


/* Fav List (used for notifications of new messages, which are placed in unreadMessages) */
if (isset($request['favRooms'])) {
    $user->editList('favRooms', $request['favRooms'], $requestHead['_action']);
}

/* Ignore List */
if (isset($request['ignoreList'])) {
    $user->editList('ignoredUsers', $request['ignoreList'], $requestHead['_action']);
}

/* Friends Lsit */
if (isset($request['friendsList'])) {
    $user->editList('friendedUsers', $request['friendsList'], $requestHead['_action']);
}

\Fim\Database::instance()->autoQueue(false);




/* Output Data */
echo new Http\ApiData($xmlData);
?>