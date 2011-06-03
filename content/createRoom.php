<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

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


$reqPhrases = true;
$reqHooks = true;

require_once('../global.php'); // Used for everything.
require_once('../functions/container.php'); // Used for /some/ formatting, though perhaps too sparcely right now.

$phase = $_GET['phase'];
if (!$phase) $phase = '1'; // Default to phase 1.

if (!$allowRoomCreation) {
  trigger_error($phrases['createRoomDisabled'],E_USER_ERROR);
}
elseif ($user['settings'] & 2) {
  trigger_error($phrases['createRoomBanned'],E_USER_ERROR);
}
elseif ($phase == '1') {
  echo template('createRoomForm');
}
elseif ($phase == '2') {
}
else {
  trigger_error($phrases['createRoomUnknownAction'],E_USER_ERROR);
}
?>