<?php

use Database\DatabaseEngine;
use Database\DatabaseTypeType;

class databaseSQLTests2 extends databaseSQLTests {
    public function passthru($value) {
        return bin2hex($value);
    }

    public function enableEncodeOnly($table) {
        $this->databaseObj->setTransformationParameters([
            $table => [
                'roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, [$this, 'passthru']],
                'list' => ['fimDatabase::packList', DatabaseTypeType::blob, [$this, 'passthru']],
            ]
        ], [
            $table => [
                'roomName' => [[$this->databaseObj, 'makeSearchable'], false, 'roomNameSearchable']
            ],
        ], [
            $table => 'id',
        ]);
    }

    public function enableEncodeDecode($table) {
        $this->databaseObj->setTransformationParameters([
            $table => [
                'roomId' => ['fimRoom::encodeId', DatabaseTypeType::blob, 'fimRoom::decodeId'],
                'list' => ['fimDatabase::packList', DatabaseTypeType::blob, 'fimDatabase::unpackList'],
            ]
        ], [
            $table => [
                'roomName' => [[$this->databaseObj, 'makeSearchable'], false, 'roomNameSearchable']
            ],
        ], [
            $table => 'id',
        ]);
    }

    public function __construct($databaseObj) {
        parent::__construct($databaseObj);

        $table = "test_2";

        startTable();
        printHeader('DatabaseSQL Test Suite 2');
        printHeader('Tests Dynamic Data Conversion');
        printRow("Create Table", $this->testCreateTable1($table));

        $this->enableEncodeOnly($table);

        $this->databaseObj->insert($table, [
            "id" => 1,
            "roomId" => 20,
            "roomName" => "himom",
            "list" => [20, 100, 50],
        ]);
        $this->databaseObj->insert($table, [
            "id" => 2,
            "roomId" => "p5,8,20",
            "roomName" => "hi.mom! are you there?//",
            "list" => [1000, 55, 11100, 99, 2, 33],
        ]);
        $this->databaseObj->insert($table, [
            "id" => 3,
            "roomId" => "o5,8,20",
            "roomName" => "café＠5²",
        ]);

        // Lists, No Decode
        printCompRow("Insert List without Decode", $this->databaseObj->select([$table => "id, list"], [
            "id" => 1,
        ])->getAsArray(false)["list"], "ff15f6af35ff");

        printCompRow("Insert List without Decode", $this->databaseObj->select([$table => "id, list"], [
            "id" => 2,
        ])->getAsArray(false)["list"], "ff46af3af3450f69f2f23ff0");

        // RoomIDs, No Decode
        printCompRow("Insert RoomID Without Decode", $this->databaseObj->select([$table => "id, roomId"], [
            "id" => 1,
        ])->getAsArray(false)["roomId"], "15f0");

        printCompRow("Insert RoomID Without Decode", $this->databaseObj->select([$table => "id, roomId"], [
            "id" => 2,
        ])->getAsArray(false)["roomId"], "f5f8f15f");

        printCompRow("Insert RoomID Without Decode", $this->databaseObj->select([$table => "id, roomId"], [
            "id" => 3,
        ])->getAsArray(false)["roomId"], "ff5f8f15f0");


        // Lists, Decode
        $this->enableEncodeDecode($table);
        printCompRow("Insert List and Decode", $this->databaseObj->select([$table => "id, list"], [
            "id" => 1,
        ])->getAsArray(false)["list"], [20, 100, 50]);

        printCompRow("Insert List and Decode", $this->databaseObj->select([$table => "id, list"], [
            "id" => 2,
        ])->getAsArray(false)["list"], [1000, 55, 11100, 99, 2, 33]);

        // RoomIDs, Decode
        printCompRow("Insert RoomID and Decode", $this->databaseObj->select([$table => "id, roomId"], [
            "id" => 1,
        ])->getAsArray(false)["roomId"], 20);

        printCompRow("Insert RoomID and Decode", $this->databaseObj->select([$table => "id, roomId"], [
            "id" => 2,
        ])->getAsArray(false)["roomId"], "p5,8,20");

        printCompRow("Insert RoomID and Decode", $this->databaseObj->select([$table => "id, roomId"], [
            "id" => 3,
        ])->getAsArray(false)["roomId"], "o5,8,20");

        printCompRow("Insert RoomName, Get Searchable", $this->databaseObj->select([$table => "id, roomNameSearchable"], [
            "id" => 1,
        ])->getAsArray(false)["roomNameSearchable"], "himom");

        printCompRow("Insert RoomName, Get Searchable", $this->databaseObj->select([$table => "id, roomNameSearchable"], [
            "id" => 2,
        ])->getAsArray(false)["roomNameSearchable"], "hi mom are you there");

        printCompRow("Insert RoomName, Get Searchable", $this->databaseObj->select([$table => "id, roomNameSearchable"], [
            "id" => 3,
        ])->getAsArray(false)["roomNameSearchable"], "cafe");


        printRow("Delete Table", $this->testCreateTable1($table));
        endTable();
    }


    public function testCreateTable1($table) {
        $this->databaseObj->createTable($table, "", DatabaseEngine::general, array(
            'id' => [
                'type' => 'int',
                'maxlen' => 2,
            ],

            'roomId' => [
                'type' => 'blob',
                'maxlen' => 23,
            ],

            'roomName' => [
                'type' => 'string',
                'maxlen' => 100,
            ],

            'roomNameSearchable' => [
                'type' => 'string',
                'maxlen' => 100,
            ],

            'list' => [
                'type' => 'blob',
                'maxlen' => 1000,
            ],
        ));

        return in_array($table, $this->getTestTables());
    }

    public function testDeleteTable1($table) {
        $this->databaseObj->deleteTable($table);

        return !in_array($table, $this->getTestTables());
    }
}