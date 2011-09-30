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
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
*/

$apiRequest = true;

require('../global.php');

/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'bbcodes' => array(
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

$queryParts['bbcodeSelect']['columns'] = array(
  "{$sqlPrefix}bbcode" => 'bbcodeId, bbcodeName, options, searchRegex, replacement',
);
$queryParts['bbcodeSelect']['conditions'] = array();
$queryParts['bbcodeSelect']['sort'] = 'bbcodeId';
$queryParts['bbcodeSelect']['limit'] = false;



/* Modify Query Data for Directives */
if (count($request['bbcodes']) > 0) {
  $queryParts['bbcodeSelect']['conditions']['both'][] = array(
    'type' => 'in',
    'left' => array(
      'type' => 'column',
      'value' => 'bbcodeId',
    ),
    'right' => array(
       'type' => 'array',
       'value' => (array) $request['bbcodes'],
    ),
  );
}



/* Plugin Hook Start */
($hook = hook('getBBCode_start') ? eval($hook) : '');



/* Get Fonts from Database */
$bbcodes = $database->select($queryParts['bbcodeSelect']['columns'],
  $queryParts['bbcodeSelect']['conditions'],
  $queryParts['bbcodeSelect']['sort'],
  $queryParts['bbcodeSelect']['limit']);
$bbcodes = $bbcodes->getAsArray('bbcodeId');



/* Start Processing */
if (is_array($bbcodes)) {
  if (count($bbcodes) > 0) {
    foreach ($bbcodes AS $bbcode) {
      $xmlData['getBBCode']['bbcodes']['bbcode ' . $bbcode['bbcodeId']] = array(
        'bbcodeId' => (int) $bbcode['bbcodeId'],
        'bbcodeName' => (string) $bbcode['bbcodeName'],
        'searchRegex' => (string) $bbcode['searchRegex'],
        'replacement' => (string) $bbcode['replacement'],
      );

      ($hook = hook('getBBCode_eachBBCode') ? eval($hook) : '');
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
?>