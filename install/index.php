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

/* Frontend of Install
 * See worker.php for backend of install. 
 * TODO: Translation support. */

$installFlags = 0; // Create an integer (bitfield) that will store all install fields.
$installStatusDB = 0;


// Define CONSTANTS (its a bit excessive here, but...)
define('INSTALL_ISSUE_PHP_VERSION', 1);
define('INSTALL_ISSUE_DB', 16);
define('INSTALL_ISSUE_HASH', 256);
define('INSTALL_ISSUE_DATE', 512);
define('INSTALL_ISSUE_MCRYPT', 1024);
define('INSTALL_ISSUE_PCRE', 2048);
define('INSTALL_ISSUE_MBSTRING', 4096);
define('INSTALL_ISSUE_DOM', 8192);
define('INSTALL_ISSUE_APC', 32768);
define('INSTALL_ISSUE_DOM', 8192);
define('INSTALL_ISSUE_APC', 32768);
define('INSTALL_ISSUE_WRITEORIGINDIR', 1048576);
define('INSTALL_ISSUE_CONFIGEXISTS', 2097152);


define('INSTALL_DB_MYSQL', 1);
define('INSTALL_DB_MYSQLI', 2);
//define('INSTALL_DB_POSTGRESQL', 4);
define('INSTALL_DB_PDO', 4);
define('INSTALL_DB_MSSQL', 8);


// Install Status - DB
if (extension_loaded('mysql')) $installStatusDB += INSTALL_DB_MYSQL;
if (extension_loaded('mysqli')) $installStatusDB += INSTALL_DB_MYSQLI;
//if (extension_loaded('postgresql')) $installStatusDB += INSTALL_DB_POSTGRESQL;

// PHP Issues
if (floatval(phpversion()) < 5.2) $installFlags += INSTALL_ISSUE_PHP_VERSION;

// DB Issues
if ($installStatusDB == 0) $installFlags += INSTALL_ISSUE_DB;

// Plugin Issues
if (!extension_loaded('hash')) $installFlags += INSTALL_ISSUE_HASH;
if (!extension_loaded('date')) $installFlags += INSTALL_ISSUE_DATE;
if (!extension_loaded('mcrypt')) $installFlags += INSTALL_ISSUE_MCRYPT;
if (!extension_loaded('pcre')) $installFlags += INSTALL_ISSUE_PCRE;
if (!extension_loaded('mbstring')) $installFlags += INSTALL_ISSUE_MBSTRING;
if (!extension_loaded('dom')) $installFlags += INSTALL_ISSUE_DOM;
if (!extension_loaded('apc')) $installFlags += INSTALL_ISSUE_APC;

// FS Issues
if (!is_writable('../')) $installFlags += INSTALL_ISSUE_WRITEORIGINDIR;
if (file_exists('../config.php')) $installFlags += INSTALL_ISSUE_CONFIGEXISTS;

?>

<!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<!-- Note: Installation Backend @ Worker.php -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Freeze Messenger Installation</title>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="author" content="Joseph T. Parsons" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="icon" id="favicon" type="image/png" href="images/favicon.png" />
  <!--[if lte IE 9]>
  <link rel="shortcut icon" id="faviconfallback" href="images/favicon1632.ico" />
  <![endif]-->

  <!-- START Styles -->
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/start/jquery-ui-1.8.16.custom.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/start/fim.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/stylesv2.css" media="screen" />
  <style>
  h1 {
    margin: 0px;
  }

  .main {
    width: 800px;
    margin-left: auto;
    margin-right: auto;
    display: block;
  }
  </style>
  <!-- END Styles -->

  <!-- START Scripts -->
  <script src="../webpro/client/js/jquery-1.6.2.min.js" type="text/javascript"></script>

  <script src="../webpro/client/js/jquery-ui-1.8.16.custom.min.js" type="text/javascript"></script>
  <script src="../webpro/client/js/jquery.plugins.js" type="text/javascript"></script>
  <script>
  function windowDraw() {
    $('body').css('min-height',window.innerHeight);
  }

  $(document).ready(function() {
    windowDraw();
    $('button, input[type=button], input[type=submit]').button();
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
<body class="ui-widget">

<div id="part1" class="main">
  <h1 class="ui-widget-header">FreezeMessenger Installation: Introduction</h1>
  <div class="ui-widget-content">
  Thank you for downloading FreezeMessenger! FreezeMessenger is a new, easy-to-use, and highly powerful messenger backend (with included frontend) intended for sites which want an easy yet powerful means to allow users to quickly communicate with each other. Unlike other solutions, FreezeMessenger has numerous benefits:<br />

  <ul>
    <li>Seperation of backend and frontend APIs to allow custom interfaces.</li>
    <li>Highly scalable, while still working on small installations.</li>
    <li>Easily extensible.</li>
  </ul><br />

  Still, there are some server requirements to using FreezeMessenger. Once allow of the following are satisfied, click "Start" below:<br />


  <ul>
    <li>PHP 5.2+ (<?php echo (($installFlags & INSTALL_ISSUE_PHP_VERSION) ? '<strong>Not Detected - Version ' . phpversion() . ' Installed</strong>' : 'Looks Good'); ?>)</li>
    <ul>
      <li>Database, Any of The Following: (<?php echo (($installFlags & INSTALL_ISSUE_DB) ? '<strong>Not Detected</strong>' : 'Looks Good'); ?>)</li>
      <ul>
        <li>MySQL (<?php echo (($installStatusDB & INSTALL_DB_MYSQL) ? 'Looks Good' : '<strong>Not Detected</strong>'); ?>)</li>
        <li>MySQLi (<?php echo (($installStatusDB & INSTALL_DB_MYSQLI) ? 'Looks Good' : '<strong>Not Detected</strong>'); ?>)</li>
        <!--<li>PostGreSQL (php echo (($installStatusDB & INSTALL_DB_POSTGRESQL) ? 'Looks Good' : '<strong>Not Detected</strong>'); )</li>-->
      </ul>
      <li>Hash Extension (<?php echo (($installFlags & INSTALL_ISSUE_HASH) ? '<strong>Not Detected</strong>' : 'Looks Good'); ?>)</li>
      <li>Date/Time Extension (<?php echo (($installFlags & INSTALL_ISSUE_DATE) ? '<strong>Not Detected</strong>' : 'Looks Good'); ?>)</li>
      <li>MCrypt Extension (<?php echo (($installFlags & INSTALL_ISSUE_MCRYPT) ? '<strong>Not Detected</strong>' : 'Looks Good'); ?>)</li>
      <li>PCRE Extension (<?php echo (($installFlags & INSTALL_ISSUE_PCRE) ? '<strong>Not Detected</strong>' : 'Looks Good'); ?>)</li>
      <li>Multibyte String Extension (<?php echo (($installFlags & INSTALL_ISSUE_MBSTRING) ? '<strong>Not Detected</strong>' : 'Looks Good'); ?>)</li>
      <li>Document Object Module Extension (<?php echo (($installFlags & INSTALL_ISSUE_DOM) ? '<strong>Not Detected</strong>' : 'Looks Good'); ?>)</li>
      <li>APC Extension (<?php echo (($installFlags & INSTALL_ISSUE_APC) ? '<strong>Not Detected</strong>' : 'Looks Good'); ?>)</li>
    </ul>
    <li>Proper Permissions (for automatic configuration file generation)</li>
    <ul>
      <li>Origin Directory Writable (<?php echo (($installFlags & INSTALL_ISSUE_WRITEORIGINDIR) ? '<strong>Not Detected</strong>' : 'Looks Good'); ?>)</li>
      <li>Config File Absent (<?php echo (($installFlags & INSTALL_ISSUE_CONFIGEXISTS) ? '<strong>Not Detected</strong>' : 'Looks Good'); ?>)</li>
    </ul>
  </ul><br />

  <div style="height: 30px;">
    <form onsubmit="return false;">
      <?php
       if ($installFlags > 0) {
         echo '<strong>Cannot continue.</strong>';
       }
       else {
         echo '<button style="float: right;" type="button" onclick="$(\'#part1\').slideUp(); $(\'#part2\').slideDown(); windowDraw();">Start &rarr;</button>';
       }
       ?>
    </form>
  </div>
  </div>
</div>


<div id="part2" style="display: none;" class="main">
  <h1 class="ui-widget-header">FreezeMessenger Installation: MySQL Setup</h1>
  <div class="ui-widget-content">
  First things first, please enter your MySQL connection details below, as well as a database (we can try to create the database ourselves, as well). If you are unable to proceed, try contacting your web host, or anyone who has helped you set up other things like this before.<br /><br />
  <form onsubmit="return false;" name="db_connect_form" id="db_connect_form">
    <table border="1" class="page">
      <tr class="ui-widget-header">
        <th colspan="2">Connection Settings</th>
      </tr>
      <tr>
        <td><strong>Driver</strong></td>
        <td><select name="db_driver">
        <?php
          if ($installStatusDB & INSTALL_DB_MYSQL) echo '<option value="mysql">MySQL</option>';
          if ($installStatusDB & INSTALL_DB_MYSQLI) echo '<option value="mysql">MySQLi</option>';
          //if ($installStatusDB & INSTALL_DB_POSTGRESQL) echo '<option value="mysql">PostGreSQL (Broken)</option>';
        ?>
        </select><br /><small>The datbase driver. For most users, "MySQL" will work fine.</td>
      </tr>
      <tr>
        <td><strong>Host</strong></td>
        <td><input type="text" name="db_host" value="<?php echo $_SERVER['SERVER_NAME']; ?>" /><br /><small>The host of the MySQL server. In most cases, the default shown here <em>should</em> work.</td>
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
        <td><input id="password" type="password" name="db_password" /><input type="button" onclick="$('<input type=\'text\' name=\'db_password\' />').val($('#password').val()).prependTo($('#password').parent()); $('#password').remove();$(this).remove();" value="Show" /><br /><small>The password of the user you will be connecting to the database with.</small></td>
      </tr>
      <tr class="ui-widget-header">
        <th colspan="2">Database Settings</th>
      </tr>
      <tr>
        <td><strong>Database Name</strong></td>
        <td><input type="text" name="db_database" /><br /><small>The name of the database FreezeMessenger's data will be stored in. <strong>If you are integrating with a forum, this must be the same database the forum uses.</strong></small></td>
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
        <td><input type="text" name="db_tableprefix" /><br /><small>The prefix that FreezeMessenger's tables should use. This can be left blank (or with the default), but if the database contains any other products you must use a <strong>different</strong> prefix than all other products.</small></td>
      </tr>
    </table>
  </form><br /><br />


  <div style="height: 30px;">
    <form onsubmit="return false;">
      <button style="float: left;" type="button" onclick="$('#part2').slideUp(); $('#part1').slideDown(); windowDraw();">&larr; Back</button>
      <button style="float: right;" type="button" onclick="dia.full({ title : 'Installing', content : '<div style=&quot;text-align: center;&quot;>Installing now. Please wait a few moments. <img src=&quot;../webpro/images/ajax-loader.gif&quot; /></div>', id : 'installingDia'}); $.get('./worker.php?phase=1',$('#db_connect_form').serialize(),function(data) { $('#installingDia').remove(); if (data == 'success') { $('#part2').slideUp(); $('#part3').slideDown(); } else { dia.error(data); } } ); windowDraw();">Setup &rarr;</button>
    </form>
  </div>
  </div>
</div>

<div id="part3" style="display: none;" class="main">
  <h1 class="ui-widget-header">FreezeMessenger Installation: Generate Configuration File</h1>
  <div class="ui-widget-content">
  Now that the database has been successfully installed, we must generate the configuration file. You can do this in a couple of ways: we would recommend simply entering the data below and you'll be on your way, though you can also do it manually by getting config.base.php from the install/ directory and saving it as config.php in the main directory.<br /><br />

  <form onsubmit="return false;" name="config_form" id="config_form">
    <table border="1" class="page">
      <tr class="ui-widget-header">
        <th colspan="2">Forum Integration</th>
      </tr>
      <tr>
        <td><strong>Forum Integration</strong></td>
        <td>
          <select name="forum">
            <option value="vanilla">No Integration</option>
            <option value="vbulletin3">vBulletin 3.8</option>
            <option value="vbulletin4">vBulletin 4.1</option>
            <option value="phpbb">PHPBB 3</option>
          </select><br /><small>If you have a forum, you can enable more advanced features than without one, and prevent users from having to create more than one account.</small>
        </td>
      </tr>
      <tr>
        <td><strong>Forum URL</strong></td>
        <td><input type="text" name="forum_url" /><br /><small>The URL your forum is installed on.</small></td>
      </tr>
      <tr>
        <td><strong>Forum Table Prefix</strong></td>
        <td><input type="text" name="forum_tableprefix" /><br /><small>The prefix of all tables the forum uses. You most likely defined this when you installed it. If unsure, check your forum's configuration file.</small></td>
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
        <td><input type="text" name="encrypt_salt" /><br /><small>This is a phrase used to encrypt the data. You can change this later as long as you don't remove referrences to this one.</td>
      </tr>
      <!--<tr class="ui-widget-header">
        <th colspan="2">Other Settings</th>
      </tr>
      <tr>
        <td><strong>Cache Method (Broken - We're Working On It)</strong></td>
        <td><select name="cache_method">
          ' . (extension_loaded('apc') ? '<option value="apc">APC</option>' : '') . '
          ' . (extension_loaded('memcache') ? '<option value="memcache">MemCache</option>' : '') . '
        </select><br /><small>The cache to use. If you are able to set up MemCache, you are encouraged to use it. APC is provided with PHP 5.4 and can be installed with most distributions. If neither option is listed, FreezeMessenger will use far more CPU than neccessary.</td>
      </tr>-->
    </table><br /><br />
  </form>

  <div style="height: 30px;">
    <form onsubmit="return false;">
      <button style="float: left;" type="button" onclick="$('#part3').slideUp(); $('#part2').slideDown(); windowDraw();">&larr; Back</button>
      <button style="float: right;" type="button" onclick="$.get('./worker.php?phase=2',$('#db_connect_form').serialize() + '&' + $('#config_form').serialize(),function(data) { if (data == 'success') { $('#part3').slideUp(); $('#part4').slideDown(); } else { alert('Could not create configuration file. Is the server allowed to write to it?'); } } ); windowDraw();">Finish &rarr;</button>
    </form>
  </div>
  </div>
</div>


<div id="part4" style="display: none;" class="main">
  <h1 class="ui-widget-header">Freezemessenger Installation: All Done!</h1>
  <div class="ui-widget-content">
  FreezeMessenger Installation is now complete. You're free to go wander (once you delete the install/ directory), though to put you in the right direction:<br />
  <ul>
    <li><a href="../">Start Chatting</a></li>
    <li><a href="../docs/">Go to the Documentation</a></li>
    <li><a href="../docs/interfaces.htm">Learn About Interfaces</a></li>
    <li><a href="../docs/configuration.htm">Learn About More Advance Configuration</a></li>
    <li><a href="http://www.josephtparsons.com/">Go to The Creator's Website</a></li>
    <li><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YL7K2CY59P9S6&lc=US&item_name=FreezeMessenger%20Development&item_number=freezemessenger&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted">Help Development with Some Money (The Whole Package is Free, but We Work More with Money!)</a></li>
  </ul>
  </div>
</div>

</body>
</html>
