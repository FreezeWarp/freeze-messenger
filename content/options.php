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

$reqPhrases = true;
$reqHooks = true;

require_once('../global.php');
require_once('../functions/generalFunctions.php');
require_once('../functions/parserFunctions.php');

$phase = $_GET['phase'];
if (!$phase) $phase = '1'; // Default to phase 1.

/*$fontData = sqlArr("SELECT * FROM {$sqlPrefix}fonts ORDER BY category, name",'id');
foreach($fontData AS $id => $font) {
  $fontBox .= "<option value=\"$font[id]\" style=\"font-family: $font[data];\" data-font=\"$font[data]\">$font[name]</option>";
}

$watchRooms = explode(',',$user['watchRooms']);*/

$reverse = ($_POST['reverse'] ? true : false);
$mature = ($_POST['mature'] ? true : false);
$disableFormatting = ($_POST['disableFormatting'] ? true : false);
$disableVideo = ($_POST['disableVideo'] ? true : false);
$disableImage = ($_POST['disableImage'] ? true : false);
$disableDing = ($_POST['disableding'] ? true : false);

$settings = ($user['settings'] & 1) + ($user['settings'] & 2) + ($user['settings'] & 4) + ($user['settings'] & 8) + ($user['settings'] & 16) + ($reverse ? 32 : 0) + ($mature ? 64 : 0) + ($disableDing ? 128 : 0) + ($disableFormatting ? 512 : 0) + ($disableVideo ? 1024 : 0) + ($disableImage ? 2048 : 0);

if ($enableDF['font'] && $_POST['defaultFace']) {
  echo $id = intval($_POST['defaultFace']);
  $font = sqlArr("SELECT * FROM {$sqlPrefix}fonts WHERE id = $id");
  echo $fontface = $font['data'];
}

if ($enableDF['colour'] && $_POST['defaultColour']) {
  if (preg_match('/(0|1|2|3|4|5|6|7|8|9|A|B|C|D|E|F){3,6}/i',$_POST['defaultColour']) || !$_POST['defaultColour']) {
    $colour = mysqlEscape(implode(',',html2rgb($_POST['defaultColour'])));
  }
  else {
    trigger_error('The specified colour, ' . $_POST['defaultColour'] . ', does not exist',E_USER_WARNING);
  }
}

  if ($enableDF['highlight'] && $_POST['defaultHighlight']) {
    if (preg_match('/(0|1|2|3|4|5|6|7|8|9|A|B|C|D|E|F){3,6}/i',$_POST['defaultHighlight']) || !$_POST['defaultHighlight']) {
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