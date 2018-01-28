<html>
<head>

    <link
            href="https://maxcdn.bootstrapcdn.com/bootswatch/4.0.0-beta.2/simplex/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-ofc00ja/z8wrU97EAHQRb4i4wsa/Zgr9JZll2R3KW33iqhFSfmVz/6xuWFx5pjcn"
            crossorigin="anonymous">

    <style>
        table td {
            word-wrap: break-word;
        }
    </style>

</head>

<body style="padding: 10px;">
<h1>API Unit Testing Suite</h1>
<p>This is a basic series of unit tests for the Messenger API. Tests should only be run on a fresh installation with development data installed.</p>
<p>At present, caches should also be disabled for unit testing. In the future, improved cache invalidation will partially obviate this issue.</p>

<div style="max-width: 100%;">

<?php
//todo: change config. maybe custom WebPro API?

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '1');
$ignoreLogin = true;
require('../global.php');

$host = $installUrl;

$adminUsername = $_GET['adminUsername'] ?? 'admin';
$adminPassword = $_GET['adminPassword'] ?? 'admin';

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
        elseif ($requestNarrow != $expectedValues[$indexNum]) {
            $return .= red(implode(',', $jsonIndex) . ': Expected ' . htmlentities(var_export($expectedValues[$indexNum], true)) . ', found ' . ($noset ? '_unset_' : htmlentities(var_export($requestNarrow, true))) . '<br />');
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

    $request = (new Http\CurlRequest("{$host}{$path}", $params));
    $request->executeGET();

    echo '<td><textarea style="width: 100%; height: 150px; font-size: .6em;">' . formatOutput($request->response) . '</textarea></td>';

    curlTestCommon($request->getAsJson(), [$jsonIndex], [$expectedValue], $callback);
}

function curlTestGETEqualsMulti($path, $params, $jsonIndexes, $expectedValues, $callback = false) {
    global $host;
    echo '<td>' . $path . '?' . http_build_query($params) . '</td>';

    $request = (new Http\CurlRequest("{$host}{$path}", $params));
    $request->executeGET();

    echo '<td><textarea style="width: 100%; height: 150px; font-size: .6em;">' . formatOutput($request->response) . '</textarea></td>';

    curlTestCommon($request->getAsJson(), $jsonIndexes, $expectedValues, $callback);
}

function curlTestPOSTEquals($path, $params, $body, $jsonIndex, $expectedValue, $callback = false) {
    global $host;
    echo '<td>' . $path . '?' . http_build_query($params) . '<br />' . http_build_query($body) . '</td>';

    $request = (new Http\CurlRequest("{$host}{$path}", $params, $body));
    $request->executePOST();

    echo '<td><textarea style="width: 100%; height: 150px; font-size: .6em;">' . formatOutput($request->response) . '</textarea></td>';

    curlTestCommon($request->getAsJson(), [$jsonIndex], [$expectedValue], $callback);
}

function curlTestPOSTEqualsMulti($path, $params, $body, $jsonIndexes, $expectedValues, $callback = false) {
    global $host;
    echo '<td>' . $path . '?' . http_build_query($params) . '<br />' . http_build_query($body) . '</td>';

    $request = (new Http\CurlRequest("{$host}{$path}", $params, $body));
    $request->executePOST();

    echo '<td><textarea style="width: 100%; height: 150px; font-size: .6em;">' . formatOutput($request->response) . '</textarea></td>';

    curlTestCommon($request->getAsJson(), $jsonIndexes, $expectedValues, $callback);
}

function green($text) {
    return '<span style="color: #00ff00;">' . $text . '</span>';
}

function red($text) {
    return '<span style="color: #ff0000;">' . $text . '</span>';
}



$accessToken = false;
$serverStatus = false;

echo '<table class="table table-bordered table-striped" style="table-layout: fixed">
    <thead class="thead-dark">
        <th width="14%">Test Description</th>
        <th width="27%">Test Query</th>
        <th width="31%">Test Results</th>
        <th width="18%">Test Summary</th>
    </thead>';


echo '<tr><td>Login</td>';
curlTestGETEquals(
    'api/serverStatus.php',
    [],
    ['serverStatus', 'fim_version'],
    '1.0-nightly',
    function($data) {
        global $serverStatus;
        $serverStatus = $data['serverStatus'];
    }
);

echo '<tr><td>Login</td>';
curlTestPOSTEquals(
    'validate.php',
    [],
    ['username' => $adminUsername, 'password' => $adminPassword, 'client_id' => 'WebPro', 'grant_type' => 'password'],
    ['login', 'userData', 'name'],
    $adminUsername,
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
    ['name' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
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

echo "<tr><td>Get Room '$testRoomName', Test Permissions as '$adminUsername'</td>";
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

echo '<tr><td>Get Messages Since Message 1 (Fresh Install Only)</td>';
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



if ($serverStatus['registrationPolicies']['registrationEnabled']) {
    $testUserId;
    $testUserToken;
    $testUserName = "testUnitUser" . uniqid();

    if ($serverStatus['registrationPolicies']['ageRequired']) {
        echo '<tr><td>Create User without birthDate</td>';
        curlTestPOSTEquals(
            'api/user.php',
            ['_action' => 'create'],
            ['name' => $testUserName, 'password' => 'password', 'email' => 'bob@example.com'],
            ['exception', 'string'],
            'birthDateRequired'
        );
    }

    echo '<tr><td>Create User With Email bob@example</td>';
    curlTestPOSTEquals(
        'api/user.php',
        ['_action' => 'create'],
        ['name' => $testUserName, 'password' => 'password', 'email' => 'bob@example', 'birthDate' => time() - 14 * 365 * 24 * 3600],
        ['exception', 'string'],
        'emailInvalid'
    );

    echo '<tr><td>Create User With Short Password</td>';
    curlTestPOSTEquals(
        'api/user.php',
        ['_action' => 'create'],
        ['name' => $testUserName, 'password' => 'pa', 'email' => 'bob@example.com', 'birthDate' => time() - 14 * 365 * 24 * 3600],
        ['exception', 'string'],
        'passwordMinimumLength'
    );

    echo '<tr><td>Create COPPA User</td>';
    curlTestPOSTEquals(
        'api/user.php',
        ['_action' => 'create'],
        ['name' => $testUserName, 'password' => 'password', 'email' => 'bob@example.com', 'birthDate' => time() - 12 * 365 * 24 * 3600],
        ['exception', 'string'],
        'ageMinimum'
    );

    // todo: email required

    echo "<tr><td>Create Valid User, $testUserName</td>";
    curlTestPOSTEquals(
        'api/user.php',
        ['_action' => 'create'],
        ['name' => $testUserName, 'password' => 'password', 'email' => 'bob@example.com', 'birthDate' => time() - 14 * 365 * 24 * 3600],
        ['user', 'name'],
        $testUserName,
        function ($data) {
            global $testUserId;
            $testUserId = $data['user']['id'];
        }
    );

    echo "<tr><td>Create Duplicate User, $testUserName</td>";
    curlTestPOSTEquals(
        'api/user.php',
        ['_action' => 'create'],
        ['name' => $testUserName, 'password' => 'password', 'email' => 'bob@example.com', 'birthDate' => time() - 14 * 365 * 24 * 3600],
        ['exception', 'string'],
        'nameTaken'
    );

    echo "<tr><td>Get $testUserName as admin</td>";
    curlTestGETEqualsMulti(
        'api/user.php',
        ['access_token' => $accessToken, 'id' => $testUserId],
        [
            ['users', $testUserId, 'name'],
            ['users', $testUserId, 'permissions', 'view'],
            ['users', $testUserId, 'permissions', 'post'],
            ['users', $testUserId, 'permissions', 'changeTopic'],
            ['users', $testUserId, 'permissions', 'createRooms'],
            ['users', $testUserId, 'permissions', 'privateRooms'],
            ['users', $testUserId, 'permissions', 'editOwnPosts'],
            ['users', $testUserId, 'permissions', 'deleteOwnPosts'],
        ],
        [
            $testUserName,
            true,
            true,
            false,
            true,
            true,
            true,
            true,
        ]
    );

    echo '<tr><td>Login as testUnitUser</td>';
    curlTestPOSTEquals(
        'validate.php',
        [],
        ['username' => $testUserName, 'password' => 'password', 'client_id' => 'WebPro', 'grant_type' => 'password'],
        ['login', 'userData', 'name'],
        $testUserName,
        function($data) {
            global $testUserToken;
            $testUserToken = $data['login']['access_token'];
        }
    );

    echo '<tr><td>Send Message "Hi", Room 1</td>';
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUserToken, 'roomId' => 1],
        ['message' => 'Hi'],
        ['message', 'censor'],
        []
    );

    echo "<tr><td>Get Room '$testRoomName', Test Permissions</td>";
    curlTestGETEqualsMulti(
        'api/room.php',
        ['access_token' => $testUserToken, 'id' => $testRoomId],
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
        ['access_token' => $testUserToken, 'roomId' => $testRoomId, 'archive' => true, 'messageIdStart' => 0],
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
        ['_action' => 'create', 'access_token' => $testUserToken, 'roomId' => $testRoomId],
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
        ['access_token' => $testUserToken, 'id' => $testRoomId],
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
        ['_action' => 'create', 'access_token' => $testUserToken, 'roomId' => $testRoomId],
        ['message' => 'Hi'],
        ['message', 'censor'],
        []
    );




    /*******************************
     ***** MESSAGE FORMATTING ******
     *******************************/

    echo '<thead><tr class="ui-widget-header"><th colspan="4">Default Formatting Tests</th></tr></thead>';

    echo "<tr><td>'$testUserName' Changes Default Formatting to Bold</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken],
        ['defaultFormatting' => ['bold']],
        ['editUserOptions'],
        []
    );

    echo "<tr><td>Get $testUserName as admin</td>";
    curlTestGETEqualsMulti(
        'api/user.php',
        ['access_token' => $accessToken, 'id' => $testUserId],
        [
            ['users', $testUserId, 'name'],
            ['users', $testUserId, 'messageFormatting'],
        ],
        [
            $testUserName,
            'font-weight:bold',
        ]
    );

    echo "<tr><td>'$testUserName' Changes Default Formatting to Italic, Blue</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken],
        ['defaultFormatting' => ['italic'], 'defaultHighlight' => '0,0,255'],
        ['editUserOptions'],
        []
    );

    echo "<tr><td>Get $testUserName as admin</td>";
    curlTestGETEqualsMulti(
        'api/user.php',
        ['access_token' => $accessToken, 'id' => $testUserId],
        [
            ['users', $testUserId, 'name'],
            ['users', $testUserId, 'messageFormatting'],
        ],
        [
            $testUserName,
            'font-style:italic;background-color:rgb(0,0,255);color:rgb(25,25,25)',
        ]
    );

    echo "<tr><td>'$testUserName' Changes Default Formatting to Italic, Bold, Blue Background, Green Foreground</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken],
        ['defaultFormatting' => ['italic', 'bold'], 'defaultHighlight' => '0,0,255', 'defaultColor' => '0,255,0'],
        ['editUserOptions'],
        []
    );

    echo "<tr><td>Get $testUserName as admin</td>";
    curlTestGETEqualsMulti(
        'api/user.php',
        ['access_token' => $accessToken, 'id' => $testUserId],
        [
            ['users', $testUserId, 'name'],
            ['users', $testUserId, 'messageFormatting'],
        ],
        [
            $testUserName,
            'font-weight:bold;font-style:italic;background-color:rgb(0,0,255);color:rgb(0,255,0)',
        ]
    );

    echo "<tr><td>'$testUserName' Changes Default Formatting to Blue Background, Light Blue Foreground</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken],
        ['defaultHighlight' => '0,0,255', 'defaultColor' => '0,127,255'],
        ['editUserOptions', 'defaultHighlight', 'string'],
        'lowContrast'
    );





    /*******************************
     ***** NEW ROOM ****************
     *******************************/

    echo '<thead><tr class="ui-widget-header"><th colspan="4">New Room Tests</th></tr></thead>';

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

    echo "<tr><td>Get Room '$testRoom2Name' as '$testUserName', Test for idNoExist</td>";
    curlTestGETEquals(
        'api/room.php',
        ['access_token' => $testUserToken, 'id' => $testRoom2Id],
        ['exception', 'string'],
        'idNoExist'
    );

    echo "<tr><td>Get Messages in '$testRoom2Name' as '$testUserName', Test for idNoExist</td>";
    curlTestGETEquals(
        'api/message.php',
        ['access_token' => $testUserToken, 'roomId' => $testRoom2Id, 'archive' => true],
        ['exception', 'string'],
        'idNoExist'
    );

    echo "<tr><td>Send Message 'Hi' in '$testRoom2Name' as '$testUserName', Test for idNoExist</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUserToken, 'roomId' => $testRoom2Id],
        ['message' => 'Hi'],
        ['exception', 'string'],
        'idNoExist'
    );

    echo "<tr><td>Edit Room '$testRoom2Name' to Allow Viewing by '$testUserName'</td>";
    curlTestPOSTEquals(
        'api/roomPermission.php',
        ['access_token' => $accessToken, 'roomId' => $testRoom2Id, 'userId' => $testUserId],
        ['permissions' => ['view']],
        ['roomPermission', 'id'],
        (string) $testRoom2Id
    );

    echo "<tr><td>Get Messages in '$testRoom2Name'</td>";
    curlTestGETEquals(
        'api/message.php',
        ['access_token' => $testUserToken, 'roomId' => $testRoom2Id, 'archive' => true],
        ['messages', 0, 'text'],
        'Huh'
    );

    echo "<tr><td>Send Message 'Hi' in '$testRoom2Name'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUserToken, 'roomId' => $testRoom2Id],
        ['message' => 'Hi'],
        ['exception', 'string'],
        'noPerm'
    );

    echo "<tr><td>Get All Rooms as '$testUserName', Testing for Rooms '$testRoomName', '$testRoom2Name' (Only At Installation)</td>";
    curlTestGETEqualsMulti(
        'api/room.php',
        ['access_token' => $testUserToken],
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

    echo "<tr><td>Edit Room '$testRoom2Name' to Allow Posting by '$testUserName'</td>";
    curlTestPOSTEquals(
        'api/roomPermission.php',
        ['access_token' => $accessToken, 'roomId' => $testRoom2Id, 'userId' => $testUserId],
        ['permissions' => ['post']],
        ['roomPermission', 'id'],
        (string) $testRoom2Id
    );

    echo "<tr><td>Get Room '$testRoom2Name', testing for changes</td>";
    curlTestGETEqualsMulti(
        'api/room.php',
        ['access_token' => $accessToken, 'id' => $testRoom2Id],
        [
            ['rooms', "room $testRoom2Id", 'name'],
            ['rooms', "room $testRoom2Id", 'userPermissions', $testUserId, 'view'],
            ['rooms', "room $testRoom2Id", 'userPermissions', $testUserId, 'post'],
            ['rooms', "room $testRoom2Id", 'userPermissions', $testUserId, 'moderate'],
            ['rooms', "room $testRoom2Id", 'userPermissions', $testUserId, 'grant'],
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
        ['access_token' => $testUserToken, 'roomId' => $testRoom2Id, 'archive' => true],
        ['messages', 0, 'text'],
        'Huh'
    );

    echo "<tr><td>Send Message '小さい問題' in '$testRoom2Name'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUserToken, 'roomId' => $testRoom2Id],
        ['message' => '小さい問題'],
        ['message', 'censor'],
        []
    );

    echo "<tr><td>Get Messages in '$testRoom2Name'</td>";
    curlTestGETEquals(
        'api/message.php',
        ['access_token' => $testUserToken, 'roomId' => $testRoom2Id, 'archive' => true],
        ['messages', 0, 'text'],
        '小さい問題'
    );

    echo '<thead><tr class="ui-widget-header"><th colspan="4">Another New User and Activity Tests</th></tr></thead>';

    $testUser2Id;
    $testUser2Token;
    $testUser2Name = "testUnitUser" . uniqid();

    echo "<tr><td>Create Valid User, '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'api/user.php',
        ['_action' => 'create'],
        ['name' => $testUser2Name, 'password' => '小さい問題', 'email' => 'billy@example.com', 'birthDate' => time() - 16 * 365 * 24 * 3600],
        ['user', 'name'],
        $testUser2Name,
        function($data) {
            global $testUser2Id;
            $testUser2Id = $data['user']['id'];
        }
    );

    echo "<tr><td>Login as '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'validate.php',
        [],
        ['username' => $testUser2Name, 'password' => '小さい問題', 'client_id' => 'WebPro', 'grant_type' => 'password'],
        ['login', 'userData', 'name'],
        $testUser2Name,
        function($data) {
            global $testUser2Token;
            $testUser2Token = $data['login']['access_token'];
        }
    );

    sleep(1); // Logging may prevent saves more than every second. (This is a TODO on its own terms.)

    echo "<tr><td>Edit Room '$testRoom2Name' to Allow Posting by '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'api/roomPermission.php',
        ['access_token' => $accessToken, 'roomId' => $testRoom2Id, 'userId' => $testUser2Id],
        ['permissions' => ['view', 'post']],
        ['roomPermission', 'id'],
        (string) $testRoom2Id
    );




    /*******************************
     ***** ACTIVE USERS ************
     *******************************/

    echo '<thead><tr class="ui-widget-header"><th colspan="4">Active Users Test</th></tr></thead>';

    echo "<tr><td>Get Rooms '$testUser2Name' is Active In</td>";
    curlTestGETEqualsMulti(
        'api/userStatus.php',
        ['access_token' => $testUserToken, 'userIds' => [$testUser2Id]],
        [
            ['users'],
            ['users', 'user 1'],
            ['users', 'user ' . $testUser2Id, 'rooms', 'room 1', 'name'],
            ['users', 'user ' . $testUser2Id, 'rooms', 'room ' . $testRoomId, 'name'],
            ['users', 'user ' . $testUser2Id, 'rooms', 'room ' . $testRoom2Id, 'name'],
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
        ['access_token' => $testUserToken, 'roomIds' => [$testRoom2Id]],
        [
            ['users', 'user 1', 'name'],
            ['users', 'user 1', 'rooms'],
            ['users', 'user ' . $testUserId, 'name'],
            ['users', 'user ' . $testUser2Id, 'name'],
        ],
        [
            $adminUsername,
            ['room ' . $testRoom2Id => ['id' => $testRoom2Id, 'name' => $testRoom2Name, 'status' => 'available', 'typing' => false]],
            $testUserName,
            null
        ]
    );

    echo "<tr><td>Send Message 'バカ' in '$testRoom2Name' as '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUser2Token, 'roomId' => $testRoom2Id],
        ['message' => 'バカ'],
        ['message', 'censor'],
        []
    );

    echo "<tr><td>Get Active Users in '$testRoom2Name'</td>";
    curlTestGETEqualsMulti(
        'api/userStatus.php',
        ['access_token' => $testUserToken, 'roomIds' => [$testRoom2Id]],
        [
            ['users', 'user 1', 'name'],
            ['users', 'user 1', 'rooms'],
            ['users', 'user ' . $testUserId, 'name'],
            ['users', 'user ' . $testUser2Id, 'name'],
        ],
        [
            $adminUsername,
            ['room ' . $testRoom2Id => ['id' => $testRoom2Id, 'name' => $testRoom2Name, 'status' => 'available', 'typing' => false]],
            $testUserName,
            $testUser2Name
        ]
    );

    echo "<tr><td>Get Active Users in '$testRoomName'</td>";
    curlTestGETEqualsMulti(
        'api/userStatus.php',
        ['access_token' => $testUserToken, 'roomIds' => [$testRoomId]],
        [
            ['users', 'user 1', 'name'],
            ['users', 'user 1', 'rooms'],
            ['users', 'user ' . $testUserId, 'name'],
            ['users', 'user ' . $testUser2Id, 'name'],
        ],
        [
            $adminUsername,
            ['room ' . $testRoomId => ['id' => $testRoomId, 'name' => $testRoomName, 'status' => 'available', 'typing' => false]],
            $testUserName,
            null
        ]
    );

    echo "<tr><td>Ping '$testRoomName' as '$testUser2Name', typing = true</td>";
    curlTestPOSTEquals(
        'api/userStatus.php',
        ['_action' => 'edit', 'access_token' => $testUser2Token, 'roomIds' => [$testRoomId]],
        ['typing' => true],
        ['response'],
        []
    );

    echo "<tr><td>Get Active Users in '$testRoomName'</td>";
    curlTestGETEqualsMulti(
        'api/userStatus.php',
        ['access_token' => $testUserToken, 'roomIds' => [$testRoomId]],
        [
            ['users', 'user 1', 'name'],
            ['users', 'user 1', 'rooms'],
            ['users', 'user ' . $testUserId, 'name'],
            ['users', 'user ' . $testUser2Id, 'name'],
            ['users', 'user ' . $testUser2Id, 'rooms'],
        ],
        [
            $adminUsername,
            ['room ' . $testRoomId => ['id' => $testRoomId, 'name' => $testRoomName, 'status' => 'available', 'typing' => false]],
            $testUserName,
            $testUser2Name,
            ['room ' . $testRoomId => ['id' => $testRoomId, 'name' => $testRoomName, 'status' => 'available', 'typing' => true]],
        ]
    );

    echo "<tr><td>Get Rooms '$testUser2Name' is Active In</td>";
    curlTestGETEqualsMulti(
        'api/userStatus.php',
        ['access_token' => $testUserToken, 'userIds' => [$testUser2Id]],
        [
            ['users', 'user 1'],
            ['users', 'user ' . $testUser2Id, 'name'],
            ['users', 'user ' . $testUser2Id, 'rooms', 'room 1', 'name'],
            ['users', 'user ' . $testUser2Id, 'rooms', 'room ' . $testRoomId, 'name'],
            ['users', 'user ' . $testUser2Id, 'rooms', 'room ' . $testRoom2Id, 'name'],
        ],
        [
            null,
            $testUser2Name,
            null,//'Your Room!',
            $testRoomName,
            $testRoom2Name,
        ]
    );

    echo "<tr><td>Go Offline in '$testRoomName' as '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'api/userStatus.php',
        ['_action' => 'edit', 'access_token' => $testUser2Token, 'roomIds' => [$testRoomId]],
        ['status' => 'offline'],
        ['response'],
        []
    );

    echo "<tr><td>Get Rooms '$testUser2Name' is Active In</td>";
    curlTestGETEqualsMulti(
        'api/userStatus.php',
        ['access_token' => $testUserToken, 'userIds' => [$testUser2Id]],
        [
            ['users', 'user ' . $testUser2Id, 'name'],
            ['users', 'user ' . $testUser2Id, 'rooms', 'room 1'],
            ['users', 'user ' . $testUser2Id, 'rooms', 'room ' . $testRoomId],
            ['users', 'user ' . $testUser2Id, 'rooms', 'room ' . $testRoom2Id, 'name'],
        ],
        [
            $testUser2Name,
            null,
            null,
            $testRoom2Name,
        ]
    );

    echo "<tr><td>Get Active Users in '$testRoomName'</td>";

    curlTestGETEqualsMulti(
        'api/userStatus.php',
        ['access_token' => $testUserToken, 'roomIds' => [$testRoomId]],
        [
            ['users', 'user 1', 'name'],
            ['users', 'user ' . $testUserId, 'name'],
            ['users', 'user ' . $testUser2Id],
        ],
        [
            $adminUsername,
            $testUserName,
            null
        ]
    );





    /*******************************
     ***** FAV ROOMS ***************
     *******************************/

    echo '<thead><tr class="ui-widget-header"><th colspan="4">Fav Rooms Test</th></tr></thead>';

    /*
     * Fav Room
     */
    echo "<tr><td>'$testUserName' Favs Room '$testRoomName'</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'create'],
        ['favRooms' => [$testRoomId]],
        ['editUserOptions'],
        []
    );

    /*
     * Check if room was successfully faved
     */
    echo "<tr><td>Get Rooms, Checking for '$testRoomName' at Top</td>";
    curlTestGETEquals(
        'api/room.php',
        ['access_token' => $testUserToken],
        ['rooms', new ArrayPosition(0), 'name'],
        $testRoomName
    );

    /*
     * Replace fav room list
     */
    echo "<tr><td>'$testUserName' Replaces Fav Rooms With 'Another Room!', '$testRoom2Name'</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'edit'],
        ['favRooms' => [2, $testRoom2Id]],
        ['editUserOptions'],
        []
    );

    /*
     * Check if rooms are sorted correctly with new fav list
     */
    echo "<tr><td>Get Rooms, Checking for 'Another Room!', '$testRoom2Name', 'Your Room!' at Top</td>";
    curlTestGETEqualsMulti(
        'api/room.php',
        ['access_token' => $testUserToken],
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

    /*
     * Unfav room
     */
    echo "<tr><td>'$testUserName' Unfavs Room '$testRoom2Name'</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'delete'],
        ['favRooms' => [$testRoom2Id]],
        ['editUserOptions'],
        []
    );

    /*
     * Check if rooms are successfully sorted with unfav
     */
    echo "<tr><td>Get Rooms, Checking for 'Another Room!', 'Your Room!' at Top</td>";
    curlTestGETEqualsMulti(
        'api/room.php',
        ['access_token' => $testUserToken],
        [
            ['rooms', new ArrayPosition(0), 'name'],
            ['rooms', new ArrayPosition(1), 'name'],
        ],
        [
            'Another Room!',
            'Your Room!'
        ]
    );





    /*******************************
     ***** HIDDEN ROOMS ************
     *******************************/

    echo '<thead><tr class="ui-widget-header"><th colspan="4">Hidden Rooms Test</th></tr></thead>';

    /*
     * Edit room to make hidden
     * TODO: moderator can't set hidden, official flags
     */
    echo "<tr><td>Edit Room '$testRoom2Name' to Make Hidden</td>";
    curlTestPOSTEquals(
        'api/room.php',
        ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoom2Id],
        ['hidden' => true],
        ['room', 'id'],
        (string) $testRoom2Id
    );

    /*
     * See if room has hidden flag
     */
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

    /*
     * See if room omitted from room list
     * TODO: don't include hidden rooms in active users
     */
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





    /*******************************
     ***** WATCH ROOMS *************
     *******************************/

    echo '<thead><tr class="ui-widget-header"><th colspan="4">Fav Rooms Test</th></tr></thead>';

    /*
     * Make sure the user isn't online.
     */
    echo "<tr><td>Go Offline in '$testRoomName' as '$testUserName'</td>";
    curlTestPOSTEquals(
        'api/userStatus.php',
        ['_action' => 'edit', 'access_token' => $testUserToken, 'roomIds' => [$testRoomId]],
        ['status' => 'offline'],
        ['response'],
        []
    );

    /*
     * No unread messages by default?
     */
    echo "<tr><td>Get Unread Messages for '$testUserName'</td>";
    curlTestGETEquals(
        'api/unreadMessages.php',
        ['access_token' => $testUserToken],
        ['unreadMessages'],
        []
    );

    /*
     * Fav the room.
     */
    echo "<tr><td>'$testUserName' Favs Room '$testRoomName'</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'create'],
        ['favRooms' => [$testRoomId]],
        ['editUserOptions'],
        []
    );

    /*
     * Still no unread messages?
     */
    echo "<tr><td>Get Unread Messages for '$testUserName'</td>";
    curlTestGETEquals(
        'api/unreadMessages.php',
        ['access_token' => $testUserToken],
        ['unreadMessages'],
        []
    );

    /*
     * Send a message in the faved room (as other user)
     */
    echo "<tr><td>Send Message 'Hello' in '$testRoomName' as '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUser2Token, 'roomId' => $testRoomId],
        ['message' => 'バカ'],
        ['message', 'censor'],
        []
    );

    /*
     * Send a message in a different room (as other user)
     */
    echo "<tr><td>Send Message 'Goodbye' in '$testRoom2Name' as '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUser2Token, 'roomId' => $testRoom2Id],
        ['message' => 'バカ'],
        ['message', 'censor'],
        []
    );

    /*
     * Get unread messages twice (make sure they persist between attempts)
     */
    for ($i = 0; $i < 2; $i++) {
        echo "<tr><td>Get Unread Messages for '$testUserName'</td>";
        curlTestGETEqualsMulti(
            'api/unreadMessages.php',
            ['access_token' => $testUserToken],
            [
                ['unreadMessages', 0, 'senderId'],
                ['unreadMessages', 0, 'roomId'],
                ['unreadMessages', 1]
            ],
            [
                $testUser2Id,
                $testRoomId,
                null
            ]
        );
    }

    /*
     * No unread messages for second user?
     */
    echo "<tr><td>Get Unread Messages for '$testUser2Name'</td>";
    curlTestGETEquals(
        'api/unreadMessages.php',
        ['access_token' => $testUser2Token],
        ['unreadMessages'],
        []
    );

    /*
     * Mark the message read.
     * todo: make delete
     */
    echo "<tr><td>'$testUserName' Marks Message Read</td>";
    curlTestGETEquals(
        'api/unreadMessages.php',
        ['access_token' => $testUserToken, '_action' => 'delete', 'roomId' => $testRoomId],
        ['markMessageRead'],
        []
    );

    /*
     * Are unread messages now empty?
     */
    echo "<tr><td>Get Unread Messages for '$testUserName'</td>";
    curlTestGETEquals(
        'api/unreadMessages.php',
        ['access_token' => $testUserToken],
        ['unreadMessages'],
        []
    );

    /*
     * Unfav the room.
     */
    echo "<tr><td>'$testUserName' Unfavs Room '$testRoomName'</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'edit'],
        ['favRooms' => ['']],
        ['editUserOptions'],
        []
    );

    /*
     * Send message as other user.
     */
    echo "<tr><td>Send Message 'Goodbye' in '$testRoomName' as '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUser2Token, 'roomId' => $testRoomId],
        ['message' => 'バカ'],
        ['message', 'censor'],
        []
    );

    /*
     * No new unread messages?
     */
    echo "<tr><td>Get Unread Messages for '$testUserName'</td>";
    curlTestGETEquals(
        'api/unreadMessages.php',
        ['access_token' => $testUserToken],
        ['unreadMessages'],
        []
    );





    /*******************************
     ***** PARENTAL CONTROLS *******
     *******************************/

    echo '<thead><tr class="ui-widget-header"><th colspan="4">Parental Controls Test</th></tr></thead>';

    /*
     * Change Test Room 1 Age to 16
     */
    echo "<tr><td>Change Room '$testRoomName' Age to 16</td>";
    curlTestPOSTEquals(
        'api/room.php',
        ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoomId],
        ['parentalAge' => 16],
        ['room', 'id'],
        (string) $testRoomId
    );

    /*
     * Get Test User 1's Permissions
     */
    echo "<tr><td>Get Room '$testRoomName' as Underage User</td>";
    curlTestGETEquals(
        'api/room.php',
        ['access_token' => $testUserToken, 'id' => $testRoomId],
        ['exception', 'string'],
        'parentalBlock'
    );

    /*
     * Change Test Room 1 Age to 13
     */
    echo "<tr><td>Change Room '$testRoomName' Age to 13</td>";
    curlTestPOSTEquals(
        'api/room.php',
        ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoomId],
        ['parentalAge' => 13],
        ['room', 'id'],
        (string) $testRoomId
    );

    /*
     * Get Test User 1's Permissions
     */
    echo "<tr><td>Get Room '$testRoomName' as Aboveage User, Test Permissions</td>";
    curlTestGETEquals(
        'api/room.php',
        ['access_token' => $testUserToken, 'id' => $testRoomId],
        ['rooms', "room $testRoomId", 'permissions', 'view'],
        true
    );

    /*
     * Set User 1's Parental Age
     */
    echo "<tr><td>'$testUserName' Sets Parental Age to 10</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'create'],
        ['parentalAge' => 10],
        ['editUserOptions'],
        []
    );

    /*
     * Get Test User 1's Permissions
     */
    echo "<tr><td>Get Room '$testRoomName' as Age-Restricted User</td>";
    curlTestGETEquals(
        'api/room.php',
        ['access_token' => $testUserToken, 'id' => $testRoomId],
        ['exception', 'string'],
        'parentalBlock'
    );

    /*
     * Change Test Room 1 Age to 10
     */
    echo "<tr><td>Change Room '$testRoomName' Age to 10</td>";
    curlTestPOSTEquals(
        'api/room.php',
        ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoomId],
        ['parentalAge' => 10],
        ['room', 'id'],
        (string) $testRoomId
    );

    /*
     * Get Test User 1's Permissions
     */
    echo "<tr><td>Get Room '$testRoomName' as Aboveage User, Test Permissions</td>";
    curlTestGETEquals(
        'api/room.php',
        ['access_token' => $testUserToken, 'id' => $testRoomId],
        ['rooms', "room $testRoomId", 'permissions', 'view'],
        true
    );

    /*
     * Set User 1's Parental Flags
     */
    echo "<tr><td>'$testUserName' Sets Parental Flags to Gore, Violence</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'create'],
        ['parentalFlags' => ['gore', 'violence']],
        ['editUserOptions'],
        []
    );

    /*
     * Change Test Room 1 Flags to Gore, Gambling
     */
    echo "<tr><td>Change Room '$testRoomName' Flags to Gore, Gambling</td>";
    curlTestPOSTEquals(
        'api/room.php',
        ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoomId],
        ['parentalFlags' => ['gore', 'gambling']],
        ['room', 'id'],
        (string) $testRoomId
    );

    echo "<tr><td>Get Room '$testRoomName' as Underage User</td>";
    curlTestGETEquals(
        'api/room.php',
        ['access_token' => $testUserToken, 'id' => $testRoomId],
        ['exception', 'string'],
        'parentalBlock'
    );

    /*
     * Reset Room
     */
    echo "<tr><td>Revert Room '$testRoomName' Flags to Empty, Age to 6</td>";
    curlTestPOSTEquals(
        'api/room.php',
        ['access_token' => $accessToken, '_action' => 'edit', 'id' => $testRoomId],
        ['parentalFlags' => [''], 'parentalAge' => 6],
        ['room', 'id'],
        (string) $testRoomId
    );

    /*
     * Verify Permissions
     */
    echo "<tr><td>Get Room '$testRoomName' as User, Test Permissions</td>";
    curlTestGETEquals(
        'api/room.php',
        ['access_token' => $testUserToken, 'id' => $testRoomId],
        ['rooms', "room $testRoomId", 'permissions', 'view'],
        true
    );






    /*******************************
     * KICKS
     * todo: test kick natural expiration
     *******************************/

    echo '<thead><tr class="ui-widget-header"><th colspan="4">Kicks</th></tr></thead>';

    /*
     * Test whether no kicks by default.
     */
    echo "<tr><td>Get Kicks as '$adminUsername', Checking for '$testUserName', '$testUser2Name'</td>";
    curlTestGETEqualsMulti(
        'api/kick.php',
        ['access_token' => $accessToken],
        [
            ['kicks', 'user ' . $testUserId],
            ['kicks', 'user ' . $testUser2Id],
        ],
        [
            null,
            null
        ]
    );

    /*
     * Can the admin not be kicked?
     * TODO: can all moderators not be kicked?
     */
    echo "<tr><td>Kick '$adminUsername' as '$adminUsername'</td>";
    curlTestPOSTEquals(
        'api/kick.php',
        ['access_token' => $accessToken, 'userId' => 1, 'roomId' => 1],
        ['length' => 60],
        ['exception', 'string'],
        'unkickableUser'
    );

    /*
     * Can the user get messages initially?
     */
    echo "<tr><td>Get Messages in '$testRoomName' as '$testUserName'</td>";
    curlTestGETEquals(
        'api/message.php',
        ['access_token' => $testUserToken, 'roomId' => $testRoomId],
        ['messages', 0, 'text'],
        true
    );

    /*
     * Is the allowed to post messages initially?
     */
    echo "<tr><td>Send Message 'Hello' in '$testRoomName' as '$testUserName'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUserToken, 'roomId' => $testRoomId],
        ['message' => 'Hello'],
        ['message', 'censor'],
        []
    );

    /*
     * Does kicking without length cause an exception:?
     */
    echo "<tr><td>Kick '$testUserName' without length as '$adminUsername' in '$testRoomName'</td>";
    curlTestPOSTEquals(
        'api/kick.php',
        ['access_token' => $accessToken, 'userId' => $testUserId, 'roomId' => $testRoomId],
        [],
        ['exception', 'string'],
        'lengthRequired'
    );

    /*
     * Does normal kick work?
     */
    echo "<tr><td>Kick '$testUserName' as '$adminUsername' in '$testRoomName'</td>";
    curlTestPOSTEquals(
        'api/kick.php',
        ['access_token' => $accessToken, 'userId' => $testUserId, 'roomId' => $testRoomId],
        ['length' => 60],
        ['kick'],
        []
    );

    /*
     * Is kick reflected in kicks list?
     * TODO: new length
     */
    echo "<tr><td>Get All Kicks as '$adminUsername'</td>";
    curlTestGETEqualsMulti(
        'api/kick.php',
        ['access_token' => $accessToken],
        [
            ['kicks', 'user ' . $testUserId, 'userId'],
            ['kicks', 'user ' . $testUserId, 'userName'],
            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'roomId'],
            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'roomName'],
            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'kickerId'],
            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'kickerName'],
//            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'length'],
        ],
        [
            $testUserId,
            $testUserName,
            $testRoomId,
            $testRoomName,
            1,
            $adminUsername,
//            60
        ]
    );


    /*
     * Did kick message appear? (And can the kicked user still get messages?)
     */
    echo "<tr><td>Get Messages in '$testRoomName' as '$testUserName'</td>";
    curlTestGETEquals(
        'api/message.php',
        ['access_token' => $testUserToken, 'roomId' => $testRoomId],
        ['messages', 0, 'text'],
        "/me kicked $testUserName"
    );

    /*
     * Is the kicked user disallowed from sending messages?
     */
    echo "<tr><td>Send Message 'Hello' in '$testRoomName' as '$testUserName'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUserToken, 'roomId' => $testRoomId],
        ['message' => 'Hello'],
        ['exception', 'string'],
        'noPerm'
    );

    /*
     * Is the second user allowed to send messages?
     */
    echo "<tr><td>Send Message 'Hello' in 'Your Room!' as '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUser2Token, 'roomId' => 1],
        ['message' => 'Hello'],
        ['message', 'censor'],
        []
    );

    /*
     * Kick the second user.
     */
    echo "<tr><td>Kick '$testUser2Name' as '$adminUsername' in 'Your Room!'</td>";
    curlTestPOSTEquals(
        'api/kick.php',
        ['access_token' => $accessToken, 'userId' => $testUser2Id, 'roomId' => 1],
        ['length' => 120],
        ['kick'],
        []
    );

    /*
     * Is the kick reflected in the kick list?
     */
    echo "<tr><td>Get All Kicks as '$adminUsername'</td>";
    curlTestGETEqualsMulti(
        'api/kick.php',
        ['access_token' => $accessToken],
        [
            ['kicks', 'user ' . $testUserId, 'userName'],
            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'roomName'],
            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'kickerName'],
//            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'length'],
            ['kicks', 'user ' . $testUser2Id, 'userName'],
            ['kicks', 'user ' . $testUser2Id, 'kicks', 'room ' . 1, 'roomName'],
            ['kicks', 'user ' . $testUser2Id, 'kicks', 'room ' . 1, 'kickerName'],
//            ['kicks', 'user ' . $testUser2Id, 'kicks', 'room ' . 1, 'length'],
        ],
        [
            $testUserName,
            $testRoomName,
            $adminUsername,
//            60,
            $testUser2Name,
            'Your Room!',
            $adminUsername,
//            120
        ]
    );

    /*
     * Is the second user disallowed from sending messages?
     */
    echo "<tr><td>Send Message 'Hello' in 'Your Room!' as '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['_action' => 'create', 'access_token' => $testUser2Token, 'roomId' => 1],
        ['message' => 'Hello'],
        ['exception', 'string'],
        'noPerm'
    );

    /*
     * Is the user disallowed from viewing all kicks?
     */
    echo "<tr><td>Get All Kicks as '$testUser2Name'</td>";
    curlTestGETEquals(
        'api/kick.php',
        ['access_token' => $testUser2Token],
        ['exception', 'string'],
        'roomIdRequired'
    );

    /*
     * Is the user disallowed from viewing room kicks?
     */
    echo "<tr><td>Get Room '$testRoomName' kicks as '$testUser2Name'</td>";
    curlTestGETEquals(
        'api/kick.php',
        ['access_token' => $testUser2Token, 'roomId' => $testRoomId],
        ['exception', 'string'],
        'noPerm'
    );

    /*
     * Is a user disallowed from viewing another user's kicks, sans room?
     */
    echo "<tr><td>Get '$testUserName's kicks as '$testUser2Name'</td>";
    curlTestGETEquals(
        'api/kick.php',
        ['access_token' => $testUser2Token, 'userId' => $testUserId],
        ['exception', 'string'],
        'roomIdRequired'
    );

    /*
     * Is a user disallowed from viewing another user's kicks, with room?
     */
    echo "<tr><td>Get '$testUserName's kicks in '$testRoomName' as '$testUser2Name'</td>";
    curlTestGETEquals(
        'api/kick.php',
        ['access_token' => $testUser2Token, 'roomId' => $testRoomId, 'userId' => $testUserId],
        ['exception', 'string'],
        'noPerm'
    );

    /*
     * Is a user allowed to see their own kicks?
     * TODO: multiple rooms
     */
    echo "<tr><td>Get '$testUserName's kicks as '$testUserName'</td>";
    curlTestGETEqualsMulti(
        'api/kick.php',
        ['access_token' => $testUserToken, 'userId' => $testUserId],
        [
            ['kicks', 'user ' . $testUser2Id],
            ['kicks', 'user ' . $testUserId, 'userName'],
            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'roomName'],
            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'kickerName'],
//            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'length'],
        ],
        [
            null,
            $testUserName,
            $testRoomName,
            $adminUsername,
//            60,
        ]
    );

    /*
     * Is a user allowed to see their own kicks in the room?
     */
    echo "<tr><td>Get '$testUserName's kicks as '$testUserName'</td>";
    curlTestGETEqualsMulti(
        'api/kick.php',
        ['access_token' => $testUserToken, 'userId' => $testUserId, 'roomId' => $testRoomId],
        [
            ['kicks', 'user ' . $testUser2Id],
            ['kicks', 'user ' . $testUserId, 'userName'],
            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'roomName'],
            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'kickerName'],
//            ['kicks', 'user ' . $testUserId, 'kicks', 'room ' . $testRoomId, 'length'],
        ],
        [
            null,
            $testUserName,
            $testRoomName,
            $adminUsername,
//            60,
        ]
    );

    /*
     * Is a user allowed to see their own kicks?
     */
    echo "<tr><td>Get '$testUser2Name's kicks as '$testUser2Name'</td>";
    curlTestGETEqualsMulti(
        'api/kick.php',
        ['access_token' => $testUser2Token, 'userId' => $testUser2Id],
        [
            ['kicks', 'user ' . $testUserId],
            ['kicks', 'user ' . $testUser2Id, 'userName'],
            ['kicks', 'user ' . $testUser2Id, 'kicks', 'room ' . 1, 'roomName'],
            ['kicks', 'user ' . $testUser2Id, 'kicks', 'room ' . 1, 'kickerName'],
//            ['kicks', 'user ' . $testUser2Id, 'kicks', 'room ' . 1, 'length'],
        ],
        [
            null,
            $testUser2Name,
            'Your Room!',
            $adminUsername,
//            120
        ]
    );

    /*
     * Can the users not be unkicked by themselves?
     */
    echo "<tr><td>Unkick '$testUser2Name' as '$testUserName' in 'Your Room!'</td>";
    curlTestPOSTEquals(
        'api/kick.php',
        ['access_token' => $testUserToken, '_action' => 'delete', 'userId' => $testUser2Id, 'roomId' => 1],
        [],
        ['exception', 'string'],
        'noPerm'
    );
    echo "<tr><td>Unkick '$testUser2Name' as '$testUser2Name' in 'Your Room!'</td>";
    curlTestPOSTEquals(
        'api/kick.php',
        ['access_token' => $testUser2Token, '_action' => 'delete', 'userId' => $testUser2Id, 'roomId' => 1],
        [],
        ['exception', 'string'],
        'noPerm'
    );

    /*
     * Can the users be unkicked by the admin?
     */
    echo "<tr><td>Unkick '$testUser2Name' as '$adminUsername' in 'Your Room!'</td>";
    curlTestPOSTEquals(
        'api/kick.php',
        ['access_token' => $accessToken, '_action' => 'delete', 'userId' => $testUser2Id, 'roomId' => 1],
        [],
        ['kick'],
        []
    );

    /*
     * Can the users be send messages after being unkicked?
     */
    echo "<tr><td>Send Message 'Hello' in 'Your Room!' as '$testUser2Name'</td>";
    curlTestPOSTEquals(
        'api/message.php',
        ['access_token' => $testUser2Token, 'roomId' => 1],
        ['message' => 'Hello'],
        ['message', 'censor'],
        []
    );





    /*******************************
     * PRIVATE ROOMS
     *******************************/

    echo '<thead><tr class="ui-widget-header"><th colspan="4">Private Rooms</th></tr></thead>';


    /*
     * Test Whether Messages Can Be Sent in Private Room
     * TODO: send flipped room ID
     */
    foreach (["", "2"] AS $testUserX) {
        echo "<tr><td>Send Message 'Hi!$testUserX' in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Name'} . "</td>";
        curlTestPOSTEquals(
            'api/message.php',
            ['_action' => 'create', 'access_token' => ${'testUser' . $testUserX . 'Token'}, 'roomId' => "p$testUserId,$testUser2Id"],
            ['message' => 'Hi!' . $testUserX],
            ['message', 'censor'],
            []
        );
    }

    foreach (["", "2"] AS $testUserX) {
        echo "<tr><td>Get Messages in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Name'} . "</td>";
        curlTestGETEqualsMulti(
            'api/message.php',
            ['access_token' => $testUserToken, 'roomId' => "p$testUserId,$testUser2Id"],
            [
                ['messages', new ArrayPosition(1), 'text'],
                ['messages', new ArrayPosition(0), 'text']
            ],
            [
                'Hi!',
                'Hi!2'
            ]
        );

        echo "<tr><td>Get Messages in 'p$testUser2Id,$testUserId' as " . ${'testUser' . $testUserX . 'Name'} . "</td>";
        curlTestGETEqualsMulti(
            'api/message.php',
            ['access_token' => $testUserToken, 'roomId' => "p$testUser2Id,$testUserId"],
            [
                ['messages', new ArrayPosition(1), 'text'],
                ['messages', new ArrayPosition(0), 'text']
            ],
            [
                'Hi!',
                'Hi!2'
            ]
        );

        echo "<tr><td>Get Fallback Stream in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Name'} . "</td>";
        curlTestGETEqualsMulti(
            'stream.php',
            ['access_token' => $testUserToken, 'fallback' => true, 'streamType' => 'room', 'queryId' => "p$testUserId,$testUser2Id"],
            [
                ['events', new ArrayPosition(1), 'data', 'text'],
                ['events', new ArrayPosition(3), 'data', 'text']
            ],
            [
                'Hi!',
                'Hi!2'
            ]
        );

        echo "<tr><td>Get Fallback Stream in 'p$testUser2Id,$testUserId' as " . ${'testUser' . $testUserX . 'Name'} . "</td>";
        curlTestGETEqualsMulti(
            'stream.php',
            ['access_token' => $testUserToken, 'fallback' => true, 'streamType' => 'room', 'queryId' => "p$testUser2Id,$testUserId"],
            [
                ['events', new ArrayPosition(1), 'data', 'text'],
                ['events', new ArrayPosition(3), 'data', 'text']
            ],
            [
                'Hi!',
                'Hi!2'
            ]
        );
    }


    /*
     * Are kicks disallowed?
     */
    echo "<tr><td>Kick '$testUserName' as '$adminUsername' in 'p$testUserId,$testUser2Id'</td>";
    curlTestPOSTEquals(
        'api/kick.php',
        ['access_token' => $accessToken, 'userId' => $testUserId, 'roomId' => "p$testUserId,$testUser2Id"],
        ['length' => 60],
        ['exception', 'string'],
        'roomIdNoExist'
    );
    echo "<tr><td>Kick '$testUserName' as '$testUserName' in 'p$testUserId,$testUser2Id'</td>";
    curlTestPOSTEquals(
        'api/kick.php',
        ['access_token' => $testUserToken, 'userId' => $testUserId, 'roomId' => "p$testUserId,$testUser2Id"],
        ['length' => 60],
        ['exception', 'string'],
        'noPerm'
    );


    /*
     * Test Whether Admin Can't Read Private Room
     */
    echo "<tr><td>Get Messages in 'p$testUserId,$testUser2Id' as '$adminUsername'</td>";
    curlTestGETEquals(
        'api/message.php',
        ['access_token' => $accessToken, 'roomId' => "p$testUserId,$testUser2Id"],
        ['exception', 'string'],
        'idNoExist'
    );


    /*
     * Test Whether Messages Can't Be Sent w/ Ignore List
     */
    echo "<tr><td>'$testUserId' Adds '$testUser2Id' to Ignore List</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'create'],
        ['ignoreList' => [$testUser2Id]],
        ['editUserOptions'],
        []
    );

    foreach (["", "2"] AS $testUserX) {
        echo "<tr><td>Send Message 'Hi!$testUserX' in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Name'} . "</td>";
        curlTestPOSTEquals(
            'api/message.php',
            ['_action' => 'create', 'access_token' => ${'testUser' . $testUserX . 'Token'}, 'roomId' => "p$testUserId,$testUser2Id"],
            ['message' => 'Hi!' . $testUserX],
            ['exception', 'string'],
            'noPerm'
        );

        echo "<tr><td>Get Messages in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Name'} . "</td>";
        curlTestGETEqualsMulti(
            'api/message.php',
            ['access_token' => $testUserToken, 'roomId' => "p$testUserId,$testUser2Id"],
            [
                ['messages', 0, 'text'],
                ['messages', 1, 'text']
            ],
            [
                'Hi!2',
                'Hi!'
            ]
        );
    }


    /*
     * Test Whether Messages Can Be Sent After Ignore List Removal
     */
    echo "<tr><td>'$testUserId' Removes '$testUser2Id' From Ignore List</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'delete'],
        ['ignoreList' => [$testUser2Id]],
        ['editUserOptions'],
        []
    );

    foreach (["", "2"] AS $testUserX) {
        echo "<tr><td>Send Message 'Hello!$testUserX' in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Name'} . "</td>";
        curlTestPOSTEquals(
            'api/message.php',
            ['_action' => 'create', 'access_token' => ${'testUser' . $testUserX . 'Token'}, 'roomId' => "p$testUserId,$testUser2Id"],
            ['message' => "Hello!$testUserX"],
            ['message', 'censor'],
            []
        );
    }

    foreach (["", "2"] AS $testUserX) {
        echo "<tr><td>Get Messages in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Name'} . "</td>";
        curlTestGETEqualsMulti(
            'api/message.php',
            ['access_token' => $testUserToken, 'roomId' => "p$testUserId,$testUser2Id"],
            [
                ['messages', 0, 'text'],
                ['messages', 1, 'text'],
            ],
            [
                'Hello!2',
                'Hello!'
            ]
        );
    }


    /*
     * Test Whether Messages Can't Be Sent w/ Private Room Friends-Only
     */
    echo "<tr><td>'$testUserId' Changes Privacy Setting to Friends-Only</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'edit'],
        ['privacyLevel' => 'friends'],
        ['editUserOptions'],
        []
    );

    foreach (["", "2"] AS $testUserX) {
        echo "<tr><td>Send Message 'Hi!$testUserX' in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Id'} . "</td>";
        curlTestPOSTEquals(
            'api/message.php',
            ['_action' => 'create', 'access_token' => ${'testUser' . $testUserX . 'Token'}, 'roomId' => "p$testUserId,$testUser2Id"],
            ['message' => 'Hi!' . $testUserX],
            ['exception', 'string'],
            'noPerm'
        );

        echo "<tr><td>Get Messages in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Id'} . "</td>";
        curlTestGETEqualsMulti(
            'api/message.php',
            ['access_token' => $testUserToken, 'roomId' => "p$testUserId,$testUser2Id"],
            [
                ['messages', 0, 'text'],
                ['messages', 1, 'text'],
            ],
            [
                'Hello!2',
                'Hello!'
            ]
        );
    }


    /*
     * Test Whether Messages Can Be Sent w/ Private Room Friends-Only, On Friends List
     */
    echo "<tr><td>'$testUserId' Adds '$testUser2Id' to Friends List</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'create'],
        ['friendsList' => [$testUser2Id]],
        ['editUserOptions'],
        []
    );

    foreach (["", "2"] AS $testUserX) {
        echo "<tr><td>Send Message 'Hi Again!$testUserX' in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Id'} . "</td>";
        curlTestPOSTEquals(
            'api/message.php',
            ['_action' => 'create', 'access_token' => ${'testUser' . $testUserX . 'Token'}, 'roomId' => "p$testUserId,$testUser2Id"],
            ['message' => 'Hi Again!' . $testUserX],
            ['message', 'censor'],
            []
        );
    }

    foreach (["", "2"] AS $testUserX) {
        echo "<tr><td>Get Messages in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Id'} . "</td>";
        curlTestGETEqualsMulti(
            'api/message.php',
            ['access_token' => ${"testUser" . $testUserX . "Token"}, 'roomId' => "p$testUserId,$testUser2Id"],
            [
                ['messages', 0, 'text'],
                ['messages', 1, 'text']
            ],
            [
                'Hi Again!2',
                'Hi Again!',
            ]
        );
    }


    /*
     * Test Whether Messages Can't Be Sent w/ Private Room Block-All
     */
    echo "<tr><td>'$testUserId' Changes Privacy Setting to Block-All</td>";
    curlTestPOSTEquals(
        'api/userOptions.php',
        ['access_token' => $testUserToken, '_action' => 'edit'],
        ['privacyLevel' => 'block'],
        ['editUserOptions'],
        []
    );

    foreach (["", "2"] AS $testUserX) {
        echo "<tr><td>Send Message 'Hi!$testUserX' in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Id'} . "</td>";
        curlTestPOSTEquals(
            'api/message.php',
            ['_action' => 'create', 'access_token' => ${'testUser' . $testUserX . 'Token'}, 'roomId' => "p$testUserId,$testUser2Id"],
            ['message' => 'Hi!' . $testUserX],
            ['exception', 'string'],
            'noPerm'
        );

        echo "<tr><td>Get Messages in 'p$testUserId,$testUser2Id' as " . ${'testUser' . $testUserX . 'Id'} . "</td>";
        curlTestGETEqualsMulti(
            'api/message.php',
            ['access_token' => ${"testUser" . $testUserX . "Token"}, 'roomId' => "p$testUserId,$testUser2Id"],
            [
                ['messages', 0, 'text'],
                ['messages', 1, 'text'],
                ['messages', 2, 'text'],
                ['messages', 3, 'text'],
                ['messages', 4, 'text'],
                ['messages', 5, 'text'],
                ['messages', 6]
            ],
            [
                'Hi Again!2',
                'Hi Again!',
                'Hello!2',
                'Hello!',
                'Hi!2',
                'Hi!',
                null
            ]
        );
    }

    // todo: message edits work for private rooms

    // todo: message edits
    // todo: message/room/user text search
    // todo: user message formatting
    // todo: active users not visible in rooms users can't view
    // todo: file uploads and enumerations (esp. whether a user should/shouldn't be allowed to see files)
    // todo: age content restrictions on rooms
    // todo: censor
}
else {
    echo "<tr><td>Create User</td>";
    curlTestPOSTEquals(
        'api/user.php',
        ['_action' => 'create'],
        ['name' => 'TestUser1234', 'password' => 'password', 'email' => 'bob@example.com', 'birthDate' => time() - 14 * 365 * 24 * 3600],
        ['exception', 'string'],
        'registrationDisabled'
    );

    echo "<tr class='table-danger'><td colspan='4'>Additional Tests Skipped - Registration Disabled</td></tr>";
}


echo '</table>';

?>
</div>
</body>
</html>