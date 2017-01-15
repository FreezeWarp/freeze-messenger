<?php
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

        echo "Table Creation Test: ", $this->testCreateTable1($table), "\n";

        echo "Insert, Bad Enum Test: ", $this->testInsertBadEnum($table), "\n";

        $this->databaseObj->startTransaction();
        echo "Insert and Select Test: ", print_r($this->testInsertSelect1($table), true), "\n";
        $this->databaseObj->rollbackTransaction();
        echo "Transaction Rollback, Insert Test: ", $this->databaseObj->select($table, [
            "integerNormal" => 5,
        ])->getCount() === 0, "\n";

        $this->testInsertSelect1($table);

        echo "Update, Bad Enum Test: ", $this->testUpdateBadEnum($table), "\n";

        $this->databaseObj->startTransaction();
        echo "Update and Select Test: ", print_r($this->testUpdateSelect1($table), true), "\n";
        $this->databaseObj->rollbackTransaction();
        $rollBackDiff = array_diff_assoc($this->databaseObj->select([$table => "integerNormal, enum, string"], [
            "integerNormal" => 5,
        ])->getAsArray(false), [
            "integerNormal" => 5,
            "enum" => 10,
            "string" => '12',
        ]);

        echo "Transaction Rollback, Update Test: ", count($rollBackDiff) === 0 ? true : print_r($rollBackDiff, true), "\n";

        echo "Delete Test: ", $this->testDelete1($table), "\n";

        echo "Advanced Select Tests:\n";
        $this->testInsertSelect2($table);

        echo "Delete Table Test: ", $this->testDeleteTable1($table), "\n";
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

    public function testInsertSelect1($table) {
        $this->databaseObj->insert($table, [
            "integerNormal" => 5,
            "enum" => 10,
            "string" => 12,
        ]);

        $row = $this->databaseObj->select([$table => "integerNormal, integerAutoIncrement, enum, string"], [
            "integerNormal" => 5,
        ])->getAsArray(false);

        $diff = array_diff_assoc($row, [
            "integerNormal" => 5,
            "enum" => 10,
            "string" => '12',
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
        ];

        foreach ($testCases AS $i => $testCase) {
            $rows = $this->databaseObj->select([$table => "integerNormal, string"], $testCase);

            echo "Testing ", $testCaseDescriptions[$i], ": ", ($rows->getCount() === $testCaseExpectedRows[$i] ? "pass" : $rows->sourceQuery . print_r($rows->getAsArray(true), true)), "\n";
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


header('Content-Type: text/plain');
echo "Requiring Core Classes...\n";
require_once('../functions/fim_user.php');
require_once('../functions/fim_room.php');

echo "Requiring Core Configuration...\n";
require_once('../config.php');
require_once('../defaultConfig.php');
$defaultConfig['dev'] = true;

echo "Requiring Database Files...\n";
require_once('../functions/database.php');
require_once('../functions/databaseSQL.php');

echo "Creating Object...\n";
$database = new databaseSQL();

echo "Performing Database Connection...\n";
$database->connect($dbConnect['core']['host'],
    $dbConnect['core']['port'],
    $dbConnect['core']['username'],
    $dbConnect['core']['password'],
    $dbConnect['core']['database'],
    $dbConnect['core']['driver'],
    $dbConfig['vanilla']['tablePrefix']);
$databaseTests = new databaseSQLTests($database);

echo "Checking For Existing Test Tables...\n";
$tables = $databaseTests->getTestTables();

if (count($tables) > 0) {
    echo "Existing Test Tables Found: " . implode(',', $tables) . "\n";
    echo "Deleting Existing Test Tables...\n";
    foreach ($tables AS $table) {
        $database->deleteTable($table);
    }

    echo "Rechecking...\n";
    $tables = $databaseTests->getTestTables();

    if (count($tables) > 0) {
        die("Test Tables Were Not Successfully Removed. Exiting.");
    }
}

$databaseTests->testSuite1();