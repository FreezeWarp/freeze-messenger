<?php
/* Planned methods, in order:
 * User Credentials, for WebPro
 * Implicit Grant, for remote WebPro
 * Authorization Code, for deskop clients
 */

$ignoreLogin = true;
require_once('global.php');
ini_set('display_errors',1);error_reporting(E_ALL);

require_once('functions/oauth2-server-php/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();

$storage = new OAuth2\Storage\FIMDatabaseOAuth($database);
//$storage = new OAuth2\Storage\Pdo(array('dsn' => 'mysql:dbname=' . $dbConnect['core']['database'] . ';host=' . $dbConnect['core']['host'], 'username' => $dbConnect['core']['username'], 'password' => $dbConnect['core']['password']));// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"

$server = new OAuth2\Server($storage); // Pass a storage object or array of storage objects to the OAuth2 server class
$server->addGrantType(new OAuth2\GrantType\UserCredentials($storage));
?>