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
        'loginMethods' => new Http\ApiOutputList(array_keys($loginConfig['extraMethods'] ?? [])),

        'parentalControls' => array(
            'parentalEnabled' => \Fim\Config::$parentalEnabled,
            'parentalAgeChangeable' => \Fim\Config::$parentalAgeChangeable,
            'parentalFlags' => new Http\ApiOutputList(\Fim\Config::$parentalFlags),
            'parentalAges' => new Http\ApiOutputList(\Fim\Config::$parentalAges),
        ),

        'censorEnabled' => \Fim\Config::$censorEnabled,

        'branding' => array(
            'forumType' => $loginConfig['method'],
            'forumUrl' => $loginConfig['url'],
        ),

        'requestMethods' => array(
            'poll' => true,
            'serverSentEvents' => (bool) \Fim\Config::$serverSentEvents,
        ),

        'registrationPolicies' => array(
            'registrationEnabled' => ($loginConfig['method'] === 'vanilla'
                ? \Fim\Config::$registrationEnabled
                : \Fim\Config::$registrationEnabledIgnoreForums
            ),
            'ageRequired' => (bool) \Fim\Config::$ageRequired,
            'ageMinimum' => (int) \Fim\Config::$ageMinimum,
            'emailRequired' => (bool) \Fim\Config::$emailRequired,
        ),

        'fileUploads' => array(
            'enabled' => (bool) \Fim\Config::$enableUploads,
            'generalEnabled' => (bool) \Fim\Config::$enableGeneralUploads,
            'maxAll' => (int) \Fim\Config::$uploadMaxFiles,
            'maxUser' => (int) \Fim\Config::$uploadMaxUserFiles,
            'chunkSize' => (int) \Fim\Config::$fileUploadChunkSize,
            'orphanFiles' => (bool) \Fim\Config::$allowOrphanFiles,
            'allowedExtensions' => new Http\ApiOutputList(\Fim\Config::$allowedExtensions),
            'mimes' => new Http\ApiOutputList(\Fim\Config::$uploadMimes),
            'extensionChanges' => \Fim\Config::$extensionChanges,
            'fileContainers' => \Fim\Config::$fileContainers,
            'fileProofs' => \Fim\Config::$uploadMimeProof,
            'sizeLimits' => \Fim\Config::$uploadSizeLimits,
        ),

        'rooms' => array(
            'roomLengthMinimum' => (int) \Fim\Config::$roomLengthMinimum,
            'roomLengthMaximum' => (int) \Fim\Config::$roomLengthMaximum,
            'disableTopic' => (bool) \Fim\Config::$disableTopic,
            'typingStatus' => (bool) \Fim\Config::$userTypingStatus,
        ),

        'officialRooms' => new Http\ApiOutputList(\Fim\Database::instance()->getRooms(['onlyOfficial' => true])->getColumnValues('id')),

        'formatting' => array(
            'fonts' => \Fim\Config::$defaultFormattingFont ? \Fim\Config::$fonts : false,
            'highlight' => \Fim\Config::$defaultFormattingHighlight,
            'color' => \Fim\Config::$defaultFormattingColor,
            'italics' => \Fim\Config::$defaultFormattingItalics,
            'bold' => \Fim\Config::$defaultFormattingBold,
        ),

        'emoticons' => \Fim\Cache::getEmoticons(),
        'pushPublicKey' => \Stream\WebPushHandler::$publicKey
    ),
]);
?>