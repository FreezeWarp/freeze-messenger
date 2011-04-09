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
require_once('../functions/generalFunctions.php');
require_once('../functions/parserFunctions.php');

$phase = $_GET['phase'];
if (!$phase) $phase = '1'; // Default to phase 1.

$fontData = sqlArr("SELECT * FROM {$sqlPrefix}fonts ORDER BY category, name",'id');
foreach($fontData AS $font) {
  $fontBox .= "<option value=\"$font[id]\" style=\"font-family: $font[data];\">$font[name]</option>";
}

$watchRooms = explode(',',$user['watchRooms']);

$roomData = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE " . ((($user['settings'] & 16) == false) ? "(owner = '$user[userid]' OR moderators REGEXP '({$user[userid]},)|{$user[userid]}$') AND " : '') . "(options & 16) = false AND (options & 4) = false AND (options & 8) = false",'id');
foreach ($roomData AS $roomData2) {
  $roomData3 .= '"' . addslashes($roomData2['name']) . '",
';
  $roomData4 .= 'roomRef["' . addslashes($roomData2['name']) . '"] = ' . $roomData2['id'] . ';
';

  if (in_array($roomData2['id'],$watchRooms)) {
    $watchList .= "<span id=\"watchRoomSubList" . $roomData2['id'] . "\">" . $roomData2['name'] . " (<a href=\"javascript:void(0);\" onclick=\"removeRoom(" . $roomData2['id'] . ");\">x</a>), </span>";
  }
}

if ($phase == '1') {
  echo '
<link rel="stylesheet" media="screen" type="text/css" href="client/colorpicker/css/colorpicker.css" />
<script type="text/javascript" src="client/colorpicker/js/colorpicker.js"></script>
<script type="text/javascript">
var roomRef = new Object;
' . $roomData4 . '

$(document).ready(function(){
  $(\'#defaultHighlight\').ColorPicker({
    color: \'' . rgb2html($user['defaultHighlight']) . '\',
    onShow: function (colpkr) {
      $(colpkr).fadeIn(500);
      return false;
    },
    onHide: function (colpkr) {
      $(colpkr).fadeOut(500);
      return false; 
    },
    onChange: function(hsb, hex, rgb) {
      $(\'#defaultHighlight\').css(\'background-color\',\'#\' + hex);
      $(\'#defaultHighlight\').val(hex);
      $(\'#fontPreview\').css(\'background-color\',\'#\' + hex);
    }
  });

  $(\'#defaultColour\').ColorPicker({
    color: \'' . rgb2html($user['defaultColour']) . '\',
    onShow: function (colpkr) {
      $(colpkr).fadeIn(500);
      return false;
    },
    onHide: function (colpkr) {
      $(colpkr).fadeOut(500);
      return false; 
    },
    onChange: function(hsb, hex, rgb) {
      $(\'#defaultColour\').css(\'background-color\',\'#\' + hex);
      $(\'#defaultColour\').val(hex);
      $(\'#fontPreview\').css(\'color\',\'#\' + hex);
    }
  });

  $(\'#fontPreview\').css(\'color\',\'' . rgb2html($user['defaultColour']) . '\');
  $(\'#defaultColor\').css(\'background-color\',\'' . rgb2html($user['defaultColour']) . '\');
  $(\'#fontPreview\').css(\'background-color\',\'' . rgb2html($user['defaultHighlight']) . '\');
  $(\'#defaultColor\').css(\'background-color\',\'' . rgb2html($user['defaultHighlight']) . '\');

  if ($(\'#defaultItalics\').is(\':checked\')) { $(\'#fontPreview\').css(\'font-style\',\'italic\'); }
  else { $(\'#fontPreview\').css(\'font-style\',\'normal\'); }

  if ($(\'#defaultBold\').is(\':checked\')) { $(\'#fontPreview\').css(\'font-weight\',\'bold\'); }
  else { $(\'#fontPreview\').css(\'font-style\',\'normal\'); }

  $("#changeSettingsForm").submit(function(){
    data = $("#changeSettingsForm").serialize(); // Serialize the form data for AJAX.
    $.post("content/options.php?phase=2",data,function(html) {
      quickDialogue(html,\'\',\'changeSettingsResultDialogue\');
    }); // Send the form data via AJAX.

    $("#changeSettingsDialogue").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
    $(".colorpicker").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.

    return false; // Don\'t submit the form.
  });
});

$(function() {
  var rooms = [
    ' . $roomData3 . '
  ];
  $("#defaultRoom").autocomplete({
    source: rooms
  });
  $("#watchRoomBridge").autocomplete({
    source: rooms
  });
});

function addRoom() {
  var val = $("#watchRoomBridge").val();
  var id = roomRef[val];

  var currentRooms = $("#watchRooms").val().split(",");
  currentRooms.push(id);

  $("#watchRoomsList").append("<span id=\"watchRoomSubList" + id + "\">" + val + " (<a href=\"javascript:void(0);\" onclick=\"removeRoom(" + id + ");\">x</a>), </span>");
  $("#watchRooms").val(currentRooms.toString(","));
}

function removeRoom(id) {
  $("#watchRoomSubList" + id).fadeOut(500,function(){$(this).remove()});

  var currentRooms = $("#watchRooms").val().split(",");
  for(var i = 0; i < currentRooms.length; i++) {
    if(currentRooms[i] == id) {
      currentRooms.splice(i, 1);
      break;
    }
  }

  $("#watchRooms").val(currentRooms.toString(","));
}
</script>

<form action="/index.php?action=options&phase=2" method="post" id="changeSettingsForm">
  <label for="reverse">Show Old Posts First:</label> <input type="checkbox" name="reverse" id="reverse" value="true"' . ($user['settings'] & 32 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">This will show newer posts at the bottom instead of the top, as is common with many instant messenging programs.</span></small><br /><br />

  <label for="disableding">Disable Ding:</label> <input type="checkbox" name="disableding" id="disableding" value="true" ' . ($user['settings'] & 128 ? ' checed="checked"' : '') . '" /><br />
  <small><span style="margin-left: 10px;">If checked, the ding will be completely disabled in the chat.</span></small><br /><br />

  <label for="mature">Disable Parental Controls:</label> <input type="checkbox" name="mature" id="mature" value="true"' . ($user['settings'] & 64 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">By default parental controls are enabled that help keep younger users safe. Check this to disable these features, however we take no responsibility for any reprecussions.</span></small><br /><br />

  <label for="disableFormatting">Disable Formatting:</label> <input type="checkbox" name="disableFormatting" id="disableFormatting" value="true"' . ($user['settings'] & 512 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">This will disable default formatting some users use on their messages.</span></small><br /><br />

  <label for="disableVideo">Disable Video Embeds:</label> <input type="checkbox" name="disableVideo" id="disableVideo" value="true"' . ($user['settings'] & 1024 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">This will disable video embeds in rooms that allow them, replaced with a "click to activate" link.</span></small><br /><br />

  <label for="disableImage">Disable Image Embeds:</label> <input type="checkbox" name="disableImage" id="disableImage" value="true"' . ($user['settings'] & 2048 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">This will disable image embeds in rooms that allow them, replaced with a link or alternate text.</span></small><br /><br />

  ' . ($enableDF ? '
  Default Formatting:<br />

  ' . ($enableDF['font'] ? '
  <select name="defaultFace" id="defaultFace" onchange="var fontFace = $(\'#defaultFace option:selected\').val(); $(\'#fontPreview\').css(\'font-family\',fontFace);" style="margin-left: 10px;">'. $fontBox . '</select>' : '') . ($enableDF['colour'] ? '

  <input style="width: 40px;" id="defaultColour" name="defaultColour" />'  : '') . ($enableDF['highlight'] ? '

  <input style="width: 40px;" id="defaultHighlight" name="defaultHighlight" />' : '') . ($enableDF['general'] ? 

  '<label for="defaultBold">Bold</label><input type="checkbox" name="defaultBold" id="defaultBold" onchange="if ($(this).is(\':checked\')) { $(\'#fontPreview\').css(\'font-weight\',\'bold\'); } else { $(\'#fontPreview\').css(\'font-weight\',\'normal\'); }" value="true"' . ($user['defaultFormatting'] & 256 ? ' checked="checked"' : '') . ' />

  <label for="defaultItalics">Italics</label><input type="checkbox" name="defaultItalics" id="defaultItalics" value="true"' . ($user['defaultFormatting'] & 256 ? ' checked="checked"' : '') . ' onchange="if ($(this).is(\':checked\')) { $(\'#fontPreview\').css(\'font-style\',\'italic\'); } else { $(\'#fontPreview\').css(\'font-style\',\'normal\'); }" /><br />' : '') . '
  <small><span style="margin-left: 10px;" id="fontPreview">Here\'s a preview!</span></small><br /><br />' : '') . '

  <label for="defaultRoom">Default Room:</label>   <input type="text" name="defaultRoom" id="defaultRoom" /><br />
  <small><span style="margin-left: 10px;">This changes what room defaults when you first visit VRIM.</span></small><br /><br />

  <label for="watchRooms">Watch Rooms:</label>  <input type="text" name="watchRoomBridge" id="watchRoomBridge" /><input type="button" value="Add" onclick="addRoom()" /><br />
  <small><span style="margin-left: 10px;">These rooms will be monitored for new posts, similar to Private IMs.</span></small><br />
  <small><span style="margin-left: 10px;">Current Rooms: <span id="watchRoomsList">' . $watchList .  '</span></span></small><br /><br />

  <input type="hidden" name="watchRooms" id="watchRooms" value="' . $user['watchRooms'] . '" />

  <button type="submit">Save Settings</button><button type="reset">Reset</button>
</form>';
}
elseif ($phase == '2') {
  $reverse = ($_POST['reverse'] ? true : false);
  $mature = ($_POST['mature'] ? true : false);
  $disableFormatting = ($_POST['disableFormatting'] ? true : false);
  $disableVideo = ($_POST['disableVideo'] ? true : false);
  $disableImage = ($_POST['disableImage'] ? true : false);
  $disableDing = ($_POST['disableding'] ? true : false);

  $settings = ($user['settings'] & 1) + ($user['settings'] & 2) + ($user['settings'] & 4) + ($user['settings'] & 8) + ($user['settings'] & 16) + ($reverse ? 32 : 0) + ($mature ? 64 : 0) + ($disableDing ? 128 : 0) + ($disableFormatting ? 512 : 0) + ($disableVideo ? 1024 : 0) + ($disableImage ? 2048 : 0);

  if ($enableDF['font']) {
  }

  if ($enableDF['colour']) {
    if (preg_match('/(0|1|2|3|4|5|6|7|8|9|A|B|C|D|E|F){3,6}/i',$_POST['defaultColour'])) {
      $colour = mysqlEscape(implode(',',html2rgb($_POST['defaultColour'])));
    }
    else {
      trigger_error('The specified colour, ' . $_POST['defaultColour'] . ', does not exist',E_USER_WARNING);
    }
  }

  if ($enableDF['highlight']) {
    if (preg_match('/(0|1|2|3|4|5|6|7|8|9|A|B|C|D|E|F){3,6}/i',$_POST['defaultHighlight']) || $_POST['defaultHighlight'] == 'transparent') {
      $highlight = mysqlEscape(implode(',',html2rgb($_POST['defaultHighlight'])));
    }
    else {
      trigger_error("The specified highlight, $_POST[defaultHighlight], does not exist",E_USER_WARNING);
    }
  }

  if ($enableDF['general']) {
    $defaultFormatting = ($_POST['defaultBold'] ? 256 : 0) + ($_POST['defaultItalics'] ? 512 : 0);
  }

  if ($_POST['defaultRoom']) {
    $defaultRoom = mysqlEscape($_POST['defaultRoom']);
    $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE name LIKE '$defaultRoom'");
    if ($room['id']) {
      $defaultRoom = $room['id'];
    }
    else {
      $defaultRoom = 1;
      trigger_error("The specified room, $_POST[defaultRoom], does not exist.",E_USER_WARNING);
    }
  }
  else {
    $defaultRoom = 1;
  }

  if ($_POST['watchRooms']) {
    $watchRooms = explode(',',$_POST['watchRooms']);
    foreach ($watchRooms AS $wroom) {
      $id = intval($wroom);
      if (!$id) continue;

      $wroom2 = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $id");
      if (!hasPermission($wroom2,$user)) continue;

      $watchRooms2[] = $wroom2['id'];
    }

    $watchRooms2 = mysqlEscape(implode(',',$watchRooms2));
  }

  mysqlQuery("UPDATE {$sqlPrefix}users SET settings = $settings, defaultFormatting = $defaultFormatting, defaultHighlight = \"$highlight\", defaultColour = \"$colour\", defaultFontface = \"$fontface\", defaultRoom = $defaultRoom, watchRooms = '$watchRooms2' WHERE userid = $user[userid]");

  echo 'Your settings have been updated successfully.';
}
?>