<?php
$apiRequest = true;

require_once('../global.php');
header('Content-type: text/xml');

$userid = intval($_GET['userid']);
$username = mysqlEscape(vrim_urldecode($_GET['username']));

if ($userid) {
  $getuservb = sqlArr("SELECT * FROM user WHERE userid = $userid");
}
elseif ($username) {
  $getuservb = sqlArr("SELECT * FROM user WHERE username = '$username'");
}
else {
  $failCode = 'nodata';
  $failMessage = 'Neither a username or userid was privided.';
}

if ($getuservb) {
  if ($user['userid'] > 0) {
    $getuser = sqlArr("SELECT * FROM {$sqlPrefix}users WHERE userid = $getuservb[userid]");
  }
  else {
    $failCode = 'logicerror';
  }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getUserInfo>
  <activeUser>
    <userid>$user[userid]</userid>
    <username>" . vrim_encodeXML($user['username']) . "</username>
  </activeUser>
  <sentData>
    <userid>$userid</userid>
    <username>$username</username>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <userData>
    <userid>$geteuser[userid]</userid>
    <username>$getuser[username]</username>
    <settings>$getuser[settings]</settings>
    <favRooms>$getuser[favRooms]</favRooms>
  </userData>
</getUserInfo>";

mysqlClose();
?>