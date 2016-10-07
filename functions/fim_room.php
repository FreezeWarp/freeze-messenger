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
  public $deleted;
  public $official;
  public $hidden;
  public $archived;

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
  public function resolve($update = false) {
    global $database;

    if (!$this->id) return false; // If no ID is present, return false;
    elseif (!$this->resolved || $update) {
      return $this->populateFromArray($database->getRooms(array(
        'roomIds' => array($this->id)
      ))->getAsArray(false));
    }
    else return true;
  }



  private function populateFromArray($roomData) {
//      var_dump($roomData); die();
    if (!count($roomData) || !is_array($roomData)) return false;

    $this->id = (int) $roomData['roomId'];
    $this->name = $roomData['roomName'];
    $this->alias = $roomData['roomAlias'];
    $this->options = (int) $roomData['options'];
    $this->deleted = (bool) ($this->options & ROOM_DELETED);
    $this->official = (bool) ($this->options & ROOM_OFFICIAL);
    $this->hidden = (bool) ($this->options & ROOM_HIDDEN);
    $this->archived = (bool) ($this->options & ROOM_ARCHIVED);
    $this->ownerId = (int) $roomData['owner'];
    $this->topic = $roomData['topic'];
    $this->parentalFlags = explode(',', $roomData['roomParentalFlags']);
    $this->parentalAge = (int) $roomData['roomParentalAge'];
    $this->defaultPermissions = (int) $roomData['defaultPermissions'];
    $this->messageCount = (int) $roomData['messageCount'];
    $this->lastMessageId = (int) $roomData['lastMessageId'];
    $this->lastMessageTime = (int) $roomData['lastMessageTime'];

    $this->resolved = true;

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
  public function set($options) {
    global $database, $config;

    $this->resolve();

    /* The first array is a list of defaults for any new room. The second array is the existing data, if we are editing the room -- that is, the existing room's data will overwrite the defaults (we could, if we wanted, only use one or the other depending on if we are creating or editing, but this way is a bit cleaner, imo). Finally, we overwrite with provided options. */
    $options = array_merge(array(
      'roomName' => $this->resolved ? $this->name : '[Missingname.]',
      'roomAlias' => $this->resolved ? $this->alias : '',
      'roomType' => $this->resolved ? $this->type : 'general',
      'ownerId' => $this->resolved ? $this->ownerId : 0,
      'defaultPermissions' => $this->resolved ? $this->defaultPermissions : 0,
      'roomParentalAge' => $this->resolved ? $this->parentalAge : $config['parentalFlagsDefault'],
      'roomParentalFlags' => $this->resolved ? $this->parentalFlags : array(),
      'officialRoom' => $this->resolved ? $this->official : false,
      'hiddenRoom' => $this->resolved ? $this->hidden : false,
      'archivedRoom' => $this->resolved ? $this->archived : false,
      'deleted' => $this->resolved ? $this->deleted : false,
    ), $this->getAsArray(), $options);

    $columns = array(
      'roomName' => $options['roomName'],
      'roomNameSearchable' => $database->makeSearchable($options['roomName']),
      'roomAlias' => $options['roomAlias'],
      'roomType' => $options['roomType'],
      'ownerId' => (int) $options['ownerId'],
      'defaultPermissions' => (int) $options['defaultPermissions'],
      'roomParentalAge' => $options['roomParentalAge'],
      'roomParentalFlags' => implode(',', $options['roomParentalFlags']),
      'options' => ($options['officialRoom'] ? ROOM_OFFICIAL : 0)
        + ($options['hiddenRoom'] ? ROOM_HIDDEN : 0)
        + ($options['archivedRoom'] ? ROOM_ARCHIVED : 0)
        + ($options['deleted'] ? ROOM_DELETED : 0)
    );

    if ($this->id === false) { // Create
      $database->insert($this->sqlPrefix . 'rooms', $columns)->insertId;
      $this->id = $database->insertId;
    }
    elseif ($this->resolved) { // Update
      $database->update($this->sqlPrefix . "rooms", $columns, array(
        'roomId' => $this->id,
      ));
    }
    else {
      throw new Exception('Room not resolved.');
    }

    $this->resolve(true);
  }

  public function changeTopic($topic) {
    global $database;

    $database->createRoomEvent('topicChange', $this->id, $topic); // name, roomId, message
    $database->update($database->sqlPrefix . "rooms", array(
      'roomTopic' => $topic,
    ), array(
      'roomId' => $this->id,
    ));
  }

  public function replacePermissions($allowedUsers, $allowedGroups) {

  }
}
?>