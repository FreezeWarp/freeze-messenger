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
 * Admin Control Panel: Emoticon Tools
 * This script can create, edit, and delete emoticons.
 * At present, it can only be used by users with modPrivs permissions; in the future, modEmoticons will be used instead.
 *
 */

if (!defined('WEBPRO_INMOD')) {
    die();
}
else {
    $request = \Fim\Utilities::sanitizeGPC('r', array(
        'do2' => array(
            'valid' => ['view', 'edit', 'edit2', 'delete'],
            'default' => 'view'
        ),

        'id' => array(
            'cast' => 'int'
        )
    ));

    if ($user->hasPriv('modPrivs')) {
        switch($request['do2']) {
            case 'view':
                $emoticons = \Fim\Cache::getEmoticons();

                foreach ($emoticons AS $emoticon) {
                    $rows .= "<tr>
                        <td>{$emoticon['text']}</td>
                        <td>{$emoticon['file']} (<img src='{$emoticon['file']}' />)</td>
                        <td>
                            <a class='btn btn-secondary' href='./index.php?do=emoticons&do2=edit&id={$emoticon['id']}'><i class='fas fa-edit'></i> Edit</a>
                            <a class='btn btn-danger' href='./index.php?do=emoticons&do2=delete&id={$emoticon['id']}'><i class='fas fa-trash'></i> Delete</a>
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
                if ($request['id']) {
                    $emoticon = \Fim\Cache::getEmoticons()[$request['id']];

                    $title = 'Edit Emote "' . $emoticon['text'] . '"';
                }
                else {
                    $emoticon = array(
                        'id' => 0,
                        'text' => '',
                        'file' => ''
                    );

                    $title = 'Add New Emote';
                }

                echo container($title, "<form action='./index.php?do=emoticons&do2=edit2&id={$emoticon['id']}' method='post'>
                    <label class='input-group'>
                        <span class='input-group-addon'>Text</span>
                        <input class='form-control' type='text' name='text' value='{$emoticon['text']}' required />
                    </label>
                    <small class='form-text text-muted'>This is the text that will be replaced with an emote.</small><br />
                    
                    <label class='input-group'>
                        <span class='input-group-addon'>File</span>
                        <input class='form-control' type='text' name='file' value='{$emoticon['file']}' required />
                    </label>
                    <small class='form-text text-muted'>This is the file that will be used for the emote image. It should be an absolute path.</small><br />
                    
                    <button class='btn btn-success' type='submit'>Submit</button>
                    <button class='btn btn-secondary' type='reset'>Reset</button>
                </form>");
            break;



            case 'edit2':
                // Get Request
                $request = array_merge($request, \Fim\Utilities::sanitizeGPC('p', [
                    'text' => [
                        'require' => true
                    ],
                    'file' => [
                        'require' => true
                    ],
                ]));

                // Log and Perform Request
                if ($request['id']) {
                    \Fim\Database::instance()->modLog('editEmoticon', $request['id']);
                    \Fim\Database::instance()->fullLog('editEmoticon', \Fim\Utilities::arrayFilterKeys($request, ['id', 'text', 'file']));

                    \Fim\Database::instance()->update(
                        \Fim\Database::$sqlPrefix . 'emoticons',
                        \Fim\Utilities::arrayFilterKeys($request, ['text', 'file']),
                        \Fim\Utilities::arrayFilterKeys($request, ['id'])
                    );
                }
                else {
                    \Fim\Database::instance()->modLog('addEmoticon', $request['text']);
                    \Fim\Database::instance()->fullLog('addEmoticon', \Fim\Utilities::arrayFilterKeys($request, ['text', 'file']));

                    \Fim\Database::instance()->insert(
                        \Fim\Database::$sqlPrefix . 'emoticons',
                        \Fim\Utilities::arrayFilterKeys($request, ['text', 'file'])
                    );
                }

                // Clear the Cache
                \Fim\Cache::clearEmoticons();

                // Respond
                echo container('Emoticon Updated','The emote has been created/updated.<br /><br /><a class="btn btn-success" href="index.php?do=emoticons">Return to Viewing Emotes</a>');
            break;



            case 'delete':
                // Log the Deletion
                $emoticon = \Fim\Cache::getEmoticons()[$request['id']];

                \Fim\Database::instance()->modLog('deleteEmoticon', $emoticon['text']);
                \Fim\Database::instance()->fullLog('deleteEmoticon', $emoticon);

                // Perform the Deletion
                \Fim\Database::instance()->delete(
                    \Fim\Database::$sqlPrefix . 'emoticons',
                    \Fim\Utilities::arrayFilterKeys($request, ['id'])
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