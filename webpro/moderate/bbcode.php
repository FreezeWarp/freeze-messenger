<?php
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

        $rows .= "<tr><td>$bbcode[bbcodeName]</td><td>$bbcode[searchRegex]</td><td>$bbcode[replacement]</td><td><a href=\"./moderate.php?do=bbcode&do2=edit&bbcodeId=$bbcode[bbcodeId]\">Edit</td></tr>";
      }

      echo container('BBCodes<a href="./moderate.php?do=bbcode&do2=edit"><span class="ui-icon ui-icon-plusthick" style="float: right;" ></span></a>','<table class="page rowHover" border="1">
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

      case 'edit2':
      if ($request['bbcodeId']) {
        $database->update("{$sqlPrefix}bbcode", array(
          'bbcodeName' => $request['bbcodeName'],
          'searchRegex' => $request['searchRegex'],
          'replacement' => $request['replacement'],
        ), array(
          'bbcodeId' => $request['bbcodeId'],
        ));

        echo container('BBCode Updated','The bbcode has been updated.<br /><br /><form method="post" action="moderate.php?do=bbcode"><button type="submit">Return to Viewing Lists</button></form>');
      }
      else {
        $database->insert("{$sqlPrefix}bbcode", array(
          'bbcodeName' => $request['bbcodeName'],
          'searchRegex' => $request['searchRegex'],
          'replacement' => $request['replacement'],
        ));

        echo container('BBCode Added','The bbcode has been added.<br /><br /><form method="post" action="moderate.php?do=bbcode"><button type="submit">Return to Viewing Lists</button></form>');
      }
      break;

      case 'delete':
      $database->delete("{$sqlPrefix}bbcode", array(
        'bbcodeId' => $request['bbcodeId'],
      ));

      echo container('BBCode Deleted','The bbcode entry has been deleted.<br /><br /><form method="post" action="moderate.php?do=bbcode"><button type="submit">Return to Viewing Lists</button></form>');
      break;
    }
  }
  else {
    echo 'You do not have permission to manage BBCodes.';
  }
}
?>