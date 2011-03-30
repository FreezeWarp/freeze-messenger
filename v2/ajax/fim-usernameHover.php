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
require_once('../functions/parserFunctions.php');
require_once('../functions/generalFunctions.php');

$userid = intval($_GET['userid']);
$user = sqlArr("SELECT * FROM {$sqlUserTable} WHERE {$sqlUserIdCol} = $userid");

$userdata = '<img title="" src="http://www.victoryroad.net/image.php?u=' . $user['userid'] . '" style="float: left;" />' . userFormat($user,false) . '<br />' . $user['usertitle'] . '<br /><em>Posts</em>: ' . $user['posts'] . '<br /><em>Member Since</em>: ' . vbdate('m/d/y',$user['joindate']);

echo $userdata;

?>