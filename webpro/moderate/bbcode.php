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

if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  $request = fim_sanitizeGPC(array(
    'request' => array(
      'bbcodeId' => array(
        'context' => array(
          'type' => 'int',
        ),
      ),
    ),

    'post' => array(
      'bbcodeName' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'searchRegex' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'replacement' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),
    ),
  ));

  if ($user['adminDefs']['modBBCode']) {
    switch ($_GET['do2']) {
      case 'view':
      case false:
      $bbcodes2 = $database->select(array(
        "{$sqlPrefix}bbcode" => "bbcodeId, bbcodeName, searchRegex, replacement",
      ));
      $bbcodes2 = $bbcodes2->getAsArray(true);

      foreach ($bbcodes2 AS $bbcode) {
        foreach (array('searchRegex', 'replacement') AS $item) {
          if (strlen($bbcode[$item]) > 80) {
            $bbcode[$item] = substr($bbcode[$item], 0, 77) . '...';
          }

          $bbcode[$item] = str_replace(array('<', '>'), array(' <', '> '), nl2br(htmlentities($bbcode[$item])));
        }

        $rows .= "<tr><td>$bbcode[bbcodeName]</td><td>$bbcode[searchRegex]</td><td>$bbcode[replacement]</td><td align=\"center\"><a href=\"./moderate.php?do=bbcode&do2=edit&bbcodeId=$bbcode[bbcodeId]\"><img src=\"./images/document-edit.png\" /></td></tr>";
      }

      echo container('BBCodes<a href="./moderate.php?do=bbcode&do2=edit"><img src="./images/document-new.png" style="float: right;" /></a>','<table class="page rowHover" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>BBCode</td>
      <td>Search Regex</td>
      <td>Replacement</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
      break;

      case 'edit':
      if ($request['bbcodeId']) {
        $bbcode = $database->getBBCode($request['bbcodeId']);
        $title = 'Edit BBCode "' . $bbcode['bbcodeName'] . '"';
      }
      else {
        $bbcode = array(
          'bbcodeName' => '',
          'bbcodeId' => 0,
          'searchRegex' => '',
          'replacement' => '',
        );

        $title = 'Create New BBCode';
      }

      echo container($title, '<form action="./moderate.php?do=bbcode&do2=edit2" method="post">
  <table border="1" class="ui-widget page">
    <tr>
      <td>Name:</td>
      <td><input type="text" name="bbcodeName" value="' . $bbcode['bbcodeName'] . '" /></td>
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
        <input type="text" name="replacement" value="' . htmlentities($bbcode['replacement']) . '" /><br />
        <small>Tips: "$1" and "\1" can be used here like with standard regular expressions. The /e flag is also possible, if adventurous.</small>
      </td>
    </tr>
  </table>

  <button type="submit">Submit</button>
  <button type="reset">Reset</button>
  <input type="hidden" name="bbcodeId" value="' . $bbcode['bbcodeId'] . '" />
</form>');
      break;

      case 'edit2':
      $bbcode = $database->getBBCode($request['bbcodeId']);

      if ($request['bbcodeId']) {
        $database->modLog('editBBCode', $bbcode['wordId']);
        $database->fullLog('editBBCode', array('bbcode' => $bbcode));

        $database->update("{$sqlPrefix}bbcode", array(
          'bbcodeName' => $request['bbcodeName'],
          'searchRegex' => $request['searchRegex'],
          'replacement' => $request['replacement'],
        ), array(
          'bbcodeId' => $request['bbcodeId'],
        ));

        echo container('BBCode Updated','The bbcode has been updated.<br /><br /><form method="post" action="moderate.php?do=bbcode"><button type="submit">Return to Viewing BBCode</button></form>');
      }
      else {
        $bbcode = array(
          'bbcodeName' => $request['bbcodeName'],
          'searchRegex' => $request['searchRegex'],
          'replacement' => $request['replacement'],
        );

        $database->insert("{$sqlPrefix}bbcode", $bbcode);
        $bbcode['bbcodeId'] = $database->insertId;

        $database->modLog('createBBCode', $bbcode['wordId']);
        $database->fullLog('createBBCode', array('bbcode' => $bbcode));

        echo container('BBCode Added','The bbcode has been added.<br /><br /><form method="post" action="moderate.php?do=bbcode"><button type="submit">Return to Viewing BBCode</button></form>');
      }
      break;

      case 'delete':
      $bbcode = $database->getBBCode($request['bbcodeId']);

      if ($bbcode) {
        $database->modLog('deleteBBCode', $bbcode['wordId']);
        $database->fullLog('deleteBBCode', array('bbcode' => $bbcode));

        $database->delete("{$sqlPrefix}bbcode", array(
          'bbcodeId' => $request['bbcodeId'],
        ));

        echo container('BBCode Deleted','The bbcode entry has been deleted.<br /><br /><form method="post" action="moderate.php?do=bbcode"><button type="submit">Return to Viewing BBCode</button></form>');
      }
      else {
        echo container('BBCode Not Found','The bbcode specified was not found.<br /><br /><form method="post" action="moderate.php?do=bbcode"><button type="submit">Return to Viewing BBCode</button></form>');
      }
      break;
    }
  }
  else {
    echo 'You do not have permission to manage BBCodes.';
  }
}
?>