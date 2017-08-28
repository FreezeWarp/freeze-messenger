<?php
require('functions/curlRequest.php');

$host = 'http://localhost/freeze-messenger';

function green($text) {
    return '<span style="text-color: #00ff00;">' . $text . '</span>';
}

function red($text) {
    return '<span style="text-color: #ff0000;">' . $text . '</span>';
}


echo '<h1>Login</h1>';
if ($accessToken = $login['login']['access_token']) {
    echo 'Success<br />';
}
else {
    echo json_encode($login);
}

echo '<h1>Get Messages, No Parameters</h1>';
$getMessages1 = curlRequest::quickRunGET("{$host}/api/message.php", []);
if ($getMessages1['exception']['string'] === 'noLogin') {
    echo 'Success<br />';
}


