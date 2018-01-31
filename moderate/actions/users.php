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
 * Admin Control Panel: User Tools
 * This script allows administrators to edit user permissions, and may house more advanced user controls in the future.
 * To use this script, users must have modPrivs permissions.
 * @todo modPrivs only for admin priviledges; modUsers for everything else.
 */

use Fim\User;

if (!defined('WEBPRO_INMOD')) {
    die();
}
else {
    $request = \Fim\Utilities::sanitizeGPC('r', array(
        'do2' => [
            'default' => 'view',
            'valid' => ['view', 'edit', 'edit2'],
        ],
        'userId' => [
            'cast' => 'int'
        ]
    ));

    $permissions = [
        'view' => 'View Rooms',
        'post' => 'Post in Rooms',
        'changeTopic' => 'Change Room Topics',
        'createRooms' => 'Create Rooms',
        'privateRooms' => 'Send Private Messages',
        'modPrivs' => 'Change User Priviledges (Super Admin)',
        'protected' => '<abbr title="This user cannot be altered by any user other than themself and the site owner.">Protected</abbr>',
        'modRooms' => 'Administrate Rooms',
        'modUsers' => 'Administrate Users',
        'modFiles' => 'Administrate Files',
        'modCensor' => 'Administrate Censor',
    ];

    if ($user->hasPriv('modPrivs')) {
        switch ($request['do2']) {
            case 'view':
            $request = array_merge($request, \Fim\Utilities::sanitizeGPC('g', [
                'page' => [
                    'cast' => 'int',
                    'default' => 0
                ],
                'sort' => [
                    'valid' => ['name', 'id'],
                    'default' => 'id'
                ],
                'userNameSearch' => [
                    'cast' => 'string',
                ]
            ]));

            $usersQuery = \Fim\Database::instance()->getUsers(
                \Fim\Utilities::arrayFilterKeys($request, ['userNameSearch']),
                [$request['sort'] => 'asc'],
                20,
                $request['page']
            );
            $users = $usersQuery->getAsUsers();

            $rows = '';
            foreach ($users AS $user2) {
                $adminPrivs = array();

                foreach ($permissions AS $permission => $permissionText) {
                    if ($user2->hasPriv($permission)) $adminPrivs[] = $permissionText;
                }

                $rows .= "<tr>
                    <td>{$user2->id}</td>
                    <td>{$user2->name}</td>
                    <td>" . implode(', ', $adminPrivs) . "</td>
                    <td><a class='btn btn-sm btn-secondary' href='./index.php?do=users&do2=edit&userId={$user2->id}'><i class='fas fa-edit'></i> Edit</a></td>
                </tr>";
            }

            echo container('User Editor', "<form method='get' action='./index.php'>
                    <label class='input-group'>
                        <span class='input-group-addon'>Search by Name</span>
                        <input class='form-control' type='text' name='userNameSearch' value='{$request['userNameSearch']}' />
                        <button class='input-group-addon'>Search</button>
                    </label>
                    <input type='hidden' name='do' value='users' />
                </form>"
                . ($request['page'] > 0
                    ? '<div class="float-left"><a href="./index.php?do=users&' . http_build_query(array_merge($request, ['page' => $request['page'] - 1])) . '">Previous Page</a></div>'
                    : ''
                ) . ($usersQuery->paginated
                    ? '<div class="float-right"><a href="./index.php?do=users&' . http_build_query(array_merge($request, ['page' => $request['page'] + 1])) . '">Next Page</a></div>'
                    : ''
                ) . "<table class='table table-striped'>
                <thead class='thead-light'>
                <tr>
                    <th><a href='./index.php?do=users&sort=id'>User ID</a></th>
                    <th><a href='./index.php?do=users&sort=name'>Username</a></th>
                    <th>Permissions</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                    $rows
                </tbody>
            </table>");
            break;

            case 'edit':
                $adminUser = \Fim\UserFactory::getFromId($request['userId']);

                if (!$adminUser->exists()) {
                    echo container('No User', 'The user specified is invalid.');
                }
                else {

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
                $request = array_merge($request, \Fim\Utilities::sanitizeGPC('p', [
                    'privs' => [
                        'cast'      => 'list',
                        'transform' => 'bitfield',
                        'bitTable'  =>  User::$permArray
                    ]
                ]));

                // Get the user from the database
                $editUser = \Fim\UserFactory::getFromId($request['userId']);
                // Use the Fim\fimUser setPrivs method to set the priviledges (which may be adjusted from what we received)
                $editUser->set('privs', $request['privs']);
                // Update the Fim\fimUser entry in the database
                $editUser->setDatabase(['privs' => $editUser->privs]);

                echo container('User Updated','The user has been updated.<br /><br /><a class="btn btn-success" href="index.php?do=users">Return to Viewing Users</a>');
            break;
        }
    }
    else {
        echo 'You do not have permission to modify admin privileges.';
    }
}
?>