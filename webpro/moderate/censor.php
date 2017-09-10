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

        'options' => array(
            'cast' => 'int',
        ),

        'listName' => array(
            'cast' => 'string',
        ),

        'listType' => array(
            'valid' => array('black', 'white'),
            'default' => 'white',
        ),

        'candis' => array(
            'cast' => 'bool',
        ),

        'privdis' => array(
            'cast' => 'bool',
        ),

        'mature' => array(
            'cast' => 'bool',
        ),
    ));

    if ($user->hasPriv('modCensor')) {
        switch($_GET['do2']) {

            case false:
            case 'viewLists':
                $lists = $database->getCensorLists()->getAsArray(true);

                foreach ($lists AS $list) {
                    $options = array();

                    if (!$list['options'] & 1)   $options[] = "Inactive";
                    if ($list['options'] & 2)    $options[] = "Disableable";
                    if ($list['options'] & 4)    $options[] = "Hidden";
                    if ($list['options'] & 256)  $options[] = "Disabled in Private";
//        if ($list['options'] & 8) $options[] = "Mature";

                    $rows .= '    <tr><td>' . $list['listName'] . '</td><td>' . ($list['listType'] == 'white' ? '<div style="border-radius: 1em; background-color: white; border: 1px solid black; width: 20px; height: 20px;"></div>' : '<div style="border-radius: 1em; background-color: black; border: 1px solid white; width: 20px; height: 20px;"></div>') . '</td><td>' . implode(', ',$options) . '</td><td><a href="./moderate.php?do=censor&do2=deleteList&listId=' . $list['listId'] . '"><img src="./images/document-close.png" /></a><a href="./moderate.php?do=censor&do2=editList&listId=' . $list['listId'] . '"><img src="./images/document-edit.png" /></a><a href="./moderate.php?do=censor&do2=viewWords&listId=' . $list['listId'] . '"><img src="./images/view-list-details.png" /></a></td></tr>
  ';
                }

                echo container('Current Lists<a href="./moderate.php?do=censor&do2=editList"><img src="./images/document-new.png" style="float: right;" /></a>', '<table class="page rowHover">
  <thead>
    <tr class="ui-widget-header">
      <td>List Name</td>
      <td>Type</td>
      <td>Notes</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
                break;

            case 'editList':
                if ($request['listId']) {
                    $list = $database->getCensorList($request['listId']);

                    $title = 'Edit Censor List "' . $list['listName'] . '"';
                }
                else {
                    $list = array(
                        'listId' => 0,
                        'listName' => '',
                        'listType' => 'white',
                        'options' => 0,
                    );

                    $title = 'Add New Censor List';
                }

                echo container($title, '<form action="./moderate.php?do=censor&do2=editList2&listId=' . $list['listId'] . '" method="post">
  <table class="page ui-widget">
    <tr>

      <td width="30%">Name:</td>
      <td width="70%"><input type="text" name="listName" value="' . $list['listName'] . '" /></td>
    </tr>
    <tr>
      <td>Type:</td>
      <td>
        ' . fimHtml_buildSelect('listType', array(
                                         'black' => 'black',
                                         'white' => 'white',
                                     ), $list['listType']) . '
      </td>
    </tr>
    <tr>
      <td>Inactive:</td>
      <td><input type="checkbox" name="inactive" value="true" ' . (!$list['options'] & 1 ? ' checked="checked"' : '') . ' /></td>
    </tr>
    <tr>
      <td>Can be Disabled:</td>
      <td><input type="checkbox" name="candis" value="true" ' . ($list['options'] & 2 ? ' checked="checked"' : '') . ' /></td>
    </tr>
    <tr>
      <td>Hidden:</td>
      <td><input type="checkbox" name="hidden" value="true" ' . ($list['options'] & 4 ? ' checked="checked"' : '') . ' /></td>
    </tr>
    <tr>
      <td>Disabled in Private Rooms:</td>
      <td><input type="checkbox" name="privdis" value="true" ' . ($list['options'] & 256 ? ' checked="checked"' : '') . ' /></td>
    </tr>
  </table><br />

  <button type="submit">Submit</button><button type="reset">Reset</button>
</form>');
                break;

            case 'editList2':
                $listOptions = (!$request['inactive'] ? 1 : 0) + ($request['candis'] ? 2 : 0) + ($request['hidden'] ? 4 : 0) + ($request['privdis'] ? 256 : 0);

                if ($request['listId']) {
                    $list = $database->getCensorList($request['listId']);
                    $newList = array(
                        'listName' => $request['listName'],
                        'listType' => $request['listType'],
                        'options' => $listOptions,
                    );

                    $database->update("{$sqlPrefix}censorLists", $newList, array(
                        'listId' => $request['listId'],
                    ));

                    $database->modLog('changeCensorList', $list['listId']);
                    $database->fullLog('changeCensorList', array('list' => $list, 'newList' => $newList));

                    echo container('List "' . $list['listName'] . '" Updated', 'The list has been updated.<br /><br /><form action="moderate.php?do=censor&do2=viewLists" method="POST"><button type="submit">Return to Viewing Lists</button></form>');
                }
                else {
                    $list = array(
                        'listName' => $request['listName'],
                        'listType' => $request['listType'],
                        'options' => $listOptions,
                    );

                    $database->insert("{$sqlPrefix}censorLists", $list);
                    $list['listId'] = $database->getLastInsertId();

                    $database->modLog('addCensorList', $list['listId']);
                    $database->fullLog('addCensorList', array('list' => $list));

                    echo container('List "' . $list['listName'] . '" Added', 'The list has been added.<br /><br /><form action="moderate.php?do=censor&do2=viewLists" method="POST"><button type="submit">Return to Viewing Lists</button></form>');
                }
                break;

            case 'deleteList':
                $list = $database->getCensorList($request['listid']);
                $words = $database->getCensorWords(array('listIds' => array($request['listIds'])))->getAsArray(true);

                $database->modLog('deleteCensorList', $list['listId']);
                $database->fullLog('deleteCensorList', array('list' => $list, 'words' => $words));

                $database->delete("{$sqlPrefix}censorLists", array(
                    'listId' => $request['listId'],
                ));
                $database->delete("{$sqlPrefix}censorWords", array(
                    'listId' => $request['listId'],
                ));

                echo container('List Deleted','The list and its words have been deleted.<br /><br /><form method="post" action="moderate.php?do=censor&do2=viewLists"><button type="submit">Return to Viewing Words</button></form>');
                break;

            case 'viewWords':
                $words = $database->getCensorWords(array('listIds' => array($request['listId'])))->getAsArray(true);

                if (count($words) > 0) {
                    foreach ($words AS $word) {
                        $rows .= '    <tr><td>' . $word['word'] . '</td><td>' . $word['severity'] . '</td><td>' . $word['param'] . '</td><td><a href="./moderate.php?do=censor&do2=deleteWord&wordId=' . $word['wordId'] . '"><img src="./images/document-close.png" /></a><a href="./moderate.php?do=censor&do2=editWord&wordId=' . $word['wordId'] . '"><img src="./images/document-edit.png" /></a></td></tr>
    ';
                    }
                }
                else {
                    $rows = '<tr><td colspan="4">No words have been added.</td></tr>';
                }

                echo container('Current Words<a href="./moderate.php?do=censor&do2=editWord&listId=' . $request['listId'] . '"><img src="./images/document-new.png" style="float: right;" /></a>','<table class="page rowHover">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>Word</td>
      <td>Type</td>
      <td>Param</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
                break;

            case 'editWord':
                if ($request['wordId']) { // We are editing a word.
                    $word = $database->getCensorWord($request['wordId']);
                    $list = $database->getCensorList($word['listId']);

                    if (!$word) die('Invalid Word');
                    if (!$list) die('Invalid List');

                    $title = 'Edit Censor Word "' . $word['word'] . '"';
                }
                elseif ($request['listId']) { // We are adding a word to a list.
                    $list = $database->getCensorList($request['listId']);

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

                echo container($title, '<form action="./moderate.php?do=censor&do2=editWord2" method="post">
  <table class="page ui-widget">
    <tr>
      <td>Text</td>
      <td>
        <input type="text" name="word" value="' . $word['word'] . '" /><br />
        <small>This is the word to be filtered or blocked out.</small>
      </td>
    </tr>
      <td>Severity:
      <td>
        ' . $selectBlock . '<br />
        <small>This is the type of filter to apply to the word. <tt>replace</tt> will replace the word above with the one below; <tt>warn</tt> warns the user upon sending the message, but sends the message without user intervention; <tt>confirm</tt> requires the user to confirm that they wish to send the message before it is sent; <tt>block</tt> outright blocks a user from sending the message - they will need to change the content of it first.</small>
      </td>
    </tr>
    <tr>
      <td>Param:</td>
      <td>
        <input type="text" name="param" value="' . $word['param'] . '"  /><br />
        <small>This is what the text will be replaced with if using the <tt>replace</tt> severity, while for <tt>warn</tt>, <tt>confirm</tt>, and <tt>block</tt> it is the message that will be displayed to the user.</small>
      </td>
    </tr>
  </table><br /><br />

  <input type="hidden" name="wordId" value="' . $word['wordId'] . '" />
  <input type="hidden" name="listId" value="' . $list['listId'] . '" />
  <button type="submit">Submit</button>
  <button type="reset">Reset</button>
</form>');
                break;

            case 'editWord2':
                if ($request['wordId']) { // We are editing a word.
                    $word = $database->getCensorWord($request['wordId']);
                    $list = $database->getCensorList($word['listId']);

                    $database->modLog('editCensorWord', $request['wordId']);
                    $database->fullLog('editCensorWord', array('word' => $word, 'list' => $list));

                    $database->update("{$sqlPrefix}censorWords", array(
                        'word' => $request['word'],
                        'severity' => $request['severity'],
                        'param' => $request['param'],
                    ), array(
                                          'wordId' => $request['wordId']
                                      ));

                    echo container('Censor Word "' . $word['word'] . '" Changed', 'The word has been changed.<br /><br /><form method="post" action="./moderate.php?do=censor&do2=viewWords&listId=' . $word['listId'] . '"><button>Return to Viewing Words</button></form>');
                }
                elseif ($request['listId']) { // We are adding a word to a list.
                    $list = $database->getCensorList($request['listId']);
                    $word = array(
                        'word' => $request['word'],
                        'severity' => $request['severity'],
                        'param' => $request['param'],
                        'listId' => $request['listId'],
                    );

                    $database->insert("{$sqlPrefix}censorWords", $word);
                    $word['wordId'] = $database->getLastInsertId();

                    $database->modLog('addCensorWord', $request['listId'] . ',' . $database->getLastInsertId());
                    $database->fullLog('addCensorWord', array('word' => $word, 'list' => $list));

                    echo container('Censor Word Added To "' . $list['listName'] . '"', 'The word has been changed.<br /><br /><form method="post" action="./moderate.php?do=censor&do2=viewWords&listId=' . $word['listId'] . '"><button>Return to Viewing Words</button></form>');
                }
                else {
                    die('Invalid params specified.');
                }
                break;

            case 'deleteWord':
                $word = $database->getCensorWord($request['wordId']);
                $list = $database->getCensorList($word['listId']);

                $database->delete("{$sqlPrefix}censorWords", array(
                    'wordId' => $request['wordId'],
                ));

                $database->modLog('deleteCensorWord', $request['wordId']);
                $database->fullLog('deleteCensorWord', array('word' => $word, 'list' => $list));

                echo container('Word Deleted','The word has been removed.<br /><br /><form method="post" action="moderate.php?do=censor&do2=viewWords&listId=' . $word['listId'] . '"><button type="submit">Return to Viewing Words</button></form>');
                break;
        }
    }
    else {
        echo 'You do not have permission to modify the censor.';
    }
}
?>