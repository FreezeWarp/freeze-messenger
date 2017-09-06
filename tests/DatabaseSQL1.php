<?php
class databaseSQLTests1 extends databaseSQLTests {
    public function __construct($databaseObj) {
        parent::__construct($databaseObj);

        $table = "test_1";

        startTable();
        printHeader('DatabaseSQL Test Suite 1');
        printHeader('Tests A Single Table For Everything');
        printRow("Create Table", $this->testCreateTable1($table));
        printRow("Insert, Bad Enum", $this->testInsertBadEnum($table));

        $this->databaseObj->startTransaction();
        printRow("Insert and Select", $this->testInsertSelect1($table));
        $this->databaseObj->rollbackTransaction();
        printRow("Transaction Rollback, Insert", $this->databaseObj->select($table, [
                "integerNormal" => 5,
            ])->getCount() === 0);

        $this->testInsertSelect1($table);

        printRow("Update, Bad Enum", $this->testUpdateBadEnum($table));

        $this->databaseObj->startTransaction();
        printRow("Update and Select", $this->testUpdateSelect1($table));

        $this->databaseObj->rollbackTransaction();
        $rollBackDiff = array_diff_assoc($this->databaseObj->select([$table => "integerNormal, enum, string"], [
            "integerNormal" => 5,
        ])->getAsArray(false), [
            "integerNormal" => 5,
            "enum" => 'd',
            "string" => '1234567890123456789012345678901234567890',
        ]);
        printRow("Transaction Rollback, Update", count($rollBackDiff) === 0 ? true : $rollBackDiff);

        $this->databaseObj->delete($table);
        printRow("Truncate", $this->databaseObj->select([$table => "integerNormal"])->getCount() === 0);
        printHeader('Advanced Select Sort/Etc. Tests:');
        $this->testInsertSelect2($table);

        $this->databaseObj->delete($table);
        printRow("Truncate", $this->databaseObj->select([$table => "integerNormal"])->getCount() === 0);
        printHeader('Advanced Select Query Tests:');
        $this->testInsertSelect3($table);

        printRow("Delete", $this->testDelete1($table));
        printRow("Delete Table", $this->testDeleteTable1($table));
        endTable();
    }


    public function testCreateTable1($table) {
        $this->databaseObj->createTable($table, "Used for unit testing.", DatabaseEngine::general, array(
            'integerNormal' => [
                'type' => 'int',
            ],

            'integerAutoIncrement' => [
                'type' => 'int',
                'autoincrement' => true,
            ],

            'integerDefault' => [
                'type' => 'int',
                'default' => 2000,
            ],

            'enum' => [
                'type' => 'enum',
                'restrict' => ['a', 'd', 'af'],
            ],

            'string' => [
                'type' => 'string',
                'maxlen' => 40,
            ]
        ));

        return in_array($table, $this->getTestTables());
    }

    public function testInsertBadEnum($table) {
        $caught = false;
        try {
            $this->databaseObj->insert($table, [
                "enum" => '7',
            ]);
        } catch(Exception $e) {
            $caught = true;
        }

        return $caught;
    }

    public function testInsertBadString($table) {
        $caught = false;
        try {
            $this->databaseObj->insert($table, [
                "string" => "12345678901234567890123456789012345678901",
            ]);
        } catch(Exception $e) {
            $caught = true;
        }

        return $caught;
    }

    /**
     * The DAL does not guarantee the order the queue will run in, merely that all queries get run. Thus, we don't want to run order-dependant queries here.
     * @param $table
     * @return mixed
     */
    public function testQueueDelete1($table) {
        $this->databaseObj->insert($table, [
            "integerNormal" => 5,
            "enum" => 'a',
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 5,
            "enum" => 'af',
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 7,
            "enum" => 'a',
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 7,
            "enum" => 'd',
        ]);

        $this->databaseObj->autoQueue(true);
        $this->databaseObj->delete($table, [
            "enum" => 'd',
        ]);
        $this->databaseObj->delete($table, [
            "integerNormal" => 5,
            "enum" => 'af',
        ]);
        $this->databaseObj->autoQueue(false);

        return $this->databaseObj->select([$table => "integerNormal"])->getCount() === 2;
    }


    public function testQueueUpdate1($table) {
        $this->databaseObj->insert($table, [
            "integerNormal" => 5,
            "string" => "hi",
            "enum" => 'a',
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 5,
            "string" => "bye",
            "enum" => 'af',
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 7,
            "string" => "hi",
            "enum" => 'a',
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 7,
            "string" => "hi",
            "enum" => 'd',
        ]);

        $this->databaseObj->autoQueue(true);
        $this->databaseObj->update($table, [
            "string" => "bye",
        ], [
            "integerNormal" => 7,
        ]);
        $this->databaseObj->update($table, [
            "enum" => 'd',
        ], [
            "integerNormal" => 7,
        ]);
        $this->databaseObj->autoQueue(false);

        return $this->databaseObj->select([$table => "integerNormal"], [
            "string" => "bye",
            "integerNormal" => 7,
        ])->getCount() === 3;
    }

    public function testInsertSelect1($table) {
        $this->databaseObj->insert($table, [
            "integerNormal" => 5,
            "enum" => 'd',
            "string" => "1234567890123456789012345678901234567890",
        ]);

        $row = $this->databaseObj->select([$table => "integerNormal, integerAutoIncrement, enum, string"], [
            "integerNormal" => 5,
        ])->getAsArray(false);

        $diff = array_diff_assoc($row, [
            "integerNormal" => 5,
            "enum" => 'd',
            "string" => '1234567890123456789012345678901234567890',
            "integerAutoIncrement" => 1,
        ]);

        return (count($diff) === 0 ? true : $diff);
    }

    public function testInsertSelect2($table) {
        $this->databaseObj->insert($table, [
            "integerNormal" => 5,
            "string" => 12,
        ]);

        $this->databaseObj->insert($table, [
            "integerNormal" => 7,
            "string" => "hello",
        ]);

        $this->databaseObj->insert($table, [
            "integerNormal" => 10,
            "string" => "hell",
        ]);

        $this->databaseObj->insert($table, [
            "integerNormal" => 12,
            "string" => "are you sure?",
        ]);

        $this->databaseObj->insert($table, [
            "integerNormal" => 12,
            "string" => "本気ですか?",
        ]);

        $testCaseDescriptions = [
            "SELECT *",
            "SELECT * WHERE integerNormal = 5",
            "SELECT * WHERE integerNormal = 12",
            "SELECT * WHERE integerNormal IN (5,7,19)",
            "SELECT * WHERE integerNormal IN (5,7)",

            "SELECT * WHERE integerNormal IN (5,12)",
            "SELECT * WHERE integerNormal > 10",
            "SELECT * WHERE integerNormal >= 10",
            "SELECT * WHERE integerNormal <= 10",
            "SELECT * WHERE integerNormal < 10",

            "SELECT * WHERE string = 12",
            "SELECT * WHERE string = 'hello'",
            "SELECT * WHERE string = 'are you sure?'",
            "SELECT * WHERE string = '本気ですか?'",
            "SELECT * WHERE string SEARCH 'hell'",

            "SELECT * WHERE string IN ('hell', 'are you sure?', 12)",
            "SELECT * WHERE integerNormal & 1",
            "SELECT * WHERE integerNormal & 5",
            "SELECT * WHERE integerNormal & 8",
            "SELECT * WHERE integerNormal & 16",

            "SELECT * WHERE integerNormal & 4 OR integerNormal & 1",
            "SELECT * WHERE integerNormal & 4 AND integerNormal & 1",
            "SELECT * WHERE string IN ('hell', 'are you sure?', 12) OR integerNormal = 12",
            "SELECT * WHERE string IN ('hell', 'are you sure?', 12) AND integerNormal = 12",
            "SELECT * WHERE (string = 'are you sure?' AND integerNormal = 12) OR integerNormal = 5",
        ];

        $testCases = [
            false,
            ["integerNormal" => 5],
            ["integerNormal" => 12],
            ["integerNormal" => $this->databaseObj->in([5, 7])],
            ["integerNormal" => $this->databaseObj->in([5, 7, 19])],

            ["integerNormal" => $this->databaseObj->in([5, 12])],
            ["integerNormal" => $this->databaseObj->int(10, 'gt')],
            ["integerNormal" => $this->databaseObj->int(10, 'gte')],
            ["integerNormal" => $this->databaseObj->int(10, 'lte')],
            ["integerNormal" => $this->databaseObj->int(10, 'lt')],

            ["string" => 12],
            ["string" => "hello"],
            ["string" => "are you sure?"],
            ["string" => "本気ですか?"],
            ["string" => $this->databaseObj->search("hell")],

            ["string" => $this->databaseObj->in(["hell", "are you sure?", 12])],
            ["integerNormal" => $this->databaseObj->int(1, 'bAnd')],
            ["integerNormal" => $this->databaseObj->int(5, 'bAnd')],
            ["integerNormal" => $this->databaseObj->int(8, 'bAnd')],
            ["integerNormal" => $this->databaseObj->int(16, 'bAnd')],

            ['either' => [
                ["integerNormal" => $this->databaseObj->int(4, 'bAnd')],
                ["integerNormal" => $this->databaseObj->int(1, 'bAnd')],
            ]],
            ['both' => [
                ["integerNormal" => $this->databaseObj->int(4, 'bAnd')],
                ["integerNormal" => $this->databaseObj->int(1, 'bAnd')],
            ]],
            ['either' => [
                "integerNormal" => $this->databaseObj->int(12),
                "string" => $this->databaseObj->in(["hell", "are you sure?", 12]),
            ]],
            [
                "integerNormal" => $this->databaseObj->int(12),
                "string" => $this->databaseObj->in(["hell", "are you sure?", 12]),
            ],
            ['either' => [
                "integerNormal" => $this->databaseObj->int(5),
                "both" => [
                    "string" => "are you sure?",
                    "integerNormal" => $this->databaseObj->int(12),
                ]
            ]],
        ];

        $testCaseExpectedRows = [
            5,
            1,
            2,
            2,
            2,

            3,
            2,
            3,
            3,
            2,

            1,
            1,
            1,
            1,
            2,

            3,
            2,
            4,
            3,
            0,

            4,
            2,
            4,
            1,
            2,
        ];

        foreach ($testCases AS $i => $testCase) {
            $rows = $this->databaseObj->select([$table => "integerNormal, string"], $testCase);

            printRow($testCaseDescriptions[$i], ($rows->getCount() === $testCaseExpectedRows[$i] ? true : $rows->getAsArray(true)), $rows->sourceQuery);
        }


        $testCaseDescriptions = [
            "DELETE WHERE integerNormal IN (3, 5, 7)",
            "DELETE WHERE string SEARCH 'e'",
            "DELETE WHERE integerNormal & 8",
        ];

        $testCases = [
            ["integerNormal" => $this->databaseObj->in([3, 5, 7])],
            ["string" => $this->databaseObj->search('e')],
            ["integerNormal" => $this->databaseObj->int(8, 'bAnd')],
        ];

        $testCaseExpectedRows = [
            3,
            1,
            0,
        ];

        foreach ($testCases AS $i => $testCase) {
            $this->databaseObj->delete($table, $testCase);
            $rows = $this->databaseObj->select([$table => "integerNormal"]);

            printRow($testCaseDescriptions[$i], ($rows->getCount() === $testCaseExpectedRows[$i] ? true : $rows->getAsArray(true)), $rows->sourceQuery);
        }
    }

    public function testInsertSelect3($table) {
        for ($i = 0; $i < 100; $i++) {
            $this->databaseObj->insert($table, [
                "integerNormal" => $i + 15,
                "integerDefault" => $i%45,
            ]);
        }

        $value = $this->databaseObj->select([$table => "integerNormal"], null, ["integerNormal" => "desc"], 1)->getColumnValue('integerNormal');
        printRow("Select Descending (15-114)", $value == 114, $value);

        $value = $this->databaseObj->select([$table => "integerNormal"], null, ["integerNormal" => "asc"], 1)->getColumnValue('integerNormal');
        printRow("Select Ascending (15-114)", $value == 15, $value);

        $value = $this->databaseObj->select([$table => "integerNormal"], ["integerNormal" => $this->databaseObj->int(55, "gt")], ["integerNormal" => "asc"], 1)->getColumnValue('integerNormal');
        printRow("Select Ascending (15-114, >55)", $value == 56, $value);

        $value = $this->databaseObj->select([$table => "integerNormal"], ["integerNormal" => $this->databaseObj->int(45, "lte")], ["integerNormal" => "desc"], 1)->getColumnValue('integerNormal');
        printRow("Select Ascending (15-114, <=45)", $value == 45, $value);

        $rows = $this->databaseObj->select([$table => "integerNormal, integerDefault"], null, ["integerDefault" => "desc", "integerNormal" => "asc"], 5)->getAsArray(true);
        printRow("Select i%45 Descending, i Ascending (i: 15-114)", $rows[2]["integerDefault"] == 43 && $rows[2]["integerNormal"] == 58 && $rows[3]["integerDefault"] == 43 && $rows[3]["integerNormal"] == 103, $rows);

        $value = $this->databaseObj->select([$table => "integerNormal"], null, false, 11)->getCount();
        printRow("Select Limit 11", $value == 11, $value);
    }

    public function testUpdateBadEnum($table) {
        $caught = false;
        try {
            $this->databaseObj->insert($table, [
                "enum" => '7',
            ]);
        } catch(Exception $e) {
            $caught = true;
        }

        return $caught;
    }

    public function testUpdateSelect1($table) {
        $this->databaseObj->update($table, [
            "string" => 15,
            "enum" => 'af',
        ], [
            "integerNormal" => 5
        ]);

        $row = $this->databaseObj->select([$table => "integerNormal, enum, string"], [
            "integerNormal" => 5,
        ])->getAsArray(false);

        $diff = array_diff_assoc($row, [
            "integerNormal" => 5,
            "enum" => 'af',
            "string" => '15',
        ]);

        return (count($diff) === 0 ? true : $diff);
    }

    public function testDelete1($table) {
        $this->databaseObj->delete($table, [
            "integerNormal" => 5
        ]);

        return $this->databaseObj->select($table, [
            "integerNormal" => 5,
        ])->getCount() === 0;
    }

    public function testDeleteTable1($table) {
        $this->databaseObj->deleteTable($table);
        return !in_array($table, $this->getTestTables());
    }
}