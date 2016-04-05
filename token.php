<?php
/* Generates token -- goes into validate.php */
// include our OAuth2 Server object
require_once __DIR__ . '/server.php';

// Handle a request for an OAuth2.0 Access Token and send the response to the client
////if (checkPassword()) {
  $response = $server->handleTokenRequest(OAuth2\Request::createFromGlobals());
  $token = $response->getParameter('access_token');
  $response->send();

  // this is only for clients
  //$username = $_SERVER['PHP_AUTH_USER'];
  //$password = $_SERVER['PHP_AUTH_PW'];
  //INSERT INTO sessions(userId, sessionId, refreshed) ($_GET['userId'], $token, TIME())
//}
//else {
  //UPDATE sessionFails(ip, count) ($_GET[ip], count+1)
//}
//var_dump($server->handleTokenRequest(OAuth2\Request::createFromGlobals()));
?>