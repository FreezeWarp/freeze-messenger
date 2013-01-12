<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

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
  $request = fim_sanitizeGPC('r', array(
    'tool' => array(
      'context' => array(
        'type' => 'string',
      ),
    ),
  ));

  if ($user['adminDefs']['modCore']) {
    switch($_GET['tool']) {
      case false:
      echo container('Please Choose a Tool','<ul><li><a href="./moderate.php?do=tools&tool=viewcache">View Cache</a></li><li><a href="./moderate.php?do=tools&tool=clearcache">Clear Cache</a></li></ul>');
      break;
      
      case 'viewcache':
      foreach (array('fim_config', 'fim_hooksCache', 'fim_kicksCache', 'fim_permissionsCache', 'fim_watchRoomsCache', 'fim_censorListsCache', 'fim_censorWordsCache', 'fim_roomListNamesCache') AS $cache) {
        $formattedCache = '';
        
        foreach ($generalCache->get($cache) AS $key => $value) {
          $formattedCache .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
        }
        
        echo container('Cache Entries: ' . $cache, '<table class="page ui-widget ui-widget-content" border="1">' . $formattedCache . '</table>');
      }
      break;
      
      case 'clearcache':
      if ($generalCache->clearAll()) {
        echo container('Cache Cleared','The cache has been cleared.<br /><br /><form action="moderate.php?do=tools" method="POST"><button type="submit">Return to Tools</button></form>');
      }
      else {
        echo container('Failed','The clear was unsuccessful.<form action="moderate.php?do=tools" method="POST"><button type="submit">Return to Tools</button></form>');
      }
      break;
    }
  }
  else {
    echo 'You do not have permission to use the tools.';
  }
}
?>