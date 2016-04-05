<?php
/* Gives a client a session token that it can use for all user actions.
 * TODO: Should there be some measure to prevent brute-forcing? Due to the quick expiration time, I believe not. */

// include our OAuth2 Server object
require_once __DIR__ . '/server.php';

// Handle a request for an OAuth2.0 Access Token and send the response to the client
$server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
?>