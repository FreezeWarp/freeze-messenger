<?php
// todo: upsert
use Database\DatabaseEngine;
use Database\Type\Comparison;

class databaseSQLTests1 extends databaseSQLTests {
    private $table = "test_1";

    private function recursive_sort_array($a) {
        if(!is_array($a))
            return $a;
        $a = array_map([$this, 'recursive_sort_array'], $a);
        sort($a);
        return array_values($a);
    }

    private function arrays_different($a, $b) {
        return $this->recursive_sort_array($a) === $this->recursive_sort_array($b);
    }

    public function __construct($databaseObj) {
        parent::__construct($databaseObj);

        startTable();
        printHeader('DatabaseSQL Test Suite 1');
        printHeader('Tests A Single Table For Everything');
        printRow("Create Table", $this->testCreateTable1());
        printRow("Insert, Bad Enum", $this->testInsertBadEnum());

        $this->databaseObj->startTransaction();
        printRow("Insert and Select", $this->testInsertSelect1());
        $this->databaseObj->rollbackTransaction();
        printRow("Transaction Rollback, Insert", $this->databaseObj->select($this->table, [
                "integerNormal" => 5,
            ])->getCount() === 0);

        $this->testInsertSelect1();

        printRow("Update, Bad Enum", $this->testUpdateBadEnum());

        $this->databaseObj->startTransaction();
        printRow("Update and Select", $this->testUpdateSelect1());

        $this->databaseObj->rollbackTransaction();
        $rollBackDiff = array_diff_assoc($this->databaseObj->select([$this->table => "integerNormal, enum, string"], [
            "integerNormal" => 5,
        ])->getAsArray(false), [
            "integerNormal" => 5,
            "enum" => 'd',
            "string" => '1234567890123456789012345678901234567890',
        ]);
        printRow("Transaction Rollback, Update", count($rollBackDiff) === 0 ? true : $rollBackDiff);

        $this->databaseObj->delete($this->table);
        printRow("Truncate", $this->databaseObj->select([$this->table => "integerNormal"])->getCount() === 0);
        printHeader('Advanced Select Sort/Etc. Tests:');
        $this->testInsertSelect2();

        $this->databaseObj->delete($this->table);
        printRow("Truncate", $this->databaseObj->select([$this->table => "integerNormal"])->getCount() === 0);
        printHeader('Advanced Select Query Tests:');
        $this->testInsertSelect3();

        printRow("Delete", $this->testDelete1());
        printRow("Delete Table", $this->testDeleteTable1());
        endTable();
    }


    public function testCreateTable1() {
        $this->databaseObj->createTable($this->table, "Used for unit testing.", DatabaseEngine::general, array(
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

            'bitfield' => [
                'type' => 'bitfield',
                'bits' => 10,
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

        return in_array($this->table, $this->getTestTables());
    }

    public function testInsertBadEnum() {
        $caught = false;
        try {
            $this->databaseObj->insert($this->table, [
                "enum" => 'ad',
            ]);
        } catch(Exception $e) {
            $caught = true;
        }

        return $caught;
    }

    public function testInsertBadString() {
        $caught = false;
        try {
            $this->databaseObj->insert($this->table, [
                "string" => "12345678901234567890123456789012345678901",
            ]);
        } catch(Exception $e) {
            $caught = true;
        }

        return $caught;
    }

    /**
     * The DAL does not guarantee the order the queue will run in, merely that all queries get run. Thus, we don't want to run order-dependant queries here.
     * @param $this->table
     * @return mixed
     */
    public function testQueueDelete1() {
        $this->databaseObj->insert($this->table, [
            "integerNormal" => 5,
            "enum" => 'a',
        ]);
        $this->databaseObj->insert($this->table, [
            "integerNormal" => 5,
            "enum" => 'af',
        ]);
        $this->databaseObj->insert($this->table, [
            "integerNormal" => 7,
            "enum" => 'a',
        ]);
        $this->databaseObj->insert($this->table, [
            "integerNormal" => 7,
            "enum" => 'd',
        ]);

        $this->databaseObj->autoQueue(true);
        $this->databaseObj->delete($this->table, [
            "enum" => 'd',
        ]);
        $this->databaseObj->delete($this->table, [
            "integerNormal" => 5,
            "enum" => 'af',
        ]);
        $this->databaseObj->autoQueue(false);

        return $this->databaseObj->select([$this->table => "integerNormal"])->getCount() === 2;
    }


    public function testQueueUpdate1() {
        $this->databaseObj->insert($this->table, [
            "integerNormal" => 5,
            "string" => "hi",
            "enum" => 'a',
        ]);
        $this->databaseObj->insert($this->table, [
            "integerNormal" => 5,
            "string" => "bye",
            "enum" => 'af',
        ]);
        $this->databaseObj->insert($this->table, [
            "integerNormal" => 7,
            "string" => "hi",
            "enum" => 'a',
        ]);
        $this->databaseObj->insert($this->table, [
            "integerNormal" => 7,
            "string" => "hi",
            "enum" => 'd',
        ]);

        $this->databaseObj->autoQueue(true);
        $this->databaseObj->update($this->table, [
            "string" => "bye",
        ], [
            "integerNormal" => 7,
        ]);
        $this->databaseObj->update($this->table, [
            "enum" => 'd',
        ], [
            "integerNormal" => 7,
        ]);
        $this->databaseObj->autoQueue(false);

        return $this->databaseObj->select([$this->table => "integerNormal"], [
            "string" => "bye",
            "integerNormal" => 7,
        ])->getCount() === 3;
    }

    public function testInsertSelect1() {
        $this->databaseObj->insert($this->table, [
            "integerNormal" => 5,
            "enum" => 'd',
            "string" => "1234567890123456789012345678901234567890",
        ]);

        $row = $this->databaseObj->select([$this->table => "integerNormal, integerAutoIncrement, enum, string"], [
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

    public function testInsertSelect2() {
        $datasets = [
            0 => [
                "integerNormal" => 5,
                "bitfield" => $this->databaseObj->bit(5),
                "string" => 12,
            ],

            1 => [
                "integerNormal" => 7,
                "bitfield" => $this->databaseObj->bit(7),
                "string" => "hello",
            ],

            2 => [
                "integerNormal" => 10,
                "bitfield" => $this->databaseObj->bit(10),
                "string" => "hell",
            ],

            3 => [
                "integerNormal" => 12,
                "bitfield" => $this->databaseObj->bit(12),
                "string" => "are you sure?",
            ],

            4 => [
                "integerNormal" => 12,
                "bitfield" => $this->databaseObj->bit(12),
                "string" => "本気ですか?",
            ]
        ];

        $resultsets = [];
        foreach ($datasets AS $setNum => $dataset) {
            $resultsets[$setNum] = $dataset;
            $resultsets[$setNum]['bitfield'] = $resultsets[$setNum]['bitfield']->value;
        }

        $this->databaseObj->insert($this->table, $datasets[0]);
        $this->databaseObj->insert($this->table, $datasets[1]);
        $this->databaseObj->insert($this->table, $datasets[2]);
        $this->databaseObj->insert($this->table, $datasets[3]);
        $this->databaseObj->insert($this->table, $datasets[4]);

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
            "SELECT * WHERE bitfield & 1",
            "SELECT * WHERE bitfield & 5",
            "SELECT * WHERE bitfield & 8",
            "SELECT * WHERE bitfield & 16",

            "SELECT * WHERE bitfield & 4 OR bitfield & 1",
            "SELECT * WHERE bitfield & 4 AND bitfield & 1",
            "SELECT * WHERE bitfield = 5",
            "SELECT * WHERE bitfield > 5",

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
            ["integerNormal" => $this->databaseObj->int(10, Comparison::greaterThan)],
            ["integerNormal" => $this->databaseObj->int(10, Comparison::greaterThanEquals)],
            ["integerNormal" => $this->databaseObj->int(10, Comparison::lessThanEquals)],
            ["integerNormal" => $this->databaseObj->int(10, Comparison::lessThan)],

            ["string" => 12],
            ["string" => "hello"],
            ["string" => "are you sure?"],
            ["string" => "本気ですか?"],
            ["string" => $this->databaseObj->search("hell")],

            ["string" => $this->databaseObj->in(["hell", "are you sure?", $this->databaseObj->str(12)])],
            ["bitfield" => $this->databaseObj->bit(1, Comparison::binaryAnd)],
            ["bitfield" => $this->databaseObj->bit(5, Comparison::binaryAnd)],
            ["bitfield" => $this->databaseObj->bit(8, Comparison::binaryAnd)],
            ["bitfield" => $this->databaseObj->bit(16, Comparison::binaryAnd)],

            ['either' => [
                ["bitfield" => $this->databaseObj->bit(4, Comparison::binaryAnd)],
                ["bitfield" => $this->databaseObj->bit(1, Comparison::binaryAnd)],
            ]],
            ['both' => [
                ["bitfield" => $this->databaseObj->bit(4, Comparison::binaryAnd)],
                ["bitfield" => $this->databaseObj->bit(1, Comparison::binaryAnd)],
            ]],
            ["bitfield" => $this->databaseObj->bit(5)],
            ["bitfield" => $this->databaseObj->bit(5, Comparison::greaterThan)],
            
            ['either' => [
                "integerNormal" => $this->databaseObj->int(12),
                "string" => $this->databaseObj->in(["hell", "are you sure?", $this->databaseObj->str(12)]),
            ]],
            [
                "integerNormal" => $this->databaseObj->int(12),
                "string" => $this->databaseObj->in(["hell", "are you sure?", $this->databaseObj->str(12)]),
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
            2,
            3,
            0,

            4,
            2,
            1,
            4,
            
            4,
            1,
            2,
        ];

        foreach ($testCases AS $i => $testCase) {
            $rows = $this->databaseObj->select([$this->table => "integerNormal, string, bitfield"], $testCase);

            printRow($testCaseDescriptions[$i], ($rows->getCount() === $testCaseExpectedRows[$i] ? true : $rows->getAsArray(true)), $rows->sourceQuery);
        }


        $testCaseDescriptions = [
            "SELECT * ORDER BY integerNormal ASC LIMIT 1",
            "SELECT * ORDER BY integerNormal ASC LIMIT 1 OFFSET 1",
            "SELECT * ORDER BY integerNormal ASC LIMIT 1 OFFSET 2",

            "SELECT * ORDER BY integerNormal DESC LIMIT 2",
            "SELECT * ORDER BY integerNormal DESC LIMIT 2 OFFSET 2",
            "SELECT * ORDER BY integerNormal DESC LIMIT 2 OFFSET 4",
        ];
        $testCasesSort = [
            ['integerNormal' => 'asc'],
            ['integerNormal' => 'asc'],
            ['integerNormal' => 'asc'],

            ['integerNormal' => 'desc'],
            ['integerNormal' => 'desc'],
            ['integerNormal' => 'desc'],
        ];
        $testCasesLimit = [
            1,
            1,
            1,

            2,
            2,
            2,
        ];
        $testCasesPage = [
            0,
            1,
            2,

            0,
            1,
            2,
        ];
        $testCaseResultsets = [
            [$resultsets[0]],
            [$resultsets[1]],
            [$resultsets[2]],

            [$resultsets[3], $resultsets[4]],
            [$resultsets[1], $resultsets[2]],
            [$resultsets[0]],
        ];

        foreach ($testCaseDescriptions AS $i => $testCaseDesc) {
            $query = $this->databaseObj->select([$this->table => "integerNormal, bitfield, string"], false, $testCasesSort[$i], $testCasesLimit[$i], $testCasesPage[$i]);
            $rows = $query->getAsArray(true);

            printRow($testCaseDescriptions[$i], (!$this->arrays_different($rows, $testCaseResultsets[$i]) ? true : $rows), $query->sourceQuery);
        }


        $testCaseDescriptions = [
            "DELETE WHERE integerNormal IN (3, 5, 7)",
            "DELETE WHERE string SEARCH 'e'",
            "DELETE WHERE bitfield & 8",
        ];

        $testCases = [
            ["integerNormal" => $this->databaseObj->in([3, 5, 7])],
            ["string" => $this->databaseObj->search('e')],
            ["bitfield" => $this->databaseObj->bit(8, Comparison::binaryAnd)],
        ];

        $testCaseExpectedRows = [
            3,
            1,
            0,
        ];

        foreach ($testCases AS $i => $testCase) {
            $this->databaseObj->delete($this->table, $testCase);
            $rows = $this->databaseObj->select([$this->table => "integerNormal"]);

            printRow($testCaseDescriptions[$i], ($rows->getCount() === $testCaseExpectedRows[$i] ? true : $rows->getAsArray(true)), $rows->sourceQuery);
        }
    }

    public function testInsertSelect3() {
        for ($i = 0; $i < 100; $i++) {
            $this->databaseObj->insert($this->table, [
                "integerNormal" => $i + 15,
                "integerDefault" => $i%45,
            ]);
        }

        $this->testInsertSelect3SortTest("Select Descending (15-114)", null, ["integerNormal" => "desc"], 114);
        $this->testInsertSelect3SortTest("Select Ascending (15-114)", null, ["integerNormal" => "asc"], 15);
        $this->testInsertSelect3SortTest("Select Ascending (15-114, >55)", ["integerNormal" => $this->databaseObj->int(55, Comparison::greaterThan)], ["integerNormal" => "asc"], 56);
        $this->testInsertSelect3SortTest("Select Ascending (15-114, <=45)", ["integerNormal" => $this->databaseObj->int(45, Comparison::lessThanEquals)], ["integerNormal" => "desc"], 45);

        $value = $this->databaseObj->select([$this->table => "integerNormal"], null, ["integerNormal" => "asc"], 11)->getCount();
        printRow("Select Limit 11", $value == 11, $value);
        $rows = $this->databaseObj->select([$this->table => "integerNormal, integerDefault"], null, ["integerDefault" => "desc", "integerNormal" => "asc"], 5)->getAsArray(true);
        printRow("Select i%45 Descending, i Ascending (i: 15-114)", $rows[2]["integerDefault"] == 43 && $rows[2]["integerNormal"] == 58 && $rows[3]["integerDefault"] == 43 && $rows[3]["integerNormal"] == 103, $rows);
    }

    public function testInsertSelect3SortTest($name, $conditionArray, $sortArray, $expectedValue) {
        $query = $this->databaseObj->select([$this->table => "integerNormal"], $conditionArray, $sortArray, 1);
        $value = $query->getColumnValue('integerNormal');

        printRow($name, ($value == $expectedValue ? true : $value), $query->sourceQuery);
    }

    public function testUpdateBadEnum() {
        $caught = false;
        try {
            $this->databaseObj->update($this->table, [
                "enum" => 'g',
            ]);
        } catch(Exception $e) {
            $caught = true;
        }

        return $caught;
    }

    public function testUpdateSelect1() {
        $this->databaseObj->update($this->table, [
            "string" => 15,
            "enum" => 'af',
        ], [
            "integerNormal" => 5
        ]);

        $row = $this->databaseObj->select([$this->table => "integerNormal, enum, string"], [
            "integerNormal" => 5,
        ])->getAsArray(false);

        $diff = array_diff_assoc($row, [
            "integerNormal" => 5,
            "enum" => 'af',
            "string" => '15',
        ]);

        return (count($diff) === 0 ? true : $diff);
    }

    public function testDelete1() {
        $this->databaseObj->delete($this->table, [
            "integerNormal" => 5
        ]);

        return $this->databaseObj->select($this->table, [
            "integerNormal" => 5,
        ])->getCount() === 0;
    }

    public function testDeleteTable1() {
        $this->databaseObj->deleteTable($this->table);
        return !in_array($this->table, $this->getTestTables());
    }
}