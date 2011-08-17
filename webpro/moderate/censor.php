<?php
if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
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

      echo container('Current Lists<a href="./moderate.php?do=censor&do2=addList"><span class="ui-icon ui-icon-plusthick" style="float: right;" ></span></a>','<table class="page rowHover" border="1">
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
      $listId = intval($_GET['listId']);

      $database->modLog('deleteCensorList',$listId);

      dbQuery("DELETE FROM {$sqlPrefix}censorLists WHERE id = $listId");
      dbQuery("DELETE FROM {$sqlPrefix}censorWords WHERE listId = $listId");

      echo container('List Deleted','The list and its words have been deleted.<br /><br /><form action="Return to Viewing Lists" method="POST"><button type="submit">Return to Viewing Lists</button></form>');
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

      case 'addWord':
      $listId = intval($_GET['listId']);

      echo container('Add New Word','<form action="./moderate.php?do=censor&do2=addWord2" method="post">
<table>
  <tr>
    <td>Text</td>
    <td><input type="text" name="text" /></td>
  </tr>
    <td>Severity:
    <td>
      <select name="severity">
        <option value="replace">replace</option>
        <option value="warn">warn</option>
        <option value="confirm">confirm</option>
        <option value="block">block</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>Param:</td>
    <td><input type="text" name="param" /></td>
  </tr>
</table><br />

  <input type="hidden" name="listId" value="' . $listId . '" />

  <button type="submit">Submit</button><button type="reset">Reset</button>
</form>');
      break;

      case 'addWord2':
      $options = array('replace','warn','confirm','block');

      $wordtext = dbEscape($_POST['text']);
      $wordsev = (in_array($_POST['severity'],$options) ? $_POST['severity'] : 'replace');
      $wordparam = dbEscape($_POST['param']);
      $listId = intval($_POST['listId']);

      dbInsert(array(
        'listId' => (int) $listId,
        'word' => $wordtext,
        'severity' => $wordsev,
        'param' => $wordparam,
      ),"{$sqlPrefix}censorWords");

      echo container('Word Added','The word has been added.<br /><br /><form action="./moderate.php?do=censor&do2=viewWords&listId=' . $listId . '" method="POST"><button type="submit">Return to Viewing Words</button></form>');
      break;

      case 'editWord':
      $wordid = intval($_GET['wordid']);
      $word = dbRows("SELECT * FROM {$sqlPrefix}censorWords WHERE id = $wordid");

      echo container('Edit Word "' . $word['word'] . '"','<form action="./moderate.php?do=censor&do2=editWord2" method="post">
<table>
  <tr>
    <td>Text</td>
    <td><input type="text" name="text" value="' . $word['word'] . '" /></td>
  </tr>
    <td>Severity:
    <td>
      <select name="severity">
        <option value="replace" ' . ($word['severity'] == 'replace' ? ' selected="selected"' : '') . '>replace</option>
        <option value="warn" ' . ($word['severity'] == 'warn' ? ' selected="selected"' : '') . '>warn</option>
        <option value="confirm" ' . ($word['severity'] == 'confirm' ? ' selected="selected"' : '') . '>confirm</option>
        <option value="block" ' . ($word['severity'] == 'block' ? ' selected="selected"' : '') . '>block</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>Param:</td>
    <td><input type="text" name="param" value="' . $word['param'] . '"  /></td>
  </tr>
</table><br />

  <input type="hidden" name="wordid" value="' . $word['id'] . '" />

  <button type="submit">Submit</button><button type="reset">Reset</button>
</form>');
      break;

      case 'editWord2':
      $options = array('replace','warn','confirm','block');

      $wordid = intval($_POST['wordid']);
      $word = dbRows("SELECT * FROM {$sqlPrefix}censorWords WHERE id = $wordid");

      $wordtext = dbEscape($_POST['text']);
      $wordsev = (in_array($_POST['severity'],$options) ? $_POST['severity'] : 'replace');
      $wordparam = dbEscape($_POST['param']);

      $database->modLog('editCensorWord',$wordid);

      dbQuery("UPDATE {$sqlPrefix}censorWords SET word = '$wordtext', severity = '$wordsev', param = '$wordparam' WHERE id = $wordid");

      echo container('Word Changed','The word has been changed.<br /><br />' . button('Return to Viewing Words','./moderate.php?do=censor&do2=viewWords&listId=' . $word['listId']));
      break;

      case 'deleteWord':
      $wordid = intval($_GET['wordid']);

      dbQuery("DELETE FROM {$sqlPrefix}censorWords WHERE id = $wordid");

      $database->modLog('deleteCensorWord',$wordid);

      echo container('Word Deleted','The word has been removed.<br /><br /><button onclick="window.history.back();" type="button">Go Back</button>');
      break;
    }
  }
  else {
    echo 'You do not have permission to modify the censor.';
  }
}
?>