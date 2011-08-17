<?php
if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  if ($user['adminDefs']['modTemplates']) {
    switch ($_GET['do2']) {
      case false:
      case 'view':
      $phrases2 = $database->select(array(
        "{$sqlPrefix}phrases" => "phraseName, languageCode, text",
      ));
      $phrases2 = $phrases2->getAsArray(true);

      foreach ($phrases2 AS $phrase) {
        if (strlen($phrase['text']) > 80) {
            $phrase['text'] = substr($phrase['text'], 0, 77) . '...';
        }

        $phrase['text'] = str_replace(array('<', '>'), array(' <', '> '), nl2br(htmlentities($phrase['text'])));

        $rows .= "<tr><td>$phrase[phraseName] ($phrase[languageCode])</td><td>$phrase[text]</td><td><a href=\"./moderate.php?do=phrases&do2=edit&phraseName=$phrase[phraseName]&languageCode=$phrase[languageCode]\">Edit</td></tr>";
      }

      echo container('Phrases','<table class="page rowHover" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
      <td width="20%">Phrase</td>
      <td width="60%">Current Value</td>
      <td width="20%">Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
      break;

      case 'edit':
      $phraseName = $_GET['phraseName'];
      $languageCode = $_GET['languageCode'];

      $phrase = $database->select(array(
          "{$sqlPrefix}phrases" => "phraseName, languageCode, text",
        ),
        array(
          'both' => array(
            array(
              'type' => 'e',
              'left' => array(
                'type' => 'column',
                'value' => 'phraseName',
              ),
              'right' => array(
                'type' => 'string',
                'value' => $phraseName,
              ),
            ),
            array(
              'type' => 'e',
              'left' => array(
                'type' => 'column',
                'value' => 'languageCode',
              ),
              'right' => array(
                'type' => 'string',
                'value' => $languageCode,
              ),
            ),
          ),
        ),
        false,
        false,
        1
      );
      $phrase = $phrase->getAsArray(false);

      echo container("Edit Phrase '$phrase[phraseName]'","<form action=\"./moderate.php?do=phrases&do2=edit2&phraseName=$phrase[phraseName]&languageCode=$phrase[languageCode]\" method=\"post\">
<label for=\"text\">New Value:</label><br />
<textarea name=\"text\" id=\"text\" style=\"width: 100%; height: 300px;\">$phrase[text]</textarea><br /><br />

<button type=\"submit\">Update</button>
<input type=\"hidden\" name=\"lang\" value=\"$lang\" />
</form>");
      break;

      case 'edit2':
      $phraseName = $_GET['phraseName'];
      $languageCode = $_GET['languageCode'];
      $newValue = $_POST['text'];

      $database->update(array(
        'text' => $newValue
      ),
      "{$sqlPrefix}phrases",
      array(
        'phraseName' => $phraseName,
        'languageCode' => $languageCode,
      ));

      $database->modLog('phraseEdit',$phraseID);

      echo container('Updated','The phrase has been updated.<br /><br /><form action="./moderate.php?do=phrases" method="POST"><button type="submit">Return</button></form>');
      break;
    }
  }
  else {
    echo 'You do not have permission to modify phrases.';
  }
}
?>