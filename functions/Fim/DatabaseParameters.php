<?php

namespace Fim;

use Database\Type\Type;

class DatabaseParameters
{
    public static function execute(DatabaseInstance $instance)
    {
        $instance->setTransformationParameters([
            \Fim\Database::$sqlPrefix . 'files'              => ['roomIdLink' => ['\Fim\Room::encodeId', Type::blob, '\Fim\Room::decodeId']],
            \Fim\Database::$sqlPrefix . 'messages'           => ['roomId' => ['\Fim\Room::encodeId', Type::blob, '\Fim\Room::decodeId']],
            \Fim\Database::$sqlPrefix . 'messageFlood'       => ['roomId' => ['\Fim\Room::encodeId', Type::blob, '\Fim\Room::decodeId']],
            \Fim\Database::$sqlPrefix . 'messageEditHistory' => ['roomId' => ['\Fim\Room::encodeId', Type::blob, '\Fim\Room::decodeId']],
            \Fim\Database::$sqlPrefix . 'ping'               => ['roomId' => ['\Fim\Room::encodeId', Type::blob, '\Fim\Room::decodeId']],
            \Fim\Database::$sqlPrefix . 'rooms'              => [
                'nameSearchable' => ['\Fim\DatabaseInstance::makeSearchable', Type::string, null],
                'parentalFlags' => ['\Fim\Utilities::encodeList', Type::string, '\Fim\Utilities::decodeList']
            ],
            \Fim\Database::$sqlPrefix . 'roomStats'          => ['roomId' => ['\Fim\Room::encodeId', Type::blob, '\Fim\Room::decodeId']],
            \Fim\Database::$sqlPrefix . 'socialGroups'       => [
                'memberUserIds' => ['\Fim\DatabaseInstance::packListCache', Type::blob, '\Fim\DatabaseInstance::unpackListCache']
            ],
            \Fim\Database::$sqlPrefix . 'searchMessages'     => ['roomId' => ['\Fim\Room::encodeId', Type::blob, '\Fim\Room::decodeId']],
            \Fim\Database::$sqlPrefix . 'searchCache'        => ['roomId' => ['\Fim\Room::encodeId', Type::blob, '\Fim\Room::decodeId']],
            \Fim\Database::$sqlPrefix . 'unreadMessages'     => ['roomId' => ['\Fim\Room::encodeId', Type::blob, '\Fim\Room::decodeId']],
            \Fim\Database::$sqlPrefix . 'users'              => [
                'nameSearchable' => ['\Fim\DatabaseInstance::makeSearchable', Type::string, null],
                'defaultRoomId'  => ['\Fim\Room::encodeId', Type::blob, '\Fim\Room::decodeId'],
                'parentalFlags' => ['\Fim\Utilities::encodeList', Type::string, '\Fim\Utilities::decodeList']
            ],
        ], [
            \Fim\Database::$sqlPrefix . 'rooms' => [
                'name' => ['\Fim\DatabaseInstance::makeSearchable', Type::string, 'nameSearchable'],
                'id'   => ['\Fim\Room::encodeId', Type::blob, 'idEncoded'],
            ],
            \Fim\Database::$sqlPrefix . 'users' => [
                'name' => ['\Fim\DatabaseInstance::makeSearchable', Type::string, 'nameSearchable'],
            ],
        ], [
            \Fim\Database::$sqlPrefix . 'users' => 'id',
            \Fim\Database::$sqlPrefix . 'rooms' => 'id',
        ]);


        /* These manipulate how data is partitioned in a database. */
        $instance->setHardPartitions([
            \Fim\Database::$sqlPrefix . 'messages'    => ['roomId', 10],
            \Fim\Database::$sqlPrefix . 'accessFlood' => ['ip', 10],
        ]);
    }
}

