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

/* Frontend of Install
 * See worker.php for backend of install.
 * TODO: Translation support. */

$installFlags = 0; // Create an integer (bitfield) that will store all install fields.
$optionalInstallFlags = 0; // ""
$installStatusDB = 0; // And one for supported DBs.


// Define CONSTANTS (it's a bit excessive here, but...)
define('INSTALL_ISSUE_PHP_VERSION', 1);
define('INSTALL_ISSUE_DB', 16);
define('INSTALL_ISSUE_DOM', 8192);
define('INSTALL_ISSUE_JSON', 16384);
define('INSTALL_ISSUE_WRITEORIGINDIR', 1048576);
define('INSTALL_ISSUE_CONFIGEXISTS', 2097152);

define('OPTIONAL_INSTALL_ISSUE_OPENSSL', 256);
define('OPTIONAL_INSTALL_ISSUE_MBSTRING', 4096);
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
if (extension_loaded('mysql'))
    $installStatusDB += INSTALL_DB_MYSQL;
if (extension_loaded('mysqli'))
    $installStatusDB += INSTALL_DB_MYSQLI;
if (extension_loaded('pdo_mysql'))
    $installStatusDB += INSTALL_DB_PDO_MYSQL;
if (extension_loaded('pdo_pgsql'))
    $installStatusDB += INSTALL_DB_PDO_POSTGRESQL;
if (extension_loaded('pgsql'))
    $installStatusDB += INSTALL_DB_POSTGRESQL;


// PHP Issues
if (PHP_VERSION_ID < 70000)
    $installFlags += INSTALL_ISSUE_PHP_VERSION; // TODO: 5.5 in preview


// DB Issues
if ($installStatusDB == 0)
    $installFlags += INSTALL_ISSUE_DB;


// Plugin Issues
if (!extension_loaded('dom'))
    $installFlags += INSTALL_ISSUE_DOM;
if (!extension_loaded('json'))
    $optionalInstallFlags += INSTALL_ISSUE_JSON;


// Optional Plugins
if (!function_exists('openssl_encrypt')
    || !in_array('AES-256-CTR', openssl_get_cipher_methods()))
    $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_OPENSSL;
if (!class_exists('Transliterator'))
    $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_TRANSLITERATOR;
if (!extension_loaded('mbstring'))
    $installFlags += OPTIONAL_INSTALL_ISSUE_MBSTRING;
if (!extension_loaded('curl'))
    $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_CURL;
if (!extension_loaded('apc')
    && !extension_loaded('apcu'))
    $optionalInstallFlags += OPTIONAL_INSTALL_ISSUE_APC;

// FS Issues
if (!is_writable('../')) $installFlags += INSTALL_ISSUE_WRITEORIGINDIR;
if (file_exists('../config.php')) $installFlags += INSTALL_ISSUE_CONFIGEXISTS;

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

    <link rel="icon" id="favicon" type="image/x-icon" href="images/favicon.ico"/>
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css"
          integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb"
          crossorigin="anonymous">


    <!-- START Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.10/handlebars.min.js"
            integrity="sha256-0JaDbGZRXlzkFbV8Xi8ZhH/zZ6QQM0Y3dCkYZ7JYq34="
            crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"
            integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js"
            integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh"
            crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js"
            integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ"
            crossorigin="anonymous"></script>

    <!-- START Styles -->
    <link rel="stylesheet" type="text/css" href="../webpro/client/css/stylesv2.css" media="screen" />
    <style>
        body {
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
        h1 {
            margin: 0px;
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

        #part2, #part3, #part4 {
            display: none;
        }

        /* General Tables */
        /* Requirements Table */
        table.requirements {
            font-size: .9em;
        }
        table tr td {
            vertical-align: middle !important;
        }
        tbody#permissionRequirements td {
            text-align: left !important;
        }
        table.requirements .installIcon {
            height: 16px;
            width: 16px;
            float: left;
            margin-right: 5px;
        }
        @media screen and (max-width: 780px) {
            table.requirements tbody tr td:last-child, table.requirements thead:first-child tr:first-child th:last-child {
                visibility: hidden;
                width: 0px;
                display: none;
            }
        }
    </style>
    <!-- END Styles -->

    <!-- START Scripts -->
    <script>
        function windowDraw() {
            $('body').css('min-height', window.innerHeight);
        }

        $(document).ready(function() {
            // We do this in Javascript instead of the HTML directly since its easier to skin that way, and also good for screenreaders.
            $('.installedFlag').each(function() {
                $(this).addClass('table-success');
                $('td:first, th:first', this).append('<img src="task-complete.png" class="installIcon" />');
                $('td:last', this).html('');
            });

            $('.uninstalledFlag').each(function() {
                $(this).addClass('table-danger');
                $('td:first, th:first', this).append('<img src="task-reject.png" class="installIcon" />');
            });

//    $('table#requirements tbody tr td:nth-child(5)').hide();
        });

        function showDetails() {
            $('table.requirements tbody tr td:nth-child(5)').show();
        }
    </script>
    <!-- END Scripts -->
</head>
<body>

<div id="modal-installing" class="modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Installing</h5>
            </div>
            <div class="modal-body">
                Installing now. Please wait a few moments.<br /><img src="../webpro/images/ajax-loader.gif" />
            </div>
        </div>
    </div>
</div>

<div id="modal-error" class="modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">Error</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>

<div id="part1" class="card">
    <h1 class="card-header">FlexChat Installation: Introduction</h1>
    <div class="card-body">
        <p>
        Thank you for downloading FlexChat! FlexChat is a new, easy-to-use, and highly powerful messenger backend (with included frontend) intended for sites which want an easy yet powerful means to allow users to quickly communicate with each other. Unlike other solutions, FlexChat has numerous benefits:
        </p>

        <ul>
            <li>Seperation of backend and frontend APIs to allow custom interfaces.</li>
            <li>Highly scalable, while still working on small installations.</li>
            <li>Easily extensible.</li>
        </ul>

        <p>
        Still, there are some server requirements to using FlexChat. They are easy to get working, and you may already have them ready. We'll outline them below:<br /><br />
        </p>

        <table class="requirements table table-sm">
            <thead class="thead-dark">
            <tr>
                <th colspan="5" class="text-center">PHP (required modules)</th>
            </tr>
            </thead>
            <thead class="thead-light">
            <tr>
                <th>Package</th>
                <th>Minimum Version</th>
                <th>Installed Version</th>
                <th>Used For</th>
                <th>How To Install</th>
            </tr>
            </thead>
            <tbody>
            <tr class="<?php echo ($installFlags & INSTALL_ISSUE_PHP_VERSION ? 'uninstalledFlag' : 'installedFlag'); ?>">
                <td>PHP</td>
                <td>7 (5.5 in Preview 2)</td>
                <td><?php echo phpversion(); ?></td>
                <td>Everything</td>
                <td>On Ubuntu: <pre>sudo apt-get install php libapache2-mod-php</pre><br />
                    On Windows: See <a href="http://php.net/manual/en/install.windows.installer.msi.php">http://php.net/manual/en/install.windows.installer.msi.php</a></td>
            </tr>
            <tr class="<?php echo ($installFlags & INSTALL_ISSUE_DOM ? 'uninstalledFlag' : 'installedFlag'); ?>">
                <td>DOM</td>
                <td>n/a</td>
                <td><?php echo phpversion('dom'); ?></td>
                <td>XML File Support</td>
                <td>With PHP 5.2</td>
            </tr>
            <tr class="<?php echo ($installFlags & INSTALL_ISSUE_JSON ? 'uninstalledFlag' : 'installedFlag'); ?>">
                <td>JSON</td>
                <td>n/a</td>
                <td><?php echo phpversion('json'); ?></td>
                <td>Reading and Writing JSON Objects</td>
                <td>On Ubuntu: <pre>sudo apt-get install php-json</pre><br />
                    </td>
            </tr>
            </tbody>
            <thead class="thead-dark">
            <tr>
                <th colspan="5" class="text-center">PHP (recommended modules)</th>
            </tr>
            </thead>
            <thead class="thead-light">
            <tr>
                <th>Package</th>
                <th>Minimum Version</th>
                <th>Installed Version</th>
                <th>Used For</th>
                <th>How To Install</th>
            </tr>
            </thead>
            <tbody>
            <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_APC ? 'uninstalledFlag' : 'installedFlag'); ?>">
                <td>APC (or APCu)</td>
                <td>3.1.4</td>
                <td><?php echo phpversion('apc'); ?></td>
                <td><abbr title="Without APC, higher disk and database usage will occur. FlexChat is optimised for in-memory caching, and will not be able to function on large installations without APC.">Caching</abbr	></td>
                <td>On Ubuntu: <pre>sudo apt-get install php-apcu</pre><br />
                    On Windows: Usually comes with PHP.</td>
            </tr>
            <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_CURL ? 'uninstalledFlag' : 'installedFlag'); ?>">
                <td>cURL</td>
                <td>3.1.4</td>
                <td><?php echo phpversion('curl'); ?></td>
                <td><abbr title="Without cURL, WebLite may not function. If it does function, it will be slower.">WebLite</abbr></td>
                <td>On Ubuntu: <pre>sudo apt-get install curl libcurl3 libcurl3-dev php-curl</pre><br />
                    On Windows: Compile APC, or find the plugin matching your version of PHP at <a href="http://dev.freshsite.pl/php-accelerators/apc.html">http://dev.freshsite.pl/php-accelerators/apc.html</a>.</td>
            </tr>
            <tr class="<?php echo ($optionalInstallFlags & OPTIONAL_INSTALL_ISSUE_OPENSSL ? 'uninstalledFlag' : 'installedFlag'); ?>">
                <td>OpenSSL (and AES-256-CTR cipher)</td>
                <td>n/a</td>
                <td><?php echo phpversion('openssl'); ?></td>
                <td><abbr title="Without OpenSSL, message encryption will be disabled.">Message Encryption</abbr></td>
                <td>On Ubuntu: <pre>sudo apt-get install php-openssl</pre><br />
                    On Windows: Install PHP, or see <a href="http://php.net/manual/en/openssl.requirements.php">http://php.net/manual/en/openssl.requirements.php</a></td>
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
                <td>On Ubuntu: <pre>sudo apt-get install php-intl</pre><br />
                    </td>
            </tr>
            </tbody>
            <thead class="thead-dark">
            <tr class="text-center <?php echo ($installFlags & INSTALL_ISSUE_DB ? 'uninstalledFlag' : 'installedFlag'); ?>">
                <th colspan="5">Database (any of the following)</th>
            </tr>
            </thead>
            <thead class="thead-light">
            <tr>
                <th>Package</th>
                <th>Minimum Version</th>
                <th>Installed Version</th>
                <th>Used For</th>
                <th>How To Install</th>
            </tr>
            </thead>
            <tbody>
            <tr class="<?php echo ($installStatusDB & (INSTALL_DB_MYSQL + INSTALL_DB_MYSQLI + INSTALL_DB_PDO_MYSQL) ? 'installedFlag' : 'unininstalledFlag'); ?>">
                <td>MySQL</td>
                <td>5.0</td>
                <td>*</td>
                <td>Database</td>
                <td>On Ubuntu: <pre>sudo apt-get install mysql-server libapache2-mod-auth-mysql php-mysql</pre><br />
                    On Windows: See <a href="http://php.net/manual/en/install.windows.installer.msi.php">http://php.net/manual/en/install.windows.installer.msi.php</a></td>
            </tr>
            <tr class="<?php echo ($installStatusDB & (INSTALL_DB_POSTGRESQL + INSTALL_DB_PDO_POSTGRESQL) ? 'installedFlag' : 'unininstalledFlag'); ?>">
                <td>PostGreSQL</td>
                <td>?</td>
                <td>*</td>
                <td>Database</td>
                <td>On Ubuntu: <pre>sudo apt-get install mysql-server libapache2-mod-auth-mysql php-mysql</pre><br />
                    On Windows: See <a href="http://php.net/manual/en/install.windows.installer.msi.php">http://php.net/manual/en/install.windows.installer.msi.php</a></td>
            </tr>
            </tbody>
            <thead class="thead-dark">
            <tr>
                <th colspan="5" class="text-center">Permissions</th>
            </tr>
            </thead>
            <thead class="thead-light">
            <tr>
                <th colspan="3">Permission</th>
                <th>Used For</th>
                <th>How to Install </th>
            </tr>
            </thead>
            <tbody id="permissionRequirements">
            <tr class="<?php echo ($installFlags & INSTALL_ISSUE_WRITEORIGINDIR ? 'uninstalledFlag' : 'installedFlag'); ?>">
                <td colspan="3">Origin Directory Writable</td>
                <td>Config File Creation</td>
                <td>The reasons for this are complicated, so we can't give copy+paste directions on fixing this. However, make sure you only give write permission to the server user (in Ubuntu, usually "www-data"). If you are running Apache on Windows, this user can be found by going to the Windows Services tool, right-clicking the "Apache" service,         selecting "properties", and finally looking under "Log On".</td>
            </tr>
            <tr class="<?php echo ($installFlags & INSTALL_ISSUE_CONFIGEXISTS ? 'uninstalledFlag' : 'installedFlag'); ?>">
                <td colspan="3">Config File Absent</td>
                <td>Security</td>
                <td>If a previous installation exists, you will need to remove its core configuration file ('config.php' in the FlexChat directory) before proceeding. This is intended as a security measure.</td>
            </tr>
            </tbody>
        </table>

        <p>
            * Database versions are not checked yet. You will be told once database installation begins if they are unsatisfactory, however FlexChat will most likely be compatibile as long as the database extension exists.
        </p>
    </div>

    <div class="card-footer">
        <form onsubmit="return false;">
            <?php
            if ($installFlags > 0) {
                echo '<strong style="float: right; font-size: 1.2em;">Cannot Continue.</strong>';
            }
            else {
                echo '<button class="btn btn-primary" style="float: right;" type="button" onclick="$(\'#part1\').slideUp(); $(\'#part2\').slideDown(); windowDraw();">Start &rarr;</button>';
            }
            ?>
        </form>
    </div>
</div>


<form onsubmit="
    $('#modal-installing').modal();

    $.get('./worker.php?phase=1', $('#db_connect_form').serialize(), function(data) {
        console.log(data);
        $('#modal-installing').modal('hide');

        if (data == 'success') {
            $('#part2').slideUp();
            $('#part3').slideDown();
        }
        else {
            $('#modal-error .modal-body').text(data)
            $('#modal-error').modal();
        }
    });

    return false;
" name="db_connect_form" id="db_connect_form">
    <div id="part2" class="card">
        <h1 class="card-header">FlexChat Installation: MySQL Setup</h1>
        <div class="card-body">
            <p>First things first, please enter your MySQL connection details below, as well as a database (we can try to create the database ourselves, as well). If you are unable to proceed, try contacting your web host, or anyone who has helped you set up other things like this before.</p>

            <table class="table">
                <thead class="thead-dark">
                <tr>
                    <th colspan="2">Connection Settings</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><strong>Database & Driver</strong></td>
                    <td>
                        <select name="db_driver" class="form-control" onchange="
                        switch (this.value) {
                            case 'mysql': case 'mysqli': case 'pdo-mysql':
                                document.getElementById('db_port').value = '3306';
                                break;

                            case 'pgsql': case 'pdo-pgsql':
                                document.getElementById('db_port').value = '5432';
                                $('#db_createdb').prop({'disabled' : true, 'checked' : false});
                                break;
                        }">
                            <?php
                            if ($installStatusDB & INSTALL_DB_MYSQL) echo '<option value="mysql">MySQL, MySQL Driver (Discouraged)</option>';
                            if ($installStatusDB & INSTALL_DB_MYSQLI) echo '<option value="mysqli">MySQL, MySQLi Driver (Recommended for MySQL)</option>';
                            if ($installStatusDB & INSTALL_DB_PDO_MYSQL) echo '<option value="pdo-mysql">MySQL, PDO Driver</option>';
                            if ($installStatusDB & INSTALL_DB_POSTGRESQL) echo '<option value="pgsql">PostGreSQL, PostGreSQL Driver</option>';
                            if ($installStatusDB & INSTALL_DB_PDO_POSTGRESQL) echo '<option value="pdo-pgsql">PostGreSQL, PDO Driver (Currently Unsupported/Broken)</option>';
                            ?>
                        </select>
                        <small class="form-text text-muted">The database and corresponding driver. If you are integrating with a forum, choose the database (either MySQL or PostgreSQL) that your forum uses. Otherwise PostgreSQL, with the PostgreSQL driver, is best if available.</small>
                    </td>
                </tr>
                <tr>
                    <td><strong>Host</strong></td>
                    <td>
                        <input id="db_host" class="form-control" type="text" name="db_host" value="localhost" required />
                        <small class="form-text text-muted">The host of the SQL server. In most cases, the default shown here <em>should</em> work.</small>
                    </td>
                </tr>
                <tr>
                    <td><strong>Port</strong></td>
                    <td>
                        <input id="db_port" class="form-control" type="text" name="db_port" value="3306" required />
                        <small class="form-text text-muted">The port your database server is configured to work on. For MySQL and MySQLi, it is usually 3306. For PostGreSQL, it is usually 5432.</small>
                    </td>
                </tr>
                <tr>
                    <td><strong>Username</strong></td>
                    <td>
                        <input id="db_userName" class="form-control" type="text" name="db_userName" required />
                        <small class="form-text text-muted">The username of the user you will be connecting to the database with.</small>
                    </td>
                </tr>
                <tr>
                    <td><strong>Password</strong></td>
                    <td>
                        <label class="input-group">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-secondary" onclick="
                                    $('input[name=db_password]').attr('type', 'text');
                                    $(this).parent().remove();
                                ">Show</button>
                            </span>
                            <input class="form-control" type="password" name="db_password" />
                        </label>
                        <small class="form-text text-muted">The password of the user you will be connecting to the database with.</small>
                    </td>
                </tr>
                </tbody>
                <thead class="thead-dark">
                <tr>
                    <th colspan="2">Database Settings</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><strong>Database Name</strong></td>
                    <td>
                        <input id="db_database" class="form-control" type="text" name="db_database" required />
                        <small class="form-text text-muted">The name of the database FlexChat's data will be stored in. <strong>If you are integrating with a forum, this must be the same database the forum uses.</strong></small>
                    </td>
                </tr>
                <tr>
                    <td><label for="db_createdb"><strong>Create Database?</strong></label></td>
                    <td><label>
                            <label class="btn btn-secondary"><input type="checkbox" id="db_createdb" name="db_createdb" /> Create Database</label>
                            <small class="form-text text-muted">This will not overwrite existing databases. You are encouraged to create the database yourself, as otherwise default permissions, etc. will be used. If you are on Postgres, you <em>must</em> created the database yourself.</small>
                        </label>
                    </td>
                </tr>
                </tbody>
                <thead class="thead-dark">
                <tr>
                    <th colspan="2">Table Settings</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><strong>Table Prefix</strong></td>
                    <td>
                        <input class="form-control" type="text" name="db_tableprefix" />
                        <small class="form-text text-muted">The prefix that FlexChat's tables should use. This can be left blank (or with the default), but if the database contains any other products you must use a <strong>different</strong> prefix than all other products.</small>
                    </td>
                </tr>
                </tbody>
                <thead class="thead-dark">
                <tr>
                    <th colspan="2">Advanced Options</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><label for="db_usedev"><strong>Insert Developer Data</strong></label></td>
                    <td>
                        <label for="db_usedev">
                            <label class="btn btn-secondary">
                                <input type="checkbox" id="db_usedev" name="db_usedev" />
                                Insert Dev Data
                            </label>
                            <small class="form-text text-muted">This will populate the database with test data. Generally only meant for the developers, you could also probably use this to test drive FlexChat.</small>
                        </label>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col text-left">
                    <button class="btn btn-secondary" type="button" onclick="
                        $('#part2').slideUp();
                        $('#part1').slideDown();
                    ">&larr; Back</button>
                </div>

                <div class="col text-right">
                    <button class="btn btn-primary">Setup &rarr;</button>
                </div>
            </div>
        </div>
    </div>
</form>

<form onsubmit="
    if ($.get('./worker.php?phase=2', $('#db_connect_form').serialize() + '&' + $('#config_form').serialize(), function(data) {
        if (data == 'success') {
            $('#part3').slideUp();
            $('#part4').slideDown();
        }
        else {
            $('#modal-error .modal-body').text(data)
            $('#modal-error').modal();
        }
    }));

    return false;
" name="config_form" id="config_form">
    <div id="part3" class="card">
        <h1 class="card-header">FlexChat Installation: Generate Configuration File</h1>
        <div class="card-body">
            <p>Now that the database has been successfully installed, we must generate the configuration file. You can do this in a couple of ways: we would recommend simply entering the data below and you'll be on your way, though you can also do it manually by getting config.base.php from the install/ directory and saving it as config.php in the main directory.</p>
            <table class="table">
                <thead class="thead-dark">
                <tr>
                    <th colspan="2">Forum Integration</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><strong>Forum Integration</strong></td>
                    <td>
                        <select class="form-control" name="forum" onchange="
                            if ($('select[name=\'forum\']').val() === 'vanilla') {
                                $('.forumShow').hide(); $('.vanillaShow').show();
                            } else {
                                $('.vanillaShow').hide(); $('.forumShow').show();
                            }
                        ">
                            <option value="vanilla">No Integration</option>
                            <option value="vbulletin3">vBulletin 3.8</option>
                            <option value="vbulletin4">vBulletin 4.1</option>
                            <option value="phpbb">PHPBB 3</option>
                        </select>
                        <small class="form-text text-muted">If you have a forum, you can enable more advanced features than without one, and prevent users from having to create more than one account.</small>
                    </td>
                </tr>
                <tr class="forumShow" style="display: none;">
                    <td><strong>Forum URL</strong></td>
                    <td>
                        <input type="text" class="form-control" name="forum_url" />
                        <small class="form-text text-muted">The URL your forum is installed on.</small>
                    </td>
                </tr>
                <tr class="forumShow" style="display: none;">
                    <td><strong>Forum Table Prefix</strong></td>
                    <td>
                        <input type="text" class="form-control" name="forum_tableprefix" />
                        <small class="form-text text-muted">The prefix of all tables the forum uses. You most likely defined this when you installed it. If unsure, check your forum's configuration file.</small>
                    </td>
                </tr>
                <tr class="vanillaShow">
                    <td><strong>Admin Username</strong></td>
                    <td>
                        <input type="text" class="form-control" name="admin_userName" required />
                        <small class="form-text text-muted">The name you wish to login with.</small>
                    </td>
                </tr>
                <tr class="vanillaShow">
                    <td><strong>Admin Password</strong></td>

                    <td>
                        <label class="input-group">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-secondary" onclick="
                                    $('input[name=admin_password]').attr('type', 'text');
                                    $(this).parent().remove();
                                ">Show</button>
                            </span>
                            <input class="form-control" type="password" name="admin_password" required />
                        </label>
                        <small class="form-text text-muted">The password you wish to login with.</small>
                    </td>
                </tr>
                </tbody>
                <thead>
                <tr class="thead-dark">
                    <th colspan="2">Encryption</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><strong>Enable Encryption?</strong></td>
                    <td>
                        <select class="form-control" name="enable_encrypt">
                            <option value="3">For Everything</option>
                            <option value="2">For Uploads Only</option>
                            <option value="1">For Messages Only</option>
                            <option value="0">For Nothing</option>
                        </select>
                        <small class="form-text text-muted">Encrypting messages and files at-rest serves little purpose unless you are sharing the database with another software (such as a forum), whose database exploits could expose FlexChat data. If you are installing FlexChat by itself, you likely would not benefit from encryption.</small>
                    </td>
                </tr>
                <tr>
                    <td><strong>Encryption Phrase</strong></td>
                    <td>
                        <input type="text" class="form-control" name="encrypt_salt" />
                        <small class="form-text text-muted">This is a phrase used to encrypt the data. You can change this later as long as you don't remove referrences to this one.
                    </td>
                </tr>
                </tbody>
                <thead>
                <tr class="thead-dark">
                    <th colspan="2">Other Settings</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><strong>Cache Method</strong></td>
                    <td>
                        <select class="form-control" name="cache_method">
                            <option value="disk">Disk Cache</option>
                            <?php echo (extension_loaded('apc') ? '<option value="apc" selected="selected">APC</option>' : '') .
                                (extension_loaded('apcu') ? '<option value="apcu" selected="selected">APCu</option>' : '') .
                                (extension_loaded('memcached') ? '<option value="memcached">Memcached</option>' : '') ?>
                        </select>
                        <small class="form-text text-muted">The primary cache to use. Only available caches are listed. We strongly recommend APC if you are able to use it.
                    </td>
                </tr>
                <tr>
                    <td><strong>Temporary Directory</strong></td>
                    <td>
                        <input class="form-control" type="text" name="tmp_dir" value="<?php echo addcslashes(realpath(sys_get_temp_dir()), '"'); ?>" />
                        <small class="form-text text-muted">The temporary directory of the system. This should not be web-accessible and must be writable by FlexChat.</small>
                    </td>
                </tr>
                <!--
                <tr>
                    <td><strong>reCAPTCHA Public Key</strong></td>
                    <td><input type="text" name="recaptcha_publicKey" /><br /><small>If a key is provided, reCAPTCHA will be enabled for user registration if you are not integrating with a forum. <a href="https://www.google.com/recaptcha/admin/create">This key can be obtained here.</a></small></td>
                </tr>
                <tr>
                    <td><strong>reCAPTCHA Private Key</strong></td>
                    <td><input type="text" name="recaptcha_privateKey" /><br /><small>This is paired with the above key, and can be found with the public key.</small></td>
                </tr>
                -->
                </tbody>
            </table>
        </div>


        <div class="card-footer">
            <div class="row">
                <div class="col text-left">
                    <button class="btn btn-secondary" type="button" onclick="
                    $('#part3').slideUp();
                    $('#part2').slideDown();
                ">&larr; Back</button>
                </div>

                <div class="col text-right">
                    <button class="btn btn-primary" style="float: right;">Finish &rarr;</button>
                </div>
            </div>
        </div>
    </div>
</form>


<div id="part4" class="card">
    <h1 class="card-header">FlexChat Installation: All Done!</h1>
    <div class="card-body">
        <p>FlexChat Installation is now complete. You're free to go wander (once you delete the install/ directory), though to put you in the right direction:</p>
        <ul>
            <li><a href="../">Start Chatting</a></li>
            <li><a href="../docs/">Go to the Documentation</a></li>
            <li><a href="http://www.josephtparsons.com/">Go to The Creator's Website</a></li>
        </ul>
    </div>
    <div class="card-footer text-right">
        <a href="../" class="btn btn-primary btn-lg">Start Chatting &rarr;</a>
    </div>
</div>

</body>
</html>