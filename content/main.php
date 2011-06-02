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

require_once('../global.php');
require_once('../functions/container.php');

if ($banned) { // Check that the user isn't banned.
  echo container('We\'re Sorry','We\'re sorry, but for the time being you have been banned from the chat. You may contact a Victory Road administrator for more information.');
}

elseif (!$room) { // No room data was returned.
  trigger_error('Room not found.',E_USER_ERROR);
}

else {
  echo '
<data name="roomid" value="' . $room['id'] . '"></data>
<data name="ding" value="' . ($user['settings'] & 128 ? 0 : 1) . '"></data>
<data name="reverse" value="' . $reverse . '"></data>


<div id="roomTemplateContainer">';
  require_once('roomTemplate.php'); // While the below arguably should be in this too [since it is needed for pretty much anything to work], we're only reusing the code in the AJAX room switcher, which itself just assumes everything below already exists in the DOM.
  echo '</div>';

  echo '<div id="dialogues">
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
</div>';

}
?>