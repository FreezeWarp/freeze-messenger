<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

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

$title = 'Moderate';
$reqPhrases = true;
$reqHooks = true;

require_once('../global.php');
require_once('templateStart.php');

eval(hook('moderateStart'));

if ($user['adminPrivs']) { // Check that the user is an admin.
  switch ($_GET['do']) {
    case 'phrases':
    if ($user['adminPrivs']['modPhrases']) {
      switch ($_GET['do2']) {
        case false:
        case 'view':
        $phrases2 = dbRows("SELECT * FROM {$sqlPrefix}phrases",'id');

        foreach ($phrases2 AS $phrase) {
          $phrase['text_en'] = nl2br(htmlentities($phrase['text_en']));

          $rows .= "<tr><td>$phrase[name]</td><td>$phrase[text_en]</td><td><a href=\"./moderate.php?do=phrases&do2=edit&phraseId=$phrase[id]&lang=en\">Edit</td></tr>";
        }

        echo container('Phrases','<table class="page rowHover" border="1">
    <thead>
      <tr class="hrow ui-widget-header">
        <td>Phrase</td>
        <td>Current Value (English)</td>
        <td>Actions</td>
      </tr>
    </thead>
    <tbody>
  ' . $rows . '
    </tbody>
  </table>');
        }
        break;

        case 'edit':
        if (in_array($_GET['lang'],array('en','es','jp'))) {
        $phraseID = intval($_GET['phraseId']);
        $lang = $_GET['lang'];

        $phrase = dbRows("SELECT * FROM {$sqlPrefix}phrases WHERE id = $phraseID");
        $phrase['text'] = $phrase['text_' . $lang];

        echo container("Edit Phrase '$phrase[name]'","

  <link rel=\"stylesheet\" href=\"./client/codemirror/lib/codemirror.css\">
  <link rel=\"stylesheet\" href=\"./client/codemirror/mode/xml/xml.css\">
  <script src=\"./client/codemirror/lib/codemirror.js\"></script>
  <script src=\"./client/codemirror/mode/xml/xml.js\"></script>


  <script>
  $(document).ready(function() {
    var editor = CodeMirror.fromTextArea(document.getElementById(\"text\"),{
      mode:  \"xml\"
    });
  });
  </script>
  <style type=\"text/css\">
  .CodeMirror {
    border: 1px solid white;
    background-color: white;
    color: black;
  }
  </style>

  <form action=\"./moderate.php?do=phrases&do2=edit2&phraseId=$phrase[id]\" method=\"post\">
    <label for=\"text\">New Value:</label><br />
    <textarea name=\"text\" id=\"text\" style=\"width: 100%; height: 300px;\">$phrase[text_en]</textarea><br /><br />

    <button type=\"submit\">Update</button>
    <input type=\"hidden\" name=\"lang\" value=\"$lang\" />
  </form>");
        }
        else {
          trigger_error('Language not found.',E_USER_ERROR);
        }
        break;

        case 'edit2':
        if (in_array($_POST['lang'],array('en','es','jp'))) {
          $phraseID = intval($_GET['phraseId']);
          $text = dbEscape($_POST['text']);

          dbQuery("UPDATE {$sqlPrefix}phrases SET text_$lang = '$text' WHERE id = $phraseID");

          modLog('phraseEdit',$phraseID);

          echo container('Updated','The phrase has been updated.<br /><br />' . button('Return','./moderate.php?do=phrases'));
        }
        else {
          trigger_error('Language not found.',E_USER_ERROR);
        }
        break;
      }
    }
    else {
      trigger_error('No permission',E_USER_ERROR);
    }
    break;

    case 'hooks':
    if ($user['adminPrivs']['modHooks']) {

      switch ($_GET['do2']) {
        case false:
        case 'view':
        $hooks2 = dbRows("SELECT * FROM {$sqlPrefix}hooks",'id');

        foreach ($hooks2 AS $hook) {
          $hook['code'] = nl2br(htmlentities($hook['code']));

          $rows .= "<tr><td>$hook[name]</td><td>$hook[code]</td><td><a href=\"./moderate.php?do=hooks&do2=edit&hookId=$hook[id]\">Edit</td></tr>";
        }

        echo container('Hooks','<table class="page rowHover" border="1">
    <thead>
      <tr class="hrow ui-widget-header">
        <td>Hook</td>
        <td>Current Value</td>
        <td>Actions</td>
      </tr>
    </thead>
    <tbody>
  ' . $rows . '
    </tbody>
  </table>');
        break;

        case 'edit':
        $hookID = intval($_GET['hookId']);

        $hook = dbRows("SELECT * FROM {$sqlPrefix}hooks WHERE id = $hookID");

        echo container("Edit Hook '$hook[name]'","

  <link rel=\"stylesheet\" href=\"./client/codemirror/lib/codemirror.css\">
  <link rel=\"stylesheet\" href=\"./client/codemirror/mode/clike/clike.css\">
  <script src=\"./client/codemirror/lib/codemirror.js\"></script>
  <script src=\"./client/codemirror/mode/clike/clike.js\"></script>


  <script>
  $(document).ready(function() {
    var editor = CodeMirror.fromTextArea(document.getElementById(\"text\"),{
      mode:  \"clike\"
    });
  });
  </script>
  <style type=\"text/css\">
  .CodeMirror {
    border: 1px solid white;
    background-color: white;
    color: black;
  }
  </style>

  <form action=\"./moderate.php?do=hooks&do2=edit2&hookId=$hook[id]\" method=\"post\">
    <label for=\"text\">New Value:</label><br />
    <textarea name=\"text\" id=\"text\" style=\"width: 100%; height: 300px;\">$hook[code]</textarea><br /><br />

    <button type=\"submit\">Update</button>
  </form>");
        break;

        case 'edit2':
        $hookID = intval($_GET['hookId']);
        $text = dbEscape($_POST['text']);

        dbQuery("UPDATE {$sqlPrefix}hooks SET code = '$text' WHERE id = $hookID");

        modLog('hookEdit',$hookID);

        echo container('Updated','The hook has been updated.<br /><br />' . button('Return','./moderate.php?do=hooks'));
        break;
      }
    }
    else {
      trigger_error('No permission.',E_USER_ERROR);
    }
    break;

    case 'templates':
    if ($user['adminPrivs']['modTemplates']) {
      switch ($_GET['do2']) {
        case false:
        case 'view':
        $templates2 = dbRows("SELECT * FROM {$sqlPrefix}templates",'id');

        foreach ($templates2 AS $template) {
          $template['code'] = nl2br(htmlentities($template['code']));

          $rows .= "<tr><td>$template[name]</td><td><a href=\"./moderate.php?do=templates&do2=edit&templateId=$template[id]\">Edit</td></tr>";
        }

        echo container('Hooks','<table class="page rowHover" border="1">
    <thead>
      <tr class="hrow ui-widget-header">
        <td>Hook</td>
        <td>Actions</td>
      </tr>
    </thead>
    <tbody>
  ' . $rows . '
    </tbody>
  </table>');
        break;

        case 'edit':
        $templateID = intval($_GET['templateId']);

        $template = dbRows("SELECT * FROM {$sqlPrefix}templates WHERE id = $templateID");

        echo container("Edit Hook '$template[name]'","
  <link rel=\"stylesheet\" href=\"./client/codemirror/lib/codemirror.css\">
  <link rel=\"stylesheet\" href=\"./client/codemirror/mode/xml/xml.css\">
  <script src=\"./client/codemirror/lib/codemirror.js\"></script>
  <script src=\"./client/codemirror/mode/xml/xml.js\"></script>


  <script>
  $(document).ready(function() {
    var editor = CodeMirror.fromTextArea(document.getElementById(\"text\"),{
      mode:  \"clike\"
    });
  });
  </script>
  <style type=\"text/css\">
  .CodeMirror {
    border: 1px solid white;
    background-color: white;
    color: black;
  }
  </style>

  <form action=\"./moderate.php?do=templates&do2=edit2&templateId=$template[id]\" method=\"post\">
    <label for=\"vars\">Vars:</label><br />
    <input type=\"text\" name=\"vars\" value=\"$template[vars]\" />

    <label for=\"text\">New Value:</label><br />
    <textarea name=\"text\" id=\"text\" style=\"width: 100%; height: 300px;\">$template[data]</textarea><br /><br />

    <button type=\"submit\">Update</button>
  </form>");
        break;

        case 'edit2':
        $templateID = intval($_GET['templateId']);
        $text = dbEscape($_POST['text']);
        $vars = dbEscape($_POST['vars']);

        dbQuery("UPDATE {$sqlPrefix}templates SET data = '$text', vars = '$vars' WHERE id = $templateID");

        modLog('templateEdit',$templateID);

        echo container('Updated','The template has been updated.<br /><br />' . button('Return','./moderate.php?do=templates'));
        break;
      }
    }
    else
      trigger_error('No permission.');
    }
    break;


    case 'censor':
    if ($user['adminPrivs']['modCensor']) {
      switch($_GET['do2']) {
        case false:
        case 'viewLists':
        $lists = dbRows("SELECT * FROM {$sqlPrefix}censorLists WHERE options & 1",'id');

        foreach ($lists AS $list) {
          $options = array();

          if ($list['options'] & 2) $options[] = "Dissabable";
          if ($list['options'] & 4) $options[] = "Disabled in Private";
          if ($list['options'] & 8) $options[] = "Mature";

          $rows .= '    <tr><td>' . $list['name'] . '</td><td align="center">' . ($list['type'] == 'white' ? '<div style="border-radius: 1em; background-color: white; border: 1px solid black; width: 20px; height: 20px;"></div>' : '<div style="border-radius: 1em; background-color: black; border: 1px solid white; width: 20px; height: 20px;"></div>') . '</td><td>' . implode(', ',$options) . '</td><td><a href="./moderate.php?do=censor&do2=deleteList&listId=' . $list['id'] . '"><img src="images/edit-delete.png" /></a><a href="./moderate.php?do=censor&do2=editList&listId=' . $list['id'] . '"><img src="images/document-edit.png" /></a><a href="./moderate.php?do=censor&do2=viewWords&listId=' . $list['id'] . '"><img src="images/view-list-text.png" alt="View Words" /></a></td></tr>
    ';
        }

        echo container('Current Lists<a href="./moderate.php?do=censor&do2=addList"><img src="images/document-new.png" style="float: right;" /></a>','<table class="page rowHover" border="1">
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

        echo container('List Added','The list has been added.<br /><br />' . button('Return to Viewing Lists','./moderate.php?do=censor&do2=viewLists'));
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

        echo container('List Updated','The list has been updated.<br /><br />' . button('Return to Viewing Lists','./moderate.php?do=censor&do2=viewLists'));
        break;

        case 'deleteList':
        $listId = intval($_GET['listId']);

        modLog('deleteCensorList',$listId);

        dbQuery("DELETE FROM {$sqlPrefix}censorLists WHERE id = $listId");
        dbQuery("DELETE FROM {$sqlPrefix}censorWords WHERE listId = $listId");

        echo container('List Deleted','The list and its words have been deleted.<br /><br />' . button('Return to Viewing Lists','./moderate.php?do=censor&do2=viewLists'));
        break;

        case 'viewWords':
        $listId = intval($_GET['listId']);
        $words = dbRows("SELECT * FROM {$sqlPrefix}censorWords WHERE listId = $listId",'id');
        if ($words) {
          foreach ($words AS $word) {
            $rows .= '    <tr><td>' . $word['word'] . '</td><td>' . $word['severity'] . '</td><td>' . $word['param'] . '</td><td><a href="./moderate.php?do=censor&do2=deleteWord&wordid=' . $word['id'] . '"><img src="images/edit-delete.png" /></a><a href="./moderate.php?do=censor&do2=editWord&wordid=' . $word['id'] . '"><img src="images/document-edit.png" /></a></td></tr>
    ';
          }
        }
        else {
          $rows = '<tr><td colspan="4">No words have been added.</td></tr>';
        }

        echo container('Current Words<a href="./moderate.php?do=censor&do2=addWord&listId=' . $listId . '"><img src="images/document-new.png" style="float: right;" /></a>','<table class="page rowHover" border="1">
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

        dbQuery("INSERT INTO {$sqlPrefix}censorWords (listId, word, severity, param) VALUES ($listId, '$wordtext', '$wordsev', '$wordparam')");

        echo container('Word Added','The word has been added.<br /><br />' . button('Return to Viewing Words','./moderate.php?do=censor&do2=viewWords&listId=' . $listId));
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

        modLog('editCensorWord',$wordid);

        dbQuery("UPDATE {$sqlPrefix}censorWords SET word = '$wordtext', severity = '$wordsev', param = '$wordparam' WHERE id = $wordid");

        echo container('Word Changed','The word has been changed.<br /><br />' . button('Return to Viewing Words','./moderate.php?do=censor&do2=viewWords&listId=' . $word['listId']));
        break;

        case 'deleteWord':
        $wordid = intval($_GET['wordid']);

        dbQuery("DELETE FROM {$sqlPrefix}censorWords WHERE id = $wordid");

        modLog('deleteCensorWord',$wordid);

        echo container('Word Deleted','The word has been removed.<br /><br /><button onclick="window.history.back();" type="button">Go Back</button>');
        break;
      }
    }
    else {
      trigger_error('No permission.');
    }
    break;

    case 'showimages':
    if ($user['adminPrivs']['modImages']) {
      $userId = intval($_GET['userId']);

      if ($userId) {
        $tableCode = dbReadThrough(dbQuery("SELECT f.id, md5hash, name, deleted FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS fv WHERE userId = $userId AND f.id = fv.fileid AND f.deleted != 1"),'<tr><td><a href="./file.php?hash=$md5hash"><img src="./file.php?hash=$md5hash" style="max-width: 200px; max-height: 200px;" /></a></td><td>$name</td><td><a href="./moderate.php?do=deleteimage&img=$id"><img src="images/edit-delete.png" /></a></td></tr>'); // This process a basic MySQL query, and returns the results as a set of table rows.

        echo container('Moderate and Delete Images','<table class="page rowHover">
    <thead>
      <tr class="hrow ui-widget-header">
        <td>Preview</td>
        <td>Name</td>
        <td>Actions</td>
      </tr>
    </thead>
    <tbody>' . $tableCode . '
    </tbody>
  </table>');
      }
      else {
        $users = dbReadThrough(dbQuery("SELECT u1.userId, userName FROM user AS u1, {$sqlPrefix}users AS u2 WHERE u2.userId = u1.userId"),'<tr><td>$userId</td><td><a href="./moderate.php?do=showimages&userId=$userId">$userName</a></td></tr>'); // This process a basic MySQL query, and returns the results as a set of table rows.
      }

      echo container('Select a User','<table class="page rowHover">
    <thead>
      <tr class="hrow ui-widget-header">
        <td>User ID</td><td>Username</td>
      </tr>
    </thead>
    <tbody>
      ' . $users . '
    </tbody>
  </table>');
    }
    break;

    case 'deleteimage':
    if ($user['adminPrivs']['modImages']) {
      $id = intval($_GET['imageId']);

      modLog('deleteImage',$id);

      dbQuery("UPDATE {$sqlPrefix}files SET deleted = 1 WHERE id = $id");

      echo container('Deleted','The image has been deleted.');
    }
    break;

    case 'undeleteimage':
    if ($user['adminPrivs']['modImages']) {
      $id = intval($_GET['imageId']);

      modLog('undeleteImage',$id);

      dbQuery("UPDATE {$sqlPrefix}files SET deleted = 0 WHERE id = $id");

      echo container('Deleted','The image has been deleted.');
    }
    break;

    case 'banuser':
    if ($user['adminPrivs']['modUsers']) {
      $userTable = dbReadThrough(dbQuery("SELECT u.userId, u.userName, u2.settings FROM user AS u, {$sqlPrefix}users AS u2 WHERE u2.userId = u.userId AND (u2.settings & 1 = false)"),'<tr><td>$userId</td><td>$userName</td><td><a href="./moderate.php?do=banuser2&userId=$userId">Ban</a></td></tr>');

      echo container('Ban a User','<table class="page rowHover">
    <thead>
      <tr class="hrow ui-widget-header">
        <td>User ID</td>
        <td>Username</td>
        <td>Actions</td>
      </tr>
    </thead>
    <tbody>' . $userTable . '
    </tbody>
  </table>');
    }
    else {
      trigger_error('No permission',E_USER_ERROR);
    }
    break;

    case 'banuser2':
    if ($user['adminPrivs']['modUsers']) {
      $userId = intval($_GET['userId']);

      modLog('banuser',$userId);

      dbQuery("UPDATE {$sqlPrefix}users SET settings = IF(settings & 1 = false,settings + 1,settings) WHERE userId = $userId");

      echo container('User Banned','The user has been banned.');
    }
    break;

    case 'unbanuser':
    if ($user['adminPrivs']['modUsers']) {
      $userTable = dbReadThrough(dbQuery("SELECT u.userId, u.userName, u2.settings FROM user AS u, {$sqlPrefix}users AS u2 WHERE u2.userId = u.userId AND u2.settings & 1"),'<tr><td>$userId</td><td>$userName</td><td><a href="./moderate.php?do=unbanuser2&userId=$userId">Unban</a></td></tr>');

      echo container('Unban a User','<table class="page rowHover">
    <thead>
      <tr class="hrow ui-widget-header">
        <td>User ID</td>
        <td>Username</td>
        <td>Actions</td>
      </tr>
    </thead>
    <tbody>' . $userTable . '
    </tbody>
  </table>');
    }
    break;

    case 'unbanuser2':
    if ($user['adminPrivs']['modImages']) {
      $userId = intval($_GET['userId']);

      modLog('unbanuser',$userId);

      dbQuery("UPDATE {$sqlPrefix}users SET settings = IF(settings & 1,settings - 1,settings) WHERE userId = $userId");

      echo container('User Unbanned','The user has been unbanned.');
    }
    break;

    case 'maintenance':
    if ($user['adminPrivs']['modMaintenance']) {
      switch ($_GET['do2']) {
        case 'disable':
        if (file_exists('.tempStop')) {
          echo container('Stop/Start VRIM','VRIM is currently stopped. Would you like to start it?:<br /><br />' . button('Enable','./moderate.php?do=maintenance&do2=disable2-enable'));
        }

        else {
          echo container('Stop/Start VRIM','VRIM is currently running. Would you like to stop it?:<br /><br />' . button('Disable','./moderate.php?do=maintenance&do2=disable2-disable'));
        }
        break;

        case 'disable2-disable':
        if (file_exists('.tempStop')) {
          echo container('Error','VRIM has already been stopped.');
        }
        else {
          modLog('disable','');

          touch('.tempStop');
          echo container('','VRIM has been stopped.');
        }
        break;

        case 'disable2-enable':
        if (file_exists('.tempStop')) {
          modLog('enable','');

          unlink('.tempStop');
          echo container('','VRIM has been re-enabled.');
        }
        else {
          echo container('Error','VRIM is already running.');
        }
        break;

        case 'postcache':
        echo container('Error','Not yet coded.');
        break;

        case 'postcountcache':
        $limit = 20;
        $offset = intval($_GET['page']) * $limit;
        $nextpage = intval($_GET['page']) + 1;

        $records = dbRows("SELECT * FROM {$sqlPrefix}ping LIMIT $limit OFFSET $offset",'id');
        foreach ($records AS $id => $record) {
          $totalPosts = dbRows("SELECT COUNT(m.id) AS count FROM {$sqlPrefix}messages AS m WHERE room = $record[roomId] AND user = $record[userId] AND m.deleted = false GROUP BY m.user");
          $totalPosts = intval($totalPosts['count']);
          dbQuery("UPDATE {$sqlPrefix}ping SET messages = $totalPosts WHERE id = $record[id]");
        }

        if ($records) {
          echo "<script type=\"text/javascript\">window.location = './moderate.php?do=maintenance&do2=postcountcache&page=$nextpage';</script>";
        }
        break;

        case 'privateroomcache':
        $rooms = dbQuery("SELECT * FROM {$sqlPrefix}rooms WHERE options & 16");
        while (false !== ($room = (mydbRowsay($rooms)))) {
          list($user1id,$user2id) = explode(',',$room['allowedUsers']);

          if (!$user1id || !$user2id) {
            $results .= "Failed to Process Room ID $room[id]; Bad allowedUsers definition '$room[allowedUsers]'.<br />";
          }
          else {
            $user1 = dbRows("SELECT * FROM user WHERE userId = $user1id");
            $user2 = dbRows("SELECT * FROM user WHERE userId = $user2id");

            if (!$user1['userName']) {
              $results .= "Failed to Process Room ID $room[id]; User ID $user1id no longer exists.<br />";
            }
            elseif (!$user2['userName']) {
              $results .= "Failed to Process Room ID $room[id]; User ID $user2id no longer exists.<br />";
            }
            else {
              $name = dbEscape("Private IM ($user1[userName] and $user2[userName])");
              if (dbQuery("UPDATE {$sqlPrefix}rooms SET name = '$name', bbcode = 1, options = 48 WHERE id = $room[id]")) {
                $results .= "Processed Room ID $room[id]; Users $user1[userName], $user2[userName]<br />";
              }
              else {
                $results .= "Failed to Process Room ID $room[id]; MySQL Query Failed";
              }
            }
          }
        }

        echo container('Updating Private Room Cache',"$results");
        break;

        case 'defaultgroup':
        if (!$defaultDisplayGroup) {
          trigger_error('A default group was not specified in the config.php file.',E_USER_ERROR);
        }
        elseif (!$_GET['confirm']) {
          $table = dbReadThrough(dbQuery("SELECT * FROM {$sqlUserTable} WHERE {$sqlUserTableCols[userGroup]} = 0"),'<tr><td>$userId</td><td>$userName</td></tr>');
          echo container('Warning','The following users will be affected: <table border="1"><thead><tr><td>UserID</td><td>Username</td></tr></thead><tbody>' . $table . '</tbody></table><br /><br /><form action="moderate.php" method="get"><button type="submit">Confirm</button><input type="hidden" name="do2" value="defaultgroup" /><input type="hidden" name="confirm" value="true" /></form><form action="moderate.php" method="get"><button type="submit">Go Back</button></form>');
        }
        else {
          mysql_query("UPDATE {$sqlUserTable} SET {$sqlUserTableCols[userGroup]} = $defaultDisplayGroup WHERE {$sqlUserTableCols[userGroup]} = 0");
          echo container('Warning','The following users will be affected: <table border="1"><thead><tr><td>UserID</td><td>Username</td></tr></thead><tbody>' . $table . '</tbody></table><br /><br /><form action="moderate.php" method="get"><button type="submit">Confirm</button><input type="hidden" name="do2" value="defaultgroup" /><input type="hidden" name="confirm" value="true" /></form><form action="moderate.php" method="get"><button type="submit">Go Back</button></form>');
        }
        break;

        default:
        echo '<table class="page">
    <thead>
      <tr class="hrow ui-widget-header">
        <td>System Maintenance</td>
      </tr>
    </thead>
    <tbody class="ui-widget-content">
      <tr>
        <td>
          <ul>
            <li><a href="./moderate.php?do=maintenance&do2=postcache">Regenerate Post Cache</a></li>
            <li><a href="./moderate.php?do=maintenance&do2=privateroomcache">Regenerate Private Room Cache</a></li>
            <li><a href="./moderate.php?do=maintenance&do2=disable">Regenerate Post Counts (WARNING: This Takes a Long Time)</a></li>
            <li><a href="./moderate.php?do=maintenance&do2=disable">Disable/Enable VRIM</a></li>
          </ul>
        </td>
      </tr>
    </tbody>
  </table>';
        break;
      }
    }
    else {
      trigger_error('No permission.',E_USER_ERROR);
    }
    break;


    default:
    $activeUsers = dbRows("SELECT COUNT(userId) AS count, userId FROM {$sqlPrefix}ping WHERE UNIX_TIMESTAMP(time) > UNIX_TIMESTAMP(NOW()) - 60 GROUP BY userId",'userId');
    $bannedUsers = dbRows("SELECT userId FROM {$sqlPrefix}users WHERE settings & 1",'userId');
    $status = (file_exists('.tempStop')) ? 'Stopped' : 'Running';
    echo container('Welcome','<script type="text/javascript">$(document).ready(function() { $(\'#moderateRight\').animate({\'height\' : window.innerHeight - 50},1750); });</script><div style="height: 0px; overflow: hidden;" id="moderateRight"><div style="text-align: center; font-size: 40px; font-weight: bold;">Welcome~~</div><br /><br />

Welcome to the FreezeMessenger control panel. Here you, as one of our grandé and spectacular administrative staff, can perform every task needed to you during any given day.<br /><br />

Server Time: ' . date('h:ia') . '<br />
Your Time: ' . fim_date('h:ia') . '<br />
Active Users in Last Minute: ' . count($activeUsers) . '<br />
Banned Users: <a href="./moderate.php?do=unbanuser">' . count($bannedUsers) . '</a><br />
Status: <a href="./moderate.php?do=maintenance&do2=disable">' . $status . '</a><br /><br /><br />


<img src="http://www.victoryroad.net/image.php?u=179&type=thumb" style="float: left;" />Freezie is Energetic.</div>');
    break;
  }
  echo '</div>';
}
else {
  trigger_error('You do not have permission to access this page.',E_USER_ERROR);
}

eval(hook('moderateEnd'));

echo template('templateEnd');
?>