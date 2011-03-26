<?php
$novalidate = true;

require('functions/parserFunctions.php');
require('global.php');

$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = 111");
$user['userid'] = 179;
$ip = mysqlEscape($_SERVER['REMOTE_ADDR']); // Get the IP address of the user.
$message = finalParse($_GET['message']);

list($messageRaw,$messageHtml,$messageVBnet,$saltNum,$iv) = $message;
$query = "INSERT INTO {$sqlPrefix}messages (user,room,rawText,htmlText,vbText,salt,iv,microtime,ip) VALUES ($user[userid],$room[id],'$messageRaw','$messageHtml','$messageVBnet',$saltNum,'$iv','" . microtime(true) . "','$ip')";


for($x;$x<7500;$x++)mysqlQuery($query);

mysqlClose();
?>