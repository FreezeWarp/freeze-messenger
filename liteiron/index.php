<?php
/* Parse Arguments */

$interfaceName = 'weblite';
$reqPhrases = true;

$mode = ($_GET['mode'] ?: $_COOKIE['liteiron_mode']);
switch ($mode) { // Used mainly for different page modes, which include currently Mobile and Normal. May also include IE6/similar in the future.
  case 'normal': case '': case false: $mode = 'normal'; break;
  case 'mobile': $mode = 'mobile'; break;
  default: trigger_error('You are trying to pass an invalid "mode" referrence. The page was terminated.',E_USER_ERROR); break;
}


/* This below bit hooks into the validate.php script to facilitate a seperate login. It is a bit cooky, though, and will need to be further tested. */
if (isset($_POST['liteiron_userName'])) {
  $hookLogin['userName'] = $_POST['liteiron_userName'];
  $hookLogin['password'] = $_POST['liteiron_password'];
}
elseif (isset($_COOKIE['liteiron_sessionHash'])) {
  $hookLogin['sessionHash'] = $_COOKIE['liteiron_sessionHash'];
  $hookLogin['userIdComp'] = $_COOKIE['liteiron_userId'];
}


/* Here we require the backend. */
require('../global.php');


/* And this sets the cookie with the session hash if possible. */
if (isset($sessionHash)) {
  if (strlen($sessionHash) > 0) {
    setcookie('liteiron_sessionHash', $sessionHash);
    setcookie('liteiron_userId', $user['userId']);
  }
}


/* Template Start */

echo template('templateStart');



/* Data */
if ($user['userid']) { // Make sure the user has been assigned a userid, and thus is logged in.
  switch ($action) {
    case 'chat': case '': case false:
      require_once('content/main.php');
    break; // No action is supplied or is it is to show the chat.
    case 'viewRooms':
      require_once('content/viewRooms.php');
    break;
    default:
    trigger_error('The page you are looking for could not be found.',E_USER_ERROR);
    break;
  }
}

else { // Not logged in.
  if ($flag) {
    switch($flag) {
      case 'nouser': $message .= 'No user exists with that user title. Is it possible that you have changed your name recently?'; break; // No user with that username exists.
      case 'nopass': $message .= 'You appeared to have entered a wrong password. Remeber, passwords are case sensitive.'; break; // The password is wrong.
    }

    echo container('Unsuccessful Login',$message);
  }

  echo container('Login to Victory Road Chat','<div id="normalLogin">
  Hello. Please Enter Your Login Credentials Below:<br />

  <form action="/index.php" method="post" style="text-align: center; display: block;">
    <label for="liteiron_userName">Username:</label><br />
    <input type="text" name="liteiron_userName" /><br /><br />

    <label for="liteiron_password">Password:</label><br />
    <input type="password" name="liteiron_password" /><br /><br />

    <input type="submit" value="Enter" /><input type="reset" value="Clear" />
  </form>
</div>');
}


/* Templates End */;

echo template('templateEnd');
?>