<?php
class fimUser {
  public $id;
  public $name;
  public $socialGroupIds;
  public $mainGroupId;
  public $allGroupIds;
  public $parentalFlags;
  public $parentalAge;
  public $anonId;
  public $privs;

  protected $userData;
  protected $resolved;


  /**
   * @param $roomData mixed Should either be an array or an integer (other values will simply fail to populate the object's data). If an array, should correspond with a row obtained from the `rooms` database, if an integer should correspond with the room ID.
   */
  public function __construct($userData) {
    if (is_int($userData)) $this->id = $userData;
    elseif (is_array($userData)) $this->populateFromArray($userData); // TODO: test contents
    elseif ($userData === false) $this->id = false;
    else throw new Exception('Invalid room data specified -- must either be an associative array corresponding to a table row, a user ID, or false (to create a user, etc.)');

    $this->userData = $userData;
  }


  public function __get($property) {
    if (in_array($property, array('', '', '', ''))) {

    }
  }


  function checkPassword($password) {
    switch ($this->userData['passwordFormat']) {
      case 'phpass':
        if (!isset($this->userData['passwordHash'])) {
          throw new Exception('User object was not generated with password hash information.');
        }
        else {
          require 'PasswordHash.php';

          $h = new PasswordHash(8, FALSE);

          return $h->CheckPassword($password, $this->userData['passwordHash']);
        }
        break;

      case 'vbmd5':
        if (!isset($this->userData['passwordHash'], $this->userData['passwordSalt'])) {
          throw new Exception('User object was not generated with password hash information.');
        }
        else {
          if ($this->userData['passwordHash'] === md5($password . $this->userData['passwordSalt'])) return true;
          else return false;
        }
        break;

      case 'raw':
        if ($this->userData['passwordHash'] === md5($password . $this->userData['passwordSalt'])) return true;
        else return false;
        break;

      default:
        throw new Exception('Invalid password format.');
        break;
    }
  }



  // TODO: Resolve only certain data.
  public function resolve() {
    global $database;

    if (!$this->id) return false; // If no ID is present, return false;
    elseif (!$this->resolved) {
      return $this->populateFromArray($database->getUsers(array(
        'userIds' => array($this->id)
      ))->getAsArray(false));
    }
    else return true;
  }



  private function populateFromArray($userData) {
    global $loginConfig, $generalCache, $config; // $config is todo

    /* Make sure userData contains neccessary information. */
    if (!count($userData) || !is_array($userData) || !isset($userData['userId'], $userData['userName'], $userData['userParentalFlags'], $userData['userParentalAge'], $userData['privs'])) return false;


    if (!$userData['lastSync'] <= (time() - $config['userSyncThreshold'])) { // This updates various caches every so often. In general, it is a rather slow process, and as such does tend to take a rather long time (that is, compared to normal - it won't exceed 500 miliseconds, really).
      // $userData = $database->updateUserCaches(); // TODO
    }


    /* Set Basic Information */
    $this->id = (int) $userData['userId'];
    $this->name = $userData['userName'];
    $this->privs = (int) $userData['privs'];
    
    
    /* Priviledges */
    // If certain features are disabled, remove user priviledges. The bitfields should be maintained, however, for when a feature is reenabled.
    if (!$generalCache->getConfig('userRoomCreation')) $this->privs &= ~USER_PRIV_CREATE_ROOMS;
    if (!$generalCache->getConfig('userPrivateRoomCreation')) $this->privs &= ~(USER_PRIV_PRIVATE_ALL | USER_PRIV_PRIVATE_FRIENDS); // Note: does not disable the usage of existing private rooms. Use "privateRoomsEnabled" for this.
    if ($generalCache->getConfig('disableTopic')) $this->privs &= ~USER_PRIV_TOPIC; // Topics are disabled (in fact, this one should also disable the returning of topics; TODO).

    // Certain bits imply other bits. Make sure that these are consistent.
    if ($this->privs & USER_PRIV_PRIVATE_ALL) $this->privs |= USER_PRIV_PRIVATE_FRIENDS;

    // Superuser override (note that any user with GRANT or in the $config superuser array is automatically given all permissions, and is marked as protected. The only way, normally, to remove a user's GRANT status, because they are automatically protected, is to do so directly in the database.)
    if (in_array($this->id, $loginConfig['superUsers']) || ($this->privs & ADMIN_GRANT)) $this->privs = 0x7FFFFFFF;
    elseif ($this->privs & ADMIN_ROOMS) $this->privs |= (USER_PRIV_VIEW | USER_PRIV_POST | USER_PRIV_TOPIC); // Being a super-moderator grants a user the ability to view, post, and make topic changes in all rooms.


    /* Anon User Information */
    if ($this->id === $generalCache->getConfig('anonymousUserId')) {
      $this->anonId = rand($generalCache->getConfig('anonymousUserMinId'), $generalCache->getConfig('anonymousUserMaxId'));
      $this->name .= $this->anonId;
    }


    /* Set Username Formatting */
    $this->avatar = $userData['avatar'] ?: $generalCache->getConfig('avatarDefault');
    $this->nameFormat = $userData['userNameFormat'] ?: $generalCache->getConfig('userNameFormat');


    /* Set Default Formatting */
    $this->messageFormatting = $userData['messageFormatting'];


    /* Set Misc Information */
    $this->defaultRoomId = $userData['defaultRoomId'] ?: $generalCache->getConfig('defaultRoomId');
    $this->profile = $userData['profile'];


    /* Handle Groups */
    $this->socialGroupIds = explode(',', $userData['socialGroupIds']);
    $this->mainGroupId = $userData['userGroupId'];
    $this->allGroupIds = explode(',', $userData['allGroupIds']);


    /* Set Parental Information */
    if ($config['parentalEnabled']) {
      $this->parentalFlags = explode(',', $userData['userParentalFlags']);
      $this->parentalAge = $userData['userParentalAge'];
    }
    else {
      $this->parentalFlags = array();
      $this->parentalAge = 255;
    }


    /* Mark Resolved */
    $this->resolved = true;


    /* Return True */
    return true;
  }



  /**
   * Modify or create a room.
   * @internal The row will be set to a merge of roomDefaults->existingRow->specifiedOptions.
   *
   * @param $options - Corresponds mostly with room columns, though the options tag is seperted.
   *
   * @return bool|resource
   */
  public function set($options, $create = false) {
    global $database;

    if (isset($options['password'])) {
      require 'PasswordHash.php';
      $h = new PasswordHash(8, FALSE);
      $options['passwordHash'] = $h->HashPassword($options['password']);
      $options['passwordFormat'] = 'phpass';

      unset($options['password']);
    }

    if ($this->id) {
      return $database->upsert($database->sqlPrefix . "users", array(
        'userId' => $this->id,
      ), $options);
    }
    else {
      return $database->insert($database->sqlPrefix . "users", $options);
    }
  }
}
?>