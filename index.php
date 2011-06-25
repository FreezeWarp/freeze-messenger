<?php

///* Handle FIM Installation *///

if (!file_exists('config.php')) {
  if (file_exists('install/')) {
    header('Location: install/');

    die('FreezeMessenger must first be installed. <a href="install/">Click here</a> to do so.');
  }
  else {
    die('FreezeMessenger must first be installed. Please modify install/config-base.php and save as config.php in the base directory.');
  }
}



///* Handle Path Redirection *///

else {
  require('config.php');

  if ($disableWeb) {
    die('Web interfaces have been disabled on this server.');
  }
  else {
    $interface = (isset($_REQUEST['interface']) ? $_REQUEST['interface'] :
      (isset($user['interface']) ? $user['interface'] : ''));


    if (is_array($enabledInterfaces)) {
      if (!in_array($interface, $enabledInterfaces)) {
        $interface = $defaultInterface; // If the interface is not enabled, use the default.
      }
    }
    else {
      $interface = $defaultInterface; // If the interface is not enabled, use the default.
    }


    if ($interface) {
      header("Location: $interface/");
    }
    else {
      die('No web-accessible interface found.');
    }
  }
}

?>