<?php
if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
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

        $rows .= "<tr><td>$bbcode[bbcodeName]</td><td>$bbcode[searchRegex]</td><td>$bbcode[replacement]</td><td><a href=\"./moderate.php?do=bbcodes&do2=edit&bbcodeId=$bbcode[bbcodeId]\">Edit</td></tr>";
      }

      echo container('BBCodes<a href="./moderate.php?do=bbcode&do2=add"><span class="ui-icon ui-icon-plusthick" style="float: right;" ></span></a>','<table class="page rowHover" border="1">
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

      case 'add':

      break;
      case 'add2':

      break;
      case 'edit':

      break;
      case 'edit2':

      break;
    }
  }
  else {
    echo 'You do not have permission to manage BBCodes.';
  }
}
?>