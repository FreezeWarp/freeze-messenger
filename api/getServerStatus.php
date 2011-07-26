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
 * Obtains Prouct Configuration and Related Information That May Alter API Behavior
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
*/

$apiRequest = true;

require_once('../global.php');




$xmlData = array(
  'getServerStatus' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'serverStatus' => array(
      'fim_version' => FIM_VERSION,
      'loginMethod' => $loginConfig['method'],
      'installedPlugins' => array(),
      'requestMethods' => array(
        'longPoll' => (bool) $config['longPolling'],
        'poll' => (bool) true,
        'serverSentEvents' => (bool) $config['serverSentEvents'],
      ),

      'fileUploads' => array(
        'enabled' => (bool) $config['enableUploads'],
        'generalEnabled' => (bool) $config['enableGeneralUploads'],
        'maxAll' => (int) $config['uploadMaxFiles'],
        'maxUser' => (int) $config['uploadUserMaxFiles'],
      ),

      'outputBuffer' => array(
        'comressOutput' => (bool) $compressOutput,
      ),

      'phpVersion' => (float) phpversion(), // We won't display the full version as it could pose an unneccessary security risk.
    ),
  ),
);

($hook = hook('getServerStatus') ? eval($hook) : '');


echo fim_outputApi($xmlData);

dbClose();
?>