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
$ignoreLogin = true;

require('../global.php');


/* Emoticons */
switch($loginConfig['method']) {
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
}


echo new apiData(
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
                'parentalEnabled' => $config['parentalEnabled'],
                'parentalForced' => $config['parentalForced'],
                'parentalAgeChangeable' => $config['parentalAgeChangeable'],
                'parentalRegistrationAge' => $config['parentalRegistrationAge'],
                'parentalFlags' => new apiOutputList($config['parentalFlags']),
                'parentalAges' => new apiOutputList($config['parentalAges']),
                'enableCensor' => $config['censorEnabled'],
            ),

            'branding' => array(
                'forumType' => $loginConfig['method'],
                'forumUrl' => $loginConfig['url'],
            ),

            'requestMethods' => array(
                'poll' => true,
                'serverSentEvents' => (bool) $config['serverSentEvents'],
            ),

            'registrationPolicies' => array(
                'ageRequired' => (bool) $config['ageRequired'],
                'ageMinimum' => (int) $config['ageMinimum'],
                'emailRequired' => (bool) $config['emailRequired'],
            ),

            'fileUploads' => array(
                'enabled' => (bool) $config['enableUploads'],
                'generalEnabled' => (bool) $config['enableGeneralUploads'],
                'maxAll' => (int) $config['uploadMaxFiles'],
                'maxUser' => (int) $config['uploadMaxUserFiles'],
                'chunkSize' => (int) $config['fileUploadChunkSize'],
                'orphanFiles' => (bool) $config['allowOrphanFiles'],
                'allowedExtensions' => new apiOutputList($config['allowedExtensions']),
                'mimes' => new apiOutputList($config['uploadMimes']),
                'extensionChanges' => $config['extensionChanges'],
                'fileContainers' => $config['fileContainers'],
                'fileProofs' => $config['uploadMimeProof'],
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
                'emoticons' => $smilies2,
            ),

            'cacheDelays' => array(
                'censorLists' => $config['censorBlackWhiteListsCacheRefresh'],
            ),

            'phpVersion' => (float) phpversion(), // We won't display the full version as it could pose an unneccessary security risk. We will, however, display the base version.
        ),
    ]
);
?>