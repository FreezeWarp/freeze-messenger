<?php
/* Verifies token -- goes into validate.php */
// include our OAuth2 Server object
require_once __DIR__.'/server.php';

// Handle a request to a resource and authenticate the access token
$attempt = $server->verifyResourceRequest(OAuth2\Request::createFromGlobals());
if (!$attempt) {
  //UPDATE sessionFails(ip, count) ($IP, count+1)
  $server->getResponse()->send();
}
else {
  $token = $server->getAccessTokenData(OAuth2\Request::createFromGlobals());
  echo json_encode(['token' => OAuth2\Request::createFromGlobals()->request['access_token'], 'userId' => $token['user_id']]);
  //$user = SELECT * FROM sessions WHERE sessionId = token
  //continue normally
}
?>