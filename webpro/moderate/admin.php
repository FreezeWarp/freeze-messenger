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
        ),
    ));

    if ($user->hasPriv('modPrivs')) {
        switch ($request['do2']) {
            case 'view': case false:
            $users = $database->getUsers(array(
                                             'hasPrivs' => array(fimUser::ADMIN_CENSOR, fimUser::ADMIN_FILES, fimUser::ADMIN_GRANT, fimUser::ADMIN_PROTECTED, fimUser::ADMIN_ROOMS, fimUser::ADMIN_USERS, fimUser::ADMIN_VIEW_PRIVATE),
                                         ))->getAsUsers();

            foreach ($users AS $user2) {
                $adminPrivs = array();

                if ($user2->hasPriv('modPrivs'))   $adminPrivs[] = 'Grant';
                if ($user2->hasPriv('protected'))  $adminPrivs[] = '<abbr title="This user cannot be altered by any user other than themself and the site owner.">Protected</abbr>';
                if ($user2->hasPriv('modRooms'))   $adminPrivs[] = 'Global Room Moderator';
                if ($user2->hasPriv('modUsers'))   $adminPrivs[] = 'Global Ban Ability';
                if ($user2->hasPriv('modFiles'))   $adminPrivs[] = 'Global Files Control';
                if ($user2->hasPriv('modCensor'))  $adminPrivs[] = 'Censor Control';

                $rows .= "<tr><td>{$user2->id}</td><td>{$user2->name}</td><td>" . implode(', ', $adminPrivs) . "</td><td><a href=\"./moderate.php?do=admin&do2=edit&user={$user2->id}\"><img src=\"./images/document-edit.png\" /></a></td></tr>";
            }

            echo container('Administrators<a href="./moderate.php?do=admin&do2=edit"><img src="./images/document-new.png" style="float: right;" /></a>','<table class="page rowHover">
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