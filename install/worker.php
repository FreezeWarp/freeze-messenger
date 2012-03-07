<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

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

error_reporting(E_ALL ^ E_NOTICE);


require('../functions/xml.php');
require('../functions/database.php');



switch ($_REQUEST['phase']) {
  case false:
  default:

  break;

  case 1: // Table Check
  // If tables do not exist, import them from SQL dump files.
  // If tables do exist, recreate if specified or leave alone.

  $driver = urldecode($_GET['db_driver']);
  $host = urldecode($_GET['db_host']);
  $port = urldecode($_GET['db_port']);
  $userName = urldecode($_GET['db_userName']);
  $password = urldecode($_GET['db_password']);
  $databaseName = urldecode($_GET['db_database']);
  $createdb = urldecode($_GET['db_createdb']);
  $prefix = urldecode($_GET['db_tableprefix']);




  /* Part 1 : Connect to the Database, Create a New Database If Needed */

  $database = new database();
  if ($driver === 'postgresql' && $createdb) {
    die('PostGreSQL is unable to create databases. Please manually create the database before you continue.');
  }
  else {
    if ($createdb) { // Databases that we will skip the create DB stuff for.
      $database->connect($host, $port, $userName, $password, false, $driver);
    }
    else {
      $database->connect($host, $port, $userName, $password, $databaseName, $driver);
    }

    $database->setErrorLevel(E_USER_WARNING);


    if ($database->error) {
      die('Connection Error: ' . $database->error);
    }
    else {
      // Get Only The Good Parts of the Database Version (we could also use a REGEX, but meh)
      for ($i = 0; $i < strlen($database->version); $i++) {
        if (in_array($database->version[$i], array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9)) || $database->version[$i] == '.') {
          $strippedVersion .= $database->version[$i];
        }
        else {
          break;
        }
      }

      $strippedVersionParts = explode('.',$strippedVersion); // Divide the decimal versions into an array; e.g. 5.0.1 becomes [0] => 5, [1] => 0, [2] => 1
      if ($driver === 'mysql' || $driver === 'mysqli') {
        if ($strippedVersionParts[0] <= 4) { // MySQL 4 is a no-go.
          die('You have attempted to connect to a MySQL version 4 database. MySQL 5.0.5+ is required for FreezeMessenger.');
        }
        elseif ($strippedVersionParts[0] == 5 && $strippedVersionParts[1] == 0 && $strippedVersionParts[2] <= 4) { // MySQL 5.0.0-5.0.4 is also a no-go (we require the BIT type, even though in theory we could work without it)
          die('You have attempted to connect to an incompatible version of a MySQL version 5 database (MySQL 5.0.0-5.0.4). MySQL 5.0.5+ is required for FreezeMessenger.');
        }
      }
      elseif ($driver === 'postgresql') {
        if ($strippedVersionParts[0] <= 7) { // PostGreSQL 7 is a no-go.
          die('You have attempted to connect to a PostGreSQL version 7 database. PostGreSQL 8.2+ is required for FreezeMessenger.');
        }
        elseif ($strippedVersionParts[0] == 8 && $strippedVersionParts[1] <= 1) { // PostGreSQL 8.1 or 8.2 is also a no-go.
          die('You have attempted to connect to an incompatible version of a PostGreSQL 8 database (PostGreSQL 8.0-8.1). PostGreSQL 8.2+ is required for FreezeMessenger.');
        }
      }


      if ($createdb) { // Create the database if needed. This will not work for all drivers.
        if (!$database->createDatabase($databaseName)) { // We're supposed to create it, let's try.
          die('The database could not be created: ' . $database->error);
        }
        elseif (!$database->selectDatabase($databaseName)) {
          die('The created database could not be selected.');
        }
      }



      // Get Pre-Existing Tables So We Don't Overwrite Any of Them Later
      $showTables = $database->getTablesAsArray();

      // Read the various XML files.
      $xmlData = new Xml2Array(file_get_contents('dbSchema.xml')); // Get the XML Data from the dbSchema.xml file, and feed it to the Xml2Array class
      $xmlData = $xmlData->getAsArray(); // Get the XML data as an array
      $xmlData = $xmlData['dbSchema']; // Get the contents of the root node

      $xmlData2 = new Xml2Array(file_get_contents('dbData.xml')); // Get the XML Data from the dbData.xml file, and feed it to the Xml2Array class
      $xmlData2 = $xmlData2->getAsArray(); // Get the XML data as an array
      $xmlData2 = $xmlData2['dbData']; // Get the contents of the root node



      // Check file versions.
      if ((float) $xmlData['@version'] != 3) { // It's possible people have an unsynced directory (or similar), so make sure we're working with the correct version of the file.
        die('The XML Schema Data Source if For An Improper Version');
      }
      elseif ((float) $xmlData2['@version'] != 3) { // It's possible people have an unsynced directory (or similar), so make sure we're working with the correct version of the file.
        die('The XML Insert Data Source if For An Improper Version');
      }
      else {
        /* Part 2: Create the Tables */

        $queries = array(); // This will be the place where all finalized queries are put when they are ready to be executed.

        foreach ($xmlData['database'][0]['table'] AS $table) { // Run through each table from the XML
          $tableType = $table['@type'];
          $tableName = $prefix . $table['@name'];
          $tableComment = $table['@comment'];

          $tableColumns = array();
          $tableIndexes = array();


          foreach ($table['column'] AS $column) {
            $tableColumns[] = array(
              'type' => $column['@type'],
              'name' => $column['@name'],
              'autoincrement' => (isset($column['@autoincrement']) ? $column['@autoincrement'] : false),
              'restrict' => (isset($column['@restrict']) ? explode(',', $column['@restrict']) : false),
              'maxlen' => (isset($column['@maxlen']) ? $column['@maxlen'] : false),
              'bits' => (isset($column['@bits']) ? $column['@bits'] : false),
              'default' => (isset($column['@default']) ? $column['@default'] : false),
              'comment' => (isset($column['@comment']) ? $column['@comment'] : false),
            );
          }


          foreach ($table['key'] AS $key) {
            $tableIndexes[] = array(
              'type' => $key['@type'],
              'name' => $key['@name'],
            );
          }


          if (in_array($tableName, (array) $showTables)) { // We are overwriting, so rename the old table to a backup. Someone else can clean it up later, but its for the best.
            if (!$database->renameTable($tableName, $tableName . '~' . time())) {
              die("Could Not Rename Table '$tableName'");
            }
          }


          if (!$database->createTable($tableName, $tableComment, $tableType, $tableColumns, $tableIndexes)) {
            die("Could not run query:\n" . $database->sourceQuery . "\n\nError:\n" . $database->error);
          }
        }





        /* Part 3: Insert Predefined Data */

        $queries = array(); // This will be the place where all finalized queries are put when they are ready to be executed.

        foreach ($xmlData2['database'][0]['table'] AS $table) { // Run through each table from the XML
          $columns = array(); // We will use this to store the column fragments that will be implode()d into the final query.
          $values = array(); // We will use this to store the column fragments that will be implode()d into the final query.
          $insertData = array();

          foreach ($table['column'] AS $column) {
            $insertData[$column['@name']] = $column['@value'];
          }

          if (!$database->insert($prefix . $table['@name'], $insertData)) {
            die("Could not run query:\n" . $database->sourceQuery . "\n\nError:\n" . $database->error);
          }
        }
      }
    }



    echo 'success';

    $database->close();
  }

  break;

  case 2: // Config File
  $driver = urldecode($_GET['db_driver']);
  $host = urldecode($_GET['db_host']);
  $port = urldecode($_GET['db_port']);
  $userName = urldecode($_GET['db_userName']);
  $password = urldecode($_GET['db_password']);
  $database = urldecode($_GET['db_database']);
  $prefix = urldecode($_GET['db_tableprefix']);

  $forum = urldecode($_GET['forum']);
  $forumUrl = urldecode($_GET['forum_url']);
  $forumTablePrefix = urldecode($_GET['forum_tableprefix']);

  $encryptSalt = urldecode($_GET['encrypt_salt']);
  $enableEncrypt = (int) $_GET['enable_encrypt'];

  $base = file_get_contents('config.base.php');

  $find = array(
    '$dbConnect[\'core\'][\'driver\'] = \'mysqli\';
$dbConnect[\'slave\'][\'driver\'] = \'mysqli\';
$dbConnect[\'integration\'][\'driver\'] = \'mysqli\';',
    '$dbConnect[\'core\'][\'host\'] = \'localhost\';
$dbConnect[\'slave\'][\'host\'] = \'localhost\';
$dbConnect[\'integration\'][\'host\'] = \'localhost\';',
    '$dbConnect[\'core\'][\'port\'] = 3306;
$dbConnect[\'slave\'][\'port\'] = 3306;
$dbConnect[\'integration\'][\'port\'] = 3306;',
    '$dbConnect[\'core\'][\'username\'] = \'\';
$dbConnect[\'slave\'][\'username\'] = \'\';
$dbConnect[\'integration\'][\'username\'] = \'\';',
    '$dbConnect[\'core\'][\'password\'] = \'\';
$dbConnect[\'slave\'][\'password\'] = \'\';
$dbConnect[\'integration\'][\'password\'] = \'\';',
    '$dbConnect[\'core\'][\'database\'] = \'\';
$dbConnect[\'slave\'][\'database\'] = \'\';
$dbConnect[\'integration\'][\'database\'] = \'\';',
    '$dbConfig[\'vanilla\'][\'tablePrefix\'] = \'\';',
    '$dbConfig[\'integration\'][\'tablePreix\'] = \'\';',
    '$loginConfig[\'method\'] = \'vanilla\';',
    '$loginConfig[\'url\'] = \'http://example.com/forums/\';',
    '$loginConfig[\'superUsers\'] = array()',
    '$installUrl = \'\';',
    '$salts = array(
  101 => \'xxx\',
);',
     '$encrypt = true;',
     '$encryptUploads = true;',
     '$enableUploads = true;',
     '$enableGeneralUploads = true;',
  );

  $replace = array(
    '$dbConnect[\'core\'][\'driver\'] = \'' . $driver . '\';
$dbConnect[\'slave\'][\'driver\'] = \'' . $driver . '\';
$dbConnect[\'integration\'][\'driver\'] = \'' . $driver . '\';',
    '$dbConnect[\'core\'][\'host\'] = \'' . $host . '\';
$dbConnect[\'slave\'][\'host\'] = \'' . $host . '\';
$dbConnect[\'integration\'][\'host\'] = \'' . $host . '\';',
    '$dbConnect[\'core\'][\'port\'] = ' . $port . ';
$dbConnect[\'slave\'][\'port\'] = ' . $port . ';
$dbConnect[\'integration\'][\'port\'] = ' . $port . ';',
    '$dbConnect[\'core\'][\'username\'] = \'' . $userName . '\';
$dbConnect[\'slave\'][\'username\'] = \'' . $userName . '\';
$dbConnect[\'integration\'][\'username\'] = \'' . $userName . '\';',
    '$dbConnect[\'core\'][\'password\'] = \'' . $password . '\';
$dbConnect[\'slave\'][\'password\'] = \'' . $password . '\';
$dbConnect[\'integration\'][\'password\'] = \'' . $password . '\';',
    '$dbConnect[\'core\'][\'database\'] = \'' . $database . '\';
$dbConnect[\'slave\'][\'database\'] = \'' . $database . '\';
$dbConnect[\'integration\'][\'database\'] = \'' . $database . '\';',
    '$dbConfig[\'vanilla\'][\'tablePrefix\'] = \'' . $prefix . '\';',
    '$dbConfig[\'integration\'][\'tablePreix\'] = \'' . $forumTablePrefix . '\';',
    '$loginConfig[\'method\'] = \'' . $forum . '\';',
    '$loginConfig[\'url\'] = \'' . $forumUrl . '\';',
    '$loginConfig[\'superUsers\'] = array(' . ($forum == 'phpbb' ? 2 : 1) . ');',
    '$installUrl = \'' . str_replace(array('install/index.php','install/'), array('',''), $_SERVER['HTTP_REFERER']) . '\';',
    '$salts = array(
  101 => \'' . $encryptSalt . '\',
);',
    '$encrypt = ' . ($enableEncrypt & 1 ? 'true' : 'false') . ';',
    '$encryptUploads = ' . ($enableEncrypt & 2 ? 'true' : 'false') . ';',
    '$enableUploads = ' . ($enableUploads & 1 ? 'true' : 'false') . ';',
    '$enableGeneralUploads = ' . ($enableUploads & 2 ? 'true' : 'false') . ';',
  );



  $baseNew = str_replace($find, $replace, $base);

  if (file_put_contents('../config.php', $baseNew)) {
    echo 'success';
  }
  break;
}
?>