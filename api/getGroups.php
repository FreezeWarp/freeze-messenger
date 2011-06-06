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


$groups = sqlArr("SELECT $sqlUserGroupTableCols[groupId] AS groupId,
  $sqlUserGroupTableCols[groupName] AS groupName
FROM {$sqlUserGroupTable} AS g
  {$tables}
WHERE TRUE
  {$where}
ORDER BY g.{$sqlUserGroupTableCols[groupId]}
  {$order}",'groupId'); // Get all rooms


if ($groups) {
  foreach ($groups AS $group) {
    switch ($loginMethod) {
      case 'phpbb':
      if (function_exists('mb_convert_case')) {
        $group['groupName'] = mb_convert_case(str_replace('_',' ',$group['groupName']), MB_CASE_TITLE, "UTF-8");
      }
      elseif (function_exists('uc_words')) {
        $group['groupName'] = ucwords(str_replace('_',' ',$group['groupName']));
      }
      else {
        $group['groupName'] = str_replace('_',' ',$group['groupName']);
      }
      break;
    }

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