<?php
error_reporting(E_ALL ^ E_NOTICE);
/*if (file_exists('config.php')) {
  die('The configuration file (config.php) exists. Please remove it before attempting reinstallation.');
}*/

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
  function resize() {
    $(\'body\').css(\'height\',window.innerHeight);
  }

  $(document).ready(resize);
  window.onresize = resize;

  var alert = function(text) {
    dia.info(text,"Alert");
  };
  </script>
  <!-- END Scripts -->
</head>
<body>

<div id="part1">
  <h1>FreezeMessenger Installation: Introduction</h1><hr />

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
      <li>MySQL Extension (' . (extension_loaded('mysql') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>Hash Extension (' . (extension_loaded('hash') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>Date/Time Extension (' . (extension_loaded('date') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>MCrypt Extension (' . (extension_loaded('mcrypt') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>PCRE Extension (' . (extension_loaded('pcre') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>Multibyte String Extension (' . (extension_loaded('mbstring') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>SimpleXML Extension (' . (extension_loaded('simplexml') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>Optional, but Required in the Future: APC Extension (' . (extension_loaded('apc') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      <li>Optional, but Required for Installation: MySQLi Extension (' . (extension_loaded('mysqli') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
    </ul>
    <li>Proper Permissions (for automatic configuration file generation)</li>
    <ul>
      <li>Origin Directory Writable (' . (is_writable('../') ? 'Looks Good' : '<strong>Nope</strong>') . ')</li>
      <li>Config File Absent (' . (!file_exists('../config.php') ? 'Looks Good' : '<strong>Nope</strong>') . ')</li>
    </ul>
  </ul><br />

  If the MySQLi Extension is not present, you can still use FreezeMessenger, but will need to install it manually.<br /><br />

  <form onsubmit="return false;">
    <button style="float: right;" type="button" onclick="$(\'#part1\').slideUp(); $(\'#part2\').slideDown();">Start &rarr;</button>
  </form>
</div>


<div id="part2" style="display: none;">
  <h1>FreezeMessenger Installation: MySQL Setup</h1><hr />

  First things first, please enter your MySQL connection details below:<br /><br />
  <form onsubmit="return false;" name="mysql_connect_form" id="mysql_connect_form">
  <table>
  <tr>
    <td>Host</td>
    <td><input type="text" name="mysql_host" value="' . $_SERVER['SERVER_NAME'] . '" /></td>
  </tr>
  <tr>
    <td>Username</td>
    <td><input type="text" name="mysql_userName" /></td>
  </tr>
  <tr>
    <td>Password</td>
    <td><input type="text" name="mysql_password" /></td>
  </tr>
  </table>
  </form><br /><br />

  <strong>Note</strong>: You are strongly encourged to create the database and corrosponding user manually to avoid any security risks. If you want the installation script to create the database, the user you specify here must have permission to do so (usually the "root" user can do this).<br /><br />
  <form onsubmit="return false;">
  <button style="float: left;" type="button" onclick="$(\'#part2\').slideUp(); $(\'#part1\').slideDown();">&larr; Back</button>
  <button style="float: right;" type="button" onclick="$.get(\'index.php?phase=1\',$(\'#mysql_connect_form\').serialize(),function(data) { if (data == \'success\') { $(\'#part2\').slideUp(); $(\'#part3\').slideDown(); } else { alert(data); } } );">Verify &rarr;</button>
  </form>
</div>


<div id="part3" style="display: none;">
  <h1>FreezeMessenger Installation: MySQL Setup</h1><hr />
  MySQL connection successful. Next, we need to create or select the database. If you are integrating with a forum, leave "create database" unchecked and enter the database name. If you are not integrating with a forum, you will most likely want to create a new database, though an existing one can be used.

  <form onsubmit="return false;" name="mysql_db_form" id="mysql_db_form">
    <table>
      <tr>
        <td>Database Name</td>
        <td><input type="text" name="mysql_database" /></td>
      </tr>
      <tr>
        <td>Create Database?</td>
        <td><input type="checkbox" name="mysql_createdb" /></td>
      </tr>
    </table>
  </form><br /><br />

  <form onsubmit="return false;">
    <button style="float: left;" type="button" onclick="$(\'#part3\').slideUp(); $(\'#part2\').slideDown();">&larr; Back</button>
    <button style="float: right;" type="button" onclick="$.get(\'index.php?phase=2\',$(\'#mysql_connect_form\').serialize() + \'&\' + $(\'#mysql_db_form\').serialize(),function(data) { if (data == \'success\') { $(\'#part3\').slideUp(); $(\'#part4\').slideDown(); } else { alert(data); } } );">Connect &rarr;</button>
  </form>
</div>


<div id="part4" style="display: none;">
  <h1>FreezeMessenger Installation: MySQL Setup</h1><hr />
  MySQL database connection successful. Next, we need to create the tables. If these already exist (for instance, if you are in some way reinstalling), check "Do not create tables if they exist." Otherwise, leave this unchecked and they will be renamed (you will need to DROP them manually later). In addition, specify a table prefix if you are using forum integration. This is very important to avoid any potential issues.

  <form onsubmit="return false;" name="mysql_table_form" id="mysql_table_form">
    <table>
      <tr>
        <td>Do not create tables if they exist.</td>
        <td><input type="checkbox" name="mysql_nooverwrite" /></td>
      </tr>
      <tr>
        <td>Table Prefix</td>
        <td><input type="text" name="mysql_tableprefix" /></td>
      </tr>
    </table><br /><br />
  </form>

  <form onsubmit="return false;">
    <button style="float: left;" type="button" onclick="$(\'#part4\').slideUp(); $(\'#part3\').slideDown();">&larr; Back</button>
    <button style="float: right;" type="button" onclick="$.get(\'index.php?phase=3\',$(\'#mysql_connect_form\').serialize() + \'&\' + $(\'#mysql_db_form\').serialize() + \'&\' + $(\'#mysql_table_form\').serialize(),function(data) { if (data == \'success\') { $(\'#part4\').slideUp(); $(\'#part5\').slideDown(); } else { alert(data); } } );">Create Tables &rarr;</button>
  </form>
</div>


<div id="part5" style="display: none;">
  <h1>FreezeMessenger Installation: Generate Configuration File</h1><hr />
  Now that the database has been successfully installed, we must generate the configuration file. There are two ways of doing this: either modify config.base.php and save it as config.php or enter the details below. You are recommended to do this manually, though.<br /><br />

  <form onsubmit="return false;" name="config_form" id="config_form">
    <table>
      <tr>
        <td>Forum Integration</td>
        <td>
          <select name="forum">
            <option value="vanilla">No Integration</option>
            <option value="vbulletin3">vBulletin 3.8</option>
            <option value="vbulletin4">vBulletin 4.1</option>
            <option value="phpbb">PHPBB 3</option>
          </select>
        </td>
      </tr>
      <tr>
        <td>Forum URL</td>
        <td><input type="text" name="forum_url" /></td>
      </tr>
      <tr>
        <td>Forum Table Prefix</td>
        <td><input type="text" name="forum_tableprefix" /></td>
      </tr>
      <tr>
        <td>Encryption Phrase</td>
        <td><input type="text" name="encrypt_salt" /></td>
      </tr>
      <tr>
        <td>Enable Encryption?</td>
        <td><select name="enable_encrypt"><option value="3">For Everything</option><option value="2">For Uploads Only</option><option value="1">For Messages Only</option><option value="0">For Nothing</option></select></td>
      </tr>
    </table><br /><br />
  </form>

  <form onsubmit="return false;">
    <button style="float: left;" type="button" onclick="$(\'#part5\').slideUp(); $(\'#part4\').slideDown();">&larr; Back</button>
    <button style="float: right;" type="button" onclick="$.get(\'index.php?phase=5\',$(\'#mysql_connect_form\').serialize() + \'&\' + $(\'#mysql_db_form\').serialize() + \'&\' + $(\'#mysql_table_form\').serialize() + \'&\' + $(\'#config_form\').serialize(),function(data) { if (data == \'success\') { $(\'#part5\').slideUp(); $(\'#part6\').slideDown(); } else { alert(\'Could not create configuration file. Is the server allowed to write to it?\'); } } );">Finish &rarr;</button>
  </form>
</div>


<div id="part6" style="display: none;">
  <h1>Freezemessenger Installation: All Done!</h1>

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

  case 1: // MySQL Check
  $host = urldecode($_GET['mysql_host']);
  $userName = urldecode($_GET['mysql_userName']);
  $password = urldecode($_GET['mysql_password']);

  $mysqli = new mysqli($host,$userName,$password);

  if (mysqli_connect_error()) {
    echo 'Connection Error: ' . mysqli_connect_error();
  }
  else {
    // Get the MySQL version -- We will also check this when we create the tables.
    $version = $mysqli->query('SELECT VERSION()',MYSQLI_USE_RESULT);
    $version = $version->fetch_row();
    $version = $version[0];
    $version->free_result();
    $strippedVersion = '';


    // Get Only The Good Parts of the Version (we could also use a REGEX, but meh)
    for ($i = 0; $i < strlen($version); $i++) {
      if (is_int($version[$i]) || $version[$i] == '.') {
        $strippedVersion .= $version[$i];
      }
      else {
        break;
      }
    }

    $strippedVersionParts = explode('.',$strippedVersion); // Divide the decimal versions into an array; e.g. 5.0.1 becomes [0] => 5, [1] => 0, [2] => 1

    if ($strippedVersionParts[0] <= 4) { // MySQL 4 is a no-go.
      die('You have attempted to connect to a MySQL version 4 database. MySQL 5.0.5+ is required for FreezeMessenger.');
    }
    elseif ($strippedVersionParts[0] == 5 && $strippedVersionParts[1] == 0 && $strippedVersionParts[2] <= 4) { // MySQL 5.0.0-5.0.4 is also a no-go (we require the BIT type, even though in theory we could work without it)
      die('You have attempted to connect to an incompatible version of a MySQL version 5 database (MySQL 5.0.0-5.0.4). MySQL 5.0.5+ is required for FreezeMessenger.');
    }

    echo 'success';
  }
  break;

  case 2: // Database Check
  $host = urldecode($_GET['mysql_host']);
  $userName = urldecode($_GET['mysql_userName']);
  $password = urldecode($_GET['mysql_password']);
  $database = urldecode($_GET['mysql_database']);
  $createdb = urldecode($_GET['mysql_createdb']);

  $mysqli = new mysqli($host,$userName,$password);

  if (mysqli_connect_error()) {
    echo 'Connection Error: ' . mysqli_connect_error();
  }
  else {
    if ($mysqli->select_db($database)) {
      echo 'success';
    }
    elseif ($createdb) {
      $databaseSafe = $mysqli->real_escape_string($database);

      if ($mysqli->query("CREATE DATABASE {$database}")) {
        echo 'success';
      }
      else {
        echo 'Database Creation Unsuccessful';
      }
    }
    else {
      echo 'Invalid Database';
    }
  }
  break;

  case 3: // Table Check
  // If tables do not exist, import them from SQL dump files.
  // If tables do exist, recreate if specified or leave alone.

  $host = urldecode($_GET['mysql_host']);
  $userName = urldecode($_GET['mysql_userName']);
  $password = urldecode($_GET['mysql_password']);
  $database = urldecode($_GET['mysql_database']);
  $createdb = urldecode($_GET['mysql_createdb']);
  $prefix = urldecode($_GET['mysql_tableprefix']);
  $nooverwrite = urldecode($_GET['mysql_nooverwrite']);

  $mysqli = new mysqli($host,$userName,$password,$database);
  $mysqli->query("SET NAMES utf8") or die('Could not Set UTF8 Encoding');

  if (mysqli_connect_error()) {
    echo 'Connection Error: ' . mysqli_connect_error();
  }
  else {
    $databaseSafe = $mysqli->real_escape_string($database); // Escape the Database to Avoid any Mishaps


    // Get Pre-Existing Tables So We Don't Overwrite Any of Them Later
    $showTables = $mysqli->query("SHOW TABLES FROM $databaseSafe", MYSQLI_USE_RESULT);

    while ($table = $showTables->fetch_row()) {
      $mysqlTables[] = $table[0];
    }

    $showTables->free_result();


    // Process the XML Data
    $xmlData = new SimpleXMLElement(file_get_contents('dbSchema.xml'));

    // http://www.php.net/manual/en/ref.simplexml.php#103617
    // Modified for addition recursion needed for the specific code used.
    function xml2array($xmlObject, $out = array()) {
      foreach ((array) $xmlObject as $index => $node) {
        if (is_array($node)) {
          foreach ($node AS $index2 => $node2) {
            $node[$index2] = (is_object($node2)) ? xml2array($node2) : $node2;
          }
        }

        $out[$index] = (is_object($node)) ? xml2array($node) : $node;
      }

      return $out;
    }

    $xmlData = xml2array($xmlData);

    if ((int) $xmlData['@attributes']['version'] != 3) {
      die('The XML Data Source if For An Improper Version');
    }
    else {
      $queries = array(); // This will be the place where all finalized queries are put when they are ready to be executed.

      foreach ($xmlData['database']['table'] AS $table) { // Run through each table from the XML
        switch ($table['@attributes']['type']) {
          case 'general': // Use this normally, and for all perm. data
          $engine = 'InnoDB';
          break;
          case 'memory': // Use this for data that is transient.
          $engine = 'MEMORY';
          break;
        }

        foreach ($table['column'] AS $column) {
          $columns = array(); // We will use this to store the column fragments that will be implode()d into the final query.
          $column = $column['@attributes'];

          switch ($column['type']) {
            case 'int':
            $typePiece = 'INT(' . (int) $column['maxlen'] . ')';

            if (!isset($column['maxlen'])) {
              $typePiece = 'INT(8)'; // Sane default, really.
            }
            elseif ($coulmn['maxlen'] > 9) {// If the maxlen is greater than 9, we use LONGINT (0 - 9,223,372,036,854,775,807; 64 Bits / 8 Bytes)
              $typePiece = 'BIGINT(' . (int) $column['maxlen'] . ')';
            }
            elseif ($column['maxlen'] > 7) { // If the maxlen is greater than 7, we use INT (0 - 4,294,967,295; 32 Bits / 4 Bytes)
              $typePiece = 'INT(' . (int) $column['maxlen'] . ')';
            }
            elseif ($column['maxlen'] > 4) { // If the maxlen is greater than 4, we use MEDIUMINT (0 - 16,777,215; 24 Bits / 3 Bytes)
              $typePiece = 'MEDIUMINT(' . (int) $column['maxlen'] . ')';
            }
            elseif ($column['maxlen'] > 2) { // If the maxlen is greater than 2, we use SMALLINT (0 - 65,535; 16 Bits / 2 Bytes)
              $typePiece = 'SMALLINT(' . (int) $column['maxlen'] . ')';
            }
            else {
              $typePiece = 'TINYINT(' . (int) $column['maxlen'] . ')';
            }

            if (isset($column['autoincrement'])) {
              if ($column['autoincrement'] == true) {
                $typePiece .= ' AUTO_INCREMENT'; // Ya know, that thing where it sets itself.
              }
            }
            break;

            case 'string':
            if (!isset($column['maxlen'])) {
              $typePiece = 'TEXT';
            }
            elseif ($coulmn['maxlen'] > 2097151) { // If the maxlen is greater than (16MB / 8) - 1B, use MEDIUM TEXT -- the division is to accompony multibyte text.
              $typePiece = 'LONGTEXT(' . (int) $column['maxlen'] . ')';
            }
            elseif ($column['maxlen'] > 8191) { // If the maxlen is greater than (64KB / 8) - 1B, use MEDIUM TEXT -- the division is to accompony multibyte text.
              $typePiece = 'MEDIUMTEXT(' . (int) $column['maxlen'] . ')';
            }
            elseif ($column['maxlen'] > 100) { // If the maxlen is greater than 100, we use TEXT since it is most likely more optimized.
              $typePiece = 'TEXT(' . (int) $column['maxlen'] . ')';
            }
            else {
              $typePiece = 'VARCHAR(' . (int) $column['maxlen'] . ')';
            }

            $typePiece .= ' CHARACTER SET utf8 COLLATE utf8_bin';
            break;

            case 'bitfield':
            if (!isset($column['bits'])) {
              $typePiece = 'BIT(8)'; // Sane default
            }
            else {
              $rounds = 0;

              for ($i = (int) $column['bits']; $i >= 1; $i /= 2) {
                if (!is_int($i)) {
                  die('(Developer Issues - Please Report) Invalid Bitfield');
                }
                else {
                  $rounds++;
                }
              }

              $typePiece = 'BIT(' . $rounds . ')'; // This is new to MySQL 5.0.5 (5.0.3 for MySIAM). In theory, INT would be just as good (but unoptimized), but meh.
            }
            break;

            case 'time':
            $typePiece = 'TIMESTAMP';
            break;
          }

          if (isset($column['default'])) {
            $typePiece .= 'DEFAULT "' . $mysqli->real_escape_string($column['comment']) . '"';
          }

          $columns[] = '`' . $column['name'] . '` ' . $typePiece . ' NOT NULL' . (isset($column['comment']) ? ' COMMENT "' . $mysqli->real_escape_string($column['comment']) . '"' : '');
        }

        $queries[] = 'CREATE TABLE IF NOT EXISTS `' . $prefix . $table['@attributes']['name']'` (
  ' . implode("\n  ",$columns) . '
  PRIMARY KEY (`bbcodeId`)
) ENGINE="' . $engine . '" COMMENT="' . $mysqli-real_escape_string($table['@attributes']['comment']) . '" DEFAULT CHARSET="utf8";';*/
      }

      $importFiles = scandir('sqldump');
      foreach ($importFiles AS $file) {
        $queries = array();

        if ($file == '.' || $file == '..') continue;

        $contents = file_get_contents("sqldump/{$file}");
        $contents = str_replace(array('{prefix}','{engine}'),array($prefix,'InnoDB'),$contents);
        $table = $prefix . str_replace('.sql','',$file);

        if (@in_array($table,$mysqlTables) && $nooverwrite) continue;
        elseif (@in_array($table,$mysqlTables)) {
          $newTable = $table . '~' . time();

          if (!$mysqli->query("RENAME TABLE `$table` TO `$newTable`")) {
            die("Could Not Rename Table '$table'");
          }
        }

        $queries = explode('-- DIVIDE', $contents);

        foreach ($queries AS $query) {
          if (!trim($query)) continue;

          if (!$mysqli->query($query)) {
            echo $query;
            echo $mysqli->error;
            die('Could Not Run Query');
          }
        }
      }
    }
    echo 'success';
  }

  break;

  case 5: // Config File
  $host = urldecode($_GET['mysql_host']);
  $userName = urldecode($_GET['mysql_userName']);
  $password = urldecode($_GET['mysql_password']);
  $database = urldecode($_GET['mysql_database']);
  $prefix = urldecode($_GET['mysql_tableprefix']);

  $forum = urldecode($_GET['forum']);
  $forumUrl = urldecode($_GET['forum_url']);
  $forumTablePrefix = urldecode($_GET['forum_tableprefix']);

  $encryptSalt = urldecode($_GET['encrypt_salt']);
  $enableEncrypt = (int) $_GET['enable_encrypt'];

  $base = file_get_contents('config.base.php');

  $find = array(
    '$dbConnect[\'core\'][\'host\'] = \'localhost\';
$dbConnect[\'slave\'][\'host\'] = \'localhost\';
$dbConnect[\'integration\'][\'host\'] = \'localhost\';',
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
  );

  $replace = array(
    '$dbConnect[\'core\'][\'host\'] = \'' . $host . '\';
$dbConnect[\'slave\'][\'host\'] = \'' . $host . '\';
$dbConnect[\'integration\'][\'host\'] = \'' . $host . '\';',
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
    '$installUrl = \'' . str_replace('/install/index.php','',$_SERVER['HTTP_REFERER']) . '\';',
    '$salts = array(
  101 => \'' . $encryptSalt . '\',
);',
    '$encrypt = ' . ($enableEncrypt & 1 ? 'true' : 'false') . ';',
    '$encryptUploads = ' . ($enableEncrypt & 2 ? 'true' : 'false') . ';',
  );



  $baseNew = str_replace($find,$replace,$base);

  if (file_put_contents('../config.php',$baseNew)) {
    echo 'success';
  }
  break;

  case 'dev': // TODO: Remove
  $mysqli = new mysqli('localhost','a','a','phpbb3');
  $mysqli->query("SET NAMES utf8");
  $prefix = 'a_';
  $tables = array("templates","phrases","sessions");

  foreach ($tables AS $table) {
    $mysqli->query("DROP TABLE {$prefix}{$table}");

    $contents = file_get_contents("sqldump/$table.sql");
    $contents = str_replace(array('{prefix}','{engine}'),array($prefix,'InnoDB'),$contents);

    $queries = explode('-- DIVIDE', $contents);

    foreach ($queries AS $query) {
      if (!trim($query)) continue;

      if (!$mysqli->query($query)) {
        echo $query;
        echo $mysqli->error;
        die('Could Not Run Query');
      }
    }
  }

  echo 'success';
  break;
}
?>