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
 * Establishing a Login

 * Directives Used Specifically for Obtaining a SessionHash via This Script:
 * @param string userName - The username of the user.
 * @param string password - The password of the user.
 * @param string passwordEncrypt - The encryption used for obtaining a login. "plaintext" and "md5" are both accepted, but the latter can only be used with vBulletin v3. Other forms of encryption will be possible soon.
 * @param string apiVersion - The version of the API being used to login. It can be comma-seperated if multiple versions will work with the client. 3.0.0 is the only currently accepted version.
 * @param bool apiLogin - Pass this when you are trying to obtain a sessionhash from this script. Otherwise, nothing will output.

 * Standard Directives Required for __ALL__ API Calls:
 * @param string fim3_userId
 * @param string fim3_sessionHash


///* Require Base *///

require_once(dirname(__FILE__) . '/global.php');







///* Some Pre-Stuff *///

require(dirname(__FILE__) . '/functions/loginReqs.php');


static $api, $goodVersion;

$banned = false;
$anonymous = false;
$noSync = false;
$userId = 0;
$userName = '';
$password = '';
$sessionHash = '';
$session = '';

$loginDefs['syncMethods'] = array(
  'phpbb',
  'vbulletin3',
  'vbulletin4',
);




///* Obtain Login Data From Different Locations *///

if (isset($_POST['userName'],$_POST['password']) || isset($_POST['userId'],$_POST['password'])) { // API.
  $apiVersion = $_POST['apiVersion']; // Get the version of the software the client intended for.

  if (!$apiVersion) {
    define('LOGIN_FLAG','API_VERSION_STRING');
  }

  else {
    $apiVersionList = explode(',',$_POST['apiVersion']); // Split for all acceptable versions of the API.


    foreach ($apiVersionList AS $version) {
      $apiVersionSubs = explode('.',$_POST['apiVersion']); // Break it up into subversions.
      if (!isset($apiVersionSubs[1])) {
        $apiVersionSubs[1] = 0;
      }

      if (!isset($apiVersionSubs[2])) {
        $apiVersionSubs[2] = 0;
      }

      if ($apiVersionSubs[0] == 3 && $apiVersionSubs[1] == 0 && $apiVersionSubs[2] == 0) { // This is the same as version 3.0.0.
        $goodVersion = true;
      }
    }

    if ($goodVersion) {
      if (isset($_POST['userName'])) {
        $userName = fim_urldecode($_POST['userName']);
      }
      elseif (isset($_POST['userId'])) {
        $userId = fim_urldecode($_POST['userId']);
      }

      $password = fim_urldecode($_POST['password']);
      $passwordEncrypt = fim_urldecode($_POST['passwordEncrypt']);

      switch ($passwordEncrypt) {
        case 'md5':
        case 'plaintext':
        // Do nothing, yet.
        break;

        case 'base64':
        $password = base64_decode($password);
        break;

        default:
        define('LOGIN_FLAG','PASSWORD_ENCRYPT');
        break;
      }
    }
  }

  $api = true;
}

elseif (isset($_REQUEST['fim3_sessionHash'])) { // Session hash defined via sent data.
  $sessionHash = $_REQUEST['fim3_sessionHash'];

  $userIdComp = $_REQUEST['fim3_userId'];

  if (isset($_POST['apiLogin'])) {
    $api = true;
  }
}

elseif ((int) $anonymousUser >= 1 && isset($_REQUEST['apiLogin'])) { // Unregistered user support.
  $userId = $anonymousUser;
  $anonymous = true;
  $api = true;
}

elseif (isset($hookLogin)) {
  if (is_array($hookLogin)) {
    if (count($hookLogin) > 0) {
      if (isset($hookLogin['userName'])) {
        $userName = $hookLogin['userName'];
      }

      if (isset($hookLogin['password'])) {
        $password = $hookLogin['password'];
      }

      if (isset($hookLogin['sessionHash'])) {
        $sessionHash = $hookLogin['sessionHash'];
      }

      if (isset($hookLogin['userIdComp'])) {
        $userIdComp = $hookLogin['userIdComp'];
      }
    }
  }
}


($hook = hook('validate_retrieval') ? eval($hook) : '');






///* Define Things *///

$tableDefinitions = array(
  'users' => array(
    'vbulletin3' => 'user',
    'vbulletin4' => 'user',
    'phpbb' => 'users',
    'vanilla' => '',
  ),
  'adminGroups' => array(
    'vbulletin3' => 'usergroup',
    'vbulletin4' => 'usergroup',
    'phpbb' => '',
    'vanilla' => '',
  ),
  'socialGroups' => array(
    'vbulletin3' => 'socialgroup',
    'vbulletin4' => 'socialgroup',
    'phpbb' => 'groups',
    'vanilla' => '',
  ),
  'socialGroupMembers' => array(
    'vbulletin3' => 'socialgroupmember',
    'vbulletin4' => 'socialgroupmember',
    'phpbb' => 'user_group',
    'vanilla' => '',
  ),
);

$columnDefinitions = array(
  'users' => array(
    'vbulletin3' => array(
      'userId' => 'userid',
      'userName' => 'username',
      'userGroup' => 'displaygroupid',
      'allGroups' => 'membergroupids',
      'timeZone' => 'timezoneoffset',
      'options' => 'options',
      'password' => 'password',
      'salt' => 'salt',
    ),
    'vbulletin4' => array(
      'userId' => 'userid',
      'userName' => 'username',
      'userGroup' => 'displaygroupid',
      'allGroups' => 'membergroupids',
      'timeZone' => 'timezoneoffset',
      'options' => 'options',
      'password' => 'password',
      'salt' => 'salt',
    ),
    'phpbb' => array(
      'userId' => 'user_id',
      'userName' => 'username',
      'userGroup' => 'group_id',
      'allGroups' => 'group_id',
      'timeZone' => 'user_timezone',
      'color' => 'user_colour',
      'avatar' => 'user_avatar',
      'password' => 'user_password',
    ),
    'vanilla' => array(
      'userId' => 'userId',
      'userName' => 'userName',
      'userGroup' => 'userGroup',
      'allGroups' => 'allGroups',
      'timeZone' => 'timeZone',
      'avatar' => 'avatar',
      'password' => 'password',
    ),
  ),
  'adminGroups' => array(
    'vbulletin3' => array(
      'groupId' => 'usergroupid',
      'groupName' => 'title',
      'startTag' => 'opentag',
      'endTag' => 'closetag',
    ),
    'vbulletin4' => array(
      'groupId' => 'usergroupid',
      'groupName' => 'title',
      'startTag' => 'opentag',
      'endTag' => 'closetag',
    ),
    'phpbb' => array(

    ),
    'vanilla' => array(

    ),
  ),
  'socialGroups' => array(
    'vbulletin3' => array(
      'groupId' => 'groupid',
      'groupName' => 'name',
    ),
    'vbulletin4' => array(
      'groupId' => 'groupid',
      'groupName' => 'name',
    ),
    'phpbb' => array(
      'groupId' => 'group_id',
      'groupName' => 'group_name',
    ),
    'vanilla' => array(

    ),
  ),
  'socialGroupMembers' => array(
    'vbulletin3' => array(
      'groupId' => 'groupid',
      'userId' => 'userid',
      'type' => 'type',
      'validType' => 'member',
    ),
    'vbulletin4' => array(
      'groupId' => 'groupid',
      'userId' => 'userid',
      'type' => 'type',
      'validType' => 'member',
    ),
    'phpbb' => array(
      'groupId' => 'group_id',
      'userId' => 'user_id',
      'type' => 'user_pending',
      'validType' => '0',
    ),
    'vanilla' => array(

    ),
  ),
);


$queryParts['userSelect']['columns'] = array(
  "{$sqlPrefix}users" => array(
    'userId' => 'userId',
    'userName' => 'userName',
    'userGroup' => 'userGroup',
    'allGroups' => 'allGroups',
    'avatar' => 'avatar',
    'profile' => 'profile',
    'socialGroups' => 'socialGroups',
    'userFormatStart' => 'userFormatStart',
    'userFormatEnd' => 'userFormatEnd',
    'password' => 'password',
    'joinDate' => 'joinDate',
    'birthDate' => 'birthDate',
    'lastSync' => 'lastSync',
    'defaultRoom' => 'defaultRoom',
    'interface' => 'interface',
    'favRooms' => 'favRooms',
    'watchRooms' => 'watchRooms',
    'ignoreList' => 'ignoreList',
    'status' => 'status',
    'defaultHighlight' => 'defaultHighlight',
    'defaultColor' => 'defaultColor',
    'defaultFontface' => 'defaultFontface',
    'defaultFormatting' => 'defaultFormatting',
    'userPrivs' => 'userPrivs',
    'adminPrivs' => 'adminPrivs',
    'lang' => 'lang',
  ),
);
$queryParts['userSelectFromSessionHash']['columns'] = array(
  "{$sqlPrefix}sessions" => array(
    'anonId' => 'anonId',
    'magicHash' => 'magicHash',
    'userId' => 'suserId',
    'time' => array(
      'name' => 'sessionTime',
      'context' => 'time',
    ),
    'ip' => 'sessionIp',
    'browser' => 'sessionBrowser',
  ),
);
$queryParts['userSelectFromUserName']['conditions'] = array(
  'both' => array(
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'userName',
      ),
      'right' => array(
        'type' => 'string',
        'value' => $userName,
      ),
    ),
  ),
);
$queryParts['userSelectFromUserId']['conditions'] = array(
  'both' => array(
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'userId',
      ),
      'right' => array(
        'type' => 'int',
        'value' => (int) $userId,
      ),
    ),
  ),
);
$queryParts['userSelectFromSessionHash']['conditions'] = array(
  'both' => array(
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'userId',
      ),
      'right' => array(
        'type' => 'column',
        'value' => 'suserId',
      ),
    ),
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'magicHash',
      ),
      'right' => array(
        'type' => 'string',
        'value' => $sessionHash,
      ),
    ),
  ),
);

($hook = hook('validate_start') ? eval($hook) : '');








///* Generate Proper Table Names for Integration *///

if (isset($tableDefinitions['users'][$loginConfig['method']])) {
  $sqlUserTable = $forumTablePrefix . $tableDefinitions['users'][$loginConfig['method']];
  $sqlAdminGroupTable = $forumTablePrefix . $tableDefinitions['adminGroups'][$loginConfig['method']];
  $sqlUserGroupTable = $forumTablePrefix . $tableDefinitions['socialGroups'][$loginConfig['method']];
  $sqlMemberGroupTable = $forumTablePrefix . $tableDefinitions['socialGroupMembers'][$loginConfig['method']];

  $sqlUserTableCols = $columnDefinitions['users'][$loginConfig['method']];
  $sqlAdminGroupTableCols = $columnDefinitions['adminGroups'][$loginConfig['method']];
  $sqlUserGroupTableCols = $columnDefinitions['socialGroups'][$loginConfig['method']];
  $sqlMemberGroupTableCols = $columnDefinitions['socialGroupMembers'][$loginConfig['method']];
}
else {
  die('Integration Subsystem Misconfigured: Login Method "' . $loginConfig['method'] . '" Unrecognized');
}






///* Process Login Data *///

if (strlen($sessionHash) > 0) {
  $user = $database->select(
    array( // Columns
      "{$sqlPrefix}users" => $queryParts['userSelect']['columns']["{$sqlPrefix}users"],
      "{$sqlPrefix}sessions" => $queryParts['userSelectFromSessionHash']['columns']["{$sqlPrefix}sessions"]
    ),
    $queryParts['userSelectFromSessionHash']['conditions']
  );
  $user = $user->getAsArray(false);

  if ($user) {
    if ((int) $user['userId'] !== (int) $userIdComp) { // The userid sent has to be the same one in the DB. In theory we could just not require a userId be specified, but there are benefits to this alternative. For instance, this eliminates some forms of injection-based session fixation.

      define('LOGIN_FLAG','INVALID_SESSION');

      $valid = false;
    }
    elseif ($user['sessionBrowser'] !== $_SERVER['HTTP_USER_AGENT']) { // Require the UA match that of the the one used to establish the session. Smart clients are encouraged to specify there own with their client name and vers
      define('LOGIN_FLAG','INVALID_SESSION');

      $valid = false;
    }
    elseif ($user['sessionIp'] !== $_SERVER['REMOTE_ADDR']) {
      define('LOGIN_FLAG','INVALID_SESSION');

      $valid = false;
    }
    else {
      if ($user['anonId']) {
        $anonId = $user['anonId'];
        $anonymous = true;
      }

      $noSync = true;
      $valid = true;

      if ($user['sessionTime'] < time() - 300) { // If five minutes have passed since the session has been generated, update ift.
        $session = 'update';
      }
    }
  }
  else {
    define('LOGIN_FLAG','INVALID_SESSION');

    $valid = false;
  }
}


elseif ($userName && $password) {
  $user = $integrationDatabase->select(
    array(
      $sqlUserTable => array_flip($sqlUserTableCols),
    ),
    $queryParts['userSelectFromUserName']['conditions'],
    false,
    false,
    1
  );
  $user = $user->getAsArray(false);

  if (processLogin($user,$password,'plaintext')) {
    $valid = true;
    $session = 'create';
  }
  else {
    $valid = false;
  }
}


elseif ($userId && $password) {
  $user = $integrationDatabase->select(
    array(
      $sqlUserTable => array_flip($sqlUserTableCols),
    ),
    $queryParts['userSelectFromUserId']['conditions'],
    false,
    false,
    1
  );
  $user = $user->getAsArray(false);


  if (processLogin($user,$password,'plaintext')) {
    $valid = true;
    $session = 'create';
  }
  else {
    $valid = false;
  }
}


elseif ($anonymousUser && $anonymous) {
  $user = $integrationDatabase->select(
    array(
      $sqlUserTable => array_flip($sqlUserTableCols),
    ),
    $queryParts['userSelectFromUserId']['conditions'],
    false,
    false,
    1
  );
  $user = $user->getAsArray(false);

  $valid = true;
  $api = true;
  $session = 'create';
}

else {
  $valid = false;
}

($hook = hook('validate_process') ? eval($hook) : '');






///* Final Forum-Specific Processing *///

if ($valid) { // If the user is valid, process their preferrences.

  if ($noSync || $loginConfig['method'] == 'vanilla') {

  }
  elseif (in_array($loginConfig['method'],$loginDefs['syncMethods'])) {
    $user2 = $user; // Create a copy of user, which will later be unset.
    unset($user); // Unset user, so we don't have to worry about collision.



    $queryParts['adminGroupSelect']['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'groupId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $user2['userGroup']
          ),
        ),
      ),
    );

    $queryParts['userPrefsSelect']['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'userId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $user2['userId'],
          ),
        ),
      ),
    );

    $queryParts['socialGroupsSelect']['columns'] = array(
      $sqlMemberGroupTable => array(
        $sqlMemberGroupTableCols['groupId'] => array(
          'context' => 'join',
          'name' => 'groups',
          'separator' => ',',
        ),
        $sqlMemberGroupTableCols['userId'] => 'userId',
        $sqlMemberGroupTableCols['type'] => 'groupType',
      ),
    );

    $queryParts['socialGroupsSelect']['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'userId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $user2['userId'],
          ),
        ),
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'groupType',
          ),
          'right' => array(
            'type' => 'string',
            'value' => $sqlMemberGroupTableCols['validType'],
          ),
        ),
      ),
    );




    switch ($loginConfig['method']) {

      case 'vbulletin3':
      case 'vbulletin4':
      if ($user2['options'] & 64) {
        if (date('I')) {
          $user2['timeZone']++; // DST is autodetect. We'll just set it by hand.
        }
      }
      elseif ($user2['options'] & 128) {
        $user2['timeZone']++; // DST is on, add an hour
      }


      $group = $integrationDatabase->select(
        array(
          $sqlAdminGroupTable => array_flip($sqlAdminGroupTableCols),
        ),
        $queryParts['adminGroupSelect']['conditions'],
        false,
        false,
        1
      );
      $group = $group->getAsArray(false);


      $user2['userFormatStart'] = $group[$sqlAdminGroupTableCols['startTag']];
      $user2['userFormatEnd'] = $group[$sqlAdminGroupTableCols['endTag']];
      $user2['avatar'] = $loginConfig['url'] . '/image.php?u=' . $user2['userId'];
      $user2['profile'] = $loginConfig['url'] . '/member.php?u=' . $user2['userId'];
      break;



      case 'phpbb':
      if ($user2['userGroup']) {
        $group = $integrationDatabase->select(
          array(
            $sqlAdminGroupTable => array_flip($sqlAdminGroupTableCols),
          ),
          $queryParts['adminGroupSelect']['conditions'],
          false,
          false,
          1
        );
        $group = $group->getAsArray(false);
      }


      if (!$user2['color']) {
        $user2['color'] = $group['color'];
      }

      $user2['userFormatStart'] = "<span style=\"color: #$user2[color]\">";
      $user2['userFormatEnd'] = '</span>';


      if ($user2['avatar']) {
        $user2['avatar'] = $loginConfig['url'] . 'download/file.php?avatar=' . $user2['avatar'];
      }

      $user2['profile'] = $loginConfig['url'] . 'memberlist.php?mode=viewprofile&u=' . $user2['userId'];
      break;
    }

    if (!$user2['avatar'] && isset($config['defaultAvatar'])) {
      $user2['avatar'] = $config['defaultAvatar'];
    }


    ($hook = hook('validate_preprefs') ? eval($hook) : '');


    $userPrefs = $integrationDatabase->select(
      $queryParts['userSelect']['columns'],
      $queryParts['userPrefsSelect']['conditions'],
      false,
      false,
      1
    );
    $userPrefs = $userPrefs->getAsArray(false);


    if (!$userPrefs) {

      /* Generate Default User Permissions */
      $priviledges = 16; // Can post

      if (!$anonymous) { // In theory, you can still manually allow anon users to do the other things.
        if ($userPermissions['roomCreation']) {
          $priviledges += 32;
        }
        if ($userPermissions['privateRoomCreation']) {
          $priviledges += 64;
        }
      }



      /* Insert User Settings Entry */
      $database->insert(array(
        'userId' => (int) $user2['userId'],
        'userName' => ($user2['userName']),
        'userGroup' => (int) $user2['userGroup'],
        'allGroups' => ($user2['allGroups']),
        'userFormatStart' => ($user2['userFormatStart']),
        'userFormatEnd' => ($user2['userFormatEnd']),
        'avatar' => ($user2['avatar']),
        'profile' => ($user2['profile']),
        'socialGroups' => ($socialGroups['groups']),
        'userPrivs' => (int) $priviledges,
        'lastSync' => array(
          'type' => 'raw',
          'value' => 'NOW()',
        ),
      ),"{$sqlPrefix}users");



      /* Re-Obtain the User Settings */
      $userPrefs = $integrationDatabase->select(
        $queryParts['userSelect']['columns'],
        $queryParts['userPrefsSelect']['conditions'],
        false,
        false,
        1
      );
      $userPrefs = $userPrefs->getAsArray(false);



      /* Update Social Groups */
      $socialGroups = $integrationDatabase->select(
        $queryParts['socialGroupsSelect']['columns'],
        $queryParts['socialGroupsSelect']['conditions']
      );
      $socialGroups = $socialGroups->getAsArray(false);

      $database->update(array(
        'userName' => $user2['userName'],
        'userGroup' => $user2['userGroup'],
        'allGroups' => $user2['allGroups'],
        'userFormatStart' => $user2['userFormatStart'],
        'userFormatStart' => $user2['userFormatStart'],
        'avatar' => $user2['avatar'],
        'profile' => $user2['profile'],
        'socialGroups' => $socialGroups['groups'],
        'lastSync' => array(
          'type' => 'raw',
          'value' => 'NOW()',
        ),
      ),
      "{$sqlPrefix}users",
      array(
        'userId' => (int) $user2['userId'],
      ));
    }

    elseif ($userPrefs['lastSync'] <= (time() - (isset($config['userSyncThreshold']) ? $config['userSyncThreshold'] : 0))) { // This updates various caches every so often. In general, it is a rather slow process, and as such does tend to take a rather long time (that is, compared to normal - it won't exceed 500miliseconds, really).

      /* Favourite Room Cleanup
      * Remove all favourite groups a user is no longer a part of. */
      if (strlen($userPrefs['favRooms']) > 0) {
        $favRooms = $database->select(
          array(
            "{$sqlPrefix}rooms" => array(
              'roomId' => 'roomId',
              'roomName' => 'roomName',
              'owner' => 'owner',
              'defaultPermissions' => 'defaultPermissions',
              'options' => 'options',
            ),
          ),
          array(
            'both' => array(
              array(
                'type' => 'bitwise',
                'left' => array(
                  'type' => 'column',
                  'value' => 'options',
                ),
                'right' => array(
                  'type' => 'int',
                  'value' => 4,
                ),
              ),
              array(
                'type' => 'in',
                'left' => array(
                  'type' => 'column',
                  'value' => 'roomId',
                ),
                'right' => array(
                  'type' => 'array',
                  'value' => fim_arrayValidate(explode(',',$userPrefs['favRooms']),'int',false),
                ),
              ),
            ),
          )
        );
        $favRooms = $favRooms->getAsArray('roomId');


        if (is_array($favRooms)) {
          if (count($favRooms) > 0) {
            foreach ($favRooms AS $roomId => $room) {
              eval(hook('templateFavRoomsEachStart'));

              if (!fim_hasPermission($room,$userPrefs,'view')) {
                $currentRooms = fim_arrayValidate(explode(',',$userPrefs['favRooms']),'int',false);

                foreach ($currentRooms as $room2) {
                  if ($room2 != $room['roomId']) { // Rebuild the array without the room ID.
                    $currentRooms2[] = (int) $room2;
                  }
                }
              }
            }

            if (count($currentRooms2) !== count($favRooms)) {
              $database->update(array(
                'favRooms' => implode(',',$currentRooms2),
              ),
              "{$sqlPrefix}users",
              array(
                'userId' => $userPrefs['userId'],
              ));
            }

            unset($room);
          }
        }
      }



      /* Update Social Groups */
      $socialGroups = $integrationDatabase->select(
        $queryParts['socialGroupsSelect']['columns'],
        $queryParts['socialGroupsSelect']['conditions']
      );
      $socialGroups = $socialGroups->getAsArray(false);

      $database->update(array(
        'userName' => $user2['userName'],
        'userGroup' => $user2['userGroup'],
        'allGroups' => $user2['allGroups'],
        'userFormatStart' => $user2['userFormatStart'],
        'userFormatStart' => $user2['userFormatStart'],
        'avatar' => $user2['avatar'],
        'profile' => $user2['profile'],
        'socialGroups' => $socialGroups['groups'],
        'lastSync' => array(
          'type' => 'raw',
          'value' => 'NOW()',
        ),
      ),
      "{$sqlPrefix}users",
      array(
        'userId' => (int) $user2['userId'],
      ));
    }


    $userPrefs = $integrationDatabase->select(
      $queryParts['userSelect']['columns'],
      $queryParts['userPrefsSelect']['conditions'],
      false,
      false,
      1
    );
    $userPrefs = $userPrefs->getAsArray(false);


    $user = $userPrefs; // Set user to userPrefs.
  }
  else {
    die('Login Subsystem Unconfigured');
  }





  if ($session == 'create') {
    ($hook = hook('validate_createsession') ? eval($hook) : '');

    $sessionHash = fim_generateSession();

    $anonId = rand(1,10000);

    $database->insert(array(
      'userId' => $user['userId'],
      'anonId' => ($anonymous ? $anonId : 0),
      'time' => array(
        'type' => 'raw',
        'value' => 'NOW()',
      ),
      'magicHash' => $sessionHash,
      'browser' => $_SERVER['HTTP_USER_AGENT'],
      'ip' => $_SERVER['REMOTE_ADDR'],
    ),"{$sqlPrefix}sessions");

    // Whenever a new user logs in, delete all sessions from 15 or more minutes in the past.
    $database->delete("{$sqlPrefix}sessions",array(
      'time' => array(
        'type' => 'raw', // Data in the value column should not be escaped.
        'cond' => 'lte', // Comparison is "<="
        'context' => 'time', // We are comparing two times; the column should be processed as a timestamp.
        'value' => 'UNIX_TIMESTAMP(NOW()) - 900',
      ),
    ));
  }

  elseif ($session == 'update' && $sessionHash) {
    ($hook = hook('validate_updatesession') ? eval($hook) : '');

    $database->update(array(
      'time' => array(
        'type' => 'raw',
        'value' => 'NOW()',
      ),
    ),
    "{$sqlPrefix}sessions",
    array(
      "magicHash" => $sessionHash,
    ));
  }

  else {
    // I dunno...
  }



  if ($anonymous) {
    $user['userName'] .= $anonId;
  }

}

else {
  unset($user);

  $user = array(
    'userId' => ($anonymousUser ? $anonymousUser : 0), // TODO: Is this handled elsewhere?
    'userName' => '',
    'settingsOfficialAjax' => 11264, // Default. TODO: Update w/ config defaults.
    'adminPrivs' => 0, // Nothing
    'userPrivs' => 16, // Allowed, but nothing else.
  );

  ($hook = hook('validate_loginInvalid') ? eval($hook) : '');
}







/* The following defines each individual user's options via an associative array. It is highly recommended this be used to referrence settings. */

if (is_array($loginConfig['superUsers'])) {
  if (in_array($user['userId'],$loginConfig['superUsers'])) {
    $user['adminPrivs'] = 65535; // Super-admin, away!!!! (this defines all bitfields up to 32768)
  }
}


$user['adminDefs'] = array(
  'modPrivs' => ($user['adminPrivs'] & 1), // This effectively allows a user to give himself everything else below
  'modCore' => ($user['adminPrivs'] & 2), // This is the "untouchable" flag, but that's more or less all it means.
  'modUsers' => ($user['adminPrivs'] & 16), // Ban, Unban, etc.
  'modFiles' => ($user['adminPrivs'] & 64), // File Uploads
  'modCensor' => ($user['adminPrivs'] & 256), // Censor
  'modBBCode' => ($user['adminPrivs'] & 1024), // BBCode

  /* Should Generally Go Together */
  'modPlugins' => ($user['adminPrivs'] & 4096), // Plugins
  'modTemplates' => ($user['adminPrivs'] & 8192), // Templates
  'modHooks' => ($user['adminPrivs'] & 16384), // Hooks
);


$user['userDefs'] = array(
  'allowed' => ($user['userPrivs'] & 16), // Is not banned
  'createRooms' => ($user['userPrivs'] & 32), // May create rooms
  'privateRooms' => ($user['userPrivs'] & 64), // May create private rooms

  'roomsOnline' => ($user['userPrivs'] & 1024), // May see rooms online (API-handled).
  'postCounts' => ($user['userPrivs'] & 2048), // May see post counts (API-handled).
);





/* General "Hard" Ban Generation (If $banned == true, the user will have no permissions) */

if ($valid) {

  if ($bannedUserGroups) { // The user is in a usergroup that is banned.
    if (fim_inArray($bannedUserGroups,explode(',',$user['allGroups']))) {
      $banned = true;
    }
  }
  elseif (!$user['userDefs']['allowed']) { // The user is not allowed to access the chat.
    $banned = true;
  }

  if ($user['adminDefs']['modCore']) { // The user is an admin, don't give a crap about the above!
    $banned = false;
  }

}





/* API Output */

if ($api) {

  if (defined('LOGIN_FLAG')) {
    switch (LOGIN_FLAG) { // Generate a message based no the LOGIN_FLAG constant (...this should probably be a variable since it changes, but meh - it seems more logical as such)
      case 'PASSWORD_ENCRYPT':
      $errDesc = 'The password encryption used was not recognized and could not be decoded.';
      break;

      case 'BAD_USERNAME':
      $errDesc = 'The user was not recognized.';
      break;

      case 'BAD_PASSWORD':
      $errDesc = 'The password was not correct.';
      break;

      case 'API_VERSION_STRING':
      $errDesc = 'The API version string specified is not recognized.';
      break;

      case 'DEPRECATED_VERSION':
      $errDesc = 'The API version specified is deprecated and may no longer be used.';
      break;

      case 'INVALID_SESSION':
      $errDesc = 'The specified session is no longer valid.';
      break;
    }
  }
  elseif ($valid === false) { // Generic login flag
    define('LOGIN_FLAG','INVALID_LOGIN');

    $errDesc = 'The login was incorrect.';
  }
  elseif ($valid !== true) {
    die('Logic Error - Programmer Oversight');
  }


  $xmlData = array(
    'login' => array(
      'valid' => (bool) $valid,

      'loginFlag' => (defined('LOGIN_FLAG') ? LOGIN_FLAG : ''),
      'loginText' => $errDesc,

      'sessionHash' => $sessionHash,
      'anonId' => ($anonymous ? $anonId : 0),
      'defaultRoomId' => (int) (isset($_GET['room']) ? $_GET['room'] :
        (isset($user['defaultRoom']) ? $user['defaultRoom'] :
          (isset($defaultRoom) ? $defaultRoom : 1))), // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.

      'userData' => array(
        'userName' => ($user['userName']),
        'userId' => (int) $user['userId'],
        'userGroup' => (int) $user['userGroup'],
        'avatar' => (isset($user['avatar']) ? $user['avatar'] :
          (isset($avatarBase) ? $avatarBase : '')),
        'profile' => (isset($user['profile']) ? $user['profile'] : ''),
        'socialGroups' => ($user['socialGroups']),
        'startTag' => ($user['userFormatStart']),
        'endTag' => ($user['userFormatEnd']),
        'defaultFormatting' => array(
          'color' => ($user['defaultColor']),
          'highlight' => ($user['defaultHighlight']),
          'fontface' => ($user['defaultFontface']),
          'general' => (int) $user['defaultFormatting']
        ),
      ),

      'userPermissions' => $user['userDefs'],
      'adminPermissions' => $user['adminDefs'],
      'banned' => $banned,
    ),
  );


  ($hook = hook('validate_api') ? eval($hook) : '');

  echo fim_outputApi($xmlData);

  die();
}


($hook = hook('validate_end') ? eval($hook) : '');
?>