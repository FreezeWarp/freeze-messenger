<?php
/* Alright, so this would be our generic login script then, which would complement our existing generic registration script. Kind-of nice that I was already going in that direction. */

// include our OAuth2 Server object
require_once __DIR__.'/server.php';

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// validate the authorize request
if (!$server->validateAuthorizeRequest($request, $response)) {
  $response->send();
  die;
}

// display an authorization form
if (!isset($_POST['username'])) {
  exit('
<form method="post">
  <label>To login to FreezeMessenger using ' . $_GET['client_id'] . ', Please Enter Your Username and Password:</label><br />
  <label>Username: <input name="username" type="text" /></label><br />
  <label>Password: <input name="password" type="text" /></label><br />
  <input type="submit" name="authorized" value="Continue" />
  <input type="submit" name="authorized" value="Cancel" />
  <hr />
  <em>You should never enter your login information directly into a FreezeMessenger client. You can later revoke access to this application from ...</em>
</form>');
}
else {
  // Get the userId. In an effort to avoid introducing incompatabilities, we'll just pass the userId to the OAuth2 library, but our storage engine will use it to reobtain the full user table.
  //$userId = $database->resolveUser('');
  $userId = 1;

  // print the authorization code if the user has authorized your client
  $is_authorized = ($_POST['authorized'] === 'Continue');
  $server->handleAuthorizeRequest($request, $response, $is_authorized, $userId);
  $response->send();
}
?>