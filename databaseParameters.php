<?php
$database->setTransformationParameters([
    $database->sqlPrefix . 'files' => ['roomIdLink' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'messages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'messageIndex' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'ping' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'rooms' => [
        'watchedByUserIds'  => ['fimDatabase::packListCache', DatabaseTypeType::blob, 'fimDatabase::unpackListCache']
    ],
    $database->sqlPrefix . 'roomEvents' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'roomStats' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'socialGroups' => [
        'memberUserIds' => ['fimDatabase::packListCache', DatabaseTypeType::blob, 'fimDatabase::unpackListCache']
    ],
    $database->sqlPrefix . 'searchMessages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'searchCache' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'unreadMessages' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
    $database->sqlPrefix . 'users' => [
        'defaultRoomId'   => ['fimRoom::encodeId',     DatabaseTypeType::blob, 'fimRoom::decodeId'],
        'favRoomIds'      => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
        'watchRoomIds'    => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
        'friendedUserIds' => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
        'ignoredUserIds'  => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
        'socialGroupIds'  => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
    ],
    $database->sqlPrefix . 'userFavRooms' => ['roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId']],
], [
    $database->sqlPrefix . 'rooms' => [
        'roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'roomIdEncoded'],
        'roomName' => ['fimDatabase::makeSearchable', false, 'roomNameSearchable']
    ],
], [
    $database->sqlPrefix . 'rooms' => 'roomId',
]);


/* These manipulate how data is partioned in a database. */
$database->setHardPartitions([
    $database->sqlPrefix . 'messages' => ['roomId', 10],
    $database->sqlPrefix . 'messagesCached' => ['roomId', 10],
]);


/* These maintain collections. */
$database->setCollectionTriggers([
    $database->sqlPrefix . 'userFavRooms' => [
        ['userId', 'roomId', [$database, 'triggerUserFavRoomIds']],
    ],
    $database->sqlPrefix . 'watchRooms' => [
        ['userId', 'roomId', [$database, 'triggerUserWatchedRoomIds']],
//            ['roomId', 'userId', 'fimDatabase:triggerRoomWatchedByIds'],
    ],
    $database->sqlPrefix . 'userIgnoreList' => [
        ['userId', 'subjectId', [$database, 'triggerUserIgnoredUserIds']],
    ],
    $database->sqlPrefix . 'userFriendsList' => [
        ['userId', 'subjectId', [$database, 'triggerUserFriendedUserIds']],
    ],
    $database->sqlPrefix . 'socialGroupMembers' => [
        ['userId', 'groupId', [$database, 'triggerUserMemberOfGroupIds']],
//            ['groupId', 'userId', 'fimDatabase:triggerGroupMemberIds'],
    ],
]);
?>