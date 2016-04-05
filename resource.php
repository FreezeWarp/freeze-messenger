<?php
/* Takes a token and verifies that it is valid. This will be merged into validate.php, replacing our existing session verification code.
 * The verification code is pretty slow, which is fine in most cases, but if faster code is desired (for the message check script, for instance), simply scanning the token database for the token should suffice. */

// include our OAuth2 Server object
require_once __DIR__.'/server.php';

if (!$attempt = $server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
  // First, we should log the attempt.
  // For greatest security, I would like to lock after a certain number of failures, but this would require scanning the failure table before checking whether a login is successful or not, and doing so on every page load would require too many database queries. By default, the tokens have a value range of 16^40 (or 320 bits), which should be sufficiently complex to ignore this concern.
  //UPDATE sessionFails(ip, count) ($IP, count+1)

  // And then we let it send the failure message.
  $server->getResponse()->send();
}

else {
  // We can get the userId like this, but it would be faster if the entire user table was copied into the oauth_access_tokens table.
  // This can be done by modifying the storage interface's setAccessToken method.
  // Then, I believe modifying getAccessToken allows us to load the entire row's data.

  //$userId = $server->getAccessTokenData(OAuth2\Request::createFromGlobals())['user_id'];
}
?>