<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
require('functions/curlRequest.php');

echo '<h1>API Unit Testing Suite</h1>';
echo '<p>This is a basic series of unit tests for the Messenger API. Tests should only be run on a fresh installation without development data. The installation user should be admin/admin.</p>';

$host = 'http://localhost/freeze-messenger/';

function curlTestCommon($input, $jsonIndex, $expectedValue, $callback = null) {
    $requestNarrow = $input;
    foreach ($jsonIndex AS $index) {
        $requestNarrow = $requestNarrow[$index];
    }

    if ($requestNarrow === $expectedValue) {
        echo green("Success<br />");
    }
    else {
        echo red('Expected ' . $expectedValue . ', found ' . $requestNarrow . '<br />' . print_r($input, true) . '<br />');
    }

    if ($callback) {
        $callback($input);
    }
}

function curlTestGETEquals($path, $params, $jsonIndex, $expectedValue, $callback = false) {
    global $host;
    curlTestCommon(curlRequest::quickRunGET("{$host}{$path}", $params), $jsonIndex, $expectedValue, $callback);
}

function curlTestPOSTEquals($path, $params, $body, $jsonIndex, $expectedValue, $callback = false) {
    global $host;
    curlTestCommon(curlRequest::quickRunPOST("{$host}{$path}", $params, $body), $jsonIndex, $expectedValue, $callback);
}

function green($text) {
    return '<span style="color: #00ff00;">' . $text . '</span>';
}

function red($text) {
    return '<span style="color: #ff0000;">' . $text . '</span>';
}


echo '<h1>Login</h1>';

$accessToken = false;


curlTestPOSTEquals(
    'validate.php',
    [],
    ['username' => 'admin', 'password' => 'admin', 'client_id' => 'WebPro', 'grant_type' => 'password'],
    ['login', 'userData', 'userName'],
    'admin',
    function($data) {
        global $accessToken;
        $accessToken = $data['login']['access_token'];
    }
);


echo '<h1>Get Messages, No Login</h1>';
curlTestGETEquals(
    'api/message.php',
    [],
    ['exception', 'string'],
    'noLogin'
);

echo '<h1>Get Messages, No Room</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken],
    ['exception', 'string'],
    'missingRoomId'
);

echo '<h1>Get Messages, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1],
    ['messages'],
    []
);

echo '<h1>Send Message "Hi", Room 1</h1>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken, 'roomId' => 1],
    ['message' => 'Hi'],
    ['sendMessage', 'censor'],
    []
);

echo '<h1>Get Messages, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1],
    ['messages', 0, 'messageText'],
    'Hi'
);

echo '<h1>Get Messages Since Message 0, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 1],
    ['messages', 0, 'messageText'],
    'Hi'
);

echo '<h1>Get Messages Since Message 1, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 2],
    ['messages'],
    []
);

echo '<h1>Send Message "Crazy", Room 1</h1>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken, 'roomId' => 1],
    ['message' => 'Crazy'],
    ['sendMessage', 'censor'],
    []
);

echo '<h1>Get Messages Since Message 0, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 1],
    ['messages', 0, 'messageText'],
    'Hi'
);

echo '<h1>Get Messages Since Message 1, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 2],
    ['messages', 0, 'messageText'],
    'Crazy'
);

echo '<h1>Get Messages Since Message 2, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 3],
    ['messages'],
    []
);

echo '<h1>Edit Message 3, Room 1</h1>';
curlTestPOSTEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'id' => 2],
    ['message' => 'Crazy For You'],
    ['sendMessage', 'censor'],
    []
);

echo '<h1>Get Message 3</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'id' => 2],
    ['messages', 0, 'messageText'],
    'Crazy For You'
);

echo '<h1>Delete Message 1, Room 1</h1>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'delete', 'access_token' => $accessToken, 'roomId' => 1, 'id' => 1],
    [],
    [],
    []
);

echo '<h1>Get Messages Since Message 0, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 0],
    ['messages', 0, 'messageText'],
    'Crazy For You'
);

echo '<h1>Get Even Deleted Messages Since Message 0, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 0, 'archive' => true, 'showDeleted' => true],
    ['messages', 0, 'messageText'],
    'Hi'
);

echo '<h1>Undelete Message 1, Room 1</h1>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'undelete', 'access_token' => $accessToken, 'roomId' => 1, 'id' => 1],
    [],
    [],
    []
);

// we need to use archive to get undeleted messages.
echo '<h1>Get Messages Since Message 0, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'archive' => true, 'messageIdStart' => 0],
    ['messages', 0, 'messageText'],
    'Hi'
);

echo '<h1>Get (Invalid) Message 10, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'id' => 10],
    ['exception', 'string'],
    'idNoExist'
);

echo '<h1>Get Message 10, (Invalid) Room 10</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 10, 'id' => 1],
    ['exception', 'string'],
    'roomIdNoExist'
);

// mutually exclusive
echo '<h1>Get Messages Between Message 0 and Message 10, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 1, 'messageIdEnd' => 10],
    ['exception', 'string'],
    'messageDateMinMessageDateMaxMessageIdStartMessageIdEndConflict'
);

// mutually exclusive
echo '<h1>Get Messages Starting Message 1 and Message Date 1, Room 1</h1>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 1, 'messageDateMin' => 1],
    ['exception', 'string'],
    'messageDateMinMessageDateMaxMessageIdStartMessageIdEndConflict'
);

echo '<h1>Undelete (Invalid) Message 10, Room 1</h1>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'undelete', 'access_token' => $accessToken, 'roomId' => 1, 'id' => 10],
    [],
    ['exception', 'string'],
    'idNoExist'
);

echo '<h1>Send Message "Hi Bob %d" 98 Times, Room 1</h1>';
for ($i = 2; $i < 100; $i++) {
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $accessToken, 'roomId' => 1],
        ['message' => 'Hi Bob ' . $i],
        ['sendMessage', 'censor'],
        []
    );
}

// todo: create admin-only room
// todo: create unpriviledged user
// todo: unpriviledged user can post in default room
// todo: unpriviledged user can't post in admin-only room
// todo: admin kick unpriviledged user in default room
// todo: unpriviledged user can't post in default room
// todo: admin unkick unpriviledged user in default room
// todo: unpriviledged user can post in default room