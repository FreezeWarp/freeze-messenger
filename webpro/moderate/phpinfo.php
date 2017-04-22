<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  if ($user->hasPriv('modPrivs')) {
    if (function_exists('phpinfo')) {
      ob_start();

      phpinfo();

      $phpinfo = ob_get_clean();

      $phpinfo = str_replace(array('<body>','<html>','</html>','</body>'), '', $phpinfo);
      $phpinfo = preg_replace(array('/<\!DOCTYPE(.*?)>/', '/\<head\>(.*)\<\/head\>/ism'), '', $phpinfo);
      $phpinfo = str_replace(array('<table','<h1','class="p"','class="e"','class="h"','class="v"','class="r"'), array('<table class="page ui-widget ui-widget-content" border="1"','<h1 class="ui-widget-header"','class="ui-widget-header"','','class="ui-widget-header"','',''), $phpinfo);

      echo $phpinfo;
    }
    else {
      echo container('Well, I Never!', 'I, for one, am apalled that you have disabled the most prestine function in all of the PHP binary. If you find it a security risk, you know nothing of security. ...Or perhaps it is I that knows nothing. Shall we enjoy a fine lager and discuss?');
    }
  }
  else {
    echo 'You do not have permission to view PHP info.';
  }
}
?>