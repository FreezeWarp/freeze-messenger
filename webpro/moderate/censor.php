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

      'param' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'listType' => array(
        'valid' => array('black', 'white'),
        'default' => 'white',
      ),
    ),
  ));

  if ($user['adminDefs']['modCensor']) {
    switch($_GET['do2']) {

      case false:
      case 'viewLists':
      $lists = dbRows("SELECT * FROM {$sqlPrefix}censorLists WHERE options & 1",'id');

      foreach ($lists AS $list) {
        $options = array();

        if ($list['options'] & 2) $options[] = "Dissabable";
        if ($list['options'] & 4) $options[] = "Disabled in Private";
        if ($list['options'] & 8) $options[] = "Mature";

        $rows .= '    <tr><td>' . $list['name'] . '</td><td align="center">' . ($list['type'] == 'white' ? '<div style="border-radius: 1em; background-color: white; border: 1px solid black; width: 20px; height: 20px;"></div>' : '<div style="border-radius: 1em; background-color: black; border: 1px solid white; width: 20px; height: 20px;"></div>') . '</td><td>' . implode(', ',$options) . '</td><td><a href="./moderate.php?do=censor&do2=deleteList&listId=' . $list['id'] . '"><span class="ui-icon ui-icon-trash"></span></a><a href="./moderate.php?do=censor&do2=editList&listId=' . $list['id'] . '"><span class="ui-icon ui-icon-gear"></span></a><a href="./moderate.php?do=censor&do2=viewWords&listId=' . $list['id'] . '"><span class="ui-icon ui-icon-document"></span></a></td></tr>
  ';
      }

      echo container('Current Lists<a href="./moderate.php?do=censor&do2=addList"><span class="ui-icon ui-icon-plusthick" style="float: right;" ></span></a>', '<table class="page rowHover" border="1">
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

      case 'addList':
      echo container('Create New Censor List','<form action="./moderate.php?do=censor&do2=addList2" method="post">
  <table>
    <tr>
      <td>Name:</td>
      <td><input type="text" name="name" /><td>
    </tr>
    <tr>
      <td>Type:</td>
      <td>
        <select name="type">
          <option value="white">whitelist</option>
          <option value="black">blacklist</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>Can be Dissabled:</td>
      <td><input type="checkbox" name="candis" value="true" /></td>
    </tr>
    <tr>
      <td>Dissabled in Private Rooms:</td>
      <td><input type="checkbox" name="privdis" value="true" /></td>
    </tr>
    <tr>
      <td>Mature:</td>
      <td><input type="checkbox" name="mature" value="true" /><td>
    </tr>
  </table>

  <button type="submit">Submit</button><button type="reset">Reset</button>
</form>');
      break;

      case 'addList2':
      $options = array('white','black');

      $listname = dbEscape($_POST['name']);
      $listtype = (in_array($_POST['name'],$options) ? $_POST['name'] : 'white');
      $listoptions = 1 + ($_POST['candis'] ? 2 : 0) + ($_POST['privdis'] ? 4 : 0) + ($_POST['mature'] ? 8 : 0);

      dbQuery("INSERT INTO {$sqlPrefix}censorLists (name, type, options) VALUES ('$listname', '$listtype', '$listoptions')");

      echo container('List Added','The list has been added.<br /><br /><form action="Return to Viewing Lists" method="POST"><button type="submit">Return to Viewing Lists</button></form>');
      break;

      case 'editList':
      $listId = intval($_GET['listId']);
      $list = dbRows("SELECT * FROM {$sqlPrefix}censorLists WHERE id = $listId");

      echo container('Edit Censor List','<form action="./moderate.php?do=censor&do2=editList2&listId=' . $list['id'] . '" method="post">
  <table>
    <tr>
      <td>Name:</td>
      <td><input type="text" name="name" value="' . $list['name'] . '" /><td>
    </tr>
    <tr>
      <td>Type:</td>
      <td>
        <select name="type">
          <option value="white" ' . ($list['type'] == 'white' ? ' selected="selected"' : '') . '>whitelist</option>
          <option value="black" ' . ($list['type'] == 'black' ? ' selected="selected"' : '') . '>blacklist</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>Can be Dissabled:</td>
      <td><input type="checkbox" name="candis" value="true" ' . ($list['options'] & 2 ? ' checked="checked"' : '') . ' /></td>
    </tr>
    <tr>
      <td>Dissabled in Private Rooms:</td>
      <td><input type="checkbox" name="privdis" value="true" ' . ($list['options'] & 4 ? ' checked="checked"' : '') . ' /></td>
    </tr>
    <tr>
      <td>Mature:</td>
      <td><input type="checkbox" name="mature" value="true" ' . ($list['options'] & 8 ? ' checked="checked"' : '') . ' /><td>
    </tr>
  </table>

  <button type="submit">Submit</button><button type="reset">Reset</button>
</form>');
      break;

      case 'editList2':
      $options = array('white','black');

      $listId = intval($_GET['listId']);
      $listname = dbEscape($_POST['name']);
      $listtype = (in_array($_POST['name'],$options) ? $_POST['name'] : 'white');
      $listoptions = 1 + ($_POST['candis'] ? 2 : 0) + ($_POST['privdis'] ? 4 : 0) + ($_POST['mature'] ? 8 : 0);

      dbQuery("UPDATE {$sqlPrefix}censorLists SET name = '$listname', type = '$listtype', options = '$listoptions' WHERE id = $listId");

      echo container('List Updated','The list has been updated.<br /><br /><form action="Return to Viewing Lists" method="POST"><button type="submit">Return to Viewing Lists</button></form>');
      break;

      case 'deleteList':
      $database->modLog('deleteCensorList', $request['listId']);

      $database->delete("{$sqlPrefix}censorLists", array(
        'listId' => $request['listId'],
      ));
      $database->delete("{$sqlPrefix}censorWords", array(
        'listId' => $request['listId'],
      ));

      echo container('Word Deleted','The list and its words have been deleted.<br /><br /><form method="post" action="moderate.php?do=censor&do2=viewLists"><button type="submit">Return to Viewing Words</button></form>');
      break;

      case 'viewWords':
      $listId = intval($_GET['listId']);
      $words = dbRows("SELECT * FROM {$sqlPrefix}censorWords WHERE listId = $listId",'id');
      if ($words) {
        foreach ($words AS $word) {
          $rows .= '    <tr><td>' . $word['word'] . '</td><td>' . $word['severity'] . '</td><td>' . $word['param'] . '</td><td><a href="./moderate.php?do=censor&do2=deleteWord&wordid=' . $word['id'] . '"><span class="ui-icon ui-icon-trash"></span></a><a href="./moderate.php?do=censor&do2=editWord&wordid=' . $word['id'] . '"><span class="ui-icon ui-icon-gear"></span></a></td></tr>
  ';
        }
      }
      else {
        $rows = '<tr><td colspan="4">No words have been added.</td></tr>';
      }

      echo container('Current Words<a href="./moderate.php?do=censor&do2=addWord&listId=' . $listId . '"><span class="ui-icon ui-icon-plusthick" style="float: right;" ></span></a>','<table class="page rowHover" border="1">
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

      echo container($title, '<form action="./moderate.php?do=bbcode&do2=edit2" method="post">
  <table>
    <tr>
      <td>Name:</td>
      <td><input type="text" name="bbcodeName" value="' . $bbcode['bbcodeName'] . '" /><td>
    </tr>
    <tr>
      <td>Search Regex:</td>
      <td>
        <input type="text" name="searchRegex" value="' . $bbcode['searchRegex'] . '" /><br />
        <small>Tips: Use standard PHP PECL-based <a href="http://php.net/manual/en/function.preg-replace.php">Regular Expressions</a>. Both the opening and closing "/" must be included, as well as any flags.<br />Example: <tt>/\_([a-zA-Z]+)\_/s</small>
      </td>
    </tr>
    <tr>
      <td>Replacement:</td>
      <td>
        <input type="text" name="replacement" value="' . $bbcode['replacement'] . '" /><br />
        <small>Tips: "$1" and "\1" can be used here like with standard regular expressions. The /e flag is also possible, if adventurous.</small>
      </td>
    </tr>
  </table>

  <button type="submit">Submit</button>
  <button type="reset">Reset</button>
  <input type="hidden" name="bbcodeId" value="' . $bbcode['bbcodeId'] . '" />
</form>');
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
  <table>
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
        $database->fullLog('addCensorWord', array('word' => $word, array($word, 'list' => $list));

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