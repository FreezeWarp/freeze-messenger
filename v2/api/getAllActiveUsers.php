<?php
require_once('../global.php');
header('Content-type: text/xml');

$time = ($_GET['time'] ?: time());
$onlineThreshold = ($_GET['onlineThreshold'] ?: $onlineThreshold);

$users = sqlArr("SELECT
  u.username, u.userid, GROUP_CONCAT(r.name) AS roomnames, GROUP_CONCAT(r.id) AS roomids
FROM
  user AS u, {$sqlPrefix}rooms AS r, {$sqlPrefix}ping AS p
WHERE
  u.userid = p.userid AND
  r.id = p.roomid AND
  UNIX_TIMESTAMP(p.time) > $time - $onlineThreshold
GROUP BY
  p.userid
ORDER BY
  u.username",'userid');

if ($users) {
  foreach ($users AS $user) {
    unset($roomsXML);

    $rooms = array_combine(explode(',',$user['roomids']),explode(',',$user['roomnames']));
    foreach ($rooms AS $id => $name) $roomsXML .= "      <room>
        <roomid>$id</roomid>
        <roomname>$name</roomname>
      </room>";

    $usersXML .= "    <user>
      <userdata>
        <userid>$user[userid]</userid>
        <username>$user[username]</username>
      </userdata>
      <rooms>
      $roomsXML
      </rooms>
    </user>
";
  }
}

$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getAllActiveUsers>
  <activeUser>
    <userid>$user[userid]</userid>
    <username>" . vrim_encodeXML($user['username']) . "</username>
  </activeUser>
  <sentData>
    <onlineThreshold>$onlineThreshold</onlineThreshold>
    <time>$time</time>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <users>
    $usersXML
  </users>
</getAllActiveUsers>";

if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>