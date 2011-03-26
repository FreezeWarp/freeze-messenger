<?php
if ($banned) { // Check that the user isn't banned.
  echo container('We\'re Sorry','We\'re sorry, but for the time being you have been banned from the chat. You make contact a Victory Road administrator for more information.');
}

elseif ($_GET['room'] == '3.14') { // ...I'm bored, kay?
  trigger_error('After trying to process your request 3.141592 times, timing out every time we saw Porygon Z, and eventually eating a wide variety of deserts made in a pastry-lined pan often with a pastry top... well, simply put: your request failed as spectacularly as Odysseus did in the Odyssey.',E_USER_ERROR);
}

elseif (!$room) { // No room data was returned.
  trigger_error('Room not found.',E_USER_ERROR);
}

elseif (($room['options'] & 2) && (($user['settings'] & 64) == false)) {
  echo container('Mature Room','This room is marked as being mature, thus access has been restricted. Parental controls can be disabled from within your user <a href="/index.php?page=options">options</a>.');
}

elseif (hasPermission($room,$user)) { // The user is not banned, and is allowed to view this room.
  // Require the server-generated Javascript.
  echo '<script src="client/js/fim-main.js" type="text/javascript"></script>
';

  if ((($room['options'] & 1) == false) && (($user['settings'] & 64) == false)) {
    if ($room['options'] & 16) {
      $stopMessage = 'This room is a private room between you and another individual, and is not accessible to any other user or administrator. If you would like to ignore this person right click their username and choose &quot;Ignore&quot;. If you are being harrassed, please contact either Cat333Pokémon or FreezeWarp.';
    }
    else {
      $stopMessage = 'This room is not an official room, and as such is not actively moderated. Please excercise caution when talking to people you do not know, and do not reveal personal information.';
    }
  }

  if (($user['settings'] & 16) && ((($room['owner'] == $user['userid'] && $room['owner'] > 0) || (in_array($user['userid'],explode(',',$room['allowedUsers'])) || $room['allowedUsers'] == '*') || (in_array($user['userid'],explode(',',$room['moderators']))) || ((inArray(explode(',',$user['membergroupids']),explode(',',$room['allowedGroups'])) || $room['allowedGroups'] == '*') && ($room['allowedGroups'] != ''))) == false)) {
    $stopMessage = 'You are not a part of this group, rather you are only granted access because you are an administrator. Please respect user privacy: do not post in this group unwanted, and moreover do not spy without due reason.';
  }

  if ($stopMessage) {
    echo '<div id="stopMessage">
    ' . container('Warning',$stopMessage . '<br /><br />

<form action="#" method="post">
  <input type="button" onclick="$(\'#stopMessage\').slideUp(); $(\'#chatContainer\').slideDown();" value="Continue." />
  <input type="button" onclick="window.location.href = \'/index.php\';" value="Go Back" />
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
    <em id="topic' . $room['id'] . '">' . $room['title'] . '</em>
  </div>','
  <div id="messageListContainer">
    <div id="messageList">
      <a href="/index.php?action=archive&roomid=' . $room['id'] . '">View older messages.</a>
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
</div>

<div id="dialogues">
  <div id="textentryBox">
    <div id="textentryBoxUpload">
      <form action="/uploadFile.php?room=' . $room['id'] . '" method="post" enctype="multipart/form-data" target="upload_target" id="uploadFileForm" onsubmit="$(\'#textentryBoxUpload\').dialog(\'close\');">
        <fieldset>
          <legend>Upload from Computer</legend>
          <label for="fileUpload">File: </label>
          <input name="fileUpload" id="fileUpload" type="file" onChange="upFiles()" /><br /><br />
        </fieldset>
        <fieldset>
          <legend>Embed from Internet</legend>
          <label for="urlUpload">URL: </label>
          <input name="urlUpload" id="urlUpload" type="url" value="http://" onchange="previewUrl()" /></span><br />
        </fieldset>
        <fieldset>
          <legend>Preview & Submit</legend>
          <div id="preview"></div><br /><br />

          <button onclick="$(\'#textentryBoxUpload\').dialog(\'close\');" type="button">Cancel</button>
          <button type="submit" id="imageUploadSubmitButton">Upload</button>
        </fieldset>
        <iframe id="upload_target" name="upload_target" class="nodisplay"></iframe>
        <input type="hidden" name="method" value="image" />
      </form>
    </div>

    <div id="textentryBoxUrl">
      <form action="/uploadFile.php?room=' . $room['id'] . '" method="post" target="upload_target3" id="linkForm" onsubmit="$(\'#textentryBoxUrl\').dialog(\'close\');">
        <fieldset>
          <legend>Normal Link</legend>
          <label for="linkUrl">URL: </label>
          <input name="linkUrl" id="linkUrl" type="url" /><br /><br />

          <label for="linkText">Text: </label>
          <input name="linkText" id="linkText" type="text" /><br /><br />
        </fieldset>

        <fieldset>
          <legend>eMail Link</legend>
          <label for="linkEmail">eMail: </label>
          <input name="linkEmail" id="linkEmail" type="email" /></span><br />
        </fieldset>
        <fieldset>
          <legend>Preview & Submit</legend>

          <button onclick="$(\'#textentryBoxUrl\').dialog(\'close\');" type="button">Cancel</button>
          <button type="submit" id="linkSubmitButton">Link</button>
        </fieldset>

        <iframe id="upload_target3" name="upload_target3" class="nodisplay"></iframe>
        <input type="hidden" name="method" value="url" />
      </form>
    </div>

    <div id="textentryBoxYoutube">
      <fieldset>
        <legend>Direct Link</legend>
        <form action="/uploadFile.php?room=' . $room['id'] . '" method="post" enctype="multipart/form-data" target="upload_target2" id="uploadYoutubeForm" onsubmit="$(\'#textentryBoxYoutube\').dialog(\'close\');">
          <label for="youtubeUpload">URL: </label>
          <input name="youtubeUpload" id="youtubeUpload" type="url" value="http://" /><br />
          <button onclick="$(\'#textentryBoxYoutube\').dialog(\'close\');" type="button">Cancel</button>
          <button type="submit">Upload</button>
          <iframe id="upload_target2" name="upload_target2" class="nodisplay"></iframe>
          <input type="hidden" name="method" value="youtube" />
        </form>
      </fieldset>
      <fieldset>
        <legend>Search for Videos</legend>
        <form action="#" onsubmit="return false;">
          <input type="text" onkeyup="updateVids(this.value);" />
          <div id="youtubeResultsContainer">
            <table id="youtubeResults">
              <tr>
                <td>Results will appear here...</td>
              </tr>
            </table>
          </div>
        </form>
      </fieldset>
    </div>

    <ul id="userMenu" class="contextMenu">
      <li><a href="javascript:void(0);" data-action="private_im">Private IM</a></li>
      <li><a href="javascript:void(0);" data-action="profile">View Profile</a></li>
      ' . (hasPermission($room,$user,'moderate') ? '<li><a href="javascript:void(0);" data-action="kick">Kick</a></li>' : '') .
      ($user['settings'] & 16 ? '<li><a href="javascript:void(0);" data-action="ban">Ban</a></li>' : '') . '
    </ul>

    <ul id="messageMenu" class="contextMenu">
      <li><a href="javascript:void(0);" data-action="delete">Delete</a></li>
    </ul>

    <ul id="roomMenu" class="contextMenu">
      <li><a href="javascript:void(0);" data-action="edit">Edit</a></li>
      <li><a href="javascript:void(0);" data-action="delete">Delete</a></li>
    </ul>

    <div style="display: none;" id="kick">
    <form action="/index.php?action=kick&phase=2" method="post" id="kickForm">
      <label for="time">Time</label>: <input type="text" name="time" id="time" style="width: 50px;" />
      <select name="interval">
        <option value="1">Seconds</option>
        <option value="60">Minutes</option>
        <option value="3600">Hours</option>
        <option value="86400">Days</option>
        <option value="604800">Weeks</option>
      </select><br /><br />

      <button type="submit">Kick User</button><button type="reset">Reset</button><input type="hidden" name="room" value="' . $room['id'] . '" />
    </form>
    </div>
  </div>
</div>

<data name="roomid" value="' . $room['id'] . '" />
<data name="ding" value="' . ($user['settings'] & 128 ? 0 : 1) . '" />
<data name="reverse" value="' . $reverse . '" />';

}

else {
  echo container('Access Denied','You see... our incredibly high standards of admittance, or perhaps just our unjust bias, has resulted in us unfairly denying you access to this probably-not-worth-your time room. We do apologize for being such snobs... but yet, we still are. So, we must now ask you to leave.');
}
?>