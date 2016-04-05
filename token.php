<?php
/* Generates token -- goes into validate.php */
// include our OAuth2 Server object
require_once __DIR__ . '/server.php';

// Handle a request for an OAuth2.0 Access Token and send the response to the client
//if (checkPassword()) {
  $response = $server->handleTokenRequest(OAuth2\Request::createFromGlobals());
  $token = $response->getParameter('access_token');
  $response->send();
  //INSERT INTO sessions(userId, sessionId, refreshed) ($_GET['userId'], $token, TIME())
  /*echo new apiData([
    'access_token' => $token,
    'expires_in' => $response->getParameter('expires_in')
  ]);*/
//}
//else {
  //UPDATE sessionFails(ip, count) ($_GET[ip], count+1)
//}
//var_dump($server->handleTokenRequest(OAuth2\Request::createFromGlobals()));

//if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
//  $server->getResponse()->send();
//  die;
//}

//$token = $server->getAccessTokenData(OAuth2\Request::createFromGlobals());

//echo "User ID associated with this token is {$token['user_id']}";
?>