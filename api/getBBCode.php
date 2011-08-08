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
 * Get Admin-Created BBCode
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @todo Implement ID filtering.
*/

$apiRequest = true;

require('../global.php');

/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'fonts' => array(
      'type' => 'string',
      'require' => false,
      'default' => '',
      'context' => array(
         'type' => 'csv',
         'filter' => 'int',
         'evaltrue' => true,
      ),
    ),
  ),
));



/* Data Predefine */
$xmlData = array(
  'getBBCode' => array(
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
  "{$sqlPrefix}fonts" => array(
    'fontId' => 'fontId',
    'fontName' => 'fontName',
    'data' => 'fontData',
    'category' => 'fontGroup',
  ),
);
$queryParts['fontsSelect']['conditions'] = array();
$queryParts['fontsSelect']['sort'] = array(
  'fontGroup' => 'asc',
  'fontName' => 'asc',
);



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
($hook = hook('getBBCode_start') ? eval($hook) : '');



/* Get Fonts from Database */
$fonts = $database->select($queryParts['fontsSelect']['columns'],
  $queryParts['fontsSelect']['conditions'],
  $queryParts['fontsSelect']['sort']);
$fonts = $fonts->getAsArray('fontId');



/* Start Processing */
if (is_array($fonts)) {
  if (count($fonts) > 0) {
    foreach ($fonts AS $font) {
      $xmlData['getBBCode']['fonts']['font ' . $font['fontId']] = array(
        'fontId' => (int) $font['fontId'],
        'fontName' => (string) $font['fontName'],
        'fontGroup' => (string) $font['fontGroup'],
        'fontData' => (string) $font['fontData'],
      );

      ($hook = hook('getBBCode_eachFont') ? eval($hook) : '');
    }
  }
}



/* Update Data for Errors */
$xmlData['getBBCode']['errStr'] = (string) $errStr;
$xmlData['getBBCode']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('getBBCode_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>