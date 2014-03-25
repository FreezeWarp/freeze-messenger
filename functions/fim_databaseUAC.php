<?php
class fimDatabaseUAC extends fimDatabase {
  ///* Define Things *///

// These are the table names that are used in different integration methods. "Users" is required, while for the rest, if one is absent functionality will not be supported.
  private $tableDefinitions = array(
    'vbulletin4' => array(
      'users' => 'user',
      'adminGroups' => 'usergroup',
      'socialGroups' => 'socialgroup',
      'socialGroupMembers' => 'socialgroupmember',
    ),
    'phpbb' => array(
      'users' => 'users',
      'adminGroups' => false,
      'socialGroups' => 'groups',
      'socialGroupMembers' => 'user_group'
    ),
    'vanilla' => array(
      'users' => 'users',
      'adminGroups' => 'adminGroups',
      'socialGroups' => 'socialGroups',
      'socialGroupMembers' => 'socialGroupMembers'
    )
  );

  private $columnDefinitions = array( // These are only used for syncing. When the original database is queried (such as with password), the field will be used explictly there.
    'vbulletin4' => array(
      'users' => array(
        'userId' => 'userid', 'userName' => 'username',
        'userGroup' => 'displaygroupid', 'userGroupAlt' => 'usergroupid',
        'allGroups' => 'membergroupids', 'timeZone' => 'timezoneoffset',
        'options' => 'options',
      ),
      'adminGroups' => array(
        'groupId' => 'usergroupid', 'groupName' => 'title',
        'startTag' => 'opentag', 'endTag' => 'closetag',
      ),
      'socialGroups' => array(
        'groupId' => 'groupid', 'groupName' => 'name',
      ),
      'socialGroupMembers' => array(
        'groupId' => 'groupid', 'userId' => 'userid',
        'type' => 'type', 'validType' => 'member',
      ),
    ),
    'phpbb' => array(
      'users' => array(
        'userId' => 'user_id', 'userName' => 'username',
        'userGroup' => 'group_id', 'userGroupAlt' => 'group_id',
        'allGroups' => 'group_id', 'timeZone' => 'user_timezone',
        'color' => 'user_colour', 'avatar' => 'user_avatar',
      ),
      'adminGroups' => false,
      'socialGroups' => array(
        'groupId' => 'groupid', 'groupName' => 'name',
        'groupId' => 'group_id', 'groupName' => 'group_name',
      ),
      'socialGroupMembers' => array(
        'groupId' => 'group_id', 'userId' => 'user_id',
        'type' => 'user_pending', 'validType' => '0',
      ),
    ),
    'vanilla' => array(
      'users' => array(
        'userId' => 'userId', 'userName' => 'userName',
        'userGroupAlt' => 'userGroup', 'userGroup' => 'userGroup', // Note: Put 'userGroupAlt' first, since the array will later be flipped to generate a list of columns to select. (and userGroupAlt with thus be over-written with userGroup)
        'allGroups' => 'allGroups', 'socialGroups' => 'socialGroups',
        'timeZone' => 'timeZone', 'avatar' => 'avatar',
        'password' => 'password', 'passwordSalt' => 'passwordSalt',
        'passwordSaltNum' => 'passwordSaltNum', 'joinDate' => 'joinDate',
        'birthDate' => 'birthDate', 'interfaceId' => 'interfaceId',
        'status' => 'status',
        'userPrivs' => 'userPrivs', 'adminPrivs' => 'adminPrivs',
        'defaultRoom' => 'defaultRoom', 'defaultFormatting '=> 'defaultFormatting',
        'defaultHighlight' => 'defaultHighlight', 'defaultColor' => 'defaultColor',
        'defaultFontface' => 'defaultFontface', 'profile' => 'profile',
        'userFormatStart' => 'userFormatStart', 'userFormatEnd' => 'userFormatEnd',
        'lang' => 'lang',
        'userParentalAge' => 'userParentalAge', 'userParentalFlags' => 'userParentalFlags',
      ),
      'adminGroups' => false,
      'socialGroups' => array(
        'groupId' => 'groupId', 'groupName' => 'groupName',
      ),
      'socialGroupMembers' => array(
        'groupId' => 'group_id', 'userId' => 'user_id',
        'type' => 'user_pending', 'validType' => '0',
      ),
    ),
  );

  public function getUserFromUAC($options) {
    $queryParts['userSelect']['columns'] = array(
      "{$sqlPrefix}users" => 'userId, userName, userGroup, allGroups, avatar, profile, socialGroups, userFormatStart, userFormatEnd, password, joinDate, birthDate, lastSync, defaultRoom, interfaceId, status, defaultHighlight, defaultColor, defaultFontface, defaultFormatting, userPrivs, adminPrivs, lang, userParentalAge, userParentalFlags',
    );
    $queryParts['userSelectFromUserName']['conditions'] = array(
      'both' => array(
        'userName' => $database->str($userName),
      ),
    );
    $queryParts['userSelectFromUserId']['conditions'] = array(
      'both' => array(
        'userId' => $database->int($userId),
      ),
    );

    return $this->
  }



  public function syncInitial() {
    $queryParts['adminGroupSelect']['conditions'] = array(
      'both' => array(
        'groupId' => $database->int($user2['userGroup'] ? $user2['userGroup'] : $user2['userGroupAlt']), // Pretty much just for VB...
      ),
    );

    $queryParts['userPrefsSelect']['conditions'] = array(
      'both' => array(
        'userId' => $database->int($user2['userId']),
      ),
    );

    $queryParts['socialGroupsSelect']['columns'] = array(
      $sqlMemberGroupTable => array(
        $sqlMemberGroupTableCols['groupId'] => 'groupId',
        $sqlMemberGroupTableCols['userId'] => 'userId',
        $sqlMemberGroupTableCols['type'] => 'groupType',
      ),
    );

    $queryParts['socialGroupsSelect']['conditions'] = array(
      'both' => array(
        'userId' => $database->int($user2['userId']),
        'groupType' => $database->str($sqlMemberGroupTableCols['validType']),
      ),
    );




    switch ($loginConfig['method']) {

      case 'vbulletin3':
      case 'vbulletin4':
        if ($user2['options'] & 64) { // DST is autodetect. We'll just set it by hand.
          if ($generalCache->exists('fim_dst')) $dst = $generalCache->get('fim_dst');
          else {
            $currentDate = (int) (date('n') . date('d')); // Example: Janurary 1st would be 101, March 12th would be 312. Thus, every subsequent day is an increase numerically.

            $dstStart = (int) ('3' . date('d', strtotime('second sunday of march')));
            $dstEnd = (int) ('11' . date('d', strtotime('first sunday of november')));

            if ($currentDate >= $dstStart && $currentDate < $dstEnd) $dst = 1;
            else $dst = 0;

            $generalCache->set('fim_dst', $dst, $ttl = 3600); // We only call this if using vBulletin because it only slows things down otherwise. In addition, we only check every hour.
          }

          if ($dst) $user2['timeZone']++;
        }
        elseif ($user2['options'] & 128) { // DST is on, add an hour
          $user2['timeZone']++;
        }


        $group = $integrationDatabase->select(
          array(
            $sqlAdminGroupTable => array_flip($sqlAdminGroupTableCols),
          ),
          $queryParts['adminGroupSelect']['conditions'],
          false,
          1
        );
        $group = $group->getAsArray(false);

        $user2['userFormatStart'] = $group['startTag'];
        $user2['userFormatEnd'] = $group['endTag'];
        $user2['avatar'] = $loginConfig['url'] . '/image.php?u=' . $user2['userId'];
        $user2['profile'] = $loginConfig['url'] . '/member.php?u=' . $user2['userId'];
        break;



        if ($user2['userGroup']) {
          $group = $integrationDatabase->select(
            array(
              $sqlAdminGroupTable => array_flip($sqlAdminGroupTableCols),
            ),
            $queryParts['adminGroupSelect']['conditions'],
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


    $userPrefs = $integrationDatabase->select(
      $queryParts['userSelect']['columns'],
      $queryParts['userPrefsSelect']['conditions'],
      false,
      1
    );
    $userPrefs = $userPrefs->getAsArray(false);


    if (!$userPrefs) {

      /* Generate Default User Permissions */
      $priviledges = 16; // Can post

      if (!$anonymous) { // In theory, you can still manually allow anon users to do the other things.
        if ($config['userRoomCreation']) $priviledges += 32;
        if ($config['userPrivateRoomCreation']) $priviledges += 64;
      }



      /* Insert User Settings Entry */
      $database->insert("{$sqlPrefix}users",array(
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
        'lastSync' => $database->now(),
      ));



      /* Re-Obtain the User Settings */
      $userPrefs = $integrationDatabase->select(
        $queryParts['userSelect']['columns'],
        $queryParts['userPrefsSelect']['conditions'],
        false,
        1
      );
      $userPrefs = $userPrefs->getAsArray(false);



      /* Update Social Groups */
      $socialGroups = $integrationDatabase->select(
        $queryParts['socialGroupsSelect']['columns'],
        $queryParts['socialGroupsSelect']['conditions']
      );
      $socialGroups = $socialGroups->getAsArray('groupId');
      $socialGroupIds = array_keys($socialGroups);

      $database->update("{$sqlPrefix}users", array(
        'userName' => $user2['userName'],
        'userGroup' => $user2['userGroup'],
        'allGroups' => $user2['allGroups'],
        'userFormatStart' => $user2['userFormatStart'],
        'userFormatStart' => $user2['userFormatStart'],
        'avatar' => $user2['avatar'],
        'profile' => $user2['profile'],
        'socialGroups' => implode(',', $socialGroupIds),
        'lastSync' => $database->now(),
      ), array(
        'userId' => (int) $user2['userId'],
      ));
    }

    public function syncRefresh() {
      /* Update Social Groups */
      $socialGroups = $integrationDatabase->select(
        $queryParts['socialGroupsSelect']['columns'],
        $queryParts['socialGroupsSelect']['conditions']
      );
      $socialGroups = $socialGroups->getAsArray('groupId');
      $socialGroupIds = array_keys($socialGroups);

      $database->update("{$sqlPrefix}users", array(
        'userName' => $user2['userName'],
        'userGroup' => $user2['userGroup'],
        'allGroups' => $user2['allGroups'],
        'userFormatStart' => $user2['userFormatStart'],
        'userFormatEnd' => $user2['userFormatEnd'],
        'avatar' => $user2['avatar'],
        'profile' => $user2['profile'],
        'socialGroups' => implode(',', $socialGroupIds),
        'lastSync' => $database->now(),
      ), array(
        'userId' => (int) $user2['userId'],
      ));
    }


    $userPrefs = $integrationDatabase->select(
      $queryParts['userSelect']['columns'],
      $queryParts['userPrefsSelect']['conditions'],
      false,
      1
    );
    $userPrefs = $userPrefs->getAsArray(false);

    $user = $userPrefs; // Set user to userPrefs.}
  }

}
?>