<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

error_reporting(E_ALL); // Report All Potential Errors
ini_set('display_errors', 1);

require('../functions/xml.php'); // For reading the db*.xml files
require('../functions/Database.php'); // DB Operations
require('../functions/DatabaseSQL.php'); // ""
require('../functions/fimDatabase.php'); // ""
require('../functions/fimUser.php'); // Creating Users
require('../functions/fimRoom.php'); // Transformation Parameters
require('../functions/fimConfig.php'); // Configuration
require('../functions/fimCache.php'); // Caching

// If possible, remove the execution time limits (often requires ~40-60 seconds). TODO: Long term, the install script should be split up into seperate HTTP requests.
if(!@ini_get('safe_mode')) {
    @ini_set('max_execution_time', 0);
    @set_time_limit(0);
}

if (file_exists('../config.php')) { // Make sure that config doesn't exist. TODO: Is this secure?
    die('Config file exists -- can\'t continue.');
}

switch ($_REQUEST['phase']) {
    case false: default:
    break;

    case 1: // Table Check
        // If tables do not exist, create them from the schema (dbSchema.xml).
        // If tables do exist, recreate if specified or leave alone. (TODO)

        $driver = urldecode($_GET['db_driver']);
        $host = urldecode($_GET['db_host']);
        $port = urldecode($_GET['db_port']);
        $userName = urldecode($_GET['db_userName']);
        $password = urldecode($_GET['db_password']);
        $databaseName = urldecode($_GET['db_database']);
        $createdb = isset($_GET['db_createdb']);
        $prefix = urldecode($_GET['db_tableprefix']);




        /* Part 1 : Connect to the Database, Create a New Database If Needed */

        $database = new fimDatabase();
        $database->setErrorLevel(E_USER_WARNING);

        try {
            $database->connect($host, $port, $userName, $password, $createdb ? false : $databaseName, $driver, $prefix);
        } catch (Exception $exception) {
            die($exception->getMessage());
        }

        $database->registerConfig(new fimConfig());


        if ($database->getLastError()) {
            die("Connection Error.\n" . $database->getLastError());
        }
        else {
            require('../databaseParameters.php');
            $database->loadVersion();

            if ($driver === 'mysql' || $driver === 'mysqli') {
                if ($database->versionPrimary <= 4) { // MySQL 4 is a no-go.
                    die('You have attempted to connect to a MySQL version ' . $database->version . ' database. MySQL 5.0.5+ is required for FreezeMessenger.');
                }
                elseif ($database->versionPrimary == 5 && $database->versionSecondary == 0 && $database->versionTertiary <= 4) { // MySQL 5.0.0-5.0.4 is also a no-g	o (we require the BIT type, even though in theory we could work without it)
                    die('You have attempted to connect to a MySQL version ' . $database->version . ' database. MySQL 5.0.5+ is required for FreezeMessenger.');
                }
                elseif ($database->versionPrimary > 5) { // Note: I figure this might be best for now. Note that the code should still run for any version of MySQL 5.x.
                    die ('You have attempted to connect to a MySQL version greater than 5. Such a thing did not exist when I was writing this code, and there is a good chance it won\'t work as expected. Either download a newer version of FreezeMessenger, or, if one does not yet exist, you can try to modify the source code of the installer script to remove this restriction. If you\'re lucky, things will still work.');
                }
            }
            elseif ($driver === 'pgsql') {
                if ($database->versionPrimary <= 7) { // PostGreSQL 7 is a no-go.
                    die('You have attempted to connect to a PostGreSQL version 7 database. PostGreSQL 8.2+ is required for FreezeMessenger.');
                }
                elseif ($database->versionPrimary == 8 && $database->versionSecondary <= 1) { // PostGreSQL 8.1 or 8.2 is also a no-go.
                    die('You have attempted to connect to an incompatible version of a PostGreSQL 8 database (PostGreSQL 8.0-8.1). PostGreSQL 8.2+ is required for FreezeMessenger.');
                }
            }
             else {
                die('Unknown driver selected.');
            }


            if ($createdb) { // Create the database if needed. This will not work for all drivers.
                if (!$database->createDatabase($databaseName)) { // We're supposed to create it, let's try.
                    die("The database could not be created.\n" . $database->getLastError());
                }
                elseif (!$database->selectDatabase($databaseName)) {
                    die('The created database could not be selected.');
                }
            }



            // Get Pre-Existing Tables So We Don't Overwrite Any of Them Later
            $showTables = array_map('strtolower', (array) $database->getTablesAsArray()); // ...In Windows, table names may not be returned as entered (uppercase letters usually become lowercase), so this is the most efficient work-around I could come up with.

            // Read the various XML files.
            $xmlData = new Xml2Array(file_get_contents('dbSchema.xml')); // Get the XML Data from the dbSchema.xml file, and feed it to the Xml2Array class
            $xmlData = $xmlData->getAsArray(); // Get the XML data as an array
            $xmlData = $xmlData['dbSchema']; // Get the contents of the root node

            $xmlData2 = new Xml2Array(file_get_contents('dbData.xml')); // Get the XML Data from the dbData.xml file, and feed it to the Xml2Array class
            $xmlData2 = $xmlData2->getAsArray(); // Get the XML data as an array
            $xmlData2 = $xmlData2['dbData']; // Get the contents of the root node


            // Check file versions.
            if ((float) $xmlData['@version'] != 3) { // It's possible people have an unsynced directory (or similar), so make sure we're working with the correct version of the file.
                die('The XML data source appears to be out of date. Reinstall FreezeMessenger and try again.');
            }
            elseif ((float) $xmlData2['@version'] != 3) { // It's possible people have an unsynced directory (or similar), so make sure we're working with the correct version of the file.
                die('The XML data source appears to be out of date. Reinstall FreezeMessenger and try again.');
            }
            else {
                /* Part 2: Create the Tables */

                $queries = array(); // This will be the place where all finalized queries are put when they are ready to be executed.
                $time = time();

                foreach ($xmlData['database'][0]['table'] AS $table) { // Run through each table from the XML
                    $tableType = isset($table['@type']) ? $table['@type'] : 'general';
                    $tableName = $prefix . $table['@name'];
                    $tableComment = $table['@comment'] ?? '';

                    $tableColumns = array();
                    $tableIndexes = array();


                    foreach ($table['column'] AS $column) {
                        $tableColumns[$column['@name']] = array(
                            'type' => $column['@type'],
                            'autoincrement' => (isset($column['@autoincrement']) ? $column['@autoincrement'] : false),
                            'restrict' => (isset($column['@restrict']) ? explode(',', $column['@restrict']) : false),
                            'maxlen' => (isset($column['@maxlen']) ? $column['@maxlen'] : false),
                            'bits' => (isset($column['@bits']) ? $column['@bits'] : false),
                            'default' => (isset($column['@default']) ? $column['@default'] : null),
                            'comment' => (isset($column['@comment']) ? $column['@comment'] : false),
                        );
                    }


                    if (isset($table['key'])) {
                        foreach ($table['key'] AS $key) {
                            $tableIndexes[$key['@name']] = array(
                                'type' => $key['@type'],
                            );
                        }
                    }

                    if (in_array(strtolower($tableName), $showTables)) { // We are overwriting, so rename the old table to a backup. Someone else can clean it up later, but its for the best.
                        if (!$database->renameTable($tableName, $tableName . '~' . $time)) {
                            die("Could Not Rename Table '$tableName'");
                        }
                    }

                    if (!$database->createTable($tableName, $tableComment, $tableType, $tableColumns, $tableIndexes, isset($table['@partitionBy']) ? $table['@partitionBy'] : false, isset($table['@hardPartitions']) ? $table['@hardPartitions'] : 1)) {
                        die("Could not create table.\n" . $database->getLastError());
                    }
                }



                /* Part 3: Insert Predefined Data */

                $queries = array(); // This will be the place where all finalized queries are put when they are ready to be executed.

                foreach ($xmlData2['database'][0]['table'] AS $table) { // Run through each table from the XML
                    if (isset($table['@mode']) && $table['@mode'] === 'dev' && !isset($_GET['db_usedev'])) // Don't insert dev data, unless asked.
                        continue;

                    $columns = array(); // We will use this to store the column fragments that will be implode()d into the final query.
                    $values = array(); // We will use this to store the column fragments that will be implode()d into the final query.
                    $insertData = array();

                    foreach ($table['column'] AS $column) {
                        $insertData[$column['@name']] = (isset($column['@type']) ? new DatabaseType($column['@type'], $column['@value']) : $database->auto($column['@value']));
                    }

                    if (!$database->insert($prefix . $table['@name'], $insertData)) {
                        die("Failed to insert data into {$prefix}{$table['@name']}.\n" . print_r($database->queryLog, true));
                    }
                }
            }
        }


        $database->close();

        echo 'success';
        break;

    // Note: This writes a file to the server, which is a very sensitive action (and for a reason is never done elsewhere). This is NOT secure, but should only be used by users wishing to install the product.
    case 2: // Config File
        require('../functions/fim_general.php');

        $driver = urldecode($_GET['db_driver']);
        $host = urldecode($_GET['db_host']);
        $port = urldecode($_GET['db_port']);
        $userName = urldecode($_GET['db_userName']);
        $password = urldecode($_GET['db_password']);
        $databaseName = urldecode($_GET['db_database']);
        $prefix = urldecode($_GET['db_tableprefix']);

        $forum = urldecode($_GET['forum']);
        $forumUrl = urldecode($_GET['forum_url']);
        $forumTablePrefix = urldecode($_GET['forum_tableprefix']);

        $encryptSalt = urldecode($_GET['encrypt_salt']);
        $enableEncrypt = (int) $_GET['enable_encrypt'];

        $recaptchaKey = urldecode($_GET['recaptcha_key'] ?? '');

        $adminUsername = urldecode($_GET['admin_userName']);
        $adminPassword = urldecode($_GET['admin_password']);

        $cacheMethod = urldecode($_GET['cache_method']);

        $tmpDir = urldecode($_GET['tmp_dir']);

        $base = file_get_contents('config.base.php');

        if ($forum == 'vanilla') {
            try {
                list ($database, $config) = fimDatabaseAndConfigFactory::init($host, $port, $userName, $password, $databaseName, $driver, $prefix);
                fimConfig::$displayBacktrace = true;
                $generalCache = new fimCache(null, 'none', $database);

                $user = new fimUser(1);
                if (!$user->setDatabase(array(
                    'name' => $adminUsername,
                    'password' => $adminPassword,
                    'privs' => 0x7FFFFFFF,
                ))) {
                    //var_dump($database->errors);
                    die("Could not create user.");
                }
            } catch(Exception $ex) {
                die($ex->getMessage());
            }
        }

        $find = array(
            "\$dbConnect['core']['driver'] = 'mysqli';
\$dbConnect['slave']['driver'] = 'mysqli';
\$dbConnect['integration']['driver'] = 'mysqli';",
            "\$dbConnect['core']['host'] = 'localhost';
\$dbConnect['slave']['host'] = 'localhost';
\$dbConnect['integration']['host'] = 'localhost';",
            "\$dbConnect['core']['port'] = 3306;
\$dbConnect['slave']['port'] = 3306;
\$dbConnect['integration']['port'] = 3306;",
            "\$dbConnect['core']['username'] = '';
\$dbConnect['slave']['username'] = '';
\$dbConnect['integration']['username'] = '';",
            "\$dbConnect['core']['password'] = '';
\$dbConnect['slave']['password'] = '';
\$dbConnect['integration']['password'] = '';",
            "\$dbConnect['core']['database'] = '';
\$dbConnect['slave']['database'] = '';
\$dbConnect['integration']['database'] = '';",
            '$dbConfig[\'vanilla\'][\'tablePrefix\'] = \'\';',
            '$dbConfig[\'integration\'][\'tablePrefix\'] = \'\';',
            '$cacheConnect[\'driver\'] = \'\';',
            '$loginConfig[\'method\'] = \'vanilla\';',
            '$loginConfig[\'url\'] = \'http://example.com/forums/\';',
            '$loginConfig[\'superUsers\'] = array();',
            '$installUrl = \'\';',
            '$salts = array(
  101 => \'xxx\',
);',
            '$encrypt = true;',
            '$encryptUploads = true;',
            '$tmpDir = \'\';'
        );

        $replace = array(
            "\$dbConnect['core']['driver'] = '" . addslashes($driver) . "';\n\$dbConnect['slave']['driver'] = '" . addslashes($driver) . "';\n\$dbConnect['integration']['driver'] = '" . addslashes($driver) . "';",
            "\$dbConnect['core']['host'] = '" . addslashes($host) . "';\n\$dbConnect['slave']['host'] = '" . addslashes($host) . "';\n\$dbConnect['integration']['host'] = '" . addslashes($host) . "';",
            "\$dbConnect['core']['port'] = '" . addslashes($port) . "';\n\$dbConnect['slave']['port'] = '" . addslashes($port) . "';\n\$dbConnect['integration']['port'] = '" . addslashes($port) . "';",
            "\$dbConnect['core']['username'] = '" . addslashes($userName) . "';\n\$dbConnect['slave']['username'] = '" . addslashes($userName) . "';\n\$dbConnect['integration']['username'] = '" . addslashes($userName) . "';",
            "\$dbConnect['core']['password'] = '" . addslashes($password) . "';\n\$dbConnect['slave']['password'] = '" . addslashes($password) . "';\n\$dbConnect['integration']['password'] = '" . addslashes($password) . "';",
            "\$dbConnect['core']['database'] = '" . addslashes($databaseName) . "';\n\$dbConnect['slave']['database'] = '" . addslashes($databaseName) . "';\n\$dbConnect['integration']['database'] = '" . addslashes($databaseName) . "';",
            '$dbConfig[\'vanilla\'][\'tablePrefix\'] = \'' . addslashes($prefix) . '\';',
            '$dbConfig[\'integration\'][\'tablePrefix\'] = \'' . addslashes($forumTablePrefix) . '\';',
            '$cacheConnect[\'driver\'] = \'' . addslashes($cacheMethod) . '\';',
            '$loginConfig[\'method\'] = \'' . addslashes($forum) . '\';',
            '$loginConfig[\'url\'] = \'' . addslashes($forumUrl) . '\';',
            '$loginConfig[\'superUsers\'] = array(' . ($forum == 'phpbb' ? 2 : 1) . ');',
            '$installUrl = \'' . str_replace(array('install/index.php', 'install/'), array('', ''), $_SERVER['HTTP_REFERER']) . '\';',
            '$salts = array(
  101 => \'' . $encryptSalt . '\',
);',
            '$encrypt = ' . ($enableEncrypt & 1 ? 'true' : 'false') . ';',
            '$encryptUploads = ' . ($enableEncrypt & 2 ? 'true' : 'false') . ';',
            '$tmpDir = \'' . $tmpDir . '\';'
        );



        $baseNew = str_replace($find, $replace, $base);
        if (file_put_contents('../config.php', $baseNew)) {
            echo 'success';
        }
        break;
}
?>
