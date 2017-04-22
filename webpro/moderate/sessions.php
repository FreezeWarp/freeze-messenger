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
  $request = fim_sanitizeGPC('r', array(
    'do2' => array(
      'cast' => 'string',
    )
  ));

  if ($user['adminDefs']['modPrivs']) {
    switch ($request['do2']) {
      case 'view': case false:
      $sessions = $database->getSessions()->getAsArray('userId');

      foreach ($sessions as $session) {
        $rows .= "<tr><td>$session[userId]-$session[anonId] ($session[userName])</td><td>$session[sessionId]</td><td>" . chunk_split($session['sessionHash'], 10,  ' ') . "</td><td>" . date('r', $session['sessionTime']) . "</td><td>$session[sessionIp]</td><td>$session[sessionBrowser]</td></tr>";
      }

      echo container('Sessions','<table class="page rowHover">
  <thead>
    <tr class="ui-widget-header">
      <td>UID-AID (Username)</td>
      <td>Session ID</td>
      <td>Session Hash</td>
      <td>Session Time</td>
      <td>IP Address</td>
      <td>Useragent</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
      break;
    }
  }
}
?>