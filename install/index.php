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
  <script src="../webpro/client/js/jquery.plugins.05182011.min.js" type="text/javascript"></script>
  <script>
  function resize() {
    $(\'body\').css(\'height\',window.innerHeight);
  }

  $(document).ready(resize);
  window.onresize = resize;

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
  <li>MySQL 5.0+</li>
  <li>PHP 5.0+ (' . (floatval(phpversion()) > 5.0 ? 'Looks Good' : 'Not Detected - Version ' . phpversion() . ' Installed') . ')</li>
  <ul>
    <li>Multibyte String Extension (' . (function_exists('mb_get_info') ? 'Looks Good' : 'Not Detected') . ')</li>
    <li>MySQLi Extension (' . (function_exists('mysqli_connect') ? 'Looks Good' : 'Not Detected') . ')</li>
    <li>MySQL Extension (' . (function_exists('mysql_connect') ? 'Looks Good' : 'Not Detected') . ')</li>
    <li>Hash Extension (' . (function_exists('hash') ? 'Looks Good' : 'Not Detected') . ')</li>
    <li>Date/Time Extension (' . (function_exists('date') ? 'Looks Good' : 'Not Detected') . ')</li>
    <li>MCrypt Extension (' . (function_exists('mcrypt_encrypt') ? 'Looks Good' : 'Not Detected') . ')</li>
    <li>Multibyte String Extension (' . (function_exists('mb_get_info') ? 'Looks Good' : 'Not Detected') . ')</li>
  </ul>
  <li>Proper Permissions (for automatic configuration file generation)</li>
  <ul>
    <li>Origin Directory Writable (' . (is_writable('../') ? 'Looks Good' : 'Nope') . ')</li>
  </ul>
</ul><br />

If the MySQLi Extension is not present, you can still use FreezeMessenger, but will need to install it manually.<br /><br />

<form onsubmit="return false;">
<button style="float: right;" type="button" onclick="$(\'#part1\').slideUp(); $(\'#part2\').slideDown();">Next &rarr;</button>
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
<button style="float: right;" type="button" onclick="$.get(\'index.php?phase=1\',$(\'#mysql_connect_form\').serialize(),function(data) { if (data == \'success\') { $(\'#part2\').slideUp(); $(\'#part3\').slideDown(); } else { alert(\'Could not connect.\'); } } );">Verify &rarr;</button>
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
<button style="float: right;" type="button" onclick="$.get(\'index.php?phase=2\',$(\'#mysql_connect_form\').serialize() + \'&\' + $(\'#mysql_db_form\').serialize(),function(data) { if (data == \'success\') { $(\'#part3\').slideUp(); $(\'#part4\').slideDown(); } else { alert(\'Could not connect.\'); } } );">Verify &rarr;</button>
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
<button style="float: right;" type="button" onclick="$.get(\'index.php?phase=3\',$(\'#mysql_connect_form\').serialize() + \'&\' + $(\'#mysql_db_form\').serialize() + \'&\' + $(\'#mysql_table_form\').serialize(),function(data) { if (data == \'success\') { $(\'#part4\').slideUp(); $(\'#part5\').slideDown(); } else { alert(\'Could not connect.\'); } } );">Verify &rarr;</button>
</form>
</div>

<div id="part5" style="display: none;">
<h1>FreezeMessenger Installation: Generate Configuration File</h1><hr />
Now that the database has been successfully installed, we must generate the configuration file. There are two ways of doing this: either modify config.base.php and save it as config.php or enter the details below. You are recommended to do this manually, though.<br /><br />

<form onsubmit="return false;" name="config_form" id="config_form">
<table>
<tr>
  <td>Forum Integration</td>
  <td><select name="forum"><option value="vanilla">No Integration</option><option value="vbulletin">vBulletin 3.8</option><option value="phpbb">PHPBB 3</option></select></td>
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
<button style="float: right;" type="button" onclick="$.get(\'index.php?phase=5\',$(\'#mysql_connect_form\').serialize() + \'&\' + $(\'#mysql_db_form\').serialize() + \'&\' + $(\'#mysql_table_form\').serialize() + \'&\' + $(\'#config_form\').serialize(),function(data) { if (data == \'success\') { $(\'#part4\').slideUp(); $(\'#part5\').slideDown(); } else { alert(\'Could not connect.\'); } } );">Verify &rarr;</button>
</form>
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
  $mysqli->query("SET NAMES utf8") or die('Hello');

  if (mysqli_connect_error()) {
    echo 'Connection Error: ' . mysqli_connect_error();
  }
  else {
    $databaseSafe = $mysqli->real_escape_string($database);
    $showTables = $mysqli->query("SHOW TABLES FROM $databaseSafe",MYSQLI_USE_RESULT);

    while ($table = $showTables->fetch_row()) {
      $mysqlTables[] = $table[0];
    }

    $showTables->free_result();

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
  $forumSalt = urldecode($_GET['forum_salt']);
  $encryptSalt = urldecode($_GET['encrypt_salt']);

  $enableEncrypt = (int) $_GET['enable_encrypt'];

  $base = file_get_contents('config.base.php');

  $find = array(
    '$sqlHost = \'localhost\';',
    '$sqlUser = \'\';',
    '$sqlPassword = \'\';',
    '$sqlDatabase = \'\';',
    '$sqlPrefix = \'\';',
    '$loginMethod = \'vanilla\';',
    '$installLoc = \'\';',
    '$installUrl = \'\';',
    '$forumUrl = \'\';',
    '$forumTablePrefix = \'\';',
    '$forumSalt = \'\';',
    '$superUsers = array();',
    '$salts = array(
  101 => \'xxx\',
);',
     '$encrypt = true;',
     '$encryptUploads = true;',
  );

  $replace = array(
    '$sqlHost = \'' . $host . '\';',
    '$sqlUser = \'' . $userName . '\';',
    '$sqlPassword = \'' . $password . '\';',
    '$sqlDatabase = \'' . $database . '\';',
    '$sqlPrefix = \'' . $prefix . '\';',
    '$loginMethod = \'' . $forum . '\';',
    '$installLoc = \'' . __DIR__ . '\';',
    '$installUrl = \'' . str_replace('index.php','',$_SERVER['HTTP_REFERER']) . '\';',
    '$forumUrl = \'' . $forumUrl . '\';',
    '$forumTablePrefix = \'' . $forumTablePrefix . '\';',
    '$forumSalt = \'' . $forumSalt . '\';',
    '$superUsers = array(' . ($forum == 'phpbb' ? 2 : 1) . ');',
    '$salts = array(
  101 => \'' . $encryptSalt . '\',
);',
    '$encrypt = ' . ($enableEncrypt & 1 ? 'true' : 'false') . ';',
    '$encryptUploads = ' . ($enableEncrypt & 2 ? 'true' : 'false') . ';',
  );



  $baseNew = str_replace($find,$replace,$base);

  file_put_contents('../config.php',$baseNew);
  break;

  case 'dev': // TODO: Remove
  $mysqli = new mysqli('localhost','a','a','phpbb3');
  $mysqli->query("SET NAMES utf8");
  $prefix = 'fim3_';
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