<?php
class fimDatabase extends database {
  public function getRoom($roomId,$roomName = false) {
    $queryParts['roomSelect']['columns'] = array(
      "{$sqlPrefix}rooms" => array(
        'roomId' => 'roomId',
        'roomName' => 'roomName',
        'roomTopic' => 'roomTopic',
        'owner' => 'owner',
        'allowedUsers' => 'allowedUsers',
        'allowedGroups' => 'allowedGroups',
        'moderators' => 'moderators',
        'options' => 'options',
      ),
    );

    if ($roomId) {
      $queryParts['roomSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'roomId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $roomId,
            ),
          ),
        ),
      );
    }
    elseif ($roomName) {
      $queryParts['roomSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'roomName'
            ),
            'right' => array(
              'type' => 'string',
              'value' => $roomName,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }


    $room = $this->select(
      $queryParts['roomSelect']['columns'],
      $queryParts['roomSelect']['conditions'],
      false,
      false,
      1
    );
    return $room->getAsArray(false);
  }


  public function getUser($userId,$userName = false) {
    $queryParts['userSelect']['columns'] = array(
      "{$sqlPrefix}users" => array(
        'userId' => 'userId',
        'userName' => 'userName',
        'userFormatStart' => 'userFormatStart',
        'userFormatEnd' => 'userFormatEnd',
        'userGroup' => 'userGroup',
        'allGroups' => 'allGroups',
        'socialGroups' => 'socialGroups',
      ),
    );

    if ($userId) {
      $queryParts['userSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'userId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $userId,
            ),
          ),
        ),
      );
    }
    elseif ($userName) {
      $queryParts['userSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'userName'
            ),
            'right' => array(
              'type' => 'string',
              'value' => $userName,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }


    $user = $this->select(
      $queryParts['userSelect']['columns'],
      $queryParts['userSelect']['conditions'],
      false,
      false,
      1
    );
    return $user->getAsArray(false);
  }


  public function getFont($fontId) {
    $queryParts['fontSelect']['columns'] = array(
      "{$sqlPrefix}fonts" => array(
        'fontId' => 'fontId',
        'fontName' => 'fontName',
      ),
    );

    if ($fontId) {
      $queryParts['fontSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'fontId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $fontId,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }

    $font = $this->select(
      $queryParts['fontSelect']['columns'],
      $queryParts['fontSelect']['conditions'],
      false,
      false,
      1
    );
    return $room->getAsArray(false);
  }


  public function getCensorList($listId) {
    $queryParts['listSelect']['columns'] = array(
      "{$sqlPrefix}lists" => array(
        'listId' => 'listId',
        'listName' => 'listName',
      ),
    );

    if ($listId) {
      $queryParts['listSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'listId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $listId,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }

    $list = $this->select(
      $queryParts['listSelect']['columns'],
      $queryParts['listSelect']['conditions'],
      false,
      false,
      1
    );
    return $list->getAsArray(false);
  }


  public function getMessage($messageId) {
    $queryParts['messageSelect']['columns'] = array(
      "{$sqlPrefix}messages" => array(
        'messageId' => 'messageId',
        'htmlText' => 'htmlText',
        'apiText' => 'apiText',
        'rawText' => 'rawText',
        'deleted' => 'deleted',
      ),
    );

    if ($messageId) {
      $queryParts['messageSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'messageId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $messageId,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }

    $message = $this->select(
      $queryParts['messageSelect']['columns'],
      $queryParts['messageSelect']['conditions'],
      false,
      false,
      1
    );
    return $room->getAsArray(false);
  }
}
?>