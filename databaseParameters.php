<?php
use Database\DatabaseTypeType;

$database->setTransformationParameters([
    $database->sqlPrefix . 'files' => ['roomIdLink' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'messages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'messageFlood' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'ping' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'rooms' => [
        'watchedByUsers'  => ['fimDatabase::packListCache', DatabaseTypeType::blob, 'fimDatabase::unpackListCache']
    ],
    $database->sqlPrefix . 'roomStats' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'socialGroups' => [
        'memberUserIds' => ['fimDatabase::packListCache', DatabaseTypeType::blob, 'fimDatabase::unpackListCache']
    ],
    $database->sqlPrefix . 'searchMessages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'searchCache' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'unreadMessages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'users' => [
        'defaultRoomId' => ['fimRoom::encodeId',     DatabaseTypeType::blob, 'fimRoom::decodeId'],
        'favRooms'      => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
        'watchRooms'    => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
        'friendedUsers' => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
        'ignoredUsers'  => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
        'socialGroupIds'  => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
    ],
    $database->sqlPrefix . 'userFavRooms' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
], [
    $database->sqlPrefix . 'rooms' => [
        'id' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'idEncoded'],
    ],
], [
    $database->sqlPrefix . 'rooms' => 'id',
]);


/* These manipulate how data is partioned in a database. */
$database->setHardPartitions([
    $database->sqlPrefix . 'messages' => ['roomId', 10],
]);


/* These maintain collections. */
$database->setCollectionTriggers([
    $database->sqlPrefix . 'watchRooms' => [
        ['roomId', 'userId', [$database, 'triggerRoomWatchedByIds']],
    ]
        // TODO: do this in fimUser
        //$database->sqlPrefix . 'socialGroupMembers' => [
        //['userId', 'groupId', [$database, 'triggerUserMemberOfGroupIds']],
        //['groupId', 'userId', 'fimDatabase:triggerGroupMemberIds'],
        //],
]);
?>