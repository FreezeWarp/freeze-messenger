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


/* Emoticons */

/*switch($loginConfig['method']) {
    case 'vbulletin3':
    case 'vbulletin4':
        $smilies = $integrationDatabase->select(array(
                                                    "{$forumTablePrefix}smilie" => 'smilietext emoticonText, smiliepath emoticonFile',
                                                ))->getAsArray(true);
        break;

    case 'phpbb':
        $smilies = $integrationDatabase->select(array(
                                                    "{$forumTablePrefix}smilies" => 'code emoticonText, smiley_url emoticonFile'
                                                ))->getAsArray(true);
        break;

    case 'vanilla':
        // TODO: Convert
        $smilies = $database->select(array(
                                         $database->sqlPrefix . "emoticons" => 'emoticonText, emoticonFile'
                                     ))->getAsArray(true);
        break;

    default:
        $smilies = array();
        break;
}



if (count($smilies)) {
    switch ($loginConfig['method']) {
        case 'phpbb':
            $forumUrlS = $loginConfig['url'] . 'images/smilies/';
            break;

        case 'vanilla':
            $forumUrlS = $installUrl;
            break;

        case 'vbulletin3':
        case 'vbulletin4':
            $forumUrlS = $loginConfig['url'];
            break;
    }

    foreach ($smilies AS $smilie) {
        $smilies2[$smilie['emoticonText']] = $forumUrlS . $smilie['emoticonFile'];
    }
}*/


echo new ApiData(
    [
        'serverStatus' => array(
            'activeUser' => array(
                'userId' => $user->id,
                'userName' => $user->name,
            ),

            'fim_version' => FIM_VERSION,
            'installedPlugins' => array(),
            'installUrl' => $installUrl,

            'parentalControls' => array(
                'parentalEnabled' => fimConfig::$parentalEnabled,
                'parentalAgeChangeable' => fimConfig::$parentalAgeChangeable,
                'parentalFlags' => new ApiOutputList(fimConfig::$parentalFlags),
                'parentalAges' => new ApiOutputList(fimConfig::$parentalAges),
                'enableCensor' => fimConfig::$censorEnabled,
            ),

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
                'allowedExtensions' => new ApiOutputList(fimConfig::$allowedExtensions),
                'mimes' => new ApiOutputList(fimConfig::$uploadMimes),
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

            'formatting' => array(
                'fonts' => fimConfig::$defaultFormattingFont ? fimConfig::$fonts : false,
                'highlight' => fimConfig::$defaultFormattingHighlight,
                'color' => fimConfig::$defaultFormattingColor,
                'italics' => fimConfig::$defaultFormattingItalics,
                'bold' => fimConfig::$defaultFormattingBold,
                ///'emoticons' => $smilies2,
            ),

            'cacheDelays' => array(
                'censorWords' => fimConfig::$censorWordsCacheRefresh,
            ),

            'phpVersion' => (float) phpversion(), // We won't display the full version as it could pose an unneccessary security risk. We will, however, display the base version.
        ),
    ]
);
?>