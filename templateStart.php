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



/*** Major Phrase Defaults ***/

if (!$phrases['doctype']) {
  $phrases['doctype'] = '<!DOCTYPE HTML>';
}
if (!$phrases['brandingTitle']) {
  $phrases['brandingTitle'] = 'FreezeMessenger';
}
if (!$phrases['brandingFaviconIE'] && $phrase['brandingFavicon']) {
  $phrases['brandingFaviconIE'] = $phrase['brandingFavicon'];
}


/*** Keyword Generation ***/

if ($phrases['keywords']) {
  $keyWordString .= ", $phrases[keywords]";
}
if ($keywords) {
  $keyWordString .= ", $keywords";
}


if ($_REQUEST['mode'] == 'light' || $_REQUESt['mode'] == 'mobile') {
  $bodyHook .= ' data-mode="light"';
  $styleHook .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"client/css/stylesv2Mobile.css\" media=\"screen,handheld\" />";
  $light = true;
}
else {
  $light = false;
}

$mode = $_GET['mode'];

/*** Start ***/

header('Content-Type: text/html; charset=utf-8'); 

eval(hook('templateStart'));

/*** Process Favourite Rooms
 * Used for IE9 Coolness ***/

if ($user['favRooms']) {
  $stop = false;

  $favRooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE AND id IN ($user[favRooms])",'id');

  eval(hook('templateFavRoomsStart'));

  foreach ($favRooms AS $id => $room2) {
    eval(hook('templateFavRoomsEachStart'));

    if (!hasPermission($room2,$user,'post') && !$stop) {
      $currentRooms = explode(',',$user['favRooms']);
      foreach ($currentRooms as $room3) if ($room3 != $room2['id'] && $room3 != '') $currentRooms2[] = $room3; // Rebuild the array without the room ID.
      $newRoomString = mysqlEscape(implode(',',$currentRooms2));

      mysqlQuery("UPDATE {$sqlPrefix}users SET favRooms = '$newRoomString' WHERE userid = $user[userid]");

      $stop = false;

      continue;
    }

    $room2['name'] = vrim_encodeXML($room2['name']);

    $roomMs .= template('templateRoomMs');
    $roomHtml .= template('templateRoomHtml');

    eval(hook('templateFavRoomsEachEnd'));

    $template['roomHtml'] = $roomHtml;
    $template['roomMs'] = $roomMs;
  }

  eval(hook('templateFavRoomsEnd'));
}


echo template('templateStart');
?>