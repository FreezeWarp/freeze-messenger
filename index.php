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


  $interface = ($_REQUEST['interface'] ? $_REQUEST['interface'] :
    ($user['interface'] ? $user['interface'] :
      ($defaultInterface ? $defaultInterface : 'interface')));


  if (is_array($enabledInterfaces)) {
    if (!in_array($interface, $enabledInterfaces)) {
      $interface = $defaultInterface; // If the interface is not enabled, use the default.
    }
  }
  else {
      $interface = $defaultInterface; // If the interface is not enabled, use the default.
  }


  if ($interface) {
    header("Location: $interface");
  }
  else {
    die('No public interface found.');
  }
}

?>