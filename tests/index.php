<?php
function startTable() {
    echo '<table>';
}
function endTable() {
    echo '</table>';
}
function printHeader($text) {
    echo "<tr><th colspan='3'>$text</th></tr>";
}
function printRow($name, $result = false, $other = null) {
    echo "<tr style='background-color: " . ($result === true ? '#7fff7f' : '#ff4f4f') . "'><td>$name</td><td>" . ($result === true ? 'pass' : print_r($result, true)) . "</td><td>" . print_r($other, true) . "</td></tr>";
}

/**
 * This is a very basic suite of tests that will attempt to verify that the core parts of the database are working correctly.
 * As many functionalities are handled low-level by the database (e.g. table engine, partition, etc.), and the DAL doesn't implement a way to retrieve table properties, these are untested.
 */
class databaseSQLTests
{
    /**
     * @var database
     */
    private $databaseObj;

    public function __construct($databaseObj) {
        $this->databaseObj = $databaseObj;
    }


    public function getTestTables() {
        return array_filter($this->databaseObj->getTablesAsArray(), function($v) {
            return substr($v, 0, 5) === 'test_';
        });
    }

    public function testSuite1() {
        $table = "test_1";


        startTable();
        printHeader('DatabaseSQL Test Suite 1');
        printRow("Create Table Test", $this->testCreateTable1($table));
        printRow("Insert, Bad Enum Test", $this->testInsertBadEnum($table));

        $this->databaseObj->startTransaction();
        printRow("Insert and Select Test", $this->testInsertSelect1($table));
        $this->databaseObj->rollbackTransaction();
        printRow("Transaction Rollback, Insert Test", $this->databaseObj->select($table, [
            "integerNormal" => 5,
        ])->getCount() === 0);

        $this->testInsertSelect1($table);

        printRow("Update, Bad Enum Test", $this->testUpdateBadEnum($table));

        $this->databaseObj->startTransaction();
        printRow("Update and Select Test", $this->testUpdateSelect1($table));

        $this->databaseObj->rollbackTransaction();
        $rollBackDiff = array_diff_assoc($this->databaseObj->select([$table => "integerNormal, enum, string"], [
            "integerNormal" => 5,
        ])->getAsArray(false), [
            "integerNormal" => 5,
            "enum" => 10,
            "string" => '1234567890123456789012345678901234567890',
        ]);
        printRow("Transaction Rollback, Update Test", count($rollBackDiff) === 0 ? true : $rollBackDiff);

        $this->databaseObj->delete($table);
        printRow("Truncate Test", $this->databaseObj->select([$table => "integerNormal"])->getCount() === 0);

        printRow("Insert->Queue Delete Test", $this->testQueueDelete1($table));

        $this->databaseObj->delete($table);
        printRow("Truncate Test", $this->databaseObj->select([$table => "integerNormal"])->getCount() === 0);

        printRow("Insert->Queue Update Test", $this->testQueueDelete1($table));

        $this->databaseObj->delete($table);
        printRow("Truncate Test", $this->databaseObj->select([$table => "integerNormal"])->getCount() === 0);

        printHeader('Advanced Select Tests:');
        $this->testInsertSelect2($table);

        printRow("Delete Test", $this->testDelete1($table));
        printRow("Delete Table Test", $this->testDeleteTable1($table));
        endTable();
    }


    //$tableName, $tableComment, $engine, $tableColumns, $tableIndexes, $partitionColumn = false
    public function testCreateTable1($table) {
        $this->databaseObj->createTable($table, "Used for unit testing.", "general", array(
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
                'restrict' => [3, 10, 50],
            ],

            'string' => [
                'type' => 'string',
                'maxlen' => 40,
            ]
        ));

        return in_array("test_1", $this->getTestTables());
    }

    public function testInsertBadEnum($table) {
        $caught = false;
        try {
            $this->databaseObj->insert($table, [
                "enum" => 7,
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
            "enum" => 3,
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 5,
            "enum" => 50,
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 7,
            "enum" => 3,
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 7,
            "enum" => 10,
        ]);

        $this->databaseObj->autoQueue(true);
        $this->databaseObj->delete($table, [
            "enum" => 10,
        ]);
        $this->databaseObj->delete($table, [
            "integerNormal" => 5,
            "enum" => 50,
        ]);
        $this->databaseObj->autoQueue(false);

        return $this->databaseObj->select([$table => "integerNormal"])->getCount() === 2;
    }


    public function testQueueUpdate1($table) {
        $this->databaseObj->insert($table, [
            "integerNormal" => 5,
            "string" => "hi",
            "enum" => 3,
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 5,
            "string" => "bye",
            "enum" => 50,
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 7,
            "string" => "hi",
            "enum" => 3,
        ]);
        $this->databaseObj->insert($table, [
            "integerNormal" => 7,
            "string" => "hi",
            "enum" => 10,
        ]);

        $this->databaseObj->autoQueue(true);
        $this->databaseObj->update($table, [
            "string" => "bye",
        ], [
            "integerNormal" => 7,
        ]);
        $this->databaseObj->update($table, [
            "enum" => 10,
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
            "enum" => 10,
            "string" => "1234567890123456789012345678901234567890",
        ]);

        $row = $this->databaseObj->select([$table => "integerNormal, integerAutoIncrement, enum, string"], [
            "integerNormal" => 5,
        ])->getAsArray(false);

        $diff = array_diff_assoc($row, [
            "integerNormal" => 5,
            "enum" => 10,
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

            "SELECT * WHERE integerNormal & 4 OR integerNormal & 5",
            "SELECT * WHERE integerNormal & 4 AND integerNormal & 5",
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

            printRow("Testing " . $testCaseDescriptions[$i], ($rows->getCount() === $testCaseExpectedRows[$i] ? true : $rows->getAsArray(true)), $rows->sourceQuery);
        }

    }

    public function testUpdateBadEnum($table) {
        $caught = false;
        try {
            $this->databaseObj->insert($table, [
                "enum" => 7,
            ]);
        } catch(Exception $e) {
            $caught = true;
        }

        return $caught;
    }

    public function testUpdateSelect1($table) {
        $this->databaseObj->update($table, [
            "string" => 15,
            "enum" => 50,
        ], [
            "integerNormal" => 5
        ]);

        $row = $this->databaseObj->select([$table => "integerNormal, enum, string"], [
            "integerNormal" => 5,
        ])->getAsArray(false);

        $diff = array_diff_assoc($row, [
            "integerNormal" => 5,
            "enum" => 50,
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
        return !in_array("test_1", $this->getTestTables());
    }
}


echo "Requiring Core Classes...<br />";
require_once('../functions/fim_user.php');
require_once('../functions/fim_room.php');

echo "Requiring Core Configuration...<br />";
require_once('../config.php');
require_once('../defaultConfig.php');
$defaultConfig['dev'] = true;

echo "Requiring Database Files...<br />";
require_once('../functions/database.php');
require_once('../functions/databaseSQL.php');

echo "Creating Object...<br />";
$database = new databaseSQL();

echo "Performing Database Connection...<br />";
$database->connect($dbConnect['core']['host'],
    $dbConnect['core']['port'],
    $dbConnect['core']['username'],
    $dbConnect['core']['password'],
    $dbConnect['core']['database'],
    $dbConnect['core']['driver'],
    $dbConfig['vanilla']['tablePrefix']);
$databaseTests = new databaseSQLTests($database);

echo "Checking For Existing Test Tables...<br />";
$tables = $databaseTests->getTestTables();

if (count($tables) > 0) {
    echo "Existing Test Tables Found: " . implode(',', $tables) . "<br />";
    echo "Deleting Existing Test Tables...<br />";
    foreach ($tables AS $table) {
        $database->deleteTable($table);
    }

    echo "Rechecking...<br />";
    $tables = $databaseTests->getTestTables();

    if (count($tables) > 0) {
        die("Test Tables Were Not Successfully Removed. Exiting.");
    }
}

$databaseTests->testSuite1();