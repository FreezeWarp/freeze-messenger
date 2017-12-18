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
        'do2' => [
            'default' => 'view',
            'valid' => ['view', 'edit', 'edit2'],
        ],
        'userId' => [
            'cast' => 'int'
        ]
    ));

    if ($user->hasPriv('modPrivs')) {
        switch ($request['do2']) {
            case 'view':
            $users = \Fim\Database::instance()->getUsers()->getAsUsers();

            $rows = '';
            foreach ($users AS $user2) {
                $adminPrivs = array();

                if ($user2->hasPriv('modPrivs'))   $adminPrivs[] = 'Grant';
                if ($user2->hasPriv('protected'))  $adminPrivs[] = '<abbr title="This user cannot be altered by any user other than themself and the site owner.">Protected</abbr>';
                if ($user2->hasPriv('modRooms'))   $adminPrivs[] = 'Global Room Moderator';
                if ($user2->hasPriv('modUsers'))   $adminPrivs[] = 'Global Ban Ability';
                if ($user2->hasPriv('modFiles'))   $adminPrivs[] = 'Global Files Control';
                if ($user2->hasPriv('modCensor'))  $adminPrivs[] = 'Censor Control';

                $rows .= "<tr><td>{$user2->id}</td><td>{$user2->name}</td><td>" . implode(', ', $adminPrivs) . "</td><td><a class='btn btn-sm btn-secondary' href='./index.php?do=users&do2=edit&userId={$user2->id}'><i class='fas fa-edit'></i> Edit</a></td></tr>";
            }

            echo container('User Editor','<table class="table table-striped">
  <thead class="thead-light">
    <tr>
      <th>User ID</th>
      <th>Username</th>
      <th>Permissions</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
            break;

            case 'edit':
                $adminUser = \Fim\UserFactory::getFromId($request['userId']);

                if (!$adminUser->exists()) {
                    echo container('No User', 'The user specified is invalid.');
                }
                else {
                    $permissions = [
                        'view' => 'View Rooms',
                        'post' => 'Post in Rooms',
                        'changeTopic' => 'Change Room Topics',
                        'createRooms' => 'Create Rooms',
                        'privateFriends' => 'Message Friended Users',
                        'privateAll' => 'Message All Users',
                        'roomsOnline' => 'View Online Users',
                        'modPrivs' => 'Change User Priviledges (Super Admin)',
                        'protected' => 'Protected Users (Priviledges Can\'t be Changed)',
                        'modRooms' => 'Administrate Rooms',
                        'modUsers' => 'Administrate Users',
                        'modFiles' => 'Administrate Files',
                        'modCensor' => 'Administrate Censor',
                    ];

                    $permissionsBox = '';
                    foreach($permissions AS $permission => $permissionText) {
                        $permissionsBox .= "<label class='btn btn-secondary'>
                            <input type='checkbox' name='privs[]' " . ($adminUser->hasPriv($permission) ? 'checked="checked"' : '') . "value='$permission' /> $permissionText
                        </label>";
                    }

                    echo container("Edit User '{$adminUser->name}'", '
                    <form action="./index.php?do=users&do2=edit2&userId=' . $request['userId'] . '" method="post">
                        <h4>Admin Permissions</h4>' . $permissionsBox . '
                        <br />
                        
                        <input type="submit" class="btn btn-primary" value="Submit" />
                    </form>');
                }

            break;

            case 'edit2':
                $request = array_merge($request, fim_sanitizeGPC('p', [
                    'privs' => [
                        'cast'      => 'list',
                        'transform' => 'bitfield',
                        'bitTable'  =>  fimUser::$permArray
                    ]
                ]));
                $editUser = \Fim\UserFactory::getFromId($request['userId']);
                $editUser->set('privs', $request['privs']);
                $editUser->setDatabase(['privs' => $editUser->privs]);
            break;
        }
    }
    else {
        echo 'You do not have permission to modify admin privileges.';
    }
}
?>