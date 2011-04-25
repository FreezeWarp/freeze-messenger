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

require_once('../global.php');
require_once('../functions/generalFunctions.php');
require_once('../functions/container.php');

$roomid = intval($_POST['roomid']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $roomid");

if ($banned || !$room) { // Check that the user isn't banned.
  echo container('We\'re Sorry','We\'re sorry, but for the time being you have been banned from the chat. You make contact a Victory Road administrator for more information.');
}

else {
  require_once('../roomTemplate.php'); // While the below arguably should be in this too [since it is needed for pretty much anything to work], we're only reusing the code in the AJAX room switcher, which itself just assumes everything below already exists in the DOM.
}

?>