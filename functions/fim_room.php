<?php
/**
 * This object will store actions that only involve a single room. Many of the methods could just as well exist in fimDatabase, but there are certain advantages of storing them together here (caching especially).
 * In general, two instances of this object involving the same room should be functionally identical.
 *
 * General thoughts:
 * This is not exactly the best example of OOP, but it's not the worst either. In general, the way I wrote this, the object can either correspond with a valid room or with nothing at all. Certain functions -- like editRoom() -- will create a room if none exists. There are certain flaws with this -- it would be better if a user explicitly said "createRoom" instead of "editRoom" to create a room. So... that might be worth changing.
 */
class fimRoom {
  public $id;
  public $name;
  public $alias;
  public $options;
  public $ownerId;
  public $topic;
  public $type;
  public $parentalFlags;
  public $parentalAge;
  public $defaultPermissions;

  protected $roomData;
  protected $resolved;


  /**
   * @param $roomData mixed Should either be an array or an integer (other values will simply fail to populate the object's data). If an array, should correspond with a row obtained from the `rooms` database, if an integer should correspond with the room ID.
   */
  function __construct($roomData) {
    if (is_int($roomData)) $this->id = $roomData;
    elseif (is_array($roomData)) $this->populateFromArray($roomData); // TODO: test contents
    elseif ($roomData === false) $this->id = false;
    else throw new Exception('Invalid room data specified -- must either be an associative array corresponding to a table row, a room ID, or false (to create a room, etc.)');

    $this->roomData = $roomData;
  }



  // TODO: Resolve only certain data.
  public function resolve() {
    global $database;

    if (!$this->id) return false; // If no ID is present, return false;
    elseif (!$this->resolved) {
      return $this->populateFromArray($database->getRooms(array(
        'roomIds' => array($this->id)
      ))->getAsArray(false));
    }
    else return true;
  }



  private function populateFromArray($roomData) {
    if (!count($roomData) || !is_array($roomData)) return false;

    $this->id = $roomData['roomId'];
    $this->name = $roomData['roomName'];
    $this->alias = $roomData['roomAlias'];
    $this->options = $roomData['options'];
    $this->deleted = $this->options & ROOM_DELETED;
    $this->official = $this->options & ROOM_OFFICIAL;
    $this->hidden = $this->options & ROOM_HIDDEN;
    $this->archived = $this->options & ROOM_ARCHIVED;
    $this->ownerId = $roomData['owner'];
    $this->topic = $roomData['topic'];
    $this->type = $roomData['roomType'];
    $this->parentalFlags = explode(',', $roomData['roomParentalFlags']);
    $this->parentalAge = $roomData['roomParentalAge'];
    $this->defaultPermissions = $roomData['defaultPermissions'];

    $this->resolved = true;

    return true;
  }



  private function getOptionsField() {
    /*    if ($options['officialRoom']) $optionsField |= ROOM_OFFICIAL;
        if ($options['deleted']) $optionsField |= ;
        if ($options['hiddenRoom']) $optionsField |= ROOM_HIDDEN;
        if ($options['archivedRoom']) $optionsField |= ROOM_ARCHIVED;*/
  }



  /* Transitional Function */
  public function getAsArray() {
    return $this->roomData;
  }



  /**
   * Queries the roomPermissions table for a specific roomId with three attribute/param pairs: one for $userId, one for $userGroupId, and one for $socialGroupIds. It is faster when looking up data on a specific user, but less versitile: it will return a bitfield representing the user's permissions instead of a result set.
   *
   * @param $roomId
   * @param $userId
   * @param $userGroupId
   * @param $socialGroupIds
   */
  public function getUserPermissions($user) {
    global $database;

    if (!$user->resolve('userGroup', 'socialGroups')) throw new Exception('hasPermission was called without a valid user.'); // Require all user information.

    $permissions = $database->select(array(
      $database->sqlPrefix . "roomPermissions" => 'roomId, attribute, param, permissions',
    ), array(
      'both' => array(
        'roomId' => $database->int($this->id),
        'either' => array(
          'both 1' => array(
            'attribute' => $database->str('user'),
            'param' => $database->int($user->id)
          ),
          'both 2' => array(
            'attribute' => $database->str('admingroup'),
            'param' => $database->int($user->groupId)
          ),
          'both 3' => array(
            'attribute' => $database->str('group'),
            'param' => $database->in($user->socialGroupIds)
          )
        )
      )
    ))->getAsArray(array('attribute', 'param'));


    if (!count($permissions)) return -1;
    elseif (isset($permissions['user'][$user->id])) { // The user setting overrides group settings. A notable limitation of this is that you can't override specific group-based permissions, but this way is a fair bit simpler (after all, there are very few situations when you want to bar a user from posting but want them to be able to view based on their group -- rather, you want to set the user's specific permissions and be done with it).
      return $permissions['user'][$user->id]['permissions'];
    }
    else { // Here we generate a user's permissions by XORing all of the group permissions that apply to them. That is, if any group a user belongs to has a certain permission, then the user will have that permission.
      $groupBitfield = isset($permissions['admingroup'][$user->groupId]) ? $permissions['admingroup'][$user->groupId]['permissions'] : 0; // Set the group field to the returned admingroup object if available; otherwise, 0.

      foreach ($permissions['group'] AS $s) $groupBitfield |= $s['permissions']; // Basically, we do a bitwise OR on all of the permissions returned as part of a social group (with the admingroup permission being our first above).

      return $groupBitfield;
    }
  }



  /**
   * Modify or create a room.
   * @internal The row will be set to a merge of roomDefaults->existingRow->specifiedOptions.
   *
   * @param $options - Corresponds mostly with room columns, though the options tag is seperted.
   *
   * @return bool|resource
   */
  public function set($options) {
    $this->resolve();

    $options = array_merge(array(
      'roomName' => '',
      'roomAlias' => '',
      'roomType' => 'general',
      'owner' => 0,
      'defaultPermissions' => 0,
      'roomParentalAge' => 0,
      'roomParentalFlags' => array(),
      'officialRoom' => false,
      'hiddenRoom' => false,
      'archivedRoom' => false,
      'deleted' => false,
    ), $this->getAsArray(), $options);

    $optionsField = 0;

    $columns = array(
      'roomName' => $options['roomName'],
      'roomNameSearchable' => fim_makeSearchable($options['roomName']), // TODO
      'roomAlias' => $options['roomAlias'],
      'roomType' => $options['roomType'],
      'owner' => (int) $options['owner'],
      'defaultPermissions' => (int) $options['defaultPermissions'],
      'roomParentalAge' => $options['roomParentalAge'],
      'roomParentalFlags' => implode(',', $options['roomParentalFlags']),
      'options' => $optionsField
    );


    /* TODO: upsert? */
    if ($this->id === false || $this->resolved) {
      return $this->upsert($this->sqlPrefix . "rooms", array(
        'roomId' => $this->id,
      ), $columns);
    }
    else {
      throw new Exception('A valid room was not specified for editRoom().');
    }
  }
}
?>