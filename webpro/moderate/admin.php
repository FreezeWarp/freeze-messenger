<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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
    ),

    'data' => array(
      'cast' => 'string',
    ),

    'languageCode' => array(
      'cast' => 'string',
    ),

    'phraseName' => array(
      'cast' => 'string',
    ),
  ));

  $config = json_decode(file_get_contents('client/data/config.json'), true);

  if ($user['adminDefs']['modPrivs']) {
    switch ($request['do2']) {
      case 'view': case false:
        $users = $database->getUsers(array(
          'hasAdminPrivs' => array(1, 2, 4, 8, 16, 32, 64, 128, 256, 512, 1024, 2048, 4096, 8192),
        ))->getAsArray('userId');

        foreach ($users AS $user2) {
          $adminPrivs = array();

          if ($user2['adminPrivs'] & 1)   $adminPrivs[] = 'Grant';
          if ($user2['adminPrivs'] & 2)    $adminPrivs[] = '<abbr title="This user cannot be altered by any user other than themself and the site owner.">Protected</abbr>';
          if ($user2['adminPrivs'] & 4)    $adminPrivs[] = 'Global Room Moderator';
          if ($user2['adminPrivs'] & 16)  $adminPrivs[] = 'Global Ban Ability';
          if ($user2['adminPrivs'] & 64)  $adminPrivs[] = 'Global Files Control';
          if ($user2['adminPrivs'] & 256)  $adminPrivs[] = 'Censor Control';
          if ($user2['adminPrivs'] & 4096)  $adminPrivs[] = 'Plugins Control';
          if ($user2['adminPrivs'] & 4096)  $adminPrivs[] = 'Interface Control';

          $rows .= "<tr><td>$user2[userId]</td><td>$user2[userName]</td><td>" . implode(', ', $adminPrivs) . "</td><td><a href=\"./moderate.php?do=admin&do2=edit&user=$user2[userId]\"><img src=\"./images/document-edit.png\" /></a></td></tr>";
        }

      echo container('Configurations<a href="./moderate.php?do=config&do2=edit"><img src="./images/document-new.png" style="float: right;" /></a>','<table class="page rowHover">
  <thead>
    <tr class="ui-widget-header">
      <td>User ID</td>
      <td>Username</td>
      <td>Permissions</td>
      <td>Actions</td>
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
    echo 'You do not have permission to modify admin privileges.';
  }
}
?>