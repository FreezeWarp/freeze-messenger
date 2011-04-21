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

if (($room['options'] & 2) && (($user['settings'] & 64) == false)) {
  echo container('Mature Room','This room is marked as being mature, thus access has been restricted. Parental controls can be disabled from within your user <a href="#" class="changeSettingsMulti">options</a>.');
}

elseif (hasPermission($room,$user)) { // The user is not banned, and is allowed to view this room.

  if ((($room['options'] & 1) == false) && (($user['settings'] & 64) == false)) {
    if ($room['options'] & 16) {
      $stopMessage = $phrases['chatPrivateRoom'];
    }
    else {
      $stopMessage = $phrases['chatNotModerated'];
    }
  }

  if (($user['settings'] & 16) && ((($room['owner'] == $user['userid'] && $room['owner'] > 0) || (in_array($user['userid'],explode(',',$room['allowedUsers'])) || $room['allowedUsers'] == '*') || (in_array($user['userid'],explode(',',$room['moderators']))) || ((inArray(explode(',',$user['membergroupids']),explode(',',$room['allowedGroups'])) || $room['allowedGroups'] == '*') && ($room['allowedGroups'] != ''))) == false)) {
    $stopMessage = $phrases['chatAdminAccess'];
  }

  if ($stopMessage) {
    echo '<div id="stopMessage">
    ' . container('Warning',$stopMessage . '<br /><br />

<form action="#" method="post">
  <input type="button" onclick="$(\'#stopMessage\').slideUp(); $(\'#chatContainer\').slideDown();" value="Continue." />
  <input type="button" onclick="window.history.back()" value="Go Back" />
</form>') . '
    </div>';
  }

  echo '<div id="chatContainer"' . ($stopMessage ? ' style="display: none;"' : '') . '>' .
    container('
  <div id="title">
    <span id="status" class="leftPart">' . 
      ($room['options'] & 1 ? '<img src="images/bookmarks.png" class="standard" title="This is an Official Room" alt="Official" />' : '') . '<br />
      <span id="refreshStatus" onclick="alert(\'Failed \' + totalFails + \' times. Current refreshing every \' + (timeout / 1000 + .1) + \' seconds.\');"></span>
    </span>

    <div id="rightTitle" class="rightPart">
      <form action="#" onsubmit="return false;" class="rightPart">
        ' . ((($user['settings'] & 128) == false) ? '<button type="button" onclick="if (soundOn) { soundOn = false; $(\'#icon_muteSound\').attr(\'src\',\'/images/audio-volume-muted.png\'); } else { soundOn = true; $(\'#icon_muteSound\').attr(\'src\',\'/images/audio-volume-high.png\'); }" class="standard" id="icon_muteSound">
        </button>' : '') . '

        <button type="button" class="standard" title="Switch the order of posts. This will refresh the page." id="icon_reversePostOrder">
        </button>

        <button type="button" class="standard" id="icon_help">
        </button>
      </form>
    </div>

    ' . $room['name'] . '<br />
    <em id="topic' . $room['id'] . '" style="white-space:nowrap;">' . $room['title'] . '</em>
  </div>','
  <div id="messageListContainer">
    <div id="messageList">
      <a href="/archive.php?roomid=' . $room['id'] . '">View older messages.</a>
    </div>
  </div>') . '
  <div id="textentryBoxMessage">
    <form onsubmit="var message = $(\'textarea#messageInput\').val(); if (message.length == 0) { alert(\'Please enter your message.\'); } else { sendMessage(message); $(\'textarea#messageInput\').val(\'\'); } return false;" id="sendform">' . container('
      <div class="leftPart">Enter a Message</div>
      <div class="rightPart">
        <button type="submit" class="standard" id="icon_submit"></button>
        <button type="reset" class="standard" id="icon_reset"></button>
      </div>','
      <div id="messageInputContainer" class="middle">
        <div id="buttonMenuLeft">' . ($room['bbcode'] <= 13 ? '
          <button type="button" onclick="$(\'#textentryBoxUrl\').dialog({width : \'600px\', title : \'Insert a Linked Document\'});" class="standard" id="icon_url"></button><br />' : '') . ($room['bbcode'] <= 5 ? '
          <button type="button" onclick="$(\'#textentryBoxUpload\').dialog({width : \'600px\', title : \'Insert an Image\'});" class="standard" id="icon_upload"></button><br />' : '') . ($room['bbcode'] <= 2 ? '
          <button type="button" onclick="$(\'#textentryBoxYoutube\').dialog({width : \'600px\', title : \'Insert a Youtube Video\'});" class="standard" id="icon_video"></button>' : '') . '
        </div>

        <textarea onkeypress="if (event.keyCode == 13 && !event.shiftKey) { $(\'#sendform\').submit(); return false; }" id="messageInput" autofocus="autofocus" placeholder="Enter your text." style="' . messageStyle($user) . '"></textarea>
      </div>') . '
    </form>
  </div>
</div>';
}

else {
  echo container('Access Denied',$phrases['chatAccessDenied']);
}
?>