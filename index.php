<?php


$ignoreLogin = true;


////* Handle FIM Installation *////

if (!file_exists('./config.php')) {
    if (file_exists('./install/index.php')) {
        header('Location: install/index.php');

        die('FreezeMessenger must first be installed. <a href="install/index.php">Click here</a> to do so.');
    }
    else { // This scenario used to make more sense. Manual installation is still technically possible, which is why this is here.
        die('FreezeMessenger must first be installed. Please modify install/config-base.php and save as config.php in the base directory.');
    }
}



////* Handle Path Redirection *////

else {
    require('./global.php');

    // Redirect to the default interface if possible. Note that an interface could be an interface-select screen, should someone desire. As this is part of FIMCore, we don't want to do that check.
    if (is_dir(fimConfig::$defaultInterface)) {
        $location = fimConfig::$defaultInterface . '/' . (isset($_REQUEST['sessionHash']) ? '#sessionHash=' . $_REQUEST['sessionHash'] : '');
        header("Location: $location");
        die("Redirecting to <a href=\"$location\">default interface.</a>");
    }
    else {
        die('No web-accessible interface found.');
    }
}

?>