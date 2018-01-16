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
 * TODO
 */

/* Common Resources */

use Fim\Error;

$apiRequest = true;
require('../global.php');
define('API_INGROUP', true);


/* Header parameters -- identifies what we're doing as well as the group itself, if applicable. */
$requestHead = fim_sanitizeGPC('g', [
    '_action' => [],
]);




/* Load the correct file to perform the action */
switch ($requestHead['_action']) {
    case 'create':
    case 'edit':
    case 'delete':
    case 'undelete':
        new \Fim\Error('unimplemented', 'This functionality is not implemented in this release.');
        break;

    case 'get':
        require('group/getGroups.php');
        break;
}