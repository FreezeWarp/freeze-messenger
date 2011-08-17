<?php
if (!defined('WEBPRO_INMOD')) {
  die();
}
else {


  $request = fim_sanitizeGPC(array(
    'request' => array(
      'listId' => array(
        'context' => array(
          'type' => 'int',
        ),
      ),

      'wordId' => array(
        'context' => array(
          'type' => 'int',
        ),
      ),
    ),

    'post' => array(
      'word' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'param' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'severity' => array(
        'valid' => array('replace', 'warn', 'confirm', 'block'),
        'default' => 'replace',
      ),

      'options' => array(
        'context' => array(
          'type' => 'int',
        ),
      ),

      'listName' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'listType' => array(
        'valid' => array('black', 'white'),
        'default' => 'white',
      ),

      'candis' => array(
        'context' => array(
          'type' => 'bool',
        ),
      ),

      'privdis' => array(
        'context' => array(
          'type' => 'bool',
        ),
      ),

      'mature' => array(
        'context' => array(
          'type' => 'bool',
        ),
      ),
    ),
  ));

  if ($user['adminDefs']['modCensor']) {
    switch($_GET['do2']) {

      case false:
      case 'viewLists':
      $lists = $database->select(array(
        "{$sqlPrefix}censorLists" => "listId, listName, listType, options",
      ), array(
        'both' => array(
          array(
            'type' => 'and',
            'left' => array(
              'type' => 'column',
              'value' => 'options',
            ),
            'right' => array(
              'type' => 'int',
              'value' => 1,
            ),
          ),
        ),
      ));
      $lists = $lists->getAsArray(true);


      foreach ($lists AS $list) {
        $options = array();

        if ($list['options'] & 2) $options[] = "Disableable";
        if ($list['options'] & 4) $options[] = "Disabled in Private";
        if ($list['options'] & 8) $options[] = "Mature";

        $rows .= '    <tr><td>' . $list['listName'] . '</td><td align="center">' . ($list['listType'] == 'white' ? '<div style="border-radius: 1em; background-color: white; border: 1px solid black; width: 20px; height: 20px;"></div>' : '<div style="border-radius: 1em; background-color: black; border: 1px solid white; width: 20px; height: 20px;"></div>') . '</td><td>' . implode(', ',$options) . '</td><td><a href="./moderate.php?do=censor&do2=deleteList&listId=' . $list['listId'] . '"><span class="ui-icon ui-icon-trash"></span></a><a href="./moderate.php?do=censor&do2=editList&listId=' . $list['listId'] . '"><span class="ui-icon ui-icon-gear"></span></a><a href="./moderate.php?do=censor&do2=viewWords&listId=' . $list['listId'] . '"><span class="ui-icon ui-icon-document"></span></a></td></tr>
  ';
      }

      echo container('Current Lists<a href="./moderate.php?do=censor&do2=editList"><span class="ui-icon ui-icon-plusthick" style="float: right;" ></span></a>', '<table class="page rowHover" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
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

      $selectBlock = fimHtml_buildSelect('listType', array(
        'black' => 'black',
        'white' => 'white',
      ), $list['listType']);

      echo container($title, '<form action="./moderate.php?do=censor&do2=editList2&listId=' . $list['listId'] . '" method="post">
  <table class="page ui-widget" border="1">
    <tr>
      <td width="30%">Name:</td>
      <td width="70%"><input type="text" name="listName" value="' . $list['listName'] . '" /></td>
    </tr>
    <tr>
      <td>Type:</td>
      <td>
        ' . $selectBlock . '
      </td>
    </tr>
    <tr>
      <td>Can be Dissabled:</td>
      <td><input type="checkbox" name="candis" value="true" ' . ($list['options'] & 2 ? ' checked="checked"' : '') . ' /></td>
    </tr>
    <tr>
      <td>Dissabled in Private Rooms:</td>
      <td><input type="checkbox" name="privdis" value="true" ' . ($list['options'] & 4 ? ' checked="checked"' : '') . ' /></td>
    </tr><!--
    <tr>
      <td>Mature:</td>
      <td><input type="checkbox" name="mature" value="true" ' . ($list['options'] & 8 ? ' checked="checked"' : '') . ' /></td>
    </tr>-->
  </table>

  <button type="submit">Submit</button><button type="reset">Reset</button>
</form>');
      break;

      case 'editList2':
      $listOptions = 1 + ($request['candis'] ? 2 : 0) + ($request['privdis'] ? 4 : 0) + ($request['mature'] ? 8 : 0);

      if ($request['listId']) {
        $list = $database->getCensorList($request['listId']);

        $database->update("{$sqlPrefix}censorLists", array(
          'listName' => $request['listName'],
          'listType' => $request['listType'],
          'options' => $listOptions,
        ), array(
          'listId' => $request['listId'],
        ));

        $database->modLog('addCensorList', $list['listId']);
        $database->fullLog('addCensorList', array('list' => $list));

        echo container('List "' . $list['listName'] . '" Updated','The list has been updated.<br /><br /><form action="moderate.php?do=censor&do2=viewLists" method="POST"><button type="submit">Return to Viewing Lists</button></form>');
      }
      else {
        $list = array(
          'listName' => $request['listName'],
          'listType' => $request['listType'],
          'options' => $listOptions,
        );

        $database->insert("{$sqlPrefix}censorLists", $list);
        $list['listId'] = $database->insertId;

        $database->modLog('addCensorList', $list['listId']);
        $database->fullLog('addCensorList', array('list' => $list));

        echo container('List "' . $list['listName'] . '" Added','The list has been added.<br /><br /><form action="moderate.php?do=censor&do2=viewLists" method="POST"><button type="submit">Return to Viewing Lists</button></form>');
      }
      break;

      case 'deleteList':
      $list = $database->getCensorList($requst['listid']);

      $words = $database->select(array(
        "{$sqlPrefix}censorWords" => "wordId, listId, word, severity, param",
      ), array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'listId',
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $list['listId'],
            ),
          ),
        ),
      ));
      $words = $words->getAsArray(true);

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
      $words = $database->select(array(
        "{$sqlPrefix}censorWords" => "wordId, listId, word, severity, param",
      ), array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'listId',
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $request['listId'],
            ),
          ),
        ),
      ));
      $words = $words->getAsArray(true);


      if ($words) {
        foreach ($words AS $word) {
          $rows .= '    <tr><td>' . $word['word'] . '</td><td>' . $word['severity'] . '</td><td>' . $word['param'] . '</td><td><a href="./moderate.php?do=censor&do2=deleteWord&wordid=' . $word['wordId'] . '"><span class="ui-icon ui-icon-trash"></span></a><a href="./moderate.php?do=censor&do2=editWord&wordid=' . $word['wordId'] . '"><span class="ui-icon ui-icon-gear"></span></a></td></tr>
    ';
        }
      }
      else {
        $rows = '<tr><td colspan="4">No words have been added.</td></tr>';
      }

      echo container('Current Words<a href="./moderate.php?do=censor&do2=editWord&listId=' . $request['listId'] . '"><span class="ui-icon ui-icon-plusthick" style="float: right;" ></span></a>','<table class="page rowHover" border="1">
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

        $title = 'Edit Censor Word "' . $word['word'] . '"';
      }
      elseif ($request['listId']) { // We are adding a word to a list.
        $list = $database->getCensorList($request['listId']);

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
  <table class="page ui-widget" border="1">
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
  </table><br />

  <input type="hidden" name="wordId" value="' . $word['id'] . '" />
  <input type="hidden" name="listId" value="' . $list['id'] . '" />
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

        echo container('Censor Word "' . $word['word'] . '" Changed', 'The word has been changed.<br /><br />' . button('Return to Viewing Words','./moderate.php?do=censor&do2=viewWords&listId=' . $word['listId']));
      }
      elseif ($request['listId']) { // We are adding a word to a list.
        $list = $database->getCensorList($request['listId']);
        $word = array(
          'word' => $request['word'],
          'severity' => $request['severity'],
          'param' => $request['param'],
        );

        $database->insert("{$sqlPrefix}censorWords", $word);
        $word['wordId'] = $database->insertId;

        $database->modLog('addCensorWord', $request['listId'] . ',' . $database->insertId);
        $database->fullLog('addCensorWord', array('word' => $word, 'list' => $list));

        echo container('Censor Word Added To "' . $list['listName'] . '"', 'The word has been changed.<br /><br />' . button('Return to Viewing Words','./moderate.php?do=censor&do2=viewWords&listId=' . $word['listId']));
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

      echo container('Word Deleted','The word has been removed.<br /><br /><form method="post" action="moderate.php?do=censor&do2=viewWords"><button type="submit">Return to Viewing Words</button></form>');
      break;
    }
  }
  else {
    echo 'You do not have permission to modify the censor.';
  }
}
?>