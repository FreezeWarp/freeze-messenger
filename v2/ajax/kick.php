<?php


require_once('../global.php');

  $roomSelect = mysqlReadThrough(mysqlQuery("SELECT * FROM {$sqlPrefix}rooms WHERE " . ((($user['settings'] & 16) == false) ? "(owner = '$user[userid]' OR moderators REGEXP '({$user[userid]},)|{$user[userid]}$') AND " : '') . "(options & 16) = false AND (options & 4) = false AND (options & 8) = false"),'<option value="$id"{{' . intval($_GET['roomid']) . ' == $id}}{{ selected="selected"}{}}>$name</option>
');
  $userSelect = mysqlReadThrough(mysqlQuery("SELECT u2.userid, u2.username FROM {$sqlPrefix}users AS u, user AS u2 WHERE u2.userid = u.userid ORDER BY username"),'<option value="$userid">$username</option>
');
  echo '';


?>