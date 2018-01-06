<?php

namespace Fim;

use Database\Type\Type;

class DatabaseParameters
{
    public static function execute(DatabaseInstance $instance)
    {
        $instance->setTransformationParameters([
            \Fim\Database::$sqlPrefix . 'files'              => ['roomIdLink' => ['Fim\fimRoom::encodeId', Type::blob, 'Fim\fimRoom::decodeId']],
            \Fim\Database::$sqlPrefix . 'messages'           => ['roomId' => ['Fim\fimRoom::encodeId', Type::blob, 'Fim\fimRoom::decodeId']],
            \Fim\Database::$sqlPrefix . 'messageFlood'       => ['roomId' => ['Fim\fimRoom::encodeId', Type::blob, 'Fim\fimRoom::decodeId']],
            \Fim\Database::$sqlPrefix . 'messageEditHistory' => ['roomId' => ['Fim\fimRoom::encodeId', Type::blob, 'Fim\fimRoom::decodeId']],
            \Fim\Database::$sqlPrefix . 'ping'               => ['roomId' => ['Fim\fimRoom::encodeId', Type::blob, 'Fim\fimRoom::decodeId']],
            \Fim\Database::$sqlPrefix . 'rooms'              => [
                'nameSearchable' => ['\Fim\DatabaseInstance::makeSearchable', Type::string, null],
            ],
            \Fim\Database::$sqlPrefix . 'roomStats'          => ['roomId' => ['Fim\fimRoom::encodeId', Type::blob, 'Fim\fimRoom::decodeId']],
            \Fim\Database::$sqlPrefix . 'socialGroups'       => [
                'memberUserIds' => ['\Fim\DatabaseInstance::packListCache', Type::blob, '\Fim\DatabaseInstance::unpackListCache']
            ],
            \Fim\Database::$sqlPrefix . 'searchMessages'     => ['roomId' => ['Fim\fimRoom::encodeId', Type::blob, 'Fim\fimRoom::decodeId']],
            \Fim\Database::$sqlPrefix . 'searchCache'        => ['roomId' => ['Fim\fimRoom::encodeId', Type::blob, 'Fim\fimRoom::decodeId']],
            \Fim\Database::$sqlPrefix . 'unreadMessages'     => ['roomId' => ['Fim\fimRoom::encodeId', Type::blob, 'Fim\fimRoom::decodeId']],
            \Fim\Database::$sqlPrefix . 'users'              => [
                'nameSearchable' => ['\Fim\DatabaseInstance::makeSearchable', Type::string, null],
                'defaultRoomId'  => ['Fim\fimRoom::encodeId', Type::blob, 'Fim\fimRoom::decodeId'],
            ],
        ], [
            \Fim\Database::$sqlPrefix . 'rooms' => [
                'name' => ['\Fim\DatabaseInstance::makeSearchable', Type::string, 'nameSearchable'],
                'id'   => ['Fim\fimRoom::encodeId', Type::blob, 'idEncoded'],
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

?>