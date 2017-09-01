<?php
//todo: change config. maybe custom WebPro API?

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
require('functions/curlRequest.php');

echo '<h2>API Unit Testing Suite</h2>';
echo '<p>This is a basic series of unit tests for the Messenger API. Tests should only be run on a fresh installation without development data. The installation user should be admin/admin.</p>';

$host = 'http://localhost/freeze-messenger/';

function curlTestCommon($input, $jsonIndexes, $expectedValues, $callback = null) {
    $good = true;

    foreach ($jsonIndexes AS $indexNum => $jsonIndex) {
        $requestNarrow = $input;

        foreach ($jsonIndex AS $index) {
            $requestNarrow = $requestNarrow[$index];
        }

        if ($requestNarrow !== $expectedValues[$indexNum]) {
            echo red('Expected ' . $expectedValues[$indexNum] . ', found ' . $requestNarrow . '<br />');
            $good = false;
        }
    }


    if ($good) {
        echo green("Success<br />");
    }
    else {
        echo red(print_r($input, true) . '<br />');
    }



    if ($callback) {
        $callback($input);
    }
}

function curlTestGETEquals($path, $params, $jsonIndex, $expectedValue, $callback = false) {
    global $host;
    curlTestCommon(curlRequest::quickRunGET("{$host}{$path}", $params), [$jsonIndex], [$expectedValue], $callback);
}

function curlTestGETEqualsMulti($path, $params, $jsonIndexes, $expectedValues, $callback = false) {
    global $host;
    curlTestCommon(curlRequest::quickRunGET("{$host}{$path}", $params), $jsonIndexes, $expectedValues, $callback);
}

function curlTestPOSTEquals($path, $params, $body, $jsonIndex, $expectedValue, $callback = false) {
    global $host;
    curlTestCommon(curlRequest::quickRunPOST("{$host}{$path}", $params, $body), [$jsonIndex], [$expectedValue], $callback);
}

function curlTestPOSTEqualsMulti($path, $params, $body, $jsonIndexes, $expectedValues, $callback = false) {
    global $host;
    curlTestCommon(curlRequest::quickRunPOST("{$host}{$path}", $params, $body), $jsonIndexes, $expectedValues, $callback);
}

function green($text) {
    return '<span style="color: #00ff00;">' . $text . '</span>';
}

function red($text) {
    return '<span style="color: #ff0000;">' . $text . '</span>';
}


echo '<h2>Login</h2>';

$accessToken = false;
$accessToken2 = false;


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

echo '<h1>Message Tests, Main User</h1>';
/*echo '<h2>Get Messages, No Login</h2>';
curlTestGETEquals(
    'api/message.php',
    [],
    ['exception', 'string'],
    'noLogin'
);

echo '<h2>Get Messages, No Room</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken],
    ['exception', 'string'],
    'missingRoomId'
);

echo '<h2>Get Messages, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1],
    ['messages'],
    []
);

echo '<h2>Send Message "Hi", Room 1</h2>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken, 'roomId' => 1],
    ['message' => 'Hi'],
    ['sendMessage', 'censor'],
    []
);

echo '<h2>Get Messages, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1],
    ['messages', 0, 'messageText'],
    'Hi'
);

echo '<h2>Get Messages Since Message 0, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 1],
    ['messages', 0, 'messageText'],
    'Hi'
);

echo '<h2>Get Messages Since Message 1, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 2],
    ['messages'],
    []
);

echo '<h2>Send Message "Crazy", Room 1</h2>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken, 'roomId' => 1],
    ['message' => 'Crazy'],
    ['sendMessage', 'censor'],
    []
);

echo '<h2>Get Messages Since Message 0, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 1],
    ['messages', 0, 'messageText'],
    'Hi'
);

echo '<h2>Get Messages Since Message 1, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 2],
    ['messages', 0, 'messageText'],
    'Crazy'
);

echo '<h2>Get Messages Since Message 2, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 3],
    ['messages'],
    []
);

echo '<h2>Edit Message 3, Room 1</h2>';
curlTestPOSTEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'id' => 2],
    ['message' => 'Crazy For You'],
    ['sendMessage', 'censor'],
    []
);

echo '<h2>Get Message 3</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'id' => 2],
    ['messages', 0, 'messageText'],
    'Crazy For You'
);

echo '<h2>Delete Message 1, Room 1</h2>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'delete', 'access_token' => $accessToken, 'roomId' => 1, 'id' => 1],
    [],
    [],
    []
);

echo '<h2>Get Messages Since Message 0, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 0],
    ['messages', 0, 'messageText'],
    'Crazy For You'
);

echo '<h2>Get Even Deleted Messages Since Message 0, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 0, 'archive' => true, 'showDeleted' => true],
    ['messages', 0, 'messageText'],
    'Hi'
);

echo '<h2>Undelete Message 1, Room 1</h2>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'undelete', 'access_token' => $accessToken, 'roomId' => 1, 'id' => 1],
    [],
    [],
    []
);

// we need to use archive to get undeleted messages.
echo '<h2>Get Messages Since Message 0, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'archive' => true, 'messageIdStart' => 0],
    ['messages', 0, 'messageText'],
    'Hi'
);

echo '<h2>Get (Invalid) Message 10, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'id' => 10],
    ['exception', 'string'],
    'idNoExist'
);

echo '<h2>Get Message 10, (Invalid) Room 10</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 10, 'id' => 1],
    ['exception', 'string'],
    'roomIdNoExist'
);

// mutually exclusive
echo '<h2>Get Messages Between Message 0 and Message 10, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 1, 'messageIdEnd' => 10],
    ['exception', 'string'],
    'messageDateMinMessageDateMaxMessageIdStartMessageIdEndConflict'
);

// mutually exclusive
echo '<h2>Get Messages Starting Message 1 and Message Date 1, Room 1</h2>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 1, 'messageDateMin' => 1],
    ['exception', 'string'],
    'messageDateMinMessageDateMaxMessageIdStartMessageIdEndConflict'
);

echo '<h2>Undelete (Invalid) Message 10, Room 1</h2>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'undelete', 'access_token' => $accessToken, 'roomId' => 1, 'id' => 10],
    [],
    ['exception', 'string'],
    'idNoExist'
);*/

echo '<h2>Send Message "Hi Bob %d" 98 Times, Room 1</h2>';
for ($i = 2; $i < 100; $i++) {
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $accessToken, 'roomId' => 1],
        ['message' => 'Hi Bob ' . $i],
        ['sendMessage', 'censor'],
        []
    );
}

echo '<h1>Room Tests, Main User</h1>';
echo '<h2>Get Room 1</h2>';
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => 1],
    ['rooms', 0, 'roomName'],
    'Your Room!'
);

echo '<h2>Get Room 2</h2>';
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => 2],
    ['rooms', 0, 'roomName'],
    'Another Room!'
);

echo '<h2>Get Room 10</h2>';
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => 10],
    ['exception', 'string'],
    'idNoExist'
);

echo '<h2>Get All Rooms</h2>';
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken],
    [
        ['rooms', 0, 'roomName'],
        ['rooms', 1, 'roomName'],
    ],
    [
        'Your Room!',
        'Another Room!'
    ]
);

echo '<h2>Create Room Without Name</h2>';
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    [],
    ['exception', 'string'],
    'nameRequired'
);

echo '<h2>Create Room With Short Name</h2>';
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    ['name' => 'a'],
    ['exception', 'string'],
    'nameMinimumLength'
);

echo '<h2>Create Room With Long Name</h2>';
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    ['name' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
    ['exception', 'string'],
    'nameMaximumLength'
);

echo '<h2>Create Room "Test Unit Room"</h2>';
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    ['name' => 'Test Unit Room'],
    ['response', 'insertId'],
    3
);

echo '<h2>Create Duplicate Room "Test Unit Room"</h2>';
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    ['name' => 'Test Unit Room'],
    ['exception', 'string'],
    'roomNameTaken'
);

echo '<h2>Get Room 3</h2>';
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => 3],
    ['rooms', 0, 'roomName'],
    'Test Unit Room'
);

echo '<h2>Get All Rooms, Test for 3</h2>';
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken],
    ['rooms', 2, 'roomName'],
    'Test Unit Room'
);

echo '<h2>Create Room "Hi Room %d" 96 Times</h2>';
for ($i = 4; $i < 100; $i++) {
    curlTestPOSTEquals(
        'api/room.php',
        ['access_token' => $accessToken, '_action' => 'create'],
        ['name' => 'Hi Room ' . $i],
        ['response', 'insertId'],
        $i
    );
}


// todo: create admin-only room


echo '<h1>New User and Permissions Tests</h1>';
echo '<h2>Create User without Birthdate</h2>';
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['userName' => 'testUnitUser', 'password' => 'password', 'email' => 'bob@example.com'],
    ['exception', 'string'],
    'birthdateRequired'
);

echo '<h2>Create User With Email bob@example</h2>';
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['userName' => 'testUnitUser', 'password' => 'password', 'email' => 'bob@example', 'birthdate' => time() - 14 * 365 * 3600],
    ['exception', 'string'],
    'emailInvalid'
);

echo '<h2>Create COPPA User</h2>';
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['userName' => 'testUnitUser', 'password' => 'password', 'email' => 'bob@example.com', 'birthdate' => time() - 12 * 365 * 3600],
    ['exception', 'string'],
    'ageMinimum'
);

// todo: email required

echo '<h2>Create Valid User, testUnitUser</h2>';
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['userName' => 'testUnitUser', 'password' => 'password', 'email' => 'bob@example.com', 'birthdate' => time() - 14 * 365 * 24 * 3600],
    ['sendUser', 'userName'],
    'testUnitUser'
);

echo '<h2>Create Duplicate User, testUnitUser</h2>';
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['userName' => 'testUnitUser', 'password' => 'password', 'email' => 'bob@example.com', 'birthdate' => time() - 14 * 365 * 24 * 3600],
    ['exception', 'string'],
    'userExists'
);

echo '<h2>Login as testUnitUser</h2>';
curlTestPOSTEquals(
    'validate.php',
    [],
    ['username' => 'testUnitUser', 'password' => 'password', 'client_id' => 'WebPro', 'grant_type' => 'password'],
    ['login', 'userData', 'userName'],
    'testUnitUser',
    function($data) {
        global $accessToken2;
        $accessToken2 = $data['login']['access_token'];
    }
);

echo '<h2>Send Message "Hi", Room 1</h2>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken2, 'roomId' => 1],
    ['message' => 'Hi'],
    ['sendMessage', 'censor'],
    []
);

// todo: unpriviledged user can post in default room
// todo: unpriviledged user can't post in admin-only room
// todo: admin kick unpriviledged user in default room
// todo: unpriviledged user can't post in default room
// todo: admin unkick unpriviledged user in default room
// todo: unpriviledged user can post in default room