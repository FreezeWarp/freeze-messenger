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
  echo container('Mature Room','This room is marked as being mature, thus access has been restricted. Parental controls can be disabled from within your user <a href="./index.php?page=options">options</a>.');
}

elseif (hasPermission($room,$user)) { // The user is not banned, and is allowed to view this room.
  // Require the server-generated Javascript.
  if ($mode == 'normal') {
    echo '
  <script src="/client/js/phpjs-base64.min.js" type="text/javascript"></script>
  <script src="/client/js/phpjs-strReplace.min.js" type="text/javascript"></script>

  <script src="/client/js/jparsons-textEntry.min.js" type="text/javascript"></script>
  <script src="/client/js/jparsons-previewFile.js" type="text/javascript"></script>

  <script src="/client/js/beeper.min.js" type="text/javascript"></script>
  <script src="/client/js/youtube.min.js" type="text/javascript"></script>
  <script src="/client/js/jgrowl.js"></script>

  <script type="text/javascript">
  jQTubeUtil.init({
    key: "AI39si5_Dbv6rqUPbSe8e4RZyXkDM3X0MAAtOgCuqxg_dvGTWCPzrtN_JLh9HlTaoC01hCLZCxeEDOaxsjhnH5p7HhZVnah2iQ",
    orderby: "relevance",  // *optional -- "viewCount" is set by default
    time: "this_month",   // *optional -- "this_month" is set by default
    maxResults: 20   // *optional -- defined as 10 results by default
  });
  </script>

  <script src="/client/js/fim-main.js" type="text/javascript"></script>
  <script src="/client/js/fim-chatLite.js" type="text/javascript"></script>
';
  }
  elseif ($mode == 'mobile') {
    echo '<script src="/content/mainMobile.js.php?room=' . $room['id'] . '&r=' . $reverse . '"></script>';
  }
  elseif ($mode == 'simple') {
    echo '<script src="/content/mainMobile.js.php?room=' . $room['id'] . '&r=' . $reverse . '"></script>';
  }

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

  echo '<div id="chatContainer"' . ($stopMessage ? ' style="display: none;"' : '') . '>
  <div id="rightPanel">' .
    container('
    <div id="title">
      <span id="status" class="leftPart">' . 
        ($room['options'] & 1 ? '<img src="../images/bookmarks.png" class="standard" title="This is an Official Room" alt="Official" />' : '') . '<br />
        <span id="refreshStatus" onclick="alert(\'Failed \' + totalFails + \' times. Current refreshing every \' + (timeout / 1000 + .1) + \' seconds.\');"></span>
      </span>

      <div id="rightTitle" class="rightPart">
        <form action="#" onsubmit="return false;" class="rightPart">
          <!--<select name="fontSizeSelect" id="fontSizeSelect" onchange="var newSize = $(\'select#fontSizeSelect option:selected\').val(); $(\'#messageList\').css(\'fontSize\',newSize + \'px\');" class="standard" style="vertical-align: top;">
            <option value="8">8px</option>
            <option value="10" selected="selected">10px</option>
            <option value="12">12px</option>
            <option value="14">14px</option>
          </select>-->

          ' . (($mode == 'normal' && (($user['settings'] & 128) == false)) ? '<button type="button" onclick="if (soundOn) { soundOn = false; $(\'#icon_muteSound\').attr(\'src\',\'/images/audio-volume-muted.png\'); } else { soundOn = true; $(\'#icon_muteSound\').attr(\'src\',\'/images/audio-volume-high.png\'); }" class="standard" onmouseover="if(soundOn) { $(\'#icon_muteSound\').attr(\'src\',\'/images/audio-volume-muted.png\'); } else { $(\'#icon_muteSound\').attr(\'src\',\'/images/audio-volume-high.png\'); }" onmouseout="if(soundOn) { $(\'#icon_muteSound\').attr(\'src\',\'/images/audio-volume-high.png\'); } else { $(\'#icon_muteSound\').attr(\'src\',\'/images/audio-volume-muted.png\'); }" >
            <img src="../images/audio-volume-high.png" class="standard" alt="(Un)mute" id="icon_muteSound" />
          </button>' : '') . '

          <button type="button" onclick="createCookie(\'vrim10-reverseOrder\',' . ($reverse ? 'false' : 'true') . ',7 * 24 * 3600); location.reload(true);" class="standard" onmouseover="$(\'#icon_revOrder\').attr(\'src\',\'/images/go-' . ($reverse ? 'down' : 'up') . '.png\');" onmouseout="$(\'#icon_revOrder\').attr(\'src\',\'/images/go-' . ($reverse ? 'up' : 'down') . '.png\');" title="Switch the order of posts. This will refresh the page.">
            <img src="../images/go-' . ($reverse ? 'up' : 'down') . '.png" class="standard" alt="Rev. Order" id="icon_revOrder" />
          </button>

          <button type="button" onclick="window.open(\'./index.php?action=help&popup=true\',\'help\',\'status=0,toolbar=0,width=600,height=400,scrollbars=1,resizable=1,location=0\');" class="standard">
            <img src="../images/help-contents.png" class="standard" alt="Help" />
          </button>
        </form>
      </div>

      ' . $room['name'] . '<br />
      <em id="topic' . $room['id'] . '">' . $room['title'] . '</em>
    </div>','<div id="messageListContainer"><div id="messageList">
  ' . $messageText . (!$_GET['popup'] ? '
  <a href="./index.php?action=archive&roomid=' . $room['id'] . '">View older messages.</a>' : '') . '
</div></div>') . '
  </div>
  <div id="leftPanel">
    ' . ($mode == 'normal' ? '<div id="roomListTable">
      ' . container('
      <div id="roomListHeader">
        <div class="rightPart">
          <form action="./index.php?action=viewRooms" method="post" class="inline">
            <button type="submit" class="standard">
              <img src="../images/view-list-details.png" alt="View All Rooms" title="View All Rooms" class="standard" />
            </button>
          </form>

          <form action="./index.php?action=createRoom" method="post" class="inline">
            <button type="submit" class="standard">
              <img src="../images/document-new.png" alt="Create a Room" title="Create a Room" class="standard" />
            </button>
          </form>' . (((($user['userid'] == $room['owner']) || ($user['settings'] & 16))) && (($room['options'] & 16) == false) ? '

          <form action="./index.php?action=editRoom&amp;roomid=' . $room['id'] . '" method="post" class="inline">
            <button type="submit" class="standard">
              <img src="../images/document-edit.png" alt="Edit This Room" title="Edit the Current Room" class="standard" />
            </button>
          </form>' : '') . '
        </div>

        Favourite Rooms<br />
        <a href="javascript:void(0);" onclick="showAllRooms();">Show All</a>
      </div>','
      <div id="roomListContainer">
        <ul>
' . $roomHtml . '
        </ul>
      </div>') . '
    </div>' : '') . '

    <div id="textentryBox">
      <div id="textentryBoxMessage">
        <form onsubmit="var message = $(\'textarea#messageInput\').val(); if (message.length == 0) { alert(\'Please enter your message.\'); } else { sendMessage(message); $(\'textarea#messageInput\').val(\'\'); } return false;" id="sendform">' . container('
          <div class="leftPart">Enter a Message</div>
          <div class="rightPart">
            <button type="submit" class="standard"><img src="../images/dialog-ok.png" alt="Apply" class="standard" title="Send the Message" /></button>
            <button type="reset" class="standard"><img src="../images/dialog-cancel.png" alt="Cancel" class="standard" title="Reset the Message Box" /></button>
          </div>','
          <div id="messageInputContainer" class="middle">' . ($mode == 'normal' ? '
            <div id="buttonMenuLeft">
              ' . ($room['bbcode'] <= 16 ? '
              <button type="button" onclick="addPTag(\'+\',\'+\');" class="standard">
                <img src="../images/format-text-bold.png" alt="B" class="standard" title="Bold" />
              </button><br />
              <button type="button" onclick="addPTag(\'_\',\'_\');" class="standard">
                <img src="../images/format-text-underline.png" alt="U" class="standard" title="Underline" />
              </button><br />
              <button type="button" onclick="addPTag(\' /\',\'/ \');" class="standard">
                <img src="../images/format-text-italic.png" alt="I" class="standard" title="Italics" />
              </button><br />
              <button type="button" onclick="addPTag(\'=\',\'=\');" class="standard">
                <img src="../images/format-text-strikethrough.png" alt="S" class="standard" title="Strikethrough" />
              </button>' : '') . '
            </div>' : '') . '

            <div id="textEntryContainer">
              <textarea onkeypress="if (event.keyCode == 13 && !event.shiftKey) { $(\'#sendform\').submit(); return false; }" id="messageInput" ' . ($mode == 'normal' ? 'autofocus="autofocus" ' : '') . 'placeholder="Enter your text." style="' . messageStyle($user) . '"></textarea>
            </div>' . 


            ($mode == 'normal' ? '<div id="buttonMenuRight">' . ($room['bbcode'] <= 13 ? '
              <button type="button" onclick="$(\'#textentryBoxMessage, #roomListTable, #activeUsersContainer, #textentryBoxUrl\').slideUp();$(\'#textentryBoxUrl\').slideDown();" class="standard">
                <img src="../images/insert-link.png" class="standard" alt="L" title="Insert Link" />
              </button><br />' : '') . ($room['bbcode'] <= 5 ? '
              <button type="button" onclick="$(\'#textentryBoxMessage, #roomListTable, #activeUsersContainer, #textentryBoxUrl\').slideUp();$(\'#textentryBoxUpload\').slideDown();" class="standard">
                <img src="../images/insert-image.png" class="standard" alt="I" title="Insert or Upload an Image" />
              </button><br />' : '') . ($room['bbcode'] <= 2 ? '
              <button type="button" onclick="$(\'#textentryBoxMessage, #roomListTable, #activeUsersContainer, #textentryBoxUrl\').slideUp();$(\'#textentryBoxYoutube\').slideDown();" class="standard">
                <img src="../images/youtube.png" class="standard" alt="YT" title="Insert a Youtube Video" />
              </button>' : '') . '
            </div>' : '') . '
          </div>') . '
        </form>
      </div>

      ' . (($mode == 'normal') ? '
      <div id="textentryBoxUpload">
        <form action="/uploadFile.php?room=' . $room['id'] . '" method="post" enctype="multipart/form-data" target="upload_target" id="uploadFileForm">' . container('Upload a File','
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

            <button onclick="stopUpload(0);" type="button">Return without Upload</button>
            <button type="submit" disabled="disabled" id="imageUploadSubmitButton">Upload and Return</button>
          </fieldset>
          <iframe id="upload_target" name="upload_target" class="nodisplay"></iframe>') . '
          <input type="hidden" name="method" value="image" />
        </form>
      </div>

      <div id="textentryBoxUrl">
        <form action="/uploadFile.php?room=' . $room['id'] . '" method="post" target="upload_target3" id="linkForm">' . container('Post a Link','
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

            <button onclick="stopUpload(0);" type="button">Return without Link</button>
            <button type="submit" id="linkSubmitButton">Link and Return</button>
          </fieldset>

          <iframe id="upload_target3" name="upload_target3" class="nodisplay"></iframe>') . '
          <input type="hidden" name="method" value="url" />
        </form>
      </div>

      <div id="textentryBoxYoutube">
        ' . container('Insert a Youtube Video','
        <fieldset>
          <legend>Direct Link</legend>
          <form action="/uploadFile.php?room=' . $room['id'] . '" method="post" enctype="multipart/form-data" target="upload_target2" id="uploadYoutubeForm">
            <label for="youtubeUpload">URL: </label>
            <input name="youtubeUpload" id="youtubeUpload" type="url" value="http://" /><br />
            <button onclick="stopUpload(0);" type="button">Return without Upload</button>
            <button type="submit">Upload and Return</button>
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
        </fieldset>') . '
      </div>' : '') . '
    </div>
    <div id="activeUsersContainer">
      ' . container('Active Users','<div id="activeUsers">Loading...</div>') . '
    </div>
  </div>
</div>';

}

else {
  echo container('Access Denied','You see... our incredibly high standards of admittance, or perhaps just our unjust bias, has resulted in us unfairly denying you access to this probably-not-worth-your time room. We do apologize for being such snobs... but yet, we still are. So, we must now ask you to leave.');
}
?>