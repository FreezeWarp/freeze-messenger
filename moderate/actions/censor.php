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
        'listId' => array(
            'cast' => 'int',
        ),

        'wordId' => array(
            'cast' => 'int',
        ),

        'word' => array(
            'cast' => 'string',
        ),

        'param' => array(
            'cast' => 'string',
        ),

        'severity' => array(
            'valid' => array('replace', 'warn', 'confirm', 'block'),
            'default' => 'replace',
        ),

        'listName' => array(
            'cast' => 'string',
        ),

        'listType' => array(
            'valid' => array('black', 'white'),
            'default' => 'white',
        ),

        'mature' => array(
            'cast' => 'bool',
        ),
    ));

    if ($user->hasPriv('modCensor')) {
        switch($_GET['do2']) {

            case false:
            case 'viewLists':
                $lists = \Fim\Database::instance()->getCensorLists()->getAsArray(true);

                foreach ($lists AS $list) {
                    $options = array();

                    if (!($list['options'] & \Fim\CensorList::CENSORLIST_ENABLED))
                        $options[] = "Inactive";
                    if ($list['options'] & \Fim\CensorList::CENSORLIST_DISABLEABLE)
                        $options[] = "Disableable";
                    if ($list['options'] & \Fim\CensorList::CENSORLIST_HIDDEN)
                        $options[] = "Hidden";
                    if ($list['options'] & \Fim\CensorList::CENSORLIST_PRIVATE_DISABLED)
                        $options[] = "Disabled in Private";
                    //if ($list['options'] & 8) $options[] = "Mature";

                    $rows .= '    <tr>
                        <td>' . $list['listName'] . '</td>
                        <td>' . ($list['listType'] == 'white'
                            ? '<div style="border-radius: 1em; background-color: white; border: 1px solid black; width: 20px; height: 20px;"></div>'
                            : '<div style="border-radius: 1em; background-color: black; border: 1px solid white; width: 20px; height: 20px;"></div>'
                        ) . '</td>
                        <td>' . implode(', ',$options) . '</td>
                        <td>
                            <a href="./index.php?do=censor&do2=editList&listId=' . $list['listId'] . '" class="btn btn-secondary"><i class="fas fa-edit"></i> Edit</a>
                            <a href="./index.php?do=censor&do2=viewWords&listId=' . $list['listId'] . '" class="btn btn-secondary" title="View List"><i class="fas fa-list"></i> Words</a>
                            <a href="./index.php?do=censor&do2=deleteList&listId=' . $list['listId'] . '" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
  ';
                }

                echo container('Current Lists <a class="btn btn-success float-right" href="./index.php?do=censor&do2=editList"><i class="fas fa-plus-square"></i> Add New List</a>', '<table class="table table-bordered table-striped table-align-middle">
                    <thead class="thead">
                    <tr>
                        <th>List Name</th>
                        <th>Type</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                        ' . $rows . '
                    </tbody>
                </table>');
                break;



            case 'editList':
                if ($request['listId']) {
                    $list = \Fim\Database::instance()->getCensorList($request['listId']);

                    $title = 'Edit Censor List "' . $list['listName'] . '"';
                }
                else {
                    $list = array(
                        'listId' => 0,
                        'listName' => '',
                        'listType' => 'white',
                        'options' => 3,
                    );

                    $title = 'Add New Censor List';
                }

                echo container($title, '<form action="./index.php?do=censor&do2=editList2&listId=' . $list['listId'] . '" method="post">
                    <label class="input-group">
                        <span class="input-group-addon">Name</span>
                        <input class="form-control" type="text" name="listName" value="' . $list['listName'] . '" />
                    </label>
                    <small class="form-text text-muted">This is the name that will identify the list to users.</small><br />
                    
                    
                    <label class="input-group">
                      <span class="input-group-addon">Type</span>
                      ' . fimHtml_buildSelect('listType', array(
                                                         'black' => 'black',
                                                         'white' => 'white',
                                                     ), $list['listType']) . '
                    </label>
                    <small class="form-text text-muted">A "black list" is opt-in: rooms can choose to black list themselves, but the list won\'t be enabled by default. A "white list" is opt-out: rooms can choose to white list themselves out of the list, but it will be enabled in those rooms by default.</small><br />
                    
                    
                    <label class="btn btn-secondary">
                        <input type="checkbox" name="options[]" value="enabled" ' . ($list['options'] & \Fim\CensorList::CENSORLIST_ENABLED ? ' checked="checked"' : '') . ' />
                        Active
                    </label>
                    
                    <label class="btn btn-secondary">
                        <input type="checkbox" name="options[]" value="disableable" ' . ($list['options'] & \Fim\CensorList::CENSORLIST_DISABLEABLE ? ' checked="checked"' : '') . ' />
                        Can be Disabled in Rooms
                    </label>
                    
                    <label class="btn btn-secondary">
                        <input type="checkbox" name="options[]" value="hidden" ' . ($list['options'] & \Fim\CensorList::CENSORLIST_HIDDEN ? ' checked="checked"' : '') . ' />
                        Hidden
                    </label>
                    
                    <label class="btn btn-secondary">
                        <input type="checkbox" name="options[]" value="privateDisabled" ' . ($list['options'] & \Fim\CensorList::CENSORLIST_PRIVATE_DISABLED ? ' checked="checked"' : '') . ' />
                        Disabled in Private Rooms
                    </label><br /><br />
                    
                    <button class="btn btn-success" type="submit">Submit</button>
                    <button class="btn btn-secondary" type="reset">Reset</button>
                </form>');
                break;



            case 'editList2':
                $request = array_merge($request, fim_sanitizeGPC('p', [
                    'options' => [
                        'cast'      => 'list',
                        'transform' => 'bitfield',
                        'bitTable'  =>  [
                            'enabled' => \Fim\CensorList::CENSORLIST_ENABLED,
                            'disableable' => \Fim\CensorList::CENSORLIST_DISABLEABLE,
                            'hidden' => \Fim\CensorList::CENSORLIST_HIDDEN,
                            'privateDisabled' => \Fim\CensorList::CENSORLIST_PRIVATE_DISABLED
                        ]
                    ],
                ]));

                if ($request['listId']) {
                    $list = \Fim\Database::instance()->getCensorList($request['listId']);
                    $newList = array(
                        'listName' => $request['listName'],
                        'listType' => $request['listType'],
                        'options' => $request['options'],
                    );

                    \Fim\Database::instance()->update(\Fim\Database::$sqlPrefix . "censorLists", $newList, array(
                        'listId' => $request['listId'],
                    ));

                    \Fim\Database::instance()->modLog('changeCensorList', $list['listId']);
                    \Fim\Database::instance()->fullLog('changeCensorList', array('list' => $list, 'newList' => $newList));

                    echo container('List "' . $list['listName'] . '" Updated', 'The list has been updated.<br /><br /><form action="index.php?do=censor&do2=viewLists" method="POST"><button type="submit" class="btn btn-success">Return to Viewing Lists</button></form>');
                }
                else {
                    $list = array(
                        'listName' => $request['listName'],
                        'listType' => $request['listType'],
                        'options' => $request['options'],
                    );

                    \Fim\Database::instance()->insert(\Fim\Database::$sqlPrefix . "censorLists", $list);
                    $list['listId'] = \Fim\Database::instance()->getLastInsertId();

                    \Fim\Database::instance()->modLog('addCensorList', $list['listId']);
                    \Fim\Database::instance()->fullLog('addCensorList', array('list' => $list));

                    echo container('List "' . $list['listName'] . '" Added', 'The list has been added.<br /><br /><a class="btn btn-success" href="index.php?do=censor&do2=viewLists">Return to Viewing Lists</a>');
                }
                break;



            case 'deleteList':
                $list = \Fim\Database::instance()->getCensorList($request['listid']);
                $words = \Fim\Database::instance()->getCensorWords(array('listIds' => array($request['listIds'])))->getAsArray(true);

                \Fim\Database::instance()->modLog('deleteCensorList', $list['listId']);
                \Fim\Database::instance()->fullLog('deleteCensorList', array('list' => $list, 'words' => $words));

                \Fim\Database::instance()->delete(\Fim\Database::$sqlPrefix . "censorLists", array(
                    'listId' => $request['listId'],
                ));
                \Fim\Database::instance()->delete(\Fim\Database::$sqlPrefix . "censorWords", array(
                    'listId' => $request['listId'],
                ));

                echo container('List Deleted','The list and its words have been deleted.<br /><br /><a class="btn btn-success" href="index.php?do=censor&do2=viewLists">Return to Viewing Words</form>');
                break;



            case 'viewWords':
                $words = \Fim\Database::instance()->getCensorWords(array('listIds' => array($request['listId'])))->getAsArray(true);

                if (count($words) > 0) {
                    foreach ($words AS $word) {
                        $rows .= '    <tr>
                            <td>' . $word['word'] . '</td>
                            <td>' . $word['severity'] . '</td>
                            <td>' . $word['param'] . '</td>
                            <td>
                                <a class="btn btn-danger" href="./index.php?do=censor&do2=deleteWord&wordId=' . $word['wordId'] . '"><i class="fas fa-trash"></i> Delete</a>
                                <a class="btn btn-secondary" href="./index.php?do=censor&do2=editWord&wordId=' . $word['wordId'] . '"><i class="fas fa-edit"></i> Edit</a>    
                            </td>
                        </tr>';
                    }
                }
                else {
                    $rows = '<tr><td colspan="4">No words have been added.</td></tr>';
                }

                echo container('Current Words <a class="btn btn-success float-right" href="./index.php?do=censor&do2=editWord&listId=' . $request['listId'] . '"><i class="fas fa-plus-square"></i> Add New Word</a>','<table class="table table-bordered table-striped table-align-middle">
                    <thead class="thead">
                        <tr>
                            <th>Word</th>
                            <th>Type</th>
                            <th>Param</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                  ' . $rows . '
                    </tbody>
                </table>');
                break;



            case 'editWord':
                if ($request['wordId']) { // We are editing a word.
                    $word = \Fim\Database::instance()->getCensorWord($request['wordId']);
                    $list = \Fim\Database::instance()->getCensorList($word['listId']);

                    if (!$word) die('Invalid Word');
                    if (!$list) die('Invalid List');

                    $title = 'Edit Censor Word "' . $word['word'] . '"';
                }
                elseif ($request['listId']) { // We are adding a word to a list.
                    $list = \Fim\Database::instance()->getCensorList($request['listId']);

                    if (!$list) die('Invalid List');

                    $word = array(
                        'word' => '',
                        'wordId' => 0,
                        'severity' => 'replace',
                        'replacement' => '',
                    );

                    $title = 'Add Censor Word to "' . $list['listName'] . '"';
                }
                else {
                    die('Invalid params specified.');
                }

                $selectBlock = fimHtml_buildSelect('severity', array(
                    'replace' => 'replace',
                    'warn' => 'warn',
                    'confirm' => 'confirm',
                    'block' => 'block',
                ), $word['severity']);

                echo container($title, '<form action="./index.php?do=censor&do2=editWord2" method="post">
                    <label class="input-group">
                        <span class="input-group-addon">Text</span>
                        <input class="form-control" type="text" name="word" value="' . $word['word'] . '" required />
                    </label>
                    <small class="form-text text-muted">This is the word to be filtered or blocked out.</small><br />
                    
                    <label class="input-group">
                        <span class="input-group-addon">Severity</span>
                        ' . $selectBlock . '
                    </label>
                    <small class="form-text text-muted">This is the type of filter to apply to the word. <tt>replace</tt> will replace the word above with the one below; <tt>warn</tt> warns the user upon sending the message, but sends the message without user intervention; <tt>confirm</tt> requires the user to confirm that they wish to send the message before it is sent; <tt>block</tt> outright blocks a user from sending the message - they will need to change the content of it first.</small><br />
                    
                    <label class="input-group">
                        <span class="input-group-addon">Param</span>
                        <input class="form-control" type="text" name="param" value="' . $word['param'] . '"  />
                    </label>
                    <small class="form-text text-muted">This is what the text will be replaced with if using the <tt>replace</tt> severity, while for <tt>warn</tt>, <tt>confirm</tt>, and <tt>block</tt> it is the message that will be displayed to the user.</small><br />
                    
                    <input type="hidden" name="wordId" value="' . $word['wordId'] . '" />
                    <input type="hidden" name="listId" value="' . $list['listId'] . '" />
                    <button type="submit" class="btn btn-success">Submit</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </form>');
                break;



            case 'editWord2':
                if ($request['wordId']) { // We are editing a word.
                    $word = \Fim\Database::instance()->getCensorWord($request['wordId']);
                    $list = \Fim\Database::instance()->getCensorList($word['listId']);

                    \Fim\Database::instance()->modLog('editCensorWord', $request['wordId']);
                    \Fim\Database::instance()->fullLog('editCensorWord', array('word' => $word, 'list' => $list));

                    \Fim\Database::instance()->update(\Fim\Database::$sqlPrefix . "censorWords", array(
                        'word' => $request['word'],
                        'severity' => $request['severity'],
                        'param' => $request['param'],
                    ), array(
                                          'wordId' => $request['wordId']
                                      ));

                    echo container('Censor Word "' . $word['word'] . '" Changed', 'The word has been changed.<br /><br /><a class="btn btn-success" href="./index.php?do=censor&do2=viewWords&listId=' . $word['listId'] . '">Return to Viewing Words</a>');
                }
                elseif ($request['listId']) { // We are adding a word to a list.
                    $list = \Fim\Database::instance()->getCensorList($request['listId']);
                    $word = array(
                        'word' => $request['word'],
                        'severity' => $request['severity'],
                        'param' => $request['param'],
                        'listId' => $request['listId'],
                    );

                    \Fim\Database::instance()->insert(\Fim\Database::$sqlPrefix . "censorWords", $word);
                    $word['wordId'] = \Fim\Database::instance()->getLastInsertId();

                    \Fim\Database::instance()->modLog('addCensorWord', $request['listId'] . ',' . \Fim\Database::instance()->getLastInsertId());
                    \Fim\Database::instance()->fullLog('addCensorWord', array('word' => $word, 'list' => $list));

                    echo container('Censor Word Added To "' . $list['listName'] . '"', 'The word has been changed.<br /><br /><a class="btn btn-success" href="./index.php?do=censor&do2=viewWords&listId=' . $word['listId'] . '">Return to Viewing Words</a>');
                }
                else {
                    die('Invalid params specified.');
                }
                break;



            case 'deleteWord':
                $word = \Fim\Database::instance()->getCensorWord($request['wordId']);
                $list = \Fim\Database::instance()->getCensorList($word['listId']);

                \Fim\Database::instance()->delete(\Fim\Database::$sqlPrefix . "censorWords", array(
                    'wordId' => $request['wordId'],
                ));

                \Fim\Database::instance()->modLog('deleteCensorWord', $request['wordId']);
                \Fim\Database::instance()->fullLog('deleteCensorWord', array('word' => $word, 'list' => $list));

                echo container('Word Deleted','The word has been removed.<br /><br /><a class="btn btn-success" href="index.php?do=censor&do2=viewWords&listId=' . $word['listId'] . '">Return to Viewing Words</a>');
                break;
        }
    }
    else {
        echo 'You do not have permission to modify the censor.';
    }
}
?>