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

$sessionHash = $_COOKIE['fim3_sessionHash'];
require_once('../global.php');

echo template('templateAdminStart');

eval(hook('moderateStart'));

if ($user['adminDefs']) { // Check that the user is an admin.
  switch ($_GET['do']) {
    case 'phrases':
    if ($user['adminDefs']['modPhrases']) {
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

          echo container('Updated','The phrase has been updated.<br /><br /><form action="./moderate.php?do=phrases" method="POST"><button type="submit">Return</button></form>');
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
    if ($user['adminDefs']['modHooks']) {

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

        echo container('Updated','The hook has been updated.<br /><br /><form action="Return" method="POST"><button type="submit">Return</button></form>');
        break;
      }
    }
    else {
      trigger_error('No permission.',E_USER_ERROR);
    }
    break;

    case 'templates':
    if ($user['adminDefs']['modTemplates']) {
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

        echo container('Updated','The template has been updated.<br /><br /><form action="Return" method="POST"><button type="submit">Return</button></form>');
        break;
      }
    }
    break;

    case 'censor':
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

        modLog('deleteCensorList',$listId);

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
      trigger_error('No permission.',E_USER_ERROR);
    }
    break;


    default:
    $activeUsers = dbRows("SELECT COUNT(userId) AS count, userId FROM {$sqlPrefix}ping WHERE UNIX_TIMESTAMP(time) > UNIX_TIMESTAMP(NOW()) - 60 GROUP BY userId",'userId');
    $bannedUsers = dbRows("SELECT userId FROM {$sqlPrefix}users WHERE settings & 1",'userId');
    $status = (file_exists('.tempStop')) ? 'Stopped' : 'Running';
    echo container('Welcome','<script type="text/javascript">$(document).ready(function() { $(\'#moderateRight\').animate({\'height\' : window.innerHeight - 50},1750); });</script><div style="height: 0px; overflow: hidden;" id="moderateRight"><div style="text-align: center; font-size: 40px; font-weight: bold;">Welcome</div><br /><br />

Welcome to the FreezeMessenger control panel. Here you, as one of our grandé and spectacular administrative staff, can perform every task needed to you during any given day.<br /><br />

Server Time: ' . date('h:ia') . '<br />
Your Time: ' . fim_date('h:ia') . '<br />
Active Users in Last Minute: ' . count($activeUsers) . '<br />
Banned Users: <a href="./moderate.php?do=unbanuser">' . count($bannedUsers) . '</a><br />
Status: <a href="./moderate.php?do=maintenance&do2=disable">' . $status . '</a><br /><br /><br />
</div>');
    break;
  }
  echo '</div>';
}
else {
  trigger_error('You do not have permission to access this page. Please login on the main chat and refresh.',E_USER_ERROR);
}

eval(hook('moderateEnd'));

echo template('templateAdminEnd');
?>