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
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string fonts - A comma-seperated list of font IDs to filter by. If not specified all fonts will be retrieved.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'fonts' => array(
    'default' => '',
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),
));



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

$queryParts['fontsSelect']['columns'] = array(
  "{$sqlPrefix}fonts" => 'fontId, fontName, data fontData, category fontGroup',
);
$queryParts['fontsSelect']['conditions'] = array();
$queryParts['fontsSelect']['sort'] = 'fontGroup, fontName';
$queryParts['fontsSelect']['limit'] = false;



/* Modify Query Data for Directives */
if (count($request['fonts']) > 0) {
  $queryParts['fontsSelect']['conditions']['both'][] = array(
    'type' => 'in',
    'left' => array(
      'type' => 'column',
      'value' => 'fontId',
    ),
    'right' => array(
       'type' => 'array',
       'value' => (array) $request['fonts'],
    ),
  );
}



/* Plugin Hook Start */
($hook = hook('getFonts_start') ? eval($hook) : '');



/* Get Fonts from Database */
if ($continue) {
  $fonts = $database->select($queryParts['fontsSelect']['columns'],
    $queryParts['fontsSelect']['conditions'],
    $queryParts['fontsSelect']['sort'],
    $queryParts['fontsSelect']['limit']);
  $fonts = $fonts->getAsArray('fontId');
}



/* Start Processing */
if ($continue) {
  if (is_array($fonts)) {
    if (count($fonts) > 0) {
      foreach ($fonts AS $font) {
        $xmlData['getFonts']['fonts']['font ' . $font['fontId']] = array(
          'fontId' => (int) $font['fontId'],
          'fontName' => (string) $font['fontName'],
          'fontGroup' => (string) $font['fontGroup'],
          'fontData' => (string) $font['fontData'],
        );

        ($hook = hook('getFonts_eachFont') ? eval($hook) : '');
      }
    }
  }
}



/* Update Data for Errors */
$xmlData['getFonts']['errStr'] = (string) $errStr;
$xmlData['getFonts']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('getFonts_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>