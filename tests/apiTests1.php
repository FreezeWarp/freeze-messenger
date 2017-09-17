<html>
<head>
    <link rel="stylesheet" type="text/css" href="../webpro/client/css/absolution/jquery-ui-1.8.16.custom.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="../webpro/client/css/absolution/fim.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="../webpro/client/css/stylesv2.css" media="screen" />
    <style>
        body {
            padding: 0px 40px;
            background-color: white;
        }
        h1 {
            margin: 0px;
            padding: 5px;
        }

        .main {
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
            display: block;
            border: 1px solid black;
        }

        .ui-widget {
            font-size: 12px;
        }
        .ui-widget-content {
            padding: 5px;
        }

        abbr {
            outline-bottom: dotted 1px;
        }
        pre {
            display: inline;
        }

        /* General Tables */
        table {
            border: 2px solid black;
        }
        table td {
            padding-top: 5px;
            padding-bottom: 5px;
        }
        table tr {
            border-bottom: 1px solid black;
        }
        table {
            border-collapse: collapse;
        }
        table tr:last-child {
            border-bottom: none;
        }
        tbody tr:nth-child(2n) {
            background: #efefef !important;
        }
    </style>

</head>

<body>
<h1>API Unit Testing Suite</h1>
<p>This is a basic series of unit tests for the Messenger API. Tests should only be run on a fresh installation without development data. The installation user should be admin/admin.</p>

<div class="ui-widget">

<?php
//todo: change config. maybe custom WebPro API?

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '1');
require('../functions/curlRequest.php');
require('../config.php');

$host = $installUrl;

class ArrayPosition {
    public $pos = 0;

    public function __construct($pos) {
        $this->pos = $pos;
    }

    public function __toString() {
        return "ArrayPosition({$this->pos})";
    }
}

function curlTestCommon($input, $jsonIndexes, $expectedValues, $callback = null) {
    $good = true;
    $noset = false;
    $return = "";

    foreach ($jsonIndexes AS $indexNum => $jsonIndex) {
        $requestNarrow = $input;

        foreach ($jsonIndex AS $index) {
            if ($index instanceof ArrayPosition) {
                $index = array_keys($requestNarrow)[$index->pos];
            }

            if (!@isset($requestNarrow[$index])) {
                $noset = true;
                break;
            }

            $requestNarrow = $requestNarrow[$index];
        }

        if ($noset && $expectedValues[$indexNum] === null) {
        }
        elseif ($requestNarrow !== $expectedValues[$indexNum]) {
            $return .= red(implode(',', $jsonIndex) . ': Expected ' . htmlentities(print_r($expectedValues[$indexNum], true)) . ', found ' . ($noset ? '_unset_' : htmlentities(print_r($requestNarrow, true))) . '<br />');
            $good = false;
        }
    }


    if ($callback) {
        $callback($input);
    }

    if ($good) {
        echo '<td>' . green("Success") . '</td></tr>';
    }
    else {
        echo '<td> ' . $return . '</td></tr>';
    }
}

function formatOutput($output) {
    $newOutput = json_decode($output);

    if (json_last_error() == JSON_ERROR_NONE)
        return json_encode($newOutput, JSON_PRETTY_PRINT);
    else
        return $output;
}
function curlTestGETEquals($path, $params, $jsonIndex, $expectedValue, $callback = false) {
    global $host;
    echo '<td>' . $path . '?' . http_build_query($params) . '</td>';

    $request = (new curlRequest("{$host}{$path}", $params))->executeGET();

    echo '<td><textarea style="width: 400px; height: 150px; font-size: .6em;">' . formatOutput($request->response) . '</textarea></td>';

    curlTestCommon($request->getAsJson(), [$jsonIndex], [$expectedValue], $callback);
}

function curlTestGETEqualsMulti($path, $params, $jsonIndexes, $expectedValues, $callback = false) {
    global $host;
    echo '<td>' . $path . '?' . http_build_query($params) . '</td>';

    $request = (new curlRequest("{$host}{$path}", $params))->executeGET();

    echo '<td><textarea style="width: 400px; height: 150px; font-size: .6em;">' . formatOutput($request->response) . '</textarea></td>';

    curlTestCommon($request->getAsJson(), $jsonIndexes, $expectedValues, $callback);
}

function curlTestPOSTEquals($path, $params, $body, $jsonIndex, $expectedValue, $callback = false) {
    global $host;
    echo '<td>' . $path . '?' . http_build_query($params) . '<br />' . http_build_query($body) . '</td>';

    $request = (new curlRequest("{$host}{$path}", $params, $body))->executePOST();

    echo '<td><textarea style="width: 400px; height: 150px; font-size: .6em;">' . formatOutput($request->response) . '</textarea></td>';

    curlTestCommon($request->getAsJson(), [$jsonIndex], [$expectedValue], $callback);
}

function curlTestPOSTEqualsMulti($path, $params, $body, $jsonIndexes, $expectedValues, $callback = false) {
    global $host;
    echo '<td>' . $path . '?' . http_build_query($params) . '<br />' . http_build_query($body) . '</td>';

    $request = (new curlRequest("{$host}{$path}", $params, $body))->executePOST();

    echo '<td><textarea style="width: 400px; height: 150px; font-size: .6em;">' . formatOutput($request->response) . '</textarea></td>';

    curlTestCommon($request->getAsJson(), $jsonIndexes, $expectedValues, $callback);
}

function green($text) {
    return '<span style="color: #00ff00;">' . $text . '</span>';
}

function red($text) {
    return '<span style="color: #ff0000;">' . $text . '</span>';
}



$accessToken = false;
echo '<table>';
echo '<tr><td>Login</td>';
curlTestPOSTEquals(
    'validate.php',
    [],
    ['username' => 'admin', 'password' => 'admin', 'client_id' => 'WebPro', 'grant_type' => 'password'],
    ['login', 'userData', 'name'],
    'admin',
    function($data) {
        global $accessToken;
        $accessToken = $data['login']['access_token'];
    }
);


echo '<thead><tr class="ui-widget-header"><th colspan="4">Message Tests, Main User</th></tr></thead>';
echo '<tr><td>Get Messages, No Login</td>';
curlTestGETEquals(
    'api/message.php',
    [],
    ['exception', 'string'],
    'noLogin'
);

echo '<tr><td>Get Messages, No Room</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken],
    ['exception', 'string'],
    'roomIdRequired'
);

echo '<tr><td>Get (Invalid) Message 1,000,000, Room 1</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'id' => 1000000],
    ['exception', 'string'],
    'idNoExist'
);

echo '<tr><td>Get Message 10, (Invalid) Room 100,000</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 100000, 'id' => 1],
    ['exception', 'string'],
    'idNoExist'
);

// mutually exclusive
echo '<tr><td>Get Messages Between Message 0 and Message 10, Room 1</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 1, 'messageIdEnd' => 10],
    ['exception', 'string'],
    'messageIdEndMessageIdStartConflict'
);

// mutually exclusive
echo '<tr><td>Get Messages Starting Message 1 and Message Date 1, Room 1</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => 1, 'messageIdStart' => 1, 'messageDateMin' => 1],
    ['exception', 'string'],
    'messageDateMinMessageIdStartConflict'
);

echo '<tr><td>Undelete (Invalid) Message 1,000,000, Room 1</td>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'undelete', 'access_token' => $accessToken, 'roomId' => 1, 'id' => 1000000],
    [],
    ['exception', 'string'],
    'idNoExist'
);

/*echo '<tr><td>Send Message "Hi Bob %d" 98 Times, Room 1</td>';
for ($i = 2; $i < 100; $i++) {
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $accessToken, 'roomId' => 1],
        ['message' => 'Hi Bob ' . $i],
        ['message', 'censor'],
        []
    );
}*/

echo '<thead><tr class="ui-widget-header"><th colspan="4">Room Tests, Main User</th></tr></thead>';
echo '<tr><td>Get Room 1</td>';
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => 1],
    ['rooms', "room 1", 'name'],
    'Your Room!'
);

echo '<tr><td>Get Room 2</td>';
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => 2],
    ['rooms', "room 2", 'name'],
    'Another Room!'
);

echo '<tr><td>Get Room 100,000</td>';
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => 100000],
    ['exception', 'string'],
    'idNoExist'
);

echo '<tr><td>Get All Rooms, Testing for Rooms 1 and 2 (Only at Installation)</td>';
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken],
    [
        ['rooms', "room 1", 'name'],
        ['rooms', "room 2", 'name'],
    ],
    [
        'Your Room!',
        'Another Room!'
    ]
);

echo '<thead><tr class="ui-widget-header"><th colspan="4">Room and Message Tests in New Room</th></tr></thead>';
echo '<tr><td>Create Room Without Name</td>';
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    [],
    ['exception', 'string'],
    'nameRequired'
);

echo '<tr><td>Create Room With Short Name</td>';
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    ['name' => 'a'],
    ['exception', 'string'],
    'nameMinimumLength'
);

echo '<tr><td>Create Room With Long Name</td>';
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    ['name' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
    ['exception', 'string'],
    'nameMaximumLength'
);

$testRoomName = 'Test Unit ' . substr(uniqid(), -10, 10);
$testRoomId;
echo "<tr><td>Create Room '$testRoomName'</td>";
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    ['name' => $testRoomName, 'defaultPermissions' => ['view']],
    ['room', 'name'],
    $testRoomName,
    function($data) {
        global $testRoomId;
        $testRoomId = $data['room']['id'];
    }
);

echo "<tr><td>Create Duplicate Room '$testRoomName'</td>";
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    ['name' => $testRoomName],
    ['exception', 'string'],
    'nameTaken'
);

echo "<tr><td>Get Room '$testRoomName'</td>";
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => $testRoomId],
    ['rooms', "room $testRoomId", 'name'],
    $testRoomName
);

echo '<tr><td>Get Messages, Test for Empty</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId],
    ['messages'],
    []
);

$testMessage1Id;
echo '<tr><td>Send Message "Hi"</td>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken, 'roomId' => $testRoomId],
    ['message' => 'Hi'],
    ['message', 'censor'],
    [],
    function($data) {
        global $testMessage1Id;
        $testMessage1Id = $data['message']['id'];
    }
);

echo '<tr><td>Get Most Recent Messages</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId],
    ['messages', 0, 'text'],
    'Hi'
);

echo '<tr><td>Get Messages Since Message 0</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId, 'messageIdStart' => 1],
    ['messages', 0, 'text'],
    'Hi'
);

echo '<tr><td>Get Messages Since Message 1</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId, 'messageIdStart' => 2],
    ['messages'],
    []
);

$testMessage2Id;
echo '<tr><td>Send Message "Crazy"</td>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken, 'roomId' => $testRoomId],
    ['message' => 'Crazy'],
    ['message', 'censor'],
    [],
    function($data) {
        global $testMessage2Id;
        $testMessage2Id = $data['message']['id'];
    }
);

echo '<tr><td>Get Messages Since Message 0</td>';
curlTestGETEqualsMulti(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId, 'messageIdStart' => 1],
    [
        ['messages', 0, 'text'],
        ['messages', 1, 'text']
    ],
    [
        'Hi',
        'Crazy'
    ]
);

echo '<tr><td>Get Messages Since Message 1 (Fresh Install Only)</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId, 'messageIdStart' => 2],
    ['messages', 0, 'text'],
    'Crazy'
);

echo '<tr><td>Get Messages Since Message 2 (Fresh Install Only)</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId, 'messageIdStart' => 3],
    ['messages'],
    []
);

echo '<tr><td>Edit Message "Crazy"</td>';
curlTestPOSTEquals(
    'api/message.php',
    ['access_token' => $accessToken, '_action' => 'edit', 'roomId' => $testRoomId, 'id' => $testMessage2Id],
    ['message' => 'Crazy For You'],
    ['message', 'censor'],
    []
);

echo '<tr><td>Get Edited Message</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId, 'id' => $testMessage2Id],
    ['messages', 0, 'text'],
    'Crazy For You'
);

echo '<tr><td>Get Messages Since Message 0</td>';
curlTestGETEqualsMulti(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId, 'messageIdStart' => 1],
    [
        ['messages', 0, 'text'],
        ['messages', 1, 'text']
    ],
    [
        'Hi',
        'Crazy For You'
    ]
);

echo '<tr><td>Delete Message "Hi"</td>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'delete', 'access_token' => $accessToken, 'roomId' => $testRoomId, 'id' => $testMessage1Id],
    [],
    ['message', 'id'],
    $testMessage1Id
);

echo '<tr><td>Get Messages Since Message 0</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId, 'messageIdStart' => 0],
    ['messages', 0, 'text'],
    'Crazy For You'
);

echo '<tr><td>Get Even Deleted Messages Since Message 0</td>';
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId, 'messageIdStart' => 0, 'archive' => true, 'showDeleted' => true],
    ['messages', 0, 'text'],
    'Hi'
);

echo '<tr><td>Undelete Message "Hi"</td>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'undelete', 'access_token' => $accessToken, 'roomId' => $testRoomId, 'id' => $testMessage1Id],
    [],
    ['message', 'id'],
    $testMessage1Id
);

// we need to use archive to get undeleted messages.
echo '<tr><td>Get Messages Since Message 0</td>';
curlTestGETEqualsMulti(
    'api/message.php',
    ['access_token' => $accessToken, 'roomId' => $testRoomId, 'archive' => true, 'messageIdStart' => 0],
    [
        ['messages', 0, 'text'],
        ['messages', 1, 'text']
    ],
    [
        'Hi',
        'Crazy For You'
    ]
);

/*echo '<tr><td>Get All Rooms, Test for 3</td>';
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken],
    ['rooms', 3, 'name'],
    $testRoomName
);*/

/*echo '<tr><td>Create Room "Hi Room %d" 96 Times</td>';
for ($i = 4; $i < 100; $i++) {
    curlTestPOSTEquals(
        'api/room.php',
        ['access_token' => $accessToken, '_action' => 'create'],
        ['name' => 'Hi Room ' . $i],
        ['response', 'insertId'],
        $i
    );
}*/


// todo: create admin-only room


echo '<thead><tr class="ui-widget-header"><th colspan="4">New User and Permissions Tests</th></tr></thead>';

echo '<tr><td>Get User 100,000</td>';
curlTestGETEquals(
    'api/user.php',
    ['access_token' => $accessToken, 'id' => 100000],
    ['exception', 'string'],
    'idNoExist'
);


$testUnitUserId;
$testUnitUserToken;
$testUnitUserName = "testUnitUser" . uniqid();

echo '<tr><td>Create User without birthDate</td>';
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['name' => $testUnitUserName, 'password' => 'password', 'email' => 'bob@example.com'],
    ['exception', 'string'],
    'birthDateRequired'
);

echo '<tr><td>Create User With Email bob@example</td>';
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['name' => $testUnitUserName, 'password' => 'password', 'email' => 'bob@example', 'birthDate' => time() - 14 * 365 * 3600],
    ['exception', 'string'],
    'emailInvalid'
);

echo '<tr><td>Create COPPA User</td>';
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['name' => $testUnitUserName, 'password' => 'password', 'email' => 'bob@example.com', 'birthDate' => time() - 12 * 365 * 3600],
    ['exception', 'string'],
    'ageMinimum'
);

// todo: email required

echo "<tr><td>Create Valid User, $testUnitUserName</td>";
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['name' => $testUnitUserName, 'password' => 'password', 'email' => 'bob@example.com', 'birthDate' => time() - 14 * 365 * 24 * 3600],
    ['user', 'name'],
    $testUnitUserName,
    function($data) {
        global $testUnitUserId;
        $testUnitUserId = $data['user']['id'];
    }
);

echo "<tr><td>Create Duplicate User, $testUnitUserName</td>";
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['name' => $testUnitUserName, 'password' => 'password', 'email' => 'bob@example.com', 'birthDate' => time() - 14 * 365 * 24 * 3600],
    ['exception', 'string'],
    'nameTaken'
);

echo "<tr><td>Get $testUnitUserName as admin</td>";
curlTestGETEqualsMulti(
    'api/user.php',
    ['access_token' => $accessToken, 'id' => $testUnitUserId],
    [
        ['users', $testUnitUserId, 'name'],
        ['users', $testUnitUserId, 'permissions', 'view'],
        ['users', $testUnitUserId, 'permissions', 'post'],
        ['users', $testUnitUserId, 'permissions', 'changeTopic'],
        ['users', $testUnitUserId, 'permissions', 'createRooms'],
        ['users', $testUnitUserId, 'permissions', 'privateRoomsFriends'],
        ['users', $testUnitUserId, 'permissions', 'privateRoomsAll'],
        ['users', $testUnitUserId, 'permissions', 'roomsOnline'],
        ['users', $testUnitUserId, 'permissions', 'postCounts'],
        ['users', $testUnitUserId, 'permissions', 'editOwnPosts'],
        ['users', $testUnitUserId, 'permissions', 'deleteOwnPosts'],
    ],
    [
        $testUnitUserName,
        true,
        true,
        false,
        false,
        true,
        false,
        true,
        true,
        true,
        true,
    ]
);

echo "<tr><td>Get Room '$testRoomName', Test Permissions as 'admin'</td>";
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => $testRoomId],
    [
        ['rooms', "room $testRoomId", 'permissions', 'view'],
        ['rooms', "room $testRoomId", 'permissions', 'post'],
        ['rooms', "room $testRoomId", 'permissions', 'changeTopic'],
        ['rooms', "room $testRoomId", 'permissions', 'properties'],
        ['rooms', "room $testRoomId", 'defaultPermissions', 'view'],
        ['rooms', "room $testRoomId", 'defaultPermissions', 'post'],
        ['rooms', "room $testRoomId", 'defaultPermissions', 'changeTopic'],
        ['rooms', "room $testRoomId", 'defaultPermissions', 'properties'],
    ],
    [
        true,
        true,
        true,
        true,
        true,
        false,
        false,
        false
    ]
);

echo '<tr><td>Login as testUnitUser</td>';
curlTestPOSTEquals(
    'validate.php',
    [],
    ['username' => $testUnitUserName, 'password' => 'password', 'client_id' => 'WebPro', 'grant_type' => 'password'],
    ['login', 'userData', 'name'],
    $testUnitUserName,
    function($data) {
        global $accessToken2;
        $accessToken2 = $data['login']['access_token'];
    }
);

echo '<tr><td>Send Message "Hi", Room 1</td>';
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken2, 'roomId' => 1],
    ['message' => 'Hi'],
    ['message', 'censor'],
    []
);

echo "<tr><td>Get Room '$testRoomName', Test Permissions</td>";
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken2, 'id' => $testRoomId],
    [
        ['rooms', "room $testRoomId", 'permissions', 'view'],
        ['rooms', "room $testRoomId", 'permissions', 'post'],
        ['rooms', "room $testRoomId", 'permissions', 'changeTopic'],
        ['rooms', "room $testRoomId", 'permissions', 'properties']
    ],
    [
        true,
        false,
        false,
        false
    ]
);

echo "<tr><td>Get Messages in $testRoomName</td>";
curlTestGETEqualsMulti(
    'api/message.php',
    ['access_token' => $accessToken2, 'roomId' => $testRoomId, 'archive' => true, 'messageIdStart' => 0],
    [
        ['messages', 0, 'text'],
        ['messages', 1, 'text']
    ],
    [
        'Hi',
        'Crazy For You'
    ]
);

echo "<tr><td>Send Message 'Hi' in $testRoomName</td>";
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken2, 'roomId' => $testRoomId],
    ['message' => 'Hi'],
    ['exception', 'string'],
    'noPerm'
);

echo "<tr><td>Edit Room '$testRoomName' to Allow Posting By All</td>";
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoomId],
    ['defaultPermissions' => ['view', 'post']],
    ['room', 'id'],
    (string) $testRoomId
);

echo "<tr><td>Get Room '$testRoomName', Test Permissions</td>";
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken2, 'id' => $testRoomId],
    [
        ['rooms', "room $testRoomId", 'permissions', 'view'],
        ['rooms', "room $testRoomId", 'permissions', 'post'],
        ['rooms', "room $testRoomId", 'permissions', 'changeTopic'],
        ['rooms', "room $testRoomId", 'permissions', 'properties']
    ],
    [
        true,
        true,
        false,
        false
    ]
);

echo "<tr><td>Send Message 'Hi' in $testRoomName</td>";
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken2, 'roomId' => $testRoomId],
    ['message' => 'Hi'],
    ['message', 'censor'],
    []
);

$testRoom2Name = 'Test Unit ' . substr(uniqid(), -10, 10);
$testRoom2Id;
echo "<tr><td>Create Room '$testRoom2Name'</td>";
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'create'],
    ['name' => $testRoom2Name, 'defaultPermissions' => ['']],
    ['room', 'name'],
    $testRoom2Name,
    function($data) {
        global $testRoom2Id;
        $testRoom2Id = $data['room']['id'];
    }
);

echo "<tr><td>Send Message 'Huh' in $testRoom2Name as admin</td>";
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken, 'roomId' => $testRoom2Id],
    ['message' => 'Huh'],
    ['message', 'censor'],
    []
);

echo "<tr><td>Get Room '$testRoom2Name' as '$testUnitUserName', Test for idNoExist</td>";
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken2, 'id' => $testRoom2Id],
    ['exception', 'string'],
    'idNoExist'
);

echo "<tr><td>Get Messages in '$testRoom2Name' as '$testUnitUserName', Test for idNoExist</td>";
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken2, 'roomId' => $testRoom2Id, 'archive' => true],
    ['exception', 'string'],
    'idNoExist'
);

echo "<tr><td>Send Message 'Hi' in '$testRoom2Name' as '$testUnitUserName', Test for idNoExist</td>";
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken2, 'roomId' => $testRoom2Id],
    ['message' => 'Hi'],
    ['exception', 'string'],
    'idNoExist'
);

echo "<tr><td>Edit Room '$testRoom2Name' to Allow Viewing by '$testUnitUserName'</td>";
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoom2Id],
    ['userPermissions' => "{\"+{$testUnitUserId}\" : [\"view\"]}"],
    ['room', 'id'],
    (string) $testRoom2Id
);

echo "<tr><td>Get Messages in '$testRoom2Name'</td>";
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken2, 'roomId' => $testRoom2Id, 'archive' => true],
    ['messages', 0, 'text'],
    'Huh'
);

echo "<tr><td>Send Message 'Hi' in '$testRoom2Name'</td>";
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken2, 'roomId' => $testRoom2Id],
    ['message' => 'Hi'],
    ['exception', 'string'],
    'noPerm'
);

echo "<tr><td>Get All Rooms as '$testUnitUserName', Testing for Rooms '$testRoomName', '$testRoom2Name' (Only At Installation)</td>";
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken2],
    [
        ['rooms', "room $testRoomId", 'name'],
        ['rooms', "room $testRoom2Id", 'name'],
    ],
    [
        $testRoomName,
        $testRoom2Name
    ]
);

sleep(1); // Logging may prevent saves more than every second. (This is a TODO on its own terms.)

echo "<tr><td>Edit Room '$testRoom2Name' to Allow Posting by '$testUnitUserName'</td>";
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoom2Id],
    ['userPermissions' => "{\"+{$testUnitUserId}\" : [\"post\"]}"],
    ['room', 'id'],
    (string) $testRoom2Id
);

echo "<tr><td>Get Room '$testRoom2Name', testing for changes</td>";
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => $testRoom2Id],
    [
        ['rooms', "room $testRoom2Id", 'name'],
        ['rooms', "room $testRoom2Id", 'userPermissions', $testUnitUserId, 'view'],
        ['rooms', "room $testRoom2Id", 'userPermissions', $testUnitUserId, 'post'],
        ['rooms', "room $testRoom2Id", 'userPermissions', $testUnitUserId, 'moderate'],
        ['rooms', "room $testRoom2Id", 'userPermissions', $testUnitUserId, 'grant'],
    ],
    [
        $testRoom2Name,
        true,
        true,
        false,
        false
    ]
);

echo "<tr><td>Get Messages in '$testRoom2Name'</td>";
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken2, 'roomId' => $testRoom2Id, 'archive' => true],
    ['messages', 0, 'text'],
    'Huh'
);

echo "<tr><td>Send Message '小さい問題' in '$testRoom2Name'</td>";
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken2, 'roomId' => $testRoom2Id],
    ['message' => '小さい問題'],
    ['message', 'censor'],
    []
);

echo "<tr><td>Get Messages in '$testRoom2Name'</td>";
curlTestGETEquals(
    'api/message.php',
    ['access_token' => $accessToken2, 'roomId' => $testRoom2Id, 'archive' => true],
    ['messages', 0, 'text'],
    '小さい問題'
);

echo '<thead><tr class="ui-widget-header"><th colspan="4">Another New User and Activity Tests</th></tr></thead>';

$testUnit2UserId;
$testUnit2UserToken;
$testUnit2UserName = "testUnitUser" . uniqid();
$accessToken3 = '';

echo "<tr><td>Create Valid User, '$testUnit2UserName'</td>";
curlTestPOSTEquals(
    'api/user.php',
    ['_action' => 'create'],
    ['name' => $testUnit2UserName, 'password' => '小さい問題', 'email' => 'billy@example.com', 'birthDate' => time() - 16 * 365 * 24 * 3600],
    ['user', 'name'],
    $testUnit2UserName,
    function($data) {
        global $testUnit2UserId;
        $testUnit2UserId = $data['user']['id'];
    }
);

echo "<tr><td>Login as '$testUnit2UserName'</td>";
curlTestPOSTEquals(
    'validate.php',
    [],
    ['username' => $testUnit2UserName, 'password' => '小さい問題', 'client_id' => 'WebPro', 'grant_type' => 'password'],
    ['login', 'userData', 'name'],
    $testUnit2UserName,
    function($data) {
        global $accessToken3;
        $accessToken3 = $data['login']['access_token'];
    }
);

sleep(1); // Logging may prevent saves more than every second. (This is a TODO on its own terms.)

echo "<tr><td>Edit Room '$testRoom2Name' to Allow Posting by '$testUnit2UserName'</td>";
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoom2Id],
    ['userPermissions' => "{\"+{$testUnit2UserId}\" : [\"view\", \"post\"]}"],
    ['room', 'id'],
    (string) $testRoom2Id
);

echo "<tr><td>Get Rooms '$testUnit2UserName' is Active In</td>";
curlTestGETEqualsMulti(
    'api/userStatus.php',
    ['access_token' => $accessToken2, 'userIds' => [$testUnit2UserId]],
    [
        ['users'],
        ['users', 'user 1'],
        ['users', 'user ' . $testUnit2UserId, 'rooms', 'room 1', 'name'],
        ['users', 'user ' . $testUnit2UserId, 'rooms', 'room ' . $testRoomId, 'name'],
        ['users', 'user ' . $testUnit2UserId, 'rooms', 'room ' . $testRoom2Id, 'name'],
    ],
    [
        [],
        null,
        null,
        null,
        null,
    ]
);

echo "<tr><td>Get Active Users in '$testRoom2Name'</td>";
curlTestGETEqualsMulti(
    'api/userStatus.php',
    ['access_token' => $accessToken2, 'roomIds' => [$testRoom2Id]],
    [
        ['users', 'user 1', 'userData', 'name'],
        ['users', 'user 1', 'rooms'],
        ['users', 'user ' . $testUnitUserId, 'userData', 'name'],
        ['users', 'user ' . $testUnit2UserId, 'userData', 'name'],
    ],
    [
        'admin',
        ['room ' . $testRoom2Id => ['id' => $testRoom2Id, 'name' => $testRoom2Name, 'status' => 'available', 'typing' => false]],
        $testUnitUserName,
        null
    ]
);

echo "<tr><td>Send Message 'バカ' in '$testRoom2Name' as '$testUnit2UserName'</td>";
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken3, 'roomId' => $testRoom2Id],
    ['message' => 'バカ'],
    ['message', 'censor'],
    []
);

echo "<tr><td>Get Active Users in '$testRoom2Name'</td>";
curlTestGETEqualsMulti(
    'api/userStatus.php',
    ['access_token' => $accessToken2, 'roomIds' => [$testRoom2Id]],
    [
        ['users', 'user 1', 'userData', 'name'],
        ['users', 'user 1', 'rooms'],
        ['users', 'user ' . $testUnitUserId, 'userData', 'name'],
        ['users', 'user ' . $testUnit2UserId, 'userData', 'name'],
    ],
    [
        'admin',
        ['room ' . $testRoom2Id => ['id' => $testRoom2Id, 'name' => $testRoom2Name, 'status' => 'available', 'typing' => false]],
        $testUnitUserName,
        $testUnit2UserName
    ]
);

echo "<tr><td>Get Active Users in '$testRoomName'</td>";
curlTestGETEqualsMulti(
    'api/userStatus.php',
    ['access_token' => $accessToken2, 'roomIds' => [$testRoomId]],
    [
        ['users', 'user 1', 'userData', 'name'],
        ['users', 'user 1', 'rooms'],
        ['users', 'user ' . $testUnitUserId, 'userData', 'name'],
        ['users', 'user ' . $testUnit2UserId, 'userData', 'name'],
    ],
    [
        'admin',
        ['room ' . $testRoomId => ['id' => $testRoomId, 'name' => $testRoomName, 'status' => 'available', 'typing' => false]],
        $testUnitUserName,
        null
    ]
);

echo "<tr><td>Ping '$testRoomName' as '$testUnit2UserName', typing = true</td>";
curlTestPOSTEquals(
    'api/userStatus.php',
    ['_action' => 'edit', 'access_token' => $accessToken3, 'roomIds' => [$testRoomId]],
    ['typing' => true],
    ['response'],
    []
);

echo "<tr><td>Get Active Users in '$testRoomName'</td>";
curlTestGETEqualsMulti(
    'api/userStatus.php',
    ['access_token' => $accessToken2, 'roomIds' => [$testRoomId]],
    [
        ['users', 'user 1', 'userData', 'name'],
        ['users', 'user 1', 'rooms'],
        ['users', 'user ' . $testUnitUserId, 'userData', 'name'],
        ['users', 'user ' . $testUnit2UserId, 'userData', 'name'],
        ['users', 'user ' . $testUnit2UserId, 'rooms'],
    ],
    [
        'admin',
        ['room ' . $testRoomId => ['id' => $testRoomId, 'name' => $testRoomName, 'status' => 'available', 'typing' => false]],
        $testUnitUserName,
        $testUnit2UserName,
        ['room ' . $testRoomId => ['id' => $testRoomId, 'name' => $testRoomName, 'status' => 'available', 'typing' => true]],
    ]
);

echo "<tr><td>Get Rooms '$testUnit2UserName' is Active In</td>";
curlTestGETEqualsMulti(
    'api/userStatus.php',
    ['access_token' => $accessToken2, 'userIds' => [$testUnit2UserId]],
    [
        ['users', 'user 1'],
        ['users', 'user ' . $testUnit2UserId, 'userData', 'name'],
        ['users', 'user ' . $testUnit2UserId, 'rooms', 'room 1', 'name'],
        ['users', 'user ' . $testUnit2UserId, 'rooms', 'room ' . $testRoomId, 'name'],
        ['users', 'user ' . $testUnit2UserId, 'rooms', 'room ' . $testRoom2Id, 'name'],
    ],
    [
        null,
        $testUnit2UserName,
        null,//'Your Room!',
        $testRoomName,
        $testRoom2Name,
    ]
);


echo '<thead><tr class="ui-widget-header"><th colspan="4">Fav Rooms Test</th></th></tr></thead>';
echo "<tr><td>'$testUnitUserName' Favs Room '$testRoomName'</td>";
curlTestPOSTEquals(
    'api/userOptions.php',
    ['access_token' => $accessToken2, '_action' => 'create'],
    ['favRooms' => [$testRoomId]],
    ['editUserOptions'],
    []
);

echo "<tr><td>Get Rooms, Checking for '$testRoomName' at Top</td>";
curlTestGETEquals(
    'api/room.php',
    ['access_token' => $accessToken2],
    ['rooms', new ArrayPosition(0), 'name'],
    $testRoomName
);

echo "<tr><td>'$testUnitUserName' Replaces Fav Rooms With 'Another Room!', '$testRoom2Name'</td>";
curlTestPOSTEquals(
    'api/userOptions.php',
    ['access_token' => $accessToken2, '_action' => 'edit'],
    ['favRooms' => [2, $testRoom2Id]],
    ['editUserOptions'],
    []
);

echo "<tr><td>Get Rooms, Checking for 'Another Room!', '$testRoom2Name', 'Your Room!' at Top</td>";
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken2],
    [
        ['rooms', new ArrayPosition(0), 'name'],
        ['rooms', new ArrayPosition(1), 'name'],
        ['rooms', new ArrayPosition(2), 'name'],
    ],
    [
        'Another Room!',
        $testRoom2Name,
        'Your Room!'
    ]
);

echo "<tr><td>'$testUnitUserName' Unfavs Room '$testRoom2Name'</td>";
curlTestPOSTEquals(
    'api/userOptions.php',
    ['access_token' => $accessToken2, '_action' => 'delete'],
    ['favRooms' => [$testRoom2Id]],
    ['editUserOptions'],
    []
);

echo "<tr><td>Get Rooms, Checking for 'Another Room!', 'Your Room!' at Top</td>";
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken2],
    [
        ['rooms', new ArrayPosition(0), 'name'],
        ['rooms', new ArrayPosition(1), 'name'],
    ],
    [
        'Another Room!',
        'Your Room!'
    ]
);

echo '<thead><tr class="ui-widget-header"><th colspan="4">Hidden Rooms Test</th></tr></thead>';

echo "<tr><td>Edit Room '$testRoom2Name' to Make Hidden</td>";
curlTestPOSTEquals(
    'api/room.php',
    ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoom2Id],
    ['hidden' => true],
    ['room', 'id'],
    (string) $testRoom2Id
);

// TODO: moderator can't set hidden, official flags

echo "<tr><td>Get Room '$testRoom2Name', testing for changes</td>";
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken, 'id' => $testRoom2Id],
    [
        ['rooms', "room $testRoom2Id", 'name'],
        ['rooms', "room $testRoom2Id", 'hidden'],
    ],
    [
        $testRoom2Name,
        true,
    ]
);

echo "<tr><td>Get All Rooms, Testing for Rooms '$testRoomName', '$testRoom2Name' (Only At Installation)</td>";
curlTestGETEqualsMulti(
    'api/room.php',
    ['access_token' => $accessToken],
    [
        ['rooms', "room $testRoomId", 'name'],
        ['rooms', "room $testRoom2Id"],
    ],
    [
        $testRoomName,
        null
    ]
);

// TODO: don't include hidden rooms in active users

echo '<thead><tr class="ui-widget-header"><th colspan="4">Watch Rooms Test</th></tr></thead>';

echo "<tr><td>Get Unread Messages for '$testUnitUserName'</td>";
curlTestGETEquals(
    'api/unreadMessages.php',
    ['access_token' => $accessToken2],
    ['unreadMessages'],
    []
);

echo "<tr><td>'$testUnitUserName' Watches Room '$testRoomName'</td>";
curlTestPOSTEquals(
    'api/userOptions.php',
    ['access_token' => $accessToken2, '_action' => 'create'],
    ['watchRooms' => [$testRoomId]],
    ['editUserOptions'],
    []
);

echo "<tr><td>Get Unread Messages for '$testUnitUserName'</td>";
curlTestGETEquals(
    'api/unreadMessages.php',
    ['access_token' => $accessToken2],
    ['unreadMessages'],
    []
);

echo "<tr><td>Send Message 'Hello' in '$testRoomName' as '$testUnit2UserName'</td>";
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken3, 'roomId' => $testRoomId],
    ['message' => 'バカ'],
    ['message', 'censor'],
    []
);

echo "<tr><td>Send Message 'Goodbye' in '$testRoom2Name' as '$testUnit2UserName'</td>";
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken3, 'roomId' => $testRoom2Id],
    ['message' => 'バカ'],
    ['message', 'censor'],
    []
);

for ($i = 0; $i < 2; $i++) {
    echo "<tr><td>Get Unread Messages for '$testUnitUserName'</td>";
    curlTestGETEqualsMulti(
        'api/unreadMessages.php',
        ['access_token' => $accessToken2],
        [
            ['unreadMessages', 0, 'senderName'],
            ['unreadMessages', 0, 'roomName'],
            ['unreadMessages', 1]
        ],
        [
            $testUnit2UserName,
            $testRoomName,
            null
        ]
    );
}

echo "<tr><td>Get Unread Messages for '$testUnit2UserName'</td>";
curlTestGETEquals(
    'api/unreadMessages.php',
    ['access_token' => $accessToken3],
    ['unreadMessages'],
    []
);

echo "<tr><td>'$testUnitUserName' Marks Message Read</td>";
// todo: make delete
curlTestPOSTEquals(
    'api/markMessageRead.php',
    ['access_token' => $accessToken2],
    ['roomId' => $testRoomId],
    ['markMessageRead'],
    []
);

echo "<tr><td>Get Unread Messages for '$testUnitUserName'</td>";
curlTestGETEquals(
    'api/unreadMessages.php',
    ['access_token' => $accessToken2],
    ['unreadMessages'],
    []
);

echo "<tr><td>'$testUnitUserName' Stops Watching Room '$testRoomName'</td>";
curlTestPOSTEquals(
    'api/userOptions.php',
    ['access_token' => $accessToken2, '_action' => 'edit'],
    ['watchRooms' => ['']],
    ['editUserOptions'],
    []
);

echo "<tr><td>Send Message 'Goodbye' in '$testRoomName' as '$testUnit2UserName'</td>";
curlTestPOSTEquals(
    'api/message.php',
    ['_action' => 'create', 'access_token' => $accessToken3, 'roomId' => $testRoomId],
    ['message' => 'バカ'],
    ['message', 'censor'],
    []
);

echo "<tr><td>Get Unread Messages for '$testUnitUserName'</td>";
curlTestGETEquals(
    'api/unreadMessages.php',
    ['access_token' => $accessToken2],
    ['unreadMessages'],
    []
);

echo '</table>';

// todo: admin kick unpriviledged user in default room
// todo: unpriviledged user can't post in default room
// todo: admin unkick unpriviledged user in default room
// todo: unpriviledged user can post in default room

// todo: message/room text search

// todo: user message formatting
// todo: user friends/ignore list and private messages
// todo: file uploads and enumerations
?>
</div>
</body>
</html>