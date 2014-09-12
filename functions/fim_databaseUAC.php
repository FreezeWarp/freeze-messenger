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


  /*
   * Obtains a user row from an integration table. The row is that processed (and merged into the vanilla table).
   */
  public function getUserFromUAC($options) {
    $options = array_merge(array(
      'userId' => 0,
      'userName' => '',
    ), $options);

    $conditions = array();

    if ($options['userId']) $conditions['both']['userId'] = $this->int($options['userId']);
    if ($options['userName']) $conditions['both']['userName'] = $this->str($options['userName']);

    $integrationUser = $this->select(array(
      $this->tableDefinitions['vbulletin4']['users'] => $this->columnDefinitions['vbulletin4']['users']
    ), $conditions);

    return $this->syncInitial($integrationUser);
  }



  public function getAdminGroup() {

    $queryParts['adminGroupSelect']['conditions'] = array(
      'both' => array(
        'groupId' => $database->int($user2['userGroup'] ? $user2['userGroup'] : $user2['userGroupAlt']), // Pretty much just for VB...
      ),
    );

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




  public function getSocialGroups() {
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

  }



  /**
   * Syncs a integration user's data into the vanilla users table.
   * @internal Aside from modifying a few attributes, this function basically just calls createUser() and is done with it.
   * @TODO: moderate function to headstart all of this.
   */
  public function syncInitial($integrationUser) {
    global $loginConfig, $generalCache;


    switch ($loginConfig['method']) {
      case 'vbulletin3':
      case 'vbulletin4':
        if ($user2['userGroup'] || $user2['userGroupAlt']) {
          $adminGroup = $this->getAdminGroup($integrationUser['userGroup'] ? $integrationUser['userGroup'] : $integrationUser['userGroupAlt']);
        }

        $integrationUser['userFormatStart'] = $adminGroup['startTag'];
        $integrationUser['userFormatEnd'] = $adminGroup['endTag'];
        $integrationUser['avatar'] = $loginConfig['url'] . '/image.php?u=' . $integrationUser['userId']; // TODO?
        $integrationUser['profile'] = $loginConfig['url'] . '/member.php?u=' . $integrationUser['userId'];
        break;

      case 'phpbb':
        if (!$integrationUser['color']) $integrationUser['color'] = $group['color'];

        $integrationUser['userFormatStart'] = "<span style=\"color: #$user2[color]\">";
        $integrationUser['userFormatEnd'] = '</span>';

        if ($integrationUser['avatar']) {
          $integrationUser['avatar'] = $loginConfig['url'] . 'do*wnload/file.php?avatar=' . $integrationUser['avatar'];
        }

        $integrationUser['profile'] = $loginConfig['url'] . 'memberlist.php?mode=viewprofile&u=' . $integrationUser['userId'];
        break;
    }


    if (!$integrationUser['avatar'] && $config['defaultAvatar']) {
      $integrationUser['avatar'] = $config['defaultAvatar'];
    }
    
    
    $this->createUser(array(
      'userId' => $integrationUser['userId'],
      'userName' => $integrationUser['userName'],
      'userGroup' => $integrationUser['userGroup'],
      'allGroups' => $integrationUser['allGroups'],
      'userFormatStart' => ($integrationUser['userFormatStart']),
      'userFormatEnd' => ($integrationUser['userFormatEnd']),
      'avatar' => ($integrationUser['avatar']),
      'profile' => ($integrationUser['profile']),
      'socialGroups' => ($socialGroups['groups']),
    ));

    syncRefresh();
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

  public function lockoutIncrement() {
    // Note: As defined, attempts will further increase, and expires will further increase, with each additional query beyond the "lockout". As a result, this function generally shouldn't be called if a user is already lockedout -- otherwise, further attempts just lock them out further, when they could be the user checking to see if they are still locked out. So always call lockoutActive before calling lockoutIncrement.
    $this->upsert($this->sqlPrefix . 'sessionLockout', array(
      'ip' => $_SERVER['REMOTE_ADDR'],
    ), array(
      'attempts' => $this->type('equation', '$attempts + 1'),
      'expires' => $this->now(60 * 15) // TOOD: Config
    ));

    return true;
  }

  public function lockoutActive() {
    global $config;

    // Note: Select condition format is experimental and untested, and numRows is not yet implemented. So, uh, do that. Lockout count is also unimplemented.
    if ($this->select(array(
        $this->sqlPrefix . 'sessionLockout' => 'ip, attempts, expires'
      ), array(
        'ip' => $_SERVER['REMOTE_ADDR'],
      ))->getColumnValue('attempts') >= $config['lockoutCount']) return true;

    return false;
  }
}
?>