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

if (!defined('WEBPRO_INMOD')) {
    die();
}
else {
    $request = fim_sanitizeGPC('r', array(
        'do2' => array(
            'valid' => ['view', 'edit', 'edit2', 'delete'],
            'default' => 'view'
        ),

        'emoticonId' => array(
            'cast' => 'int'
        )
    ));

    if ($user->hasPriv('modPrivs')) {
        switch($request['do2']) {
            case 'view':
                $emoticons = \Fim\Cache::getEmoticons();

                foreach ($emoticons AS $emoticon) {
                    $rows .= "<tr>
                        <td>{$emoticon['emoticonText']}</td>
                        <td>{$emoticon['emoticonFile']} (<img src='{$emoticon['emoticonFile']}' />)</td>
                        <td>
                            <a class='btn btn-secondary' href='./index.php?do=emoticons&do2=edit&emoticonId={$emoticon['emoticonId']}'><i class='fas fa-edit'></i> Edit</a>
                            <a class='btn btn-danger' href='./index.php?do=emoticons&do2=delete&emoticonId={$emoticon['emoticonId']}'><i class='fas fa-trash'></i> Delete</a>
                        </td>
                    </tr>";
                }

                echo container('Current Emotes <a class="btn btn-success float-right" href="./index.php?do=emoticons&do2=edit"><i class="fas fa-plus-square"></i> Add New Emote</a>', '<table class="table table-bordered table-striped table-align-middle">
                    <thead class="thead">
                    <tr>
                        <th>Emote Text</th>
                        <th>Emote File</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                        ' . $rows . '
                    </tbody>
                </table>');
            break;



            case 'edit':
                if ($request['emoticonId']) {
                    $emoticon = \Fim\Cache::getEmoticons()[$request['emoticonId']];

                    $title = 'Edit Emote "' . $emoticon['emoticonText'] . '"';
                }
                else {
                    $emoticon = array(
                        'emoticonId' => 0,
                        'emoticonText' => '',
                        'emoticonFile' => ''
                    );

                    $title = 'Add New Emote';
                }

                echo container($title, "<form action='./index.php?do=emoticons&do2=edit2&emoticonId={$emoticon['emoticonId']}' method='post'>
                    <label class='input-group'>
                        <span class='input-group-addon'>Text</span>
                        <input class='form-control' type='text' name='emoticonText' value='{$emoticon['emoticonText']}' required />
                    </label>
                    <small class='form-text text-muted'>This is the text that will be replaced with an emote.</small><br />
                    
                    <label class='input-group'>
                        <span class='input-group-addon'>File</span>
                        <input class='form-control' type='text' name='emoticonFile' value='{$emoticon['emoticonFile']}' required />
                    </label>
                    <small class='form-text text-muted'>This is the file that will be used for the emote image. It should be an absolute path.</small><br />
                    
                    <button class='btn btn-success' type='submit'>Submit</button>
                    <button class='btn btn-secondary' type='reset'>Reset</button>
                </form>");
            break;



            case 'edit2':
                // Get Request
                $request = array_merge($request, fim_sanitizeGPC('p', [
                    'emoticonText' => [
                        'require' => true
                    ],
                    'emoticonFile' => [
                        'require' => true
                    ],
                ]));

                // Log and Perform Request
                if ($request['emoticonId']) {
                    \Fim\Database::instance()->modLog('editEmoticon', $request['emoticonId']);
                    \Fim\Database::instance()->fullLog('editEmoticon', fim_arrayFilterKeys($request, ['emoticonId', 'emoticonText', 'emoticonFile']));

                    \Fim\Database::instance()->update(
                        \Fim\Database::$sqlPrefix . 'emoticons',
                        fim_arrayFilterKeys($request, ['emoticonText', 'emoticonFile']),
                        fim_arrayFilterKeys($request, ['emoticonId'])
                    );
                }
                else {
                    \Fim\Database::instance()->modLog('addEmoticon', $request['emoticonText']);
                    \Fim\Database::instance()->fullLog('addEmoticon', fim_arrayFilterKeys($request, ['emoticonText', 'emoticonFile']));

                    \Fim\Database::instance()->insert(
                        \Fim\Database::$sqlPrefix . 'emoticons',
                        fim_arrayFilterKeys($request, ['emoticonText', 'emoticonFile'])
                    );
                }

                // Clear the Cache
                \Fim\Cache::clearEmoticons();

                // Respond
                echo container('Emoticon Updated','The emote has been created/updated.<br /><br /><a class="btn btn-success" href="index.php?do=emoticons">Return to Viewing Emotes</a>');
            break;



            case 'delete':
                // Log the Deletion
                $emoticon = \Fim\Cache::getEmoticons()[$request['emoticonId']];

                \Fim\Database::instance()->modLog('deleteEmoticon', $emoticon['emoticonText']);
                \Fim\Database::instance()->fullLog('deleteEmoticon', $emoticon);

                // Perform the Deletion
                \Fim\Database::instance()->delete(
                    \Fim\Database::$sqlPrefix . 'emoticons',
                    fim_arrayFilterKeys($request, ['emoticonId'])
                );

                // Clear the Cache
                \Fim\Cache::clearEmoticons();

                // Respond
                echo container('Emoticon Deleted','The emote has been deleted.<br /><br /><a class="btn btn-success" href="index.php?do=emoticons">Return to Viewing Emotes</a>');
            break;
        }
    }
    else {
        echo 'You do not have permission to modify the censor.';
    }
}
?>