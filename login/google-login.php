<?php
require_once('../vendor/autoload.php');


// create our client credentials
$client = new Google_Client();

$client->setApplicationName("FlexMessenger Login");
$client->setDeveloperKey("AIzaSyDxK4wHgx7NAy6NU3CcSsQ2D3JX3K6FwVs");
//$client->setAuthConfig('client_secrets.json');
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri('http://localhost/messenger/login/google-login.php');
$client->addScope([
    Google_Service_Oauth2::USERINFO_EMAIL,
    Google_Service_Oauth2::USERINFO_PROFILE,
]);


// before Google request
if (isset($_GET['start'])) {
    // redirect to the login URL
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
}

// after Google request
elseif (isset($_GET['code'])) {
    // verify returned code
    $client->fetchAccessTokenWithAuthCode($_GET['code']);

    $access_token = $client->getAccessToken();
    var_dump($access_token);

    // get user info
    $user = new Google_Service_Oauth2($client);
    $userInfo = $user->userinfo->get();
    var_dump($userInfo);

    // store user info...
}

else {
    die('Failed.');
}



?>