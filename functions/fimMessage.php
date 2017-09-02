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
     * @var string The message's text, unencrypted.
     */
    public $text;

    /**
     * @var string The message's text, encrypted.
     */
    public $textEncrypted;

    /**
     * @var string The iv used to encrypt the message.
     */
    public $iv;

    /**
     * @var int The salt # used to encrypt the message.
     */
    public $salt;

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
     * @var string A CSS string to be applied to the message when displaying.
     */
    public $formatting;

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
     * @param string ['messageFormatting'] See fimMessage::messageFormatting.
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

            $this->id = $messageData['messageId'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have id column.');
            $this->user = fimUserFactory::getFromId((int)($messageData['userId'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have userId column.')));
            $this->room = new fimRoom((int)($messageData['roomId'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have roomId column.')));

            if (isset($messageData['salt'], $messageData['iv'])) { // Typically when in permanent store.
                $this->textEncrypted = $messageData['text'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have text column.');
                $this->salt = $messageData['salt'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have salt column.');
                $this->iv = $messageData['iv'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have iv column.');
                $this->text = fim_decrypt(
                    $this->textEncrypted,
                    $this->salt,
                    $this->iv
                );
            } else { // Typically when in caches.
                $this->text = $messageData['text'];
                list($this->textEncrypted, $this->iv, $this->salt) = fim_encrypt($this->text, FIM_ENCRYPT_MESSAGETEXT);
            }

            $this->flag = $messageData['flag'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have flag column.');
            $this->time = $messageData['time'] ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have time column.');
            $this->formatting = $messageData['messageFormatting'] ?? '';// todo: ?? new fimError('badFimMessage', 'fimMessage when invoked with a fimDatabaseResult must have messageFormatting column.');
        } // When creating a new message.
        else if (is_array($messageData)) {
            $this->user = $messageData['user'] ?? new fimError('badFimMessage', 'fimMessage when invoked with an associative array must contain user.');
            $this->room = $messageData['room'] ?? new fimError('badFimMessage', 'fimMessage when invoked with an associative array must contain room.');
            $this->text = $messageData['text'] ?? new fimError('badFimMessage', 'fimMessage when invoked with an associative array must contain text.');
            $this->flag = $messageData['flag'] ?? '';
            $this->formatting = $messageData['messageFormatting'] ?? '';
            $this->time = time();

            if (!in_array($this->flag, array('image', 'video', 'url', 'email', 'html', 'audio', 'text'))) {
                $this->text = $generalCache->censorScan($this->text, $this->room->id, $messageData['ignoreBlock'] ?? false, $this->censorMatches);
            }

            list($this->textEncrypted, $this->iv, $this->salt) = fim_encrypt($this->text, FIM_ENCRYPT_MESSAGETEXT);
        } elseif ($messageData !== null) {
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
    public function setText($text, $ignoreBlock)
    {
        $this->text = $this->generalCache->censorScan($text, $this->room->id, $ignoreBlock, $this->censorMatches);
        list($this->textEncrypted, $this->iv, $this->salt) = fim_encrypt($this->text, FIM_ENCRYPT_MESSAGETEXT);
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