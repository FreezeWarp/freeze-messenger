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

/**
 * Get all Installed Fonts
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
*/

$apiRequest = true;

require_once('../global.php');



/* Data Predefine */
$xmlData = array(
  'getFonts' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'fonts' => array(),
  ),
);



/* Plugin Hook Start */
($hook = hook('getFonts_start') ? eval($hook) : '');


$fonts = dbRows("SELECT f.fontId AS fontId,
  f.name AS fontName,
  f.data AS fontData,
  f.category AS fontGroup
  {$fonts_columns}
FROM {$sqlPrefix}fonts AS f
  {$fonts_tables}
WHERE TRUE
  {$fonts_where}
ORDER BY f.category,
  f.name
  {$fonts_order}
{$fonts_end}",'fontId'); // Get all fonts



/* Start Processing */
if ($fonts) {
  foreach ($fonts AS $font) {
    $xmlData['getFonts']['fonts']['font ' . $font['fontId']] = array(
      'fontId' => (int) $font['fontId'],
      'fontName' => ($font['fontName']),
      'fontGroup' => ($font['fontGroup']),
      'fontData' => ($font['fontData']),
    );

    ($hook = hook('getFonts_eachFont') ? eval($hook) : '');
  }
}



/* Update Data for Errors */
$xmlData['getFonts']['errStr'] = (string) $errStr;
$xmlData['getFonts']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('getFonts_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>