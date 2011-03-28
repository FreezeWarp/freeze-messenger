<?php
$room = intval($_GET['room'] ?: $user['defaultRoom'] ?: 1); // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = '$room'"); // Data on the room.
?>