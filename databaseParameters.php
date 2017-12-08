<?php
use Database\DatabaseTypeType;

\Fim\Database::instance()->setTransformationParameters([
    \Fim\Database::instance()->sqlPrefix . 'files' => ['roomIdLink' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::instance()->sqlPrefix . 'messages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::instance()->sqlPrefix . 'messageFlood' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::instance()->sqlPrefix . 'messageEditHistory' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::instance()->sqlPrefix . 'ping' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::instance()->sqlPrefix . 'rooms' => [
        'watchedByUsers'  => ['\Fim\DatabaseInstance::packListCache', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackListCache']
    ],
    \Fim\Database::instance()->sqlPrefix . 'roomStats' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::instance()->sqlPrefix . 'socialGroups' => [
        'memberUserIds' => ['\Fim\DatabaseInstance::packListCache', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackListCache']
    ],
    \Fim\Database::instance()->sqlPrefix . 'searchMessages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::instance()->sqlPrefix . 'searchCache' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::instance()->sqlPrefix . 'unreadMessages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    \Fim\Database::instance()->sqlPrefix . 'users' => [
        'defaultRoomId' => ['fimRoom::encodeId',     DatabaseTypeType::blob, 'fimRoom::decodeId'],
        'favRooms'      => ['\Fim\DatabaseInstance::packList', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackList'],
        'watchRooms'    => ['\Fim\DatabaseInstance::packList', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackList'],
        'friendedUsers' => ['\Fim\DatabaseInstance::packList', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackList'],
        'ignoredUsers'  => ['\Fim\DatabaseInstance::packList', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackList'],
        'socialGroupIds'  => ['\Fim\DatabaseInstance::packList', DatabaseTypeType::blob, '\Fim\DatabaseInstance::unpackList'],
    ],
    \Fim\Database::instance()->sqlPrefix . 'userFavRooms' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
], [
    \Fim\Database::instance()->sqlPrefix . 'rooms' => [
        'id' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'idEncoded'],
    ],
], [
    \Fim\Database::instance()->sqlPrefix . 'users' => 'id',
    \Fim\Database::instance()->sqlPrefix . 'rooms' => 'id',
]);


/* These manipulate how data is partitioned in a database. */
\Fim\Database::instance()->setHardPartitions([
    \Fim\Database::instance()->sqlPrefix . 'messages' => ['roomId', 10],
    \Fim\Database::instance()->sqlPrefix . 'accessFlood' => ['ip', 10],
]);


/* These maintain collections. */
\Fim\Database::instance()->setCollectionTriggers([
    \Fim\Database::instance()->sqlPrefix . 'watchRooms' => [
        ['roomId', 'userId', [\Fim\Database::instance(), 'triggerRoomWatchedByIds']],
    ]
        // TODO: do this in fimUser
        //\Fim\Database::instance()->sqlPrefix . 'socialGroupMembers' => [
        //['userId', 'groupId', [\Fim\Database::instance(), 'triggerUserMemberOfGroupIds']],
        //['groupId', 'userId', '\Fim\DatabaseInstance:triggerGroupMemberIds'],
        //],
]);
?>