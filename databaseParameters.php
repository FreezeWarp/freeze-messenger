<?php
use Database\DatabaseTypeType;

\Fim\Database::instance()->setTransformationParameters([
    \Fim\Database::$sqlPrefix . 'files' => ['roomIdLink' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::$sqlPrefix . 'messages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::$sqlPrefix . 'messageFlood' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::$sqlPrefix . 'messageEditHistory' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::$sqlPrefix . 'ping' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::$sqlPrefix . 'rooms' => [
        'nameSearchable'  => ['\Fim\DatabaseInstance::makeSearchable', DatabaseTypeType::string, null],
        'watchedByUsers'  => ['\Fim\DatabaseInstance::packListCache', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackListCache']
    ],
    \Fim\Database::$sqlPrefix . 'roomStats' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::$sqlPrefix . 'socialGroups' => [
        'memberUserIds' => ['\Fim\DatabaseInstance::packListCache', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackListCache']
    ],
    \Fim\Database::$sqlPrefix . 'searchMessages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::$sqlPrefix . 'searchCache' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::$sqlPrefix . 'unreadMessages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::$sqlPrefix . 'users' => [
        'nameSearchable'  => ['\Fim\DatabaseInstance::makeSearchable', DatabaseTypeType::string, null],
        'defaultRoomId' => ['fimRoom::encodeId',     DatabaseTypeType::blob, 'fimRoom::decodeId'],
        'favRooms'      => ['\Fim\DatabaseInstance::packList', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackList'],
        'watchRooms'    => ['\Fim\DatabaseInstance::packList', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackList'],
        'friendedUsers' => ['\Fim\DatabaseInstance::packList', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackList'],
        'ignoredUsers'  => ['\Fim\DatabaseInstance::packList', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackList'],
        'socialGroupIds'  => ['\Fim\DatabaseInstance::packList', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackList'],
    ],
    \Fim\Database::$sqlPrefix . 'userFavRooms' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
], [
    \Fim\Database::$sqlPrefix . 'rooms' => [
        'id' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'idEncoded'],
    ],
], [
    \Fim\Database::$sqlPrefix . 'users' => 'id',
    \Fim\Database::$sqlPrefix . 'rooms' => 'id',
]);


/* These manipulate how data is partitioned in a database. */
\Fim\Database::instance()->setHardPartitions([
    \Fim\Database::$sqlPrefix . 'messages' => ['roomId', 10],
    \Fim\Database::$sqlPrefix . 'accessFlood' => ['ip', 10],
]);


/* These maintain collections. */
\Fim\Database::instance()->setCollectionTriggers([
    \Fim\Database::$sqlPrefix . 'watchRooms' => [
        ['roomId', 'userId', [\Fim\Database::instance(), 'triggerRoomWatchedByIds']],
    ]
        // TODO: do this in fimUser
        //\Fim\Database::$sqlPrefix . 'socialGroupMembers' => [
        //['userId', 'groupId', [\Fim\Database::instance(), 'triggerUserMemberOfGroupIds']],
        //['groupId', 'userId', '\Fim\DatabaseInstance:triggerGroupMemberIds'],
        //],
]);
?>