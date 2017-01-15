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
function printCompRow($name, $result, $expectedResult) {
    printRow($name, $result == $expectedResult ? true : $result, $expectedResult);
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
    protected $databaseObj;


    public function truncate($table) {
        $this->databaseObj->delete($table);
    }

    public function __construct($databaseObj) {
        $this->databaseObj = $databaseObj;
    }


    public function getTestTables() {
        return array_filter($this->databaseObj->getTablesAsArray(), function($v) {
            return substr($v, 0, 5) === 'test_';
        });
    }
}


echo "Requiring Core Classes...<br />";
require_once('../functions/fim_user.php');
require_once('../functions/fim_room.php');

echo "Requiring Core Configuration...<br />";
require_once('../config.php');
require_once('../defaultConfig.php');
$config = $defaultConfig;
$config['dev'] = true;

echo "Requiring Database Files...<br />";
require_once('../functions/database.php');
require_once('../functions/databaseSQL.php');
require_once('../functions/fim_database.php');

echo "Requiring Test Suites...<br />";
require_once('./DatabaseSQL1.php');
require_once('./DatabaseSQL2.php');

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

echo "Running DatabaseSQL Test Suite 1...<br />";
$databaseTests = new databaseSQLTests1($database);

echo "Running DatabaseSQL Test Suite 2...<br />";
$databaseTests = new databaseSQLTests2($database);