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


$room = (int) $_GET['roomid'];


$censorLists = sqlArr("SELECT c.id AS listId,
  c.name as listName,
  c.type AS listType,
  c.options AS listOptions
FROM {$sqlPrefix}censorLists AS c
  {$tables}
WHERE TRUE
  {$where}
ORDER BY c.name
  {$order}",'groupId'); // Get all rooms


if ($censorLists) {
  foreach ($censorLists AS $list) {
    $listXML .= "    <list>
      <listId>$list[listId]</listId>
      <listName>" . fim_encodeXml($list['listName']) . "</listName>
      <listType>" . fim_encodeXml($list['listType']) . "</listType>
      <listOptions>$list[listOptions]</listOptions>
    </list>";
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

  <lists>
    $listXML
  </lists>
</getFonts>";


mysqlClose();
?>