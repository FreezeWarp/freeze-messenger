<?php
$noReqLogin = true;
$title = 'Room List';
$reqPhrases = true;
$reqHooks = true;

require_once('global.php');


eval(hook('viewRoomsStart'));


require_once('templateStart.php');
require_once('functions/container.php');

$showAdvanced = ($mode == 'normal' ? true : false); // For advanced functionality.
static $roomHtml;

$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE AND options & 8 = FALSE ORDER BY options & 1 DESC, options & 16 ASC, id ASC",'id'); // Get all rooms

if ($user['userid']) { // Logged in user
  $favRooms = explode(',',$user['favRooms']);

  $stop = false;
  $id = 0;

  eval(hook('viewRoomsUserStart'));

  foreach ($rooms AS $id => $room2) {

    eval(hook('viewRoomsUserRoomEachStart'));

    if (hasPermission($room2,$user) && !$stop) {
      if ($room2['options'] & 16) $room2['class'] = 'Private';
      elseif ($room2['options'] & 1) $room2['class'] = 'Official';
      else $room2['class'] = 'Unofficial';

      $rooms2[$id] = $room2;
    }

    $stop = false;

    eval(hook('viewRoomsUserRoomEachEnd'));

  }


  if ($rooms2) {
    foreach ($rooms2 AS $room3) {
      $id = $room3['id'];

      $opacity = (in_array($room3['id'],$favRooms) ? 1 : .5);
      $active = (in_array($room3['id'],$favRooms) ? 1 : 0);

      $roomRow = "  <tr id=\"row$id\">
      " . ($showAdvanced ? "<td>$room3[class]</td>" : '') . "
      <td><a href=\"/chat.php?room=$room3[id]\">$room3[name]</a></td>
      <td>" . (($user['userid'] == $room3['owner'] || $user['settings'] & 16) ? '<a href="#" class="editRoomMulti" data-roomid="' . $room3['id'] . '"><img src="/images/document-edit.png" class="standard" alt="Configure" /></a>' . (($room3['options'] & 1) == false ? "<a href=\"javascript:void(0);\" onclick=\"if (confirm('Are you sure you want to delete this room')) { $.ajax({url: '/ajax/modAction.php?action=deleteroom&amp;roomid=$room3[id]', type: 'GET', cache: false, success: function() { $('#row$id').fadeOut(); } }); }\"><img src=\"/images/document-close.png\" class=\"standard\" alt=\"Delete\" /></a>" : '') : '') . "<a href=\"javascript:void(0);\" onclick=\"$.ajax({url: '/ajax/modAction.php?action=favroom&amp;roomid=$room3[id]', type: 'GET', cache: false, success: function() { if ($('#star$id').attr('data-active') == 1) { $('#star$id').attr('data-active','0'); $('#star$id').fadeTo(150,.5); } else { $('#star$id').attr('data-active','1'); $('#star$id').fadeTo(150,1); } } });\"><img id=\"star$id\" src=\"/images/bookmarks.png\" class=\"standard\" alt=\"(Un-)Favourite\" style=\"opacity: $opacity\" onmouseover=\"if ($(this).attr('data-active') == 1) { $(this).fadeTo(150,.5); } else { $(this).fadeTo(150,1); }\" onmouseout=\"if ($(this).attr('data-active') == 1) { $(this).fadeTo(150,1); } else { $(this).fadeTo(150,.5); }\" data-active=\"$active\" /></a></td>
    </tr>
  ";

      eval(hook('viewRoomsUserDisplayEach'));

      $roomHtml .= $roomRow;
    }

    eval(hook('viewRoomsUserStartOutput'));

    echo '<script type="text/javascript" src="/client/changeTitle.js"></script>';

    echo container('Quick Tips','<ul><li>Change the topic by pressing the button directly to the left of the existing title. Hit enter when you are done.</li><li>"<img src="images/bookmarks.png" style="height: 16px; width: 16px;" title="Official" alt="Official" />" denotes an official room. These are moderated and monitored more than other rooms.</li><li>If you own any group listed, you can modify or delete it with the buttons under "Actions".</li></ul>');

    echo '<table class="page ui-widget">
  <thead>
    <tr class="hrow ui-widget-header">
      ' . ($showAdvanced ? '<td style="width: 10%;">Class</td>' : '') . '
      <td style="width: 26%;">Room Name</td>
      ' . ($showAdvanced ? '<td style="width: 54%;">Topic</td>' : '') . '
      <td style="width: 10%;">Actions</td>
    </tr>
  </thead>
  <tbody class="ui-widget-content">
    ' . $roomHtml . '
  </tbody>
</table>';
  }
  else {
    echo container('Error','No rooms were found which you are allowed to view.');
  }

  eval(hook('viewRoomsUserEnd'));
}
else {
  eval(hook('viewRoomsAnonStart'));

  $stop = false;

  foreach ($rooms AS $id => $room2) {
    eval(hook('viewRoomsAnonRoomEachStart'));

    if (hasPermission($room2,$user,'view') && !$stop) {
      $rooms2[$id] = $room2;
    }

    $stop = false;

    eval(hook('viewRoomsAnonRoomEachEnd'));
  }

  if ($rooms2) {
    foreach ($rooms2 AS $room3) {
      $roomRow = "  <tr id=\"row$id\">
      <td><a href=\"/chat.php?action=archive&roomid=$room3[id]&numresults=50\">$room3[name]</a></td>
      <td id=\"title$id\">$room3[title]</td>
    </tr>
  ";

      eval(hook('viewRoomsAnonDisplayEach'));

      $roomHtml .= $roomRow;
    }

    eval(hook('viewRoomsAnonStartOutput'));

    echo '<table class="page ui-widget" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
      <td style="width: 25%;">Room Name</td>
      <td style="width: 75%;">Topic</td>
    </tr>
  </thead>
  <tbody class="ui-widget-content">
    ' . $roomHtml . '
  </tbody>
</table>';
  }
  else {
    echo container('Error','No rooms were found which you are allowed to view.');
  }

  eval(hook('viewRoomsAnonEnd'));
}


eval(hook('viewRoomsEnd'));

require_once('templateEnd.php');
?>