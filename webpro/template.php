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
$interfaceName = 'webpro';

require_once('../global.php');

$template = $_GET['template'];

switch ($template) {
  case 'kickForm':
  case 'unkickForm':
  case 'copyright':
  case 'userSettingsForm':
  case 'online':
  case 'help':
  case 'privateRoomForm':
  case 'createRoomForm':
  case 'contextMenu':
  case 'login':
  case 'register':
  case 'editRoomForm':
  case 'insertDoc':
  case 'createRoomSuccess':
  echo template($template);
  break;

  default:
  trigger_error("Unknown Template: '$template'", E_USER_ERROR);
  break;
}
?>