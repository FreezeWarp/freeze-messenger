<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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
 * @copyright Joseph T. Parsons 2017
 */

$apiRequest = true;
$ignoreLogin = true;

require('../global.php');

echo new Http\ApiData([
    'serverStatus' => array(
        'fim_version' => FIM_VERSION,
        'installedPlugins' => array(),
        'installUrl' => $installUrl,
        'loginMethods' => new Http\ApiOutputList(array_keys($loginConfig['extraMethods'])),

        'parentalControls' => array(
            'parentalEnabled' => fimConfig::$parentalEnabled,
            'parentalAgeChangeable' => fimConfig::$parentalAgeChangeable,
            'parentalFlags' => new Http\ApiOutputList(fimConfig::$parentalFlags),
            'parentalAges' => new Http\ApiOutputList(fimConfig::$parentalAges),
        ),

        'censorEnabled' => fimConfig::$censorEnabled,

        'branding' => array(
            'forumType' => $loginConfig['method'],
            'forumUrl' => $loginConfig['url'],
        ),

        'requestMethods' => array(
            'poll' => true,
            'serverSentEvents' => (bool) fimConfig::$serverSentEvents,
        ),

        'registrationPolicies' => array(
            'ageRequired' => (bool) fimConfig::$ageRequired,
            'ageMinimum' => (int) fimConfig::$ageMinimum,
            'emailRequired' => (bool) fimConfig::$emailRequired,
        ),

        'fileUploads' => array(
            'enabled' => (bool) fimConfig::$enableUploads,
            'generalEnabled' => (bool) fimConfig::$enableGeneralUploads,
            'maxAll' => (int) fimConfig::$uploadMaxFiles,
            'maxUser' => (int) fimConfig::$uploadMaxUserFiles,
            'chunkSize' => (int) fimConfig::$fileUploadChunkSize,
            'orphanFiles' => (bool) fimConfig::$allowOrphanFiles,
            'allowedExtensions' => new Http\ApiOutputList(fimConfig::$allowedExtensions),
            'mimes' => new Http\ApiOutputList(fimConfig::$uploadMimes),
            'extensionChanges' => fimConfig::$extensionChanges,
            'fileContainers' => fimConfig::$fileContainers,
            'fileProofs' => fimConfig::$uploadMimeProof,
            'sizeLimits' => fimConfig::$uploadSizeLimits,
        ),

        'rooms' => array(
            'roomLengthMinimum' => (int) fimConfig::$roomLengthMinimum,
            'roomLengthMaximum' => (int) fimConfig::$roomLengthMaximum,
            'disableTopic' => (bool) fimConfig::$disableTopic,
            'hiddenRooms' => (bool) fimConfig::$hiddenRooms,
        ),

        'officialRooms' => new Http\ApiOutputList($database->getRooms(['onlyOfficial' => true])->getColumnValues('id')),

        'formatting' => array(
            'fonts' => fimConfig::$defaultFormattingFont ? fimConfig::$fonts : false,
            'highlight' => fimConfig::$defaultFormattingHighlight,
            'color' => fimConfig::$defaultFormattingColor,
            'italics' => fimConfig::$defaultFormattingItalics,
            'bold' => fimConfig::$defaultFormattingBold,
        ),

        'cacheDelays' => array(
            'censorWords' => fimConfig::$censorWordsCacheRefresh,
        ),

        'emoticons' => $generalCache->getEmoticons(),

        'phpVersion' => (float) phpversion(), // We won't display the full version as it could pose an unneccessary security risk. We will, however, display the base version.
    ),
]);
?>