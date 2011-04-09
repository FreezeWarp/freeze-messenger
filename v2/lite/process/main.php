<?php
$room = intval($_GET['room'] ?: $user['defaultRoom'] ?: 1); // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = '$room'"); // Data on the room.


/* Cookie Processing */
// Cookies are stored as strings, and 'false' == bool(true) in PHP... stupid, I know, so instead we use the string "true", which returns true when compared against both the string and the bool "true" types. It also returns false against the integer one.
// Also note that for GET transfer, the integer values "0" and "1" evaluate false and true respectively.
if ($_COOKIE['vrim10-reverseOrder'] == 'false') $reverse = 0;
elseif ($_COOKIE['vrim10-reverseOrder'] == 'true' || $user['settings'] & 32) $reverse = 1; // Check the cookies for reverse post order.

if ($_COOKIE['slowConnection'] == 'false') $slowConnection = 0;
elseif ($_COOKIE['slowConnection'] == 'true' || $user['settings'] & 256) $slowConnection = 1;


setcookie('lastmessage-room' . $room['id'],1,time() + 60 * 15,'/','.victoryroad.net');


if ($mode == 'normal' || $mode == 'simple') {
  /* Start Room Code
   * Get and format all of the rooms for display in a second here. */
  if ($user['favRooms']) $rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE AND id IN ($user[favRooms])",'id'); // Get all rooms
  if ($rooms) foreach ($rooms AS $room3) $roomHtml .= "            <li><a href=\"/index.php?room=$room3[id]\">$room3[name]</a></li>\n";
}
?>