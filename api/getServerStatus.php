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




$xmlData = array(
  'getServerStatus' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'sentData' => array(),
    'errorcode' => ($failCode),
    'errortext' => ($failMessage),
    'serverStatus' => array(
      'fim_version' => FIM_VERSION,
      'installedPlugins' => array(),
      'requestMethods' => array(
        'longPoll' => (bool) $longPolling,
        'poll' => (bool) true,
        'push' => (bool) false,
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