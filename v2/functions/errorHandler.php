<?php
/* This function is based off of the Filer project. */
function errorHandler($errno, $errstr, $errfile, $errline) {
  $errorString = $errstr . ($_GET['showErrorsFull'] ? " on line $errline" : '');

  switch ($errno) {
    case E_USER_ERROR:
    echo '<div class="ui-state-error">' . $errorString . '</div>';
    break;
    case E_USER_WARNING:
    echo '<div class="ui-state-error">The following error has been encountered, though it has been ignored: "' . $errorString . '".</div>';
    break;
    case E_USER_NOTICE:
    break;
    case E_ERROR:
    die('The script you are running has died with the error "' . $errorString . '".<br />');
    break;
    case E_WARNING: echo $errorString;
    echo '<div class="ui-state-error">System error: "' . $errorString . '".</div>';
    break;
    case E_NOTICE:
    break;
    default:
    echo '<div class="ui-state-error">Invalid error code: the error handler could not launch.</div>';
    break;
  }

  error_log("$errno-level error in $errfile on line $errline: $errstr");

  // Don't execute the internal PHP error handler.
  return true;
}

// Set the new error handler.
$old_error_handler = set_error_handler("errorHandler");
?>