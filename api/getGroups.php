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

$apiRequest = true;

require_once('../global.php');
header('Content-type: text/xml');


$groups = sqlArr("SELECT $sqlGroupTableCols[groupId] AS groupId,
  $sqlGroupTableCols[groupName] AS groupName
FROM {$sqlGroupTable} AS g
  {$tables}
WHERE TRUE
  {$where}
ORDER BY g.{$sqlGroupTableCols[groupId]}
  {$order}",'groupId'); // Get all rooms


if ($groups) {
  foreach ($groups AS $group) {
    $groupXML .= "    <group>
      <groupId>$group[groupId]</groupId>
      <groupName>" . fim_encodeXml($group['groupName']) . "</groupName>
    </group>";
  }
}


echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getFonts>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . fim_encodeXml($user['userName']) . "</userName>
  </activeUser>

  <sentData>
  </sentData>

  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>

  <groups>
    $groupXML
  </groups>
</getFonts>";


mysqlClose();

?>