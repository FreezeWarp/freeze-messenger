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


if ($_REQUEST['layout'] == 'alt') {
  $styleHook .= "<style>#menu { display: none; width: 0px; } #messageListContainer { float: right; width: 50%; } #textentryBoxMessage { float: left; width: 50%; } #content { width: 100%; }</style>";
  $layout = 'alt';
}

$theme = ($_REQUEST['style'] ? $_REQUEST['style']
  : ($_REQUEST['s']['style'] ? $_REQUEST['s']['style']
    : ($user['themeOfficialAjax'] ? $user['themeOfficialAjax']
      : ($defaultTheme ? $defaultTheme : 4))));

$styles = array(
  1 => 'ui-darkness',
  2 => 'ui-lightness',
  3 => 'redmond',
  4 => 'cupertino',
  5 => 'dark-hive',
  6 => 'start',
);
$style = $styles[$theme];



/*** Start ***/

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

      mysqlQuery("UPDATE {$sqlPrefix}users SET favRooms = '$newRoomString' WHERE userId = $user[userId]");

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

if ($mode == 'mobile' && !$showMobileMenus) {}
else {  echo template('templateStart'); }
?>