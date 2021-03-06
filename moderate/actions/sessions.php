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

/**
 * Admin Control Panel: Session Tools
 * This script can list the current active user sessions.
 * To use this script, users must have modPrivs permissions.
 */


if (!defined('WEBPRO_INMOD')) {
    die();
}
else {
    $request = \Fim\Utilities::sanitizeGPC('r', array(
        'do2' => array(
            'default' => 'view',
            'valid' => ['view'],
        )
    ));

    if ($user->hasPriv('modPrivs')) {
        switch ($request['do2']) {
            case 'view':
            $sessions = \Fim\Database::instance()->getSessions()->getAsArray(true);

            $rows = '';

            foreach ($sessions as $session) {
                $rows .= "<tr><td>{$session['id']}-{$session['anonId']} ({$session['name']})</td><td>" . date('r', $session['expires']) . "</td><td>{$session['sessionIp']}</td><td>{$session['clientId']}</td><td>{$session['userAgent']}</td></tr>";
            }

            echo container('Sessions','<table class="table table-striped">
  <thead class="thead-light">
    <tr>
      <th>UID-AID (Username)</th>
      <th>Expires</th>
      <th>IP Address</th>
      <th>Client</th>
      <th>Useragent</th>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
            break;
        }
    }
    else {
        echo 'Permission denied.';
    }
}