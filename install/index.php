<?php
error_reporting(E_ALL ^ E_NOTICE);


require('../functions/xml.php');
require('../functions/database.php');



switch ($_REQUEST['phase']) {
  case false:
  default:
  echo '<!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Freeze Messenger Installation</title>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="author" content="Joseph T. Parsons" />
  <link rel="icon" id="favicon" type="image/png" href="images/favicon.png" />
  <!--[if lte IE 9]>
  <link rel="shortcut icon" id="faviconfallback" href="images/favicon1632.ico" />
  <![endif]-->

  <!-- START Styles -->
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/cupertino/jquery-ui-1.8.13.custom.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/cupertino/fim.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/stylesv2.css" media="screen" />
  <!-- END Styles -->

  <!-- START Scripts -->
  <script src="../webpro/client/js/jquery-1.6.1.min.js" type="text/javascript"></script>

  <script src="../webpro/client/js/jquery-ui-1.8.13.custom.min.js" type="text/javascript"></script>
  <script src="../webpro/client/js/jquery.plugins.js" type="text/javascript"></script>
  <script>
  function windowDraw() {
    $(\'body\').css(\'min-height\',window.innerHeight);
  }

  $(document).ready(function() {
    windowDraw();
    $(\'button, input[type=button], input[type=submit]\').button();
  });
  window.onwindowDraw = windowDraw;

  var alert = function(text) {
    dia.info(text,"Alert");
  };
  </script>
  <style>
  .ui-widget {
    font-size: 12px;
  }
  </style>
  <!-- END Scripts -->
</head>
<body>

<div id="part1">
  <h1>FreezeMessenger Installation: Introduction</h1><hr class="ui-widget-header" />

  Thank you for downloading FreezeMessenger! FreezeMessenger is a new, easy-to-use, and highly powerful messenger backend (with included frontend) intended for sites which want an easy yet powerful means to allow users to quickly communicate with each other. Unlike other solutions, FreezeMessenger has numerous benefits:<br />

  <ul>
    <li>Seperation of backend and frontend APIs to allow custom interfaces.</li>
    <li>Highly scalable, while still working on small installations.</li>
    <li>Easily extensible.</li>
  </ul><br />

  Still, there are some server requirements to using FreezeMessenger. Make sure all of the following are installed, then click "Next" below:<br />

  <ul>
    <li>MySQL 5.0.5+</li>
    <li>PHP 5.2+ (' . (floatval(phpversion()) > 5.2 ? 'Looks Good' : 'Not Detected - Version ' . phpversion() . ' Installed') . ')</li>
    <ul>
      <li>MySQL or MySQLi Extension (' . (extension_loaded('mysql') || extension_loaded('mysqli') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>Hash Extension (' . (extension_loaded('hash') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>Date/Time Extension (' . (extension_loaded('date') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>MCrypt Extension (' . (extension_loaded('mcrypt') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>PCRE Extension (' . (extension_loaded('pcre') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>Multibyte String Extension (' . (extension_loaded('mbstring') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>Document Object Module Extension (' . (extension_loaded('dom') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>APC Extension (' . (extension_loaded('apc') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
    </ul>
    <li>Proper Permissions (for automatic configuration file generation)</li>
    <ul>
      <li>Origin Directory Writable (' . (is_writable('../') ? 'Looks Good' : '<strong>Nope</strong>') . ')</li>
      <li>Config File Absent (' . (!file_exists('../config.php') ? 'Looks Good' : '<strong>Nope</strong>') . ')</li>
    </ul>
  </ul><br />

  If the MySQLi Extension is not present, you can still use FreezeMessenger, but will need to install it manually.<br /><br />

  <form onsubmit="return false;">
    <button style="float: right;" type="button" onclick="$(\'#part1\').slideUp(); $(\'#part2\').slideDown(); windowDraw();">Start &rarr;</button>
  </form>
</div>


<div id="part2" style="display: none;">
  <h1>FreezeMessenger Installation: MySQL Setup</h1><hr class="ui-widget-header" />

  First things first, please enter your MySQL connection details below, as well as a database (we can try to create the database ourselves, as well). If you are unable to proceed, try contacting your web host, or anyone who has helped you set up other things like this before.<br /><br />
  <form onsubmit="return false;" name="db_connect_form" id="db_connect_form">
    <table border="1" class="page">
      <tr class="ui-widget-header">
        <th colspan="2">Connection Settings</th>
      </tr>
      <tr>
        <td><strong>Driver</strong></td>
        <td><select name="db_driver">
          <option value="mysql">MySQL</option>
          <option value="mysqli">MySQLi</option>
        </select><br /><small>The datbase driver. For most users, "MySQL" will work fine.</td>
      </tr>
      <tr>
        <td><strong>Host</strong></td>
        <td><input type="text" name="db_host" value="' . $_SERVER['SERVER_NAME'] . '" /><br /><small>The host of the MySQL server. In most cases, the default shown here <em>should</em> work.</td>
      </tr>
      <tr>
        <td><strong>Port</strong></td>
        <td><input type="text" name="db_port" value="3306" /><br /><small>The port your database server is configured to work on. For MySQL and MySQLi, it is usually 3306.</small></td>
      </tr>
      <tr>
        <td><strong>Username</strong></td>
        <td><input type="text" name="db_userName" /><br /><small>The username of the user you will be connecting to the database with.</small></td>
      </tr>
      <tr>
        <td><strong>Password</strong></td>
        <td><input id="password" type="password" name="db_password" /><input type="button" onclick="$(\'<input type=\\\'text\\\' name=\\\'db_password\\\' />\').val($(\'#password\').val()).prependTo($(\'#password\').parent());$(\'#password\').remove();$(this).remove();" value="Show" /><br /><small>The password of the user you will be connecting to the database with.</small></td>
      </tr>
      <tr class="ui-widget-header">
        <th colspan="2">Database Settings</th>
      </tr>
      <tr>
        <td><strong>Database Name</strong></td>
        <td><input type="text" name="db_database" /><br /><small>The name of the database FreezeMessenger\'s data will be stored in.</small></td>
      </tr>
      <tr>
        <td><strong>Create Database?<strong></td>
        <td><input type="checkbox" name="db_createdb" /><br /><small>This will not overwrite existing databases. You are encouraged to create the database yourself, as otherwise default permissions, etc. will be used (which is rarely ideal).</td>
      </tr>
      <tr class="ui-widget-header">
        <th colspan="2">Table Settings</th>
      </tr>
      <tr>
        <td><strong>Table Prefix</strong></td>
        <td><input type="text" name="db_tableprefix" /><br /><small>The prefix that FreezeMessenger\'s tables should use. This can be left blank (or with the default), but if the database contains any other products you must use a <strong>different</strong> prefix than all other products.</small></td>
      </tr>
    </table>
  </form><br /><br />

  <form onsubmit="return false;">
    <button style="float: left;" type="button" onclick="$(\'#part2\').slideUp(); $(\'#part1\').slideDown(); windowDraw();">&larr; Back</button>
    <button style="float: right;" type="button" onclick="dia.full({ title : \'Installing\', content : \'<div style=&quot;text-align: center;&quot;>Installing now. Please wait a few moments. <img src=&quot;../webpro/images/ajax-loader.gif&quot; /></div>\', id : \'installingDia\'}); $.get(\'index.php?phase=1\',$(\'#db_connect_form\').serialize(),function(data) { $(\'#installingDia\').remove(); if (data == \'success\') { $(\'#part2\').slideUp(); $(\'#part3\').slideDown(); } else { dia.error(data); } } ); windowDraw();">Setup &rarr;</button>
  </form>
</div>

<div id="part3" style="display: none;">
  <h1>FreezeMessenger Installation: Generate Configuration File</h1><hr class="ui-widget-header" />
  Now that the database has been successfully installed, we must generate the configuration file. You can do this in a couple of ways: we would recommend simply entering the data below and you\'ll be on your way, though you can also do it manually by getting config.base.php from the install/ directory and saving it as config.php in the main directory.<br /><br />

  <form onsubmit="return false;" name="config_form" id="config_form">
    <table border="1" class="page">
      <tr class="ui-widget-header">
        <th colspan="2">Forum Integration</th>
      </tr>
      <tr>
        <td><strong>Forum Integration</strong></td>
        <td>
          <select name="forum">
            <option value="vanilla">No Integration (Broken)</option>
            <option value="vbulletin3">vBulletin 3.8</option>
            <option value="vbulletin4">vBulletin 4.1</option>
            <option value="phpbb">PHPBB 3</option>
          </select><br /><small>If you have a forum, you can enable more advanced features than without one, and prevent users from having to instll more than one account.</small>
        </td>
      </tr>
      <tr>
        <td><strong>Forum URL</strong></td>
        <td><input type="text" name="forum_url" /><br /><small>The URL your forum is installed on.</small></td>
      </tr>
      <tr>
        <td><strong>Forum Table Prefix</strong></td>
        <td><input type="text" name="forum_tableprefix" /><br /><small>The prefix of all tables the forum uses. You most likely defined this when you installed it. If unsure, check your forum\'s configuration file.</small></td>
      </tr>
      <tr class="ui-widget-header">
        <th colspan="2">Encryption</th>
      </tr>
      <tr>
        <td><strong>Enable Encryption?</strong></td>
        <td><select name="enable_encrypt"><option value="3">For Everything</option><option value="2">For Uploads Only</option><option value="1">For Messages Only</option><option value="0">For Nothing</option></select><br /><small>Encryption is strongly encouraged, though it will cause slight slowdown.</small></td>
      </tr>
      <tr>
        <td><strong>Encryption Phrase</strong></td>
        <td><input type="text" name="encrypt_salt" /><br /><small>This is a phrase used to encrypt the data. You can change this later as long as you don\'t remove referrences to this one.</td>
      </tr>
    </table><br /><br />
  </form>

  <form onsubmit="return false;">
    <button style="float: left;" type="button" onclick="$(\'#part3\').slideUp(); $(\'#part2\').slideDown(); windowDraw();">&larr; Back</button>
    <button style="float: right;" type="button" onclick="$.get(\'index.php?phase=2\',$(\'#db_connect_form\').serialize() + \'&\' + $(\'#config_form\').serialize(),function(data) { if (data == \'success\') { $(\'#part3\').slideUp(); $(\'#part4\').slideDown(); } else { alert(\'Could not create configuration file. Is the server allowed to write to it?\'); } } ); windowDraw();">Finish &rarr;</button>
  </form>
</div>


<div id="part4" style="display: none;">
  <h1>Freezemessenger Installation: All Done!</h1><hr class="ui-widget-header" />

  FreezeMessenger Installation is now complete. You\'re free to go wander (once you delete the install/ directory), though to put you in the right direction:<br />
  <ul>
    <li><a href="../">Start Chatting</a></li>
    <li><a href="../docs/">Go to the Documentation</a></li>
    <li><a href="../docs/interfaces.htm">Learn About Interfaces</a></li>
    <li><a href="../docs/configuration.htm">Learn About More Advance Configuration</a></li>
    <li><a href="http://www.josephtparsons.com/">Go to The Creator\'s Website</a></li>
    <li><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YL7K2CY59P9S6&lc=US&item_name=FreezeMessenger%20Development&item_number=freezemessenger&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted">Help Development with Some Money (The Whole Package is Free, but We Work More with Money!)</a></li>
  </ul>
</div>

</body>
</html>';
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

      $xmlData3 = new Xml2Array(file_get_contents('webProTemplate.xml')); // Get the XML Data from the webProTemplate.xml file, and feed it to the Xml2Array class
      $xmlData3 = $xmlData3->getAsArray(); // Get the XML data as an array
      $xmlData3 = $xmlData3['interface']; // Get the contents of the root node

      $xmlData4 = new Xml2Array(file_get_contents('webProLangEn.xml')); // Get the XML Data from the webProLangEn.xml file, and feed it to the Xml2Array class
      $xmlData4 = $xmlData4->getAsArray(); // Get the XML data as an array
      $xmlData4 = $xmlData4['languagePack']; // Get the contents of the root node

      $xmlData5 = new Xml2Array(file_get_contents('webLiteTemplate.xml')); // Get the XML Data from the webProTemplate.xml file, and feed it to the Xml2Array class
      $xmlData5 = $xmlData5->getAsArray(); // Get the XML data as an array
      $xmlData5 = $xmlData5['interface']; // Get the contents of the root node

      $xmlData6 = new Xml2Array(file_get_contents('webLiteLangEn.xml')); // Get the XML Data from the webProLangEn.xml file, and feed it to the Xml2Array class
      $xmlData6 = $xmlData6->getAsArray(); // Get the XML data as an array
      $xmlData6 = $xmlData6['languagePack']; // Get the contents of the root node



      // Check file versions.
      if ((float) $xmlData['@version'] != 3) { // It's possible people have an unsynced directory (or similar), so make sure we're working with the correct version of the file.
        die('The XML Schema Data Source if For An Improper Version');
      }
      elseif ((float) $xmlData2['@version'] != 3) { // It's possible people have an unsynced directory (or similar), so make sure we're working with the correct version of the file.
        die('The XML Insert Data Source if For An Improper Version');
      }
      elseif ((float) $xmlData3['@version'] != 3) { // It's possible people have an unsynced directory (or similar), so make sure we're working with the correct version of the file.
        die('The XML Interface Data Source if For An Improper Version');
      }
      elseif ((float) $xmlData4['@version'] != 3) { // It's possible people have an unsynced directory (or similar), so make sure we're working with the correct version of the file.
        die('The XML Language Data Source if For An Improper Version');
      }
      elseif ((float) $xmlData5['@version'] != 3) { // It's possible people have an unsynced directory (or similar), so make sure we're working with the correct version of the file.
        die('The XML Interface Data Source if For An Improper Version');
      }
      elseif ((float) $xmlData6['@version'] != 3) { // It's possible people have an unsynced directory (or similar), so make sure we're working with the correct version of the file.
        die('The XML Language Data Source if For An Improper Version');
      }
      elseif (!$xmlData4['@languageName']) {
        die('Language name not specified.');
      }
      elseif (!$xmlData4['@languageCode']) {
        die('Language code not specified.');
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





        /* Part 4: Insert WebPro, WebLite Templates */
        foreach (array($xmlData3, $xmlData5) AS $templateData) {
          if (!$database->insert($prefix . 'interfaces', array(
            'interfaceName' => $templateData['@name'],
            'version' => $templateData['metadata'][0]['version'][0]['#text'],
            'fimVersion' => $templateData['@version'],
            'author' => $templateData['metadata'][0]['copyright'][0]['#text'],
          ))) {
            die("Could not run query:\n" . $database->sourceQuery . "\n\nError:\n" . $database->error);
          }
          else {
            $interfaceIds[$templateData['@name']] = $database->insertId;
          }


          foreach ($templateData['templates'][0]['template'] AS $template) { // Run through each template from the XML
            if (!$database->insert($prefix . 'templates', array(
              'templateName' => $template['@name'],
              'data' => $template['#text'],
              'vars' => $template['@vars'],
              'interfaceId' => $interfaceIds[$templateData['@name']],
            ))) {
              die("Could not run query:\n" . $database->sourceQuery . "\n\nError:\n" . $database->error);
            }
          }
        }





        /* Part 5: Insert WebPro Phrases */
        $languages = array();

        foreach (array($xmlData4, $xmlData6) AS $phraseData) {
          if (!in_array($phraseData['@languageCode'], $languages)) {
            $languages[] = $phraseData['@languageCode'];
            if (!$database->insert($prefix . 'languages', array(
              'languageCode' => $phraseData['@languageCode'],
              'languageName' => $phraseData['@languageName'],
            ))) {
              die("Could not run query:\n" . $database->sourceQuery . "\n\nError:\n" . $database->error);
            }
          }

          foreach ($phraseData['phrases'][0]['phrase'] AS $phrase) { // Run through each phrase from the XML
            if (!$database->insert($prefix . 'phrases', array(
              'phraseName' => $phrase['@name'],
              'languageCode' => $phraseData['@languageCode'],
              'text' => $phrase['#text'],
              'interfaceId' => $interfaceIds[$phraseData['@product']],
            ))) {
              die("Could not run query:\n" . $database->sourceQuery . "\n\nError:\n" . $database->error);
            }
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