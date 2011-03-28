<?php
/* Start Libs */

require_once('global.php'); // Used for everything.
require_once('functions/container.php'); // Used for /some/ formatting, though perhaps too sparcely right now.



/* Parse Arguments */

$action = $_GET['action']; // What are we gonna be doing today?

/* Cookie Processing */
// Cookies are stored as strings, and 'false' == bool(true) in PHP... stupid, I know, so instead we use the string "true", which returns true when compared against both the string and the bool "true" types. It also returns false against the integer one.
// Also note that for GET transfer, the integer values "0" and "1" evaluate false and true respectively.
if ($_COOKIE['vrim10-reverseOrder'] == 'false') $reverse = 0;
elseif ($_COOKIE['vrim10-reverseOrder'] == 'true' || $user['settings'] & 32) $reverse = 1; // Check the cookies for reverse post order.

/* Start Room Code
 * Get and format all of the rooms for display in a second here. */
if ($user['favRooms']) $rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE AND id IN ($user[favRooms])",'id'); // Get all rooms
if ($rooms) foreach ($rooms AS $room3) $roomHtml .= "            <li><a href=\"/index.php?room=$room3[id]\" class=\"room\" data-roomid=\"$room3[id]\">$room3[name]</a></li>\n";


/* Code required before headers are sent. */

if ($user['userid']) {
  switch ($action) {
    case 'chat': case '': case false:
      $includeIE9 = true;
      $inRoom = true;
      require_once('process/main.php');
      $title = 'Room ' . htmlentities($room['name']);
    break;
    case 'logout': require_once('process/logout.php'); break;
  }
}



/* Template Start */

require_once('templateStart.php');

/* Data */

if ($user['userid'] || in_array($action,array('archive','viewRooms','help','online','stats'))) { // Make sure the user has been assigned a userid, and thus is logged in.
  switch ($action) {
    case 'chat': case '': case false: $chat = true; require_once('content/main.php'); break; // No action is supplied or is it is to show the chat.
    case 'createRoom': require_once('content/createRoom.php'); break;
    case 'editRoom': require_once('content/editRoom.php'); break;
    case 'viewRooms': require_once('content/viewRooms.php'); break;
    case 'privateRoom': require_once('content/privateRoom.php'); break;
    case 'archive': require_once('content/archive.php'); break;
    case 'options': require_once('content/options.php'); break;
    case 'moderate': require_once('content/moderate.php'); break;
    case 'help': require_once('content/help.php'); break;
    case 'online': require_once('content/online.php'); break;
    case 'kick': require_once('content/kick.php'); break;
    case 'logout': require_once('content/logout.php'); break;
    case 'unkick': require_once('content/unkick.php'); break;
    case 'manageKick': require_once('content/manageKick.php'); break;
    case 'stats': require_once('content/stats.php'); break;
    default: trigger_error('The page you are looking for could not be found.',E_USER_ERROR); break;
  }
}

else { // Not logged in.
  if ($flag) {
    switch($flag) {
      case 'nouser': $message .= 'No user exists with that user title. Is it possible that you have changed your name recently?'; break; // No user with that username exists.
      case 'nopass': $message .= 'You appeared to have entered a wrong password. Remeber, passwords are case sensitive.'; break; // The password is wrong.
    }

    echo container('Unsuccessful Login',$message);
  }

  echo container('Login to Victory Road Chat','<div id="normalLogin">
    Hello. Please Enter Your Login Credentials Below:<br />

    <form action="/index.php" method="post" style="text-align: center; display: block;">
      <label for="username">Username:</label><br />
      <input type="text" name="username" placeholder="Please enter your username." /><br /><br />

      <label for="password">Password:</label><br />
      <input type="password" name="password" placeholder="Please enter your password." /><br /><br />

      <label for="rememberme">Remember Me for One Week?:</label>
      <input type="checkbox" name="rememberme" id="rememberme" /><br /><br />

      <button type="submit">Launch</button><button type="reset">Start Over</button><button type="button" onclick="$(\'#normalLogin\').slideUp(); $(\'#secureLogin\').slideDown();">Secure Login</button>
    </form>
  </div>

  <div id="secureLogin" style="display: none;">
    Below you can login directly to the forums via SSL encyption:<br /><br />

    <span style="middle" onclick="$(this).html(\'<iframe src=&quot;https://www.victoryroad.net/login.php?do=vrimLogin&quot; style=&quot;width: 300px; height: 150px;&quot;>iFrames are disabled.</iframe>\');"><a href="javascript:void(0);">[Display Login]</a></span><br /><br />

    Once you have logged in, <a href="/">click here</a>.
  </div>');

  echo container('Guest Links','<ul><li><a href="/index.php?action=online">Who\'s Online</a></li><li><a href="/index.php?action=viewRooms">Room List</a></li><li><a href="/index.php?action=archive">Archives</a></li><ul><li><a href="/index.php?action=archive&roomid=1">Main</a></li></ul></ul>');
}


/* Templates End */

require_once('templateEnd.php');



mysqlClose(); // Cancel MySQL Connection
?>