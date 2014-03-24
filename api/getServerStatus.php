<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
*/

$apiRequest = true;

require('../global.php');


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
      'installedPlugins' => array(),
      'installUrl' => $installUrl,

      'parentalControls' => array(
        'parentalEnabled' => $config['parentalEnabled'],
        'parentalForced' => $config['parentalForced'],
        'parentalAgeChangeable' => $config['parentalAgeChangeable'],
        'parentalRegistrationAge' => $config['parentalRegistrationAge'],
        'parentalFlags' => $config['parentalFlags'],
        'parentalAges' => $config['parentalAges'],
        'enableCensor' => $config['enableCensor'],
      ),

      'branding' => array(
        'forumType' => $loginConfig['method'],
        'forumUrl' => $loginConfig['url'],
      ),

      'requestMethods' => array(
        'longPoll' => (bool) $config['longPolling'],
        'poll' => true,
        'serverSentEvents' => (bool) $config['serverSentEvents'],
      ),

      'registrationPolicies' => array(
        'ageRequired' => (bool) $config['ageRequired'],
        'ageMinimum' => (int) $config['ageMinimum'],
        'ageMaximum' => (int) $config['ageMaximum'],
        'emailRequired' => (bool) $config['emailRequired'],
      ),

      'fileUploads' => array(
        'enabled' => (bool) $config['enableUploads'],
        'generalEnabled' => (bool) $config['enableGeneralUploads'],
        'maxAll' => (int) $config['uploadMaxFiles'],
        'maxUser' => (int) $config['uploadMaxUserFiles'],
        'chunkSize' => (int) $config['fileUploadChunkSize'],
        'emptyFiles' => (bool) $config['allowEmptyFiles'],
        'orphanFiles' => (bool) $config['allowOrphanFiles'],
        'allowedExtensions' => (array) $config['allowedExtensions'],
        'mimes' => (array) $config['uploadMimes'],
        'extensionChanges' => $config['extensionChanges'],
        'fileContainers' => $config['fileContainers'],
        'mimeProofs' => $config['uploadMimeTypes'],
        'sizeLimits' => $config['uploadSizeLimits'],
      ),

      'rooms' => array(
        'roomLengthMinimum' => (int) $config['roomLengthMinimum'],
        'roomLengthMaximum' => (int) $config['roomLengthMaximum'],
        'disableTopic' => (bool) $config['disableTopic'],
        'officialRooms' => (bool) $config['officialRooms'],
        'hiddenRooms' => (bool) $config['hiddenRooms'],
      ),

      'defaultFormatting' => array(
        'color' => $config['defaultFormattingColor'],
        'font' => $config['defaultFormattingFont'],
        'highlight' => $config['defaultFormattingHighlight'],
        'bold' => $config['defaultFormattingBold'],
        'italics' => $config['defaultFormattingItalics'],
        'underline' => $config['defaultFormattingUnderline'],
        'strikethrough' => $config['defaultFormattingStrikethrough'],
        'overline' => $config['defaultFormattingOverline'],
      ),

      'formatting' => array(
        'fonts' => $config['fonts'],
      ),

      'outputBuffer' => array(
        'comressOutput' => (bool) $config['compressOutput'],
      ),

      'phpVersion' => (float) phpversion(), // We won't display the full version as it could pose an unneccessary security risk. We will, however, display the base version.
    ),
  ),
);



/* Plugin Hook End */
($hook = hook('getServerStatus') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>