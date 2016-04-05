<?php
/* Alright, so this would be our generic login script then, which would complement our existing generic registration script. Kind-of nice that I was already going in that direction. */

// include our OAuth2 Server object
require_once __DIR__.'/server.php';

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// validate the authorize request
//$userId = $database->resolveUser('');
$userId = 1;
if (!$server->validateAuthorizeRequest($request, $response)) {
  $response->send();
  die;
}
// display an authorization form
if (empty($_POST)) {
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

// print the authorization code if the user has authorized your client
$is_authorized = ($_POST['authorized'] === 'Continue');
$server->handleAuthorizeRequest($request, $response, $is_authorized, $userId);
if ($is_authorized) {
  // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
  //$code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
  //exit("SUCCESS! Authorization Code: $code");
}
$response->send();
?>