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
 * Get All Usergroups
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string groups - A comma-seperated list of group IDs to filter by. If not specified all groups will be retrieved.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'groups' => array(
    'type' => 'string',
    'require' => false,
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
  'getGroups' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'groups' => array(),
  ),
);

$queryParts['groupsSelect']['columns'] = array(
  "{$sqlUserGroupTable}" => array(
    "$sqlUserGroupTableCols[groupId]" => 'groupId',
    "$sqlUserGroupTableCols[groupName]" => 'groupName',
  ),
);
$queryParts['groupsSelect']['conditions'] = array();
$queryParts['groupsSelect']['sort'] = array(
  'groupId' => 'asc',
);
$queryParts['groupsSelect']['limit'] = false;



/* Modify Query Data for Directives */
if (count($request['groups']) > 0) {
  $queryParts['groupsSelect']['conditions']['both'][] = array(
    'type' => 'in',
    'left' => array(
      'type' => 'column',
      'value' => 'groupId',
    ),
    'right' => array(
       'type' => 'array',
       'value' => (array) $request['groups'],
    ),
  );
}



/* Plugin Hook Start */
($hook = hook('getGroups_start') ? eval($hook) : '');



/* Get Groups from Database */
if ($continue) {
  $groups = $integrationDatabase->select($queryParts['groupsSelect']['columns'],
    $queryParts['groupsSelect']['conditions'],
    $queryParts['groupsSelect']['sort'],
    $queryParts['groupsSelect']['limit']);
  $groups = $groups->getAsArray('groupId');
}


/* Start Processing */
if ($continue) {
  if (is_array($groups)) {
    if (count($groups) > 0) {
      foreach ($groups AS $group) {
        /* Integration-Specific Conversion
        /* TODO: Move to Hooks */
        if ($loginConfig['method'] == 'phpbb') {
          if (function_exists('mb_convert_case')) {
            $group['groupName'] = mb_convert_case(
              str_replace('_',' ',$group['groupName']), // PHPBB replaces spaces with underscores - revert this.
              MB_CASE_TITLE, // Specifies that the first letter of each word should be capitalized, all the rest should not be.
              "UTF-8" // Unicode
            );
          }
          elseif (function_exists('uc_words')) {
            $group['groupName'] = ucwords( // Finally, captilize the first letter of each word.
              strtolower( // Next, convert the entire string to lower case.
                str_replace('_',' ',$group['groupName']) // First, replace underscores (see above)
              )
            );
          }
          else {
            $group['groupName'] = str_replace('_',' ',$group['groupName']); // Just replace underscores (see above).
          }
        }

        $xmlData['getGroups']['groups']['group ' . $group['groupId']] = array(
          'groupId' => (int) $group['groupId'],
          'groupName' => (string) $group['groupName'],
        );

        ($hook = hook('getGroups_eachGroup') ? eval($hook) : '');
      }
    }
  }
}



/* Update Data for Errors */
$xmlData['getGroups']['errStr'] = ($errStr);
$xmlData['getGroups']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('getGroups_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>