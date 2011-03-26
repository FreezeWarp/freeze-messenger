<?php
header('Content-type: text/plain');
require_once('../global.php');

$time = ($_GET['time'] ?: time());
$onlineThreshold = ($_GET['onlineThreshold'] ?: $onlineThreshold);
$autoResolveNames = ($_GET['autoResolveNames'] ? true : false);

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
    unset($rooms2);

    $rooms = array_combine(explode(',',$user['roomids']),explode(',',$user['roomnames']));
    foreach ($rooms AS $id => $name) $rooms2[] = ($autoResolveNames ? $name : $id);

    $rooms2 = implode(',',$rooms2);

    $users2 .= "'$rooms2','$user[userid]'\n";
  }
}

echo $users2;

mysqlClose();
?>