<?php
header('Content-type: text/plain');
require_once('../global.php');

$room = intval($_GET['room']);
$time = time();

if ($room) {
  if (mysqlQuery("INSERT INTO {$sqlPrefix}ping (userid,roomid,time) VALUES ($user[userid],$room,CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE time = CURRENT_TIMESTAMP()")) {
    echo '\'1\'';
  }
  else {
    echo '\'0\'';
  }
}
else {
  echo '\'1\'';
}

mysqlClose();
?>