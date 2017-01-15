<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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
$optionalInstallFlags = 0; // ""
$installStatusDB = 0; // And one for supported DBs.


/* Available in 5.2.7+ */
if (!defined('PHP_VERSION_ID')) {
  $version = explode('.', PHP_VERSION);

  define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}


// Define CONSTANTS (it's a bit excessive here, but...)
define('INSTALL_ISSUE_PHP_VERSION', 1);
define('INSTALL_ISSUE_DB', 16);
define('INSTALL_ISSUE_DATE', 512);
define('INSTALL_ISSUE_PCRE', 2048);
define('INSTALL_ISSUE_DOM', 8192);
define('INSTALL_ISSUE_WRITEORIGINDIR', 1048576);
define('INSTALL_ISSUE_CONFIGEXISTS', 2097152);
define('INSTALL_ISSUE_WRITEDATADIR', 4194304);

define('OPTIONAL_INSTALL_ISSUE_MCRYPT', 256);
define('OPTIONAL_INSTALL_ISSUE_HASH', 512);
define('OPTIONAL_INSTALL_ISSUE_SHA256', 1024);
define('OPTIONAL_INSTALL_ISSUE_SHA512', 2048);
define('OPTIONAL_INSTALL_ISSUE_MBSTRING', 4096);
define('OPTIONAL_INSTALL_ISSUE_JSON', 16384);
define('OPTIONAL_INSTALL_ISSUE_APC', 32768);
define('OPTIONAL_INSTALL_ISSUE_CURL', 65536);
define('OPTIONAL_INSTALL_ISSUE_TRANSLITERATOR', 131072);

define('INSTALL_DB_MYSQL', 1);
define('INSTALL_DB_MYSQLI', 2);
define('INSTALL_DB_PDO_MYSQL', 4);
define('INSTALL_DB_POSTGRESQL', 8);
define('INSTALL_DB_PDO_POSTGRESQL', 16);
define('INSTALL_DB_MSSQL', 32);


// Install Status - DB
if (extension_loaded('mysql') && PHP_VERSION_ID < 50500) $installStatusDB += INSTALL_DB_MYSQL; // MySQL is deprecated in 5.5. We could just ignore this, but instead it seems reasonable to remove the feature entirely -- after all, mysqli and pdo_mysql should both be options.
if (extension_loaded('mysqli') && PHP_VERSION_ID > 50209) $installStatusDB += INSTALL_DB_MYSQLI; // MySQLi has a weird issue that was fixed in 5.2.9 and 5.3.0; see http://www.php.net/manual/en/mysqli.connect-error.php
if (extension_loaded('pdo_mysql')) $installStatusDB += INSTALL_DB_PDO_MYSQL;
if (extension_loaded('pdo_pgsql')) $installStatusDB += INSTALL_DB_PDO_POSTGRESQL;
if (extension_loaded('pgsql')) $installStatusDB += INSTALL_DB_POSTGRESQL;


// PHP Issues
if (PHP_VERSION_ID < 50200) $installFlags += INSTALL_ISSUE_PHP_VERSION;


// DB Issues
if ($installStatusDB == 0) $installFlags += INSTALL_ISSUE_DB;


// Plugin Issues
if (!extension_loaded('date')) $installFlags += INSTALL_ISSUE_DATE;
if (!extension_loaded('pcre')) $installFlags += INSTALL_ISSUE_PCRE;
if (!extension_loaded('mbstring')) $installFlags += INSTALL_ISSUE_MBSTRING;
if (!extension_loaded('dom')) $installFlags += INSTALL_ISSUE_DOM;


// Optional Plugins
if (!extension_loaded('hash')) $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_HASH;
if (!extension_loaded('mcrypt')) $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_MCRYPT;
if (!extension_loaded('json')) $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_JSON;
if (!extension_loaded('curl')) $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_CURL;
if (!extension_loaded('apc')) $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_APC;

if (!class_exists('Transliterator')) $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_TRANSLITERATOR;

if (CRYPT_SHA256 !== 1) $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_SHA256; // ...Will be removed, prolly.
if (CRYPT_SHA512 !== 1) $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_SHA512;


// FS Issues
if (!is_writable('../')) $installFlags += INSTALL_ISSUE_WRITEORIGINDIR;
if (file_exists('../config.php')) $installFlags += INSTALL_ISSUE_CONFIGEXISTS;

foreach(array('../webpro/client/data/config.json', '../webpro/client/data/language_enGB.json', '../webpro/client/data/language_enUS.json', '../webpro/client/data/templates.json', '../webpro/client/data/') AS $file) {
  if (!is_writable($file)) { 
    $installFlags += INSTALL_ISSUE_WRITEDATADIR;
    break;
  }
}

?><!DOCTYPE HTML>
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
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/absolution/jquery-ui-1.8.16.custom.css" media="screen" />-
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/absolution/fim.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/stylesv2.css" media="screen" />
  <style>
  h1 {
    margin: 0px;
    padding: 5px;
  }

  .main {
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
    display: block;
    border: 1px solid black;
  }
  
  .ui-widget {
    font-size: 12px;
  }
  .ui-widget-content {
    padding: 5px;
  }
  .uninstalledFlag {
    font-weight: bold;
  }
  abbr {
    outline-bottom: dotted 1px;
  }
  pre {
    display: inline;
  }

  /* General Tables */
  table td {
    padding-top: 5px;
    padding-bottom: 5px;
  }
  table tr {
    border-bottom: 1px solid black;
  }
  table {
    border-collapse: collapse;
  }
  table tr:last-child {
    border-bottom: none;
  }
  tbody tr:nth-child(2n) {
    background: #efefef !important;
  }

  /* Requirements Table */
  table#requirements tr td:nth-child(2), table#requirements tr td:nth-child(3) {
    text-align: center;
  }
  tbody#permissionRequirements td {
    text-align: left !important;
  }
  table#requirements .installIcon {
    height: 16px;
    width: 16px;
    float: left;
    margin-right: 5px;
  }
  @media screen and (max-width: 780px) {
    table#requirements tbody tr td:last-child, table#requirements thead:first-child tr:first-child th:last-child {
      visibility: hidden;
      width: 0px;
      display: none;
    }
  }
  </style>
  <!-- END Styles -->

  <!-- START Scripts -->
  <script src="../webpro/client/js/jquery-1.11.0.min.js" type="text/javascript"></script>
  <script src="../webpro/client/js/jquery-ui-1.10.4.custom.min.js" type="text/javascript"></script>
  <script src="../webpro/client/js/jquery.plugins.js" type="text/javascript"></script>
  <script>
  function windowDraw() {
    $('body').css('min-height', window.innerHeight);
  }

  $(document).ready(function() {
    windowDraw();
    $('button, input[type=button], input[type=submit]').button();
    
    // We do this in Javascript instead of the HTML directly since its easier to skin that way, and also good for screenreaders.
    $('.installedFlag').each(function() {
      $('td:first, th:first', this).append('<img src="task-complete.png" class="installIcon" />');
      $('td:last', this).html('');
    });
    
    $('.uninstalledFlag').each(function() {
      $('td:first, th:first', this).append('<img src="task-reject.png" class="installIcon" />');
    });
    
//    $('table#requirements tbody tr td:nth-child(5)').hide();
  });
  window.onwindowDraw = windowDraw;
  
  function showDetails() {
    $('table#requirements tbody tr td:nth-child(5)').show();
  }

  var alert = function(text) {
    dia.info(text,"Alert");
  };
  </script>
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

  Still, there are some server requirements to using FreezeMessenger. They are easy to get working, and you may already have them ready. We'll outline them below:<br /><br />

  <table id="requirements">
    <thead>
      <tr class="ui-widget-header">
        <th width="10%">Package</th>
        <th width="10%">Minimum Version</th>
        <th width="10%">Installed Version</th>
        <th width="10%">Used For</th>
        <th width="60%">How To Install<!-- (<a href="#" onclick="showDetails();">Show</a>)--></th>
      </tr>
      <tr class="ui-widget-header">
        <th colspan="5">PHP (required modules)</th>
      </tr>
    </thead>
    <tbody>
      <tr class="<?php echo ($installFlags & INSTALL_ISSUE_PHP_VERSION ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>PHP</td>
        <td>5.2</td>
        <td><?php echo phpversion(); ?></td>
        <td>Everything</td>
        <td>On Ubuntu: <pre>sudo apt-get install php5 libapache2-mod-php5</pre><br />
          On Windows: See <a href="http://php.net/manual/en/install.windows.installer.msi.php">http://php.net/manual/en/install.windows.installer.msi.php</a></td>
      </tr>
      <tr class="<?php echo ($installFlags & INSTALL_ISSUE_DATE ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>Date/Time</td>
        <td>n/a</td>
        <td><?php echo phpversion('date'); ?></td>
        <td>Date/Time</td>
        <td>With PHP 5.2</td>
      </tr>
      <tr class="<?php echo ($installFlags & INSTALL_ISSUE_DOM ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>DOM</td>
        <td>n/a</td>
        <td><?php echo phpversion('dom'); ?></td>
        <td>XML File Support</td>
        <td>With PHP 5.2</td>
      </tr>
      <tr class="<?php echo ($installFlags & INSTALL_ISSUE_PCRE ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>PCRE</td>
        <td>n/a</td>
        <td><?php echo phpversion('pcre'); ?></td>
        <td>Regex</td>
        <td>With PHP 5.2</td>
      </tr>
    </tbody>
    <tbody>
    <thead>
      <tr class="ui-widget-header">
        <th colspan="5">PHP (recommended modules)</th>
      </tr>
    </thead>
    <tbody>
      <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_SHA512 ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>SHA512</td>
        <td>n/a</td>
        <td><?php echo phpversion('hash'); ?></td>
        <td><abbr title="Without SHA512 support, the default hashing algorithm, which is both secure and fast, will not be available. The HASH extension will be used instead for a slightly less secure algorithm. If it is not available, a much slower, slightly less secure algorithm with be used.">Password Hashing</abbr></td>
        <td>With PHP 5.3</td>
      </tr>
      <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_HASH ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>Hash</td>
        <td>1.1</td>
        <td><?php echo phpversion('hash'); ?></td>
        <td><abbr title="Without the Hash extension, password encryption will be far, far slower, unless you use SHA512 (above).">Password Hashing</abbr></td>
        <td>With PHP 5.2</td>
      </tr>
      <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_JSON ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>JSON</td>
        <td>1.2.0</td>
        <td><?php echo phpversion('json'); ?></td>
        <td><abbr title="Without the JSON extension, JSON-based operations will become slower.">API and WebPro</abbr></td>
        <td>With PHP 5.2</td>
      </tr>
      <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_APC ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>APC</td>
        <td>3.1.4</td>
        <td><?php echo phpversion('apc'); ?></td>
        <td><abbr title="Without APC, higher disk and database usage will occur. FreezeMessenger is optimised for in-memory caching, and will not be able to function on large installations without APC.">Caching</abbr	></td>
        <td>On Ubuntu: <pre>sudo apt-get install php-apc</pre><br />
          On Windows: Usually comes with PHP.</td>
      </tr>
      <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_CURL ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>cURL</td>
        <td>3.1.4</td>
        <td><?php echo phpversion('curl'); ?></td>
        <td><abbr title="Without cURL, WebLite may not function. If it does function, it will be slower.">WebLite</abbr></td>
        <td>On Ubuntu: <pre>sudo apt-get install curl libcurl3 libcurl3-dev php5-curl</pre><br />
          On Windows: Compile APC, or find the plugin matching your version of PHP at <a href="http://dev.freshsite.pl/php-accelerators/apc.html">http://dev.freshsite.pl/php-accelerators/apc.html</a>.</td>
      </tr>
      <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_MCRYPT ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>MCrypt</td>
        <td>n/a</td>
        <td><?php echo phpversion('mcrypt'); ?></td>
        <td><abbr title="Without MCrypt, message encryption will be disabled.">Message Encryption</abbr></td>
        <td>On Ubuntu: <pre>sudo apt-get install php5-mcrypt</pre><br />
          On Windows: Install PHP 5.3, or see <a href="http://php.net/manual/en/mcrypt.requirements.php">http://php.net/manual/en/mcrypt.requirements.php</a></td>
      </tr>
      <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_MBSTRING ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td>MB String</td>
        <td>n/a</td>
        <td><?php echo phpversion('mbstring'); ?></td>
        <td><abbr title="Without MBString, certain localisations will not function correctly, and certain characters may not appear correctly. In addition, bugs may occur with non-latin characters in the database.">Foreign Language Support</abbr></td>
        <td>With PHP 5.2</td>
      </tr>
      <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_TRANSLITERATOR ? 'uninstalledFlag' : 'installedFlag'); ?>">
          <td>Transliterator</td>
          <td>n/a</td>
          <td>n/a</td>
          <td><abbr title="Without Transliteration, search indexing is not as effective.">Text Searching</abbr></td>
          <td>With PHP 5.2</td>
      </tr>
    </tbody>
    <thead>
      <tr class="ui-widget-header <?php echo ($installFlags & INSTALL_ISSUE_DB ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <th colspan="5">Database (any of the following)</th>
      </tr>
    </thead>
    <tbody>
      <tr class="<?php echo ($installStatusDB & (INSTALL_DB_MYSQL + INSTALL_DB_MYSQLI + INSTALL_DB_PDO_MYSQL) ? 'installedFlag' : 'unininstalledFlag'); ?>">
        <td>MySQL</td>
        <td>5.0</td>
        <td>*</td>
        <td>Database</td>
        <td>On Ubuntu: <pre>sudo apt-get install mysql-server libapache2-mod-auth-mysql php5-mysql</pre><br />
          On Windows: See <a href="http://php.net/manual/en/install.windows.installer.msi.php">http://php.net/manual/en/install.windows.installer.msi.php</a></td>
      </tr>
      <tr class="<?php echo ($installStatusDB & (INSTALL_DB_POSTGRESQL + INSTALL_DB_PDO_POSTGRESQL) ? 'installedFlag' : 'unininstalledFlag'); ?>">
        <td>PostGreSQL</td>
        <td>?</td>
        <td>*</td>
        <td>Database</td>
        <td>On Ubuntu: <pre>sudo apt-get install mysql-server libapache2-mod-auth-mysql php5-mysql</pre><br />
          On Windows: See <a href="http://php.net/manual/en/install.windows.installer.msi.php">http://php.net/manual/en/install.windows.installer.msi.php</a></td>
      </tr>
<!--      <tr class="<?php echo ($installStatusDB & INSTALL_DB_MYSQLI ? 'installedFlag' : 'uninstalledFlag'); ?>">
        <td>MySQLi</td>
        <td>5.0</td>
        <td>*</td>
        <td>Database</td>
        <td>On Ubuntu: <pre>sudo apt-get install mysql-server libapache2-mod-auth-mysql php5-mysql</pre><br />
          On Windows: See <a href="http://php.net/manual/en/install.windows.installer.msi.php">http://php.net/manual/en/install.windows.installer.msi.php</a></td>
      </tr>-->
    </tbody>
    <thead>
      <tr class="ui-widget-header">
        <th colspan="5">Permissions</th>
      </tr>
    </thead>
    <tbody id="permissionRequirements">
      <tr class="<?php echo ($installFlags & INSTALL_ISSUE_WRITEORIGINDIR ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td colspan="3">Origin Directory Writable</td>
        <td>Config File Creation</td>
        <td>The reasons for this are complicated, so we can't give copy+paste directions on fixing this. However, make sure you only give write permission to the server user (in Ubuntu, usually "www-data"). If you are running Apache on Windows, this user can be found by going to the Windows Services tool, right-clicking the "Apache" service,         selecting "properties", and finally looking under "Log On".</td>
      </tr>
      <tr class="<?php echo ($installFlags & INSTALL_ISSUE_WRITEDATADIR ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td colspan="3">WebPro Data Directory Writable</td>
        <td>WebPro Customisation</td>
        <td>Similar to above, you will need to enable writing for all files in the 'webpro/client/data' directory (as well as the directory itself).</td>
      </tr>
      <tr class="<?php echo ($installFlags & INSTALL_ISSUE_CONFIGEXISTS ? 'uninstalledFlag' : 'installedFlag'); ?>">
        <td colspan="3">Config File Absent</td>
        <td>Security</td>
        <td>If a previous installation exists, you will need to remove its core configuration file ('config.php' in the FreezeMessenger directory) before proceeding. This is intended as a security measure.</td>
      </tr>
    </tbody>
  </table><br />
  * Database versions are not checked yet. You will be told once database installation begins if they are unsatisfactory, however FreezeMessenger will most likely be compatibile as long as the database extension exists.<br /><br />

  <div style="height: 30px;">
    <form onsubmit="return false;">
      <?php
       if ($installFlags > 0) {
         echo '<strong style="float: right; font-size: 1.2em;">Cannot Continue.</strong>';
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
    <table class="page">
      <thead>
        <tr class="ui-widget-header">
          <th colspan="2">Connection Settings</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td width="20%"><strong>Database & Driver</strong></td>
          <td width="80%"><select name="db_driver">
          <?php
            if ($installStatusDB & INSTALL_DB_MYSQL) echo '<option value="mysql">MySQL, MySQL Driver (Discouraged)</option>';
            if ($installStatusDB & INSTALL_DB_MYSQLI) echo '<option value="mysqli">MySQL, MySQLi Driver (Recommended for MySQL)</option>';
            if ($installStatusDB & INSTALL_DB_PDO_MYSQL) echo '<option value="pdo-mysql">MySQL, PDO Driver</option>';
            if ($installStatusDB & INSTALL_DB_POSTGRESQL) echo '<option value="pgsql">PostGreSQL, PostGreSQL Driver (Recommended for PostGreSQL)</option>';
            if ($installStatusDB & INSTALL_DB_PDO_POSTGRESQL) echo '<option value="pdo-pgsql">PostGreSQL, PDO Driver</option>';
          ?>
          </select><br /><small>The database and corresponding driver. If you are integrating with a forum, choose the database (either MySQL or PostgreSQL) that your forum uses. Otherwise PostgreSQL, with the PostgreSQL driver, is best if available.</small></td>
        </tr>
        <tr>
          <td><strong>Host</strong></td>
          <td><input id="db_host" type="text" name="db_host" value="<?php echo $_SERVER['SERVER_NAME']; ?>" /><br /><small>The host of the SQL server. In most cases, the default shown here <em>should</em> work.</small></td>
        </tr>
        <tr>
          <td><strong>Port</strong></td>
          <td><input id="db_port" type="text" name="db_port" value="3306" /><br /><small>The port your database server is configured to work on. For MySQL and MySQLi, it is usually 3306.</small></td>
        </tr>
        <tr>
          <td><strong>Username</strong></td>
          <td><input id="db_userName" type="text" name="db_userName" /><br /><small>The username of the user you will be connecting to the database with.</small></td>
        </tr>
        <tr>
          <td><strong>Password</strong></td>
          <td><input id="db_password" type="password" name="db_password" /><input type="button" onclick="$('<input type=\'text\' name=\'db_password\' />').val($('#db_password').val()).prependTo($('#db_password').parent()); $('#db_password').remove();$(this).remove();" value="Show" /><br /><small>The password of the user you will be connecting to the database with.</small></td>
        </tr>
      </tbody>
      <thead>
        <tr class="ui-widget-header">
          <th colspan="2">Database Settings</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Database Name</strong></td>
          <td><input id="db_database" type="text" name="db_database" /><br /><small>The name of the database FreezeMessenger's data will be stored in. <strong>If you are integrating with a forum, this must be the same database the forum uses.</strong></small></td>
        </tr>
        <tr>
          <td><strong>Create Database?</strong></td>
          <td><input type="checkbox" name="db_createdb" /><small>This will not overwrite existing databases. You are encouraged to create the database yourself, as otherwise default permissions, etc. will be used (which is rarely ideal).</small></td>
        </tr>
      </tbody>
      <thead>
        <tr class="ui-widget-header">
          <th colspan="2">Table Settings</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Table Prefix</strong></td>
          <td><input type="text" name="db_tableprefix" /><br /><small>The prefix that FreezeMessenger's tables should use. This can be left blank (or with the default), but if the database contains any other products you must use a <strong>different</strong> prefix than all other products.</small></td>
        </tr>
      </tbody>
      <thead>
        <tr class="ui-widget-header">
          <th colspan="2">Advanced Options</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Insert Developer Data</strong></td>
          <td><input type="checkbox" name="db_usedev" /><small>This will populate the database with test data. Generally only meant for the developers, you could also probably use this to test drive FreezeMessenger.</small></td>
        </tr>
      </tbody>
    </table>
  </form><br /><br />


  <div style="height: 30px;">
    <form onsubmit="return false;">
      <button style="float: left;" type="button" onclick="$('#part2').slideUp(); $('#part1').slideDown(); windowDraw();">&larr; Back</button>
      <button style="float: right;" type="button" onclick="if (!$('#db_database').val().length) { dia.error('Please enter a database.'); } else if (!$('#db_userName').val().length) { dia.error('Please enter a username.'); } else { dia.full({ title : 'Installing', content : '<div style=&quot;text-align: center;&quot;>Installing now. Please wait a few moments.<br /><img src=&quot;../webpro/images/ajax-loader.gif&quot; /></div>', id : 'installingDia'}); $.get('./worker.php?phase=1', $('#db_connect_form').serialize(), function(data) { $('#installingDia').remove(); if (data == 'success') { $('#part2').slideUp(); $('#part3').slideDown(); } else { dia.error(data); } } ); windowDraw(); }">Setup &rarr;</button>
    </form>
  </div>
  </div>
</div>

<div id="part3" style="display: none;" class="main">
  <h1 class="ui-widget-header">FreezeMessenger Installation: Generate Configuration File</h1>
  <div class="ui-widget-content">
  Now that the database has been successfully installed, we must generate the configuration file. You can do this in a couple of ways: we would recommend simply entering the data below and you'll be on your way, though you can also do it manually by getting config.base.php from the install/ directory and saving it as config.php in the main directory.<br /><br />

  <form onsubmit="return false;" name="config_form" id="config_form">
    <table class="page">
      <thead>
        <tr class="ui-widget-header">
          <th colspan="2">Forum Integration</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Forum Integration</strong></td>
          <td>
            <select name="forum" onchange="if ($('select[name=\'forum\']').val() === 'vanilla') { $('.forumShow').hide(); $('.vanillaShow').show(); } else { $('.vanillaShow').hide(); $('.forumShow').show(); }">
              <option value="vanilla">No Integration</option>
              <option value="vbulletin3">vBulletin 3.8</option>
              <option value="vbulletin4">vBulletin 4.1</option>
              <option value="phpbb">PHPBB 3</option>
            </select><br /><small>If you have a forum, you can enable more advanced features than without one, and prevent users from having to create more than one account.</small>
          </td>
        </tr>
        <tr class="forumShow" style="display: none;">
          <td><strong>Forum URL</strong></td>
          <td><input type="text" name="forum_url" /><br /><small>The URL your forum is installed on.</small></td>
        </tr>
        <tr class="forumShow" style="display: none;">
          <td><strong>Forum Table Prefix</strong></td>
          <td><input type="text" name="forum_tableprefix" /><br /><small>The prefix of all tables the forum uses. You most likely defined this when you installed it. If unsure, check your forum's configuration file.</small></td>
        </tr>
        <tr class="vanillaShow">
          <td><strong>Admin Username</strong></td>
          <td><input type="text" name="admin_userName" required /><br /><small>The name you wish to login with.</small></td>
        </tr>
        <tr class="vanillaShow">
          <td><strong>Admin Password</strong></td>
          <td><input id="admin_password"  type="password" name="admin_password" required /><input type="button" onclick="$('<input type=\'text\' name=\'admin_password\' />').val($('#admin_password').val()).prependTo($('#admin_password').parent()); $('#admin_password').remove();$(this).remove();" value="Show" /><br /><small>The password you wish to login with.</small></td>
        </tr>
      </tbody>
      <thead>
        <tr class="ui-widget-header">
          <th colspan="2">Encryption</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Enable Encryption?</strong></td>
          <td><select name="enable_encrypt"><option value="3">For Everything</option><option value="2">For Uploads Only</option><option value="1">For Messages Only</option><option value="0">For Nothing</option></select><br /><small>Encryption is strongly encouraged, though it will cause slight slowdown.</small></td>
        </tr>
        <tr>
          <td><strong>Encryption Phrase</strong></td>
          <td><input type="text" name="encrypt_salt" /><br /><small>This is a phrase used to encrypt the data. You can change this later as long as you don't remove referrences to this one.</td>
        </tr>
      </tbody>
      <thead>
        <tr class="ui-widget-header">
          <th colspan="2">Other Settings</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Cache Method</strong></td>
          <td><select name="cache_method">
            <option value="disk">Disk Cache</option>
            <?php echo (extension_loaded('apc') ? '<option value="apc" selected="selected">APC</option>' : '') .
            (extension_loaded('memcache') ? '<option value="memcache">MemCache</option>' : '') ?>
          </select><br /><small>The primary cache to use. Only available caches are listed. We strongly recommend APC if you are able to use it. (Dev Note: Memcached will be available in Beta 5.)</td>
        </tr>
        <tr>
          <td><strong>Temporary Directory</strong></td>
          <td><input type="text" name="tmp_dir" value="<?php echo addcslashes(realpath(sys_get_temp_dir()), '"'); ?>" /><br /><small>The temporary directory of the system. This should not be web-accessible and must be writable by FreezeMessenger.</a></small></td>
        </tr>
        <tr>
          <td><strong>reCAPTCHA Public Key</strong></td>
          <td><input type="text" name="recaptcha_publicKey" /><br /><small>If a key is provided, reCAPTCHA will be enabled for user registration if you are not integrating with a forum. <a href="https://www.google.com/recaptcha/admin/create">This key can be obtained here.</a></small></td>
        </tr>
        <tr>
          <td><strong>reCAPTCHA Private Key</strong></td>
          <td><input type="text" name="recaptcha_privateKey" /><br /><small>This is paired with the above key, and can be found with the public key.</small></td>
        </tr>
      </tbody>
    </table><br /><br />
  </form>

  <div style="height: 30px;">
    <form onsubmit="return false;">
        <button style="float: left;" type="button" onclick="$('#part3').slideUp(); $('#part2').slideDown(); windowDraw();">&larr; Back</button>
        <button style="float: right;" type="button" onclick="if($('#config_form')[0].checkValidity()) { if ($.get('./worker.php?phase=2', $('#db_connect_form').serialize() + '&' + $('#config_form').serialize(), function(data) { if (data == 'success') { $('#part3').slideUp(); $('#part4').slideDown(); } else { alert('Could not create configuration file. Is the server allowed to write to it?'); } } )) windowDraw(); } else { dia.error('Please fill in all required fields.'); }">Finish &rarr;</button>
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