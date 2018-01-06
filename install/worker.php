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

use Database\Type;

error_reporting(E_ALL); // Report All Potential Errors
ini_set('display_errors', 1);

require_once(__DIR__ . '/../vendor/autoload.php'); // Various Functions

require(__DIR__ . '/../functions/Xml2Array.php'); // For reading the db*.xml files
require(__DIR__ . '/../functions/fimUser.php'); // Creating Users
require(__DIR__ . '/../functions/fimRoom.php');
require(__DIR__ . '/../functions/fimError.php');

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

        $driver = $_GET['db_driver'];
        $host = $_GET['db_host'];
        $port = $_GET['db_port'];
        $userName = $_GET['db_userName'];
        $password = $_GET['db_password'];
        $databaseName = $_GET['db_database'];
        $createdb = isset($_GET['db_createdb']);
        $prefix = $_GET['db_tableprefix'];




        /* Part 1 : Connect to the Database, Create a New Database If Needed */

        $databaseInstance = new \Fim\DatabaseInstance();
        $databaseInstance->setErrorLevel(E_USER_WARNING);

        try {
            $databaseInstance->connect($host, $port, $userName, $password, $createdb ? false : $databaseName, $driver, $prefix);
        } catch (Exception $exception) {
            die($exception->getMessage());
        }


        if ($databaseInstance->getLastError()) {
            die("Connection Error.\n" . \Fim\Database::instance()->getLastError());
        }
        else {
            \Fim\Database::setInstance($databaseInstance);


            // Check Version Issues
            \Fim\Database::instance()->loadVersion();

            switch ($driver) {
                case 'mysql':
                case 'mysqli':
                case 'pdoMysql':
                    if (\Fim\Database::instance()->versionPrimary <= 4) { // MySQL 4 is a no-go.
                        die('You have attempted to connect to a MySQL version ' . \Fim\Database::instance()->versionString . ' database. MySQL 5.0.5+ is required for FreezeMessenger.');
                    }
                    elseif (\Fim\Database::instance()->versionPrimary == 5 && \Fim\Database::instance()->versionSecondary == 0 && \Fim\Database::instance()->versionTertiary <= 4) { // MySQL 5.0.0-5.0.4 is also a no-g	o (we require the BIT type, even though in theory we could work without it)
                        die('You have attempted to connect to a MySQL version ' . \Fim\Database::instance()->versionString . ' database. MySQL 5.0.5+ is required for FreezeMessenger.');
                    }
                    elseif (\Fim\Database::instance()->versionPrimary > 5) { // Note: I figure this might be best for now. Note that the code should still run for any version of MySQL 5.x.
                        die ('You have attempted to connect to a MySQL version greater than 5. Such a thing did not exist when I was writing this code, and there is a good chance it won\'t work as expected. Either download a newer version of FreezeMessenger, or, if one does not yet exist, you can try to modify the source code of the installer script to remove this restriction. If you\'re lucky, things will still work.');
                    }
                break;

                case 'pgsql':
                case 'pdoPgsql':
                    if (\Fim\Database::instance()->versionPrimary <= 8) { // PostGreSQL 8 is a no-go.
                        die('You have attempted to connect to a PostGreSQL version 8 or older database. PostGreSQL 9.3+ is required for FreezeMessenger.');
                    }
                    elseif (\Fim\Database::instance()->versionPrimary == 9 && \Fim\Database::instance()->versionSecondary <= 2) { // PostGreSQL 9.0-9.2 is also a no-go.
                        die('You have attempted to connect to an out-of-date version of a PostGreSQL 9 database (PostGreSQL 9.0-9.2). PostGreSQL 9.3+ is required for FreezeMessenger.');
                    }
                break;

                case 'sqlsrv':
                case 'pdoSqlsrv':
                    if (\Fim\Database::instance()->versionPrimary <= 12) {
                        die('SqlServer 13+ is required for FreezeMessenger.');
                    }
                break;

                default:
                    die('Unknown driver selected.');
                break;
            }


            // Create the database if needed. This will not work for all drivers.
            if ($createdb) {
                if (!\Fim\Database::instance()->createDatabase($databaseName)) { // We're supposed to create it, let's try.
                    die("The database could not be created.\n" . \Fim\Database::instance()->getLastError());
                }
                elseif (!\Fim\Database::instance()->selectDatabase($databaseName)) {
                    die('The created database could not be selected.');
                }
            }



            // Get Pre-Existing Tables So We Don't Overwrite Any of Them Later
            $showTables = \Fim\Database::instance()->getTablesAsArray();

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
                \Fim\Database::instance()->holdTriggers(true); // Don't run triggers. The trigger statements set our foreign keys, and thus must be run at the very end.

                $time = time();

                foreach ($xmlData['database'][0]['table'] AS $table) { // Run through each table from the XML
                    $tableType = isset($table['@type']) ? $table['@type'] : 'general';
                    $tableName = $prefix . $table['@name'];
                    $tableComment = $table['@comment'] ?? '';

                    $tableColumns = array();
                    $tableIndexes = array();


                    foreach ($table['column'] AS $column) {
                        $tableColumns[$column['@name']] = [
                            'type'          => $column['@type'],
                            'autoincrement' => $column['@autoincrement'] ?? false,
                            'restrict'      => (isset($column['@restrict'])
                                ? explode(',', $column['@restrict'])
                                : false),
                            'maxlen'        => $column['@maxlen'] ?? false,
                            'bits'          => $column['@bits'] ?? false,
                            'default'       => $column['@default'] ?? null,
                            'comment'       => $column['@comment'] ?? false,
                            'preferAscii'   => isset($column['@preferAscii']),
                        ];

                        if (isset($column['@fkey'])) {
                            $values = explode('.', $column['@fkey']);
                            $values[0] = $prefix . $values[0];
                            $tableColumns[$column['@name']]['restrict'] = new \Database\Type(\Database\Type\Type::tableColumn, $values);
                        }
                    }


                    if (isset($table['key'])) {
                        foreach ($table['key'] AS $key) {
                            $tableIndexes[$key['@name']] = array(
                                'type'    => $key['@type'],
                                'storage' => $key['@storage'] ?? '',
                                'comment' => $key['@comment'] ?? '',
                            );
                        }
                    }


                    if (!\Fim\Database::instance()->createTable($tableName, $tableComment, $tableType, $tableColumns, $tableIndexes, isset($table['@partitionBy']) ? $table['@partitionBy'] : false, isset($table['@hardPartitions']) ? $table['@hardPartitions'] : 1, true)) {
                        die("Could not create table.\n" . \Fim\Database::instance()->getLastError());
                    }
                }

                \Fim\Database::instance()->holdTriggers(false);


                /* Part 3: Insert Predefined Data */
                foreach ($xmlData2['database'][0]['table'] AS $table) { // Run through each table from the XML
                    if (isset($table['@mode']) && $table['@mode'] === 'dev' && !isset($_GET['db_usedev'])) // Don't insert dev data, unless asked.
                        continue;

                    $columns = array(); // We will use this to store the column fragments that will be implode()d into the final query.
                    $values = array(); // We will use this to store the column fragments that will be implode()d into the final query.
                    $insertData = array();

                    foreach ($table['column'] AS $column) {
                        $insertData[$column['@name']] = (isset($column['@type']) ? new Type($column['@type'], $column['@value']) : \Fim\Database::instance()->auto($column['@value']));
                    }

                    if (!\Fim\Database::instance()->insert(\Fim\Database::$sqlPrefix . $table['@name'], $insertData)) {
                        die("Failed to insert data into {$prefix}{$table['@name']}.\n" . print_r(\Fim\Database::instance()->queryLog, true));
                    }
                }
            }
        }


        \Fim\Database::instance()->close();

        echo 'success';
        break;

    // Note: This writes a file to the server, which is a very sensitive action (and for a reason is never done elsewhere). This is NOT secure, but should only be used by users wishing to install the product.
    case 2: // Config File
        require('../functions/fim_general.php');

        $forum = $_GET['forum'];
        $forumUrl = rtrim($_GET['forum_url'], '/') . '/';

        // $recaptchaKey = $_GET['recaptcha_key'] ?? ''; TODO

        $adminUsername = $_GET['admin_userName'];
        $adminPassword = $_GET['admin_password'];

        $loginConfig = [
            'method' => $forum,
            'url' => $forumUrl,
            'adminGroups' => [],
            'superUsers' => [],
            'bannedGroups' => [],
        ];

        $base = file_get_contents('config.base.php');

        if ($forum == 'vanilla') {
            try {
                \Fim\Database::setInstance(new \Fim\DatabaseInstance($_GET['db_host'], $_GET['db_port'], $_GET['db_userName'], $_GET['db_password'], $_GET['db_database'], $_GET['db_driver'], $_GET['db_tableprefix']));
                \Fim\Config::$displayBacktrace = true;

                $user = new fimUser(false);
                if (!$user->setDatabase(array(
                    'name' => $adminUsername,
                    'password' => $adminPassword,
                    'privs' => 0x7FFFFFFF,
                ))) {
                    die("Could not create user.");
                }
            } catch(Exception $ex) {
                die($ex->getMessage());
            }
        }
        else {
            /* Prepare Login Runner */
            $_REQUEST['username'] = $adminUsername;
            $_REQUEST['password'] = $adminPassword;


            \Fim\Database::setInstance(new \Fim\DatabaseInstance($_GET['db_host'], $_GET['db_port'], $_GET['db_userName'], $_GET['db_password'], $_GET['db_database'], $_GET['db_driver'], $_GET['db_tableprefix']));
            \Fim\DatabaseLogin::setInstance(new \Fim\DatabaseInstance($_GET['forum_db_host'], $_GET['forum_db_port'], $_GET['forum_db_userName'], $_GET['forum_db_password'], $_GET['forum_db_database'], $_GET['forum_db_driver'], $_GET['forum_db_tableprefix']));


            OAuth2\Autoloader::register();
            $oauthStorage = new \Fim\OAuthProvider(\Fim\Database::instance(), 'fimError');
            $oauthServer = new OAuth2\Server($oauthStorage); // Pass a storage object or array of storage objects to the OAuth2 server class
            $oauthRequest = OAuth2\Request::createFromGlobals();
            $loginFactory = new \Login\LoginFactory($oauthRequest, $oauthStorage, $oauthServer, \Fim\DatabaseLogin::instance());


            try {
                $loginFactory->loginRunner->setUser();
                $user = $loginFactory->user;

                if (!$user->id) {
                    die('Admin user could not be retrieved.');
                }
            } catch(fimErrorThrown $ex) {
                die($ex->getCode() . ": " . $ex->getString());
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
            '$dbConnect[\'vanilla\'][\'tablePrefix\'] = \'\';',
            '$dbConnect[\'integration\'][\'tablePrefix\'] = \'\';',
            '$loginConfig[\'method\'] = \'vanilla\';',
            '$loginConfig[\'url\'] = \'http://example.com/forums/\';',
            '$loginConfig[\'superUsers\'] = array();',
            '$installUrl = \'\';',
            '$loginConfig[\'adminGroups\'] = array()',
            '$loginConfig[\'bannedGroups\'] = array()',

        );

        $replace = array(
            "\$dbConnect['core']['driver'] = '" . addslashes($_GET['db_driver']) . "';
\$dbConnect['slave']['driver'] = '" . addslashes($_GET['db_driver']) . "';
\$dbConnect['integration']['driver'] = '" . addslashes($_GET['forum_db_driver']) . "';",
            "\$dbConnect['core']['host'] = '" . addslashes($_GET['db_host']) . "';
\$dbConnect['slave']['host'] = '" . addslashes($_GET['db_host']) . "';
\$dbConnect['integration']['host'] = '" . addslashes($_GET['forum_db_host']) . "';",
            "\$dbConnect['core']['port'] = '" . addslashes($_GET['db_port']) . "';
\$dbConnect['slave']['port'] = '" . addslashes($_GET['db_port']) . "';
\$dbConnect['integration']['port'] = '" . addslashes($_GET['forum_db_port']) . "';",
            "\$dbConnect['core']['username'] = '" . addslashes($_GET['db_userName']) . "';
\$dbConnect['slave']['username'] = '" . addslashes($_GET['db_userName']) . "';
\$dbConnect['integration']['username'] = '" . addslashes($_GET['forum_db_userName']) . "';",
            "\$dbConnect['core']['password'] = '" . addslashes($_GET['db_password']) . "';
\$dbConnect['slave']['password'] = '" . addslashes($_GET['db_password']) . "';
\$dbConnect['integration']['password'] = '" . addslashes($_GET['forum_db_password']) . "';",
            "\$dbConnect['core']['database'] = '" . addslashes($_GET['db_database']) . "';
\$dbConnect['slave']['database'] = '" . addslashes($_GET['db_database']) . "';
\$dbConnect['integration']['database'] = '" . addslashes($_GET['forum_db_database']) . "';",
            '$dbConnect[\'vanilla\'][\'tablePrefix\'] = \'' . addslashes($_GET['db_tableprefix']) . '\';',
            '$dbConnect[\'integration\'][\'tablePrefix\'] = \'' . addslashes($_GET['forum_db_tableprefix']) . '\';',
            '$loginConfig[\'method\'] = \'' . addslashes($forum) . '\';',
            '$loginConfig[\'url\'] = \'' . addslashes($forumUrl) . '\';',
            '$loginConfig[\'superUsers\'] = array(' . $user->id . ');',
            '$installUrl = \'' . str_replace(array('install/index.php', 'install/'), array('', ''), $_SERVER['HTTP_REFERER']) . '\';',
            '$loginConfig[\'adminGroups\'] = array(' . (($forum === 'vbulletin3' || $forum == 'vbulletin5') ? '6' : '') . ')',
            '$loginConfig[\'bannedGroups\'] = array(' . (($forum === 'vbulletin3' || $forum == 'vbulletin5') ? '4, 8' : '') . ')',
        );


        foreach ($_GET['oauthMethods'] AS $methodName => $methodData) {
            if ($methodData['clientId']) {
                $find[] = "/*\$loginConfig['extraMethods']['$methodName'] = [\n    'clientId' => '',\n    'clientSecret' => ''\n];*/";
                $replace[] = "\$loginConfig['extraMethods']['$methodName'] = [\n    'clientId' => '{$methodData['clientId']}',\n    'clientSecret' => '" . ($methodData['clientSecret'] ?? '') . "'\n];";
            }
        }


        /* Automatically enable APCu if it is available. */
        if (extension_loaded('apcu')) {
            $find[] = '/*$cacheConnectMethods[\'apcu\'] = [];*/';
            $replace[] = '$cacheConnectMethods[\'apcu\'] = [];';
        }

        /* Automatically enable APC if it is available. */
        elseif (extension_loaded('apc')) {
            $find[] = '/*$cacheConnectMethods[\'apc\'] = [];*/';
            $replace[] = '$cacheConnectMethods[\'apc\'] = [];';
        }


        $baseNew = str_replace($find, $replace, $base);
        if (file_put_contents('../config.php', $baseNew)) {
            echo 'success';
        }
        break;
}
?>
