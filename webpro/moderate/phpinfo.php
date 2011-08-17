<?php
if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  if ($user['adminDefs']['modCore']) {
    if (function_exists('phpinfo')) {
      ob_start();

      phpinfo();

      $phpinfo = ob_get_clean();
      //ob_flush();

      $phpinfo = str_replace(array('<body>','<html>','</html>','</body>'), '', $phpinfo);
      $phpinfo = preg_replace(array('/<\!DOCTYPE(.*?)>/', '/\<head\>(.*)\<\/head\>/ism'), '', $phpinfo);
      $phpinfo = str_replace(array('<table','class="p"','class="e"','class="h"','class="v"','class="r"'), array('<table class="page ui-widget" border="1"','class="ui-widget-header"','','class="ui-widget-header"','',''), $phpinfo);

      echo $phpinfo;
    }
    else {
      echo container('Well, I Never!', 'I, for one, am apalled that you have disabled the most prestine function in all of the PHP binary. If you find it a security risk, you know nothing of security. ...Or maybe that\'s me. Shall we have a fine lager and discuss?');
    }
  }
  else {
    echo 'You do not have permission to view PHP info.';
  }
}
?>