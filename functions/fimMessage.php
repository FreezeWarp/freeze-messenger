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

class fimMessage
{
    /**
     * @var fimRoom The room the message is in.
     */
    public $room = false;

    /**
     * @var int The ID of the message, unique in its room.
     */
    public $id = false;

    /**
     * @var fimUser The user who posted the message.
     */
    public $user;

    /**
     * @var string The message's text.
     */
    public $text;

    /**
     * @var bool Whether the message is currently marked as deleted.
     */
    public $deleted;

    /**
     * @var int The timestamp the message was posted on.
     */
    public $time;

    /**
     * @var string A classification for the message, one of: video, image, text, archive, html, url, email
     * (Others will be allowed for support of plugins.)
     */
    public $flag;

    /**
     * @var array When the message text is set anew (either for insertion or update), this will be the list of censor matches triggered by the censor.
     */
    public $censorMatches = [];

    /**
     * @var fimCache The general cache, for caching operations.
     */
    private $generalCache;

    /**
     * @param $messageData mixed The source of message data. Should be a fimDatabaseResult if from the database, or an associative array with the following indexes:
     * @param fimRoom ['room']             The room of the message.
     * @param fimUser ['user']             The user of the message.
     * @param string ['text']              An array of messageIds to filter by. Overrides other message ID filter parameters.
     * @param string ['flag']              A valid message flag, see fimMessage::flag.
     * @param bool ['ignoreBlock']         Whether to ignore censor prompts. Defaults false.
     * @param bool ['archive']           Whether to query the message archive instead of the main table. Default false. (On average, the main table only includes around 100 messages, so this must be true for archive viewing.)
     */
    function __construct($messageData)
    {
        global $generalCache;
        $this->generalCache = $generalCache;

        // When working with an existing message row. We require that all indexes be present, as otherwise we may accidentally forget certain information on edits.
        if ($messageData instanceof fimDatabaseResult) {
            $messageData = $messageData->getAsArray(false);

            $this->id = (int) $messageData['id'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have id column.');
            $this->user = fimUserFactory::getFromId((int)($messageData['userId'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have userId column.')));
            $this->room = new fimRoom((int)($messageData['roomId'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have roomId column.')));
            $this->text = $messageData['text'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have text column.');
            $this->flag = $messageData['flag'] ?? '';
            $this->time = $messageData['time'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have time column.');
        }

        // When creating a new message.
        else if (is_array($messageData)) {
            $this->user = $messageData['user'] ?? new fimError('badFimMessage', 'fimMessage when invoked with an associative array must contain user.');
            $this->room = $messageData['room'] ?? new fimError('badFimMessage', 'fimMessage when invoked with an associative array must contain room.');
            $this->flag = $messageData['flag'] ?? '';
            $this->time = time();

            $this->setText(
                $messageData['text'] ?? new fimError('badFimMessage', 'fimMessage when invoked with an associative array must contain text.'),
                $messageData['ignoreBlock'] ?? false
            );
        }

        elseif ($messageData !== null) {
            throw new Exception('Invalid message data specified -- must be an associative array corresponding to a table row. Passed: ' . print_r($messageData, true));
        }
    }


    /**
     * Get the value of $property.
     * @param $property string The property to get.
     * @return mixed The value of the property.
     * @throws Exception If property is invalid.
     */
    public function __get($property)
    {
        if (!property_exists($this, $property))
            throw new Exception("Invalid property accessed in fimMessage: $property");

        return $this->$property;
    }


    /*******************
     ***** SETTERS *****
     *******************/

    /**
     * After running, make sure to run $database->updateMessage() on this message object.
     *
     * @param $text string New message text.
     * @param $ignoreBlock bool True if a censor prompt should be ignored.
     */
    public function setText($text, $ignoreBlock = false)
    {
        if (!in_array($this->flag, array('image', 'video', 'url', 'email', 'html', 'audio'))) {
            $this->text = $this->room->censorScan($text, $ignoreBlock, $this->censorMatches);
        }
        else {
            $this->text = $text;
        }
    }

    /**
     * After running, make sure to run $database->updateMessage() on this message object.
     *
     * @param $flag string See fimMessage::flag.
     */
    public function setFlag($flag)
    {
        $this->flag = $flag;
    }

    /**
     * After running, make sure to run $database->updateMessage() on this message object.
     *
     * @param $deleted bool Whether or not the message is deleted.
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }
}
?>