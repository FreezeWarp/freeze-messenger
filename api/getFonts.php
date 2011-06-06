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


$fonts = sqlArr("SELECT f.id AS fontId,
  f.name AS fontName,
  f.data AS fontData,
  f.category AS fontGroup
  {$cols}
FROM {$sqlPrefix}fonts AS f
  {$tables}
WHERE TRUE
  {$where}
ORDER BY f.category,
  f.name
  {$order}",'fontId'); // Get all rooms


if ($fonts) {
  foreach ($fonts AS $font) {
    $fontXML .= "    <font>
      <fontId>$font[fontId]</fontId>
      <fontName>" . fim_encodeXml($font['fontName']) . "</fontName>
      <fontGroup>" . fim_encodeXml($font['fontGroup']) . "</fontGroup>
      <fontData>" . fim_encodeXml($font['fontData']) . "</fontData>
    </font>";
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

  <fonts>
    $fontXML
  </fonts>
</getFonts>";


mysqlClose();

?>