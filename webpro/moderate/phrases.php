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
  $request = fim_sanitizeGPC('r', array(
    'phraseName' => array(
      'context' => array(
        'type' => 'string',
      ),
    ),

    'interfaceId' => array(
      'context' => array(
        'type' => 'int',
      ),
    ),

    'languageCode' => array(
      'context' => array(
        'type' => 'string',
      ),
    ),

    'text' => array(
      'context' => array(
        'type' => 'string',
      ),
    ),
  ));

  if ($user['adminDefs']['modTemplates']) {
    switch ($_GET['do2']) {
      case false:
      case 'interface':
      $interfaces = $database->select(array(
        "{$sqlPrefix}interfaces" => "interfaceId, interfaceName",
      ));
      $interfaces = $interfaces->getAsArray(true);

      $interfaceLinks = '';

      foreach ($interfaces AS $interface) {
        $interfaceLinks .= "<a href=\"moderate.php?do=phrases&do2=lang&interfaceId={$interface['interfaceId']}\">{$interface['interfaceName']}</a><br />";
      }

      echo container('Choose an Interface', $interfaceLinks);
      break;

      case 'lang':
      $languages = $database->select(array(
        "{$sqlPrefix}languages" => "languageCode, languageName",
      ));
      $languages = $languages->getAsArray(true);

      $langugeLinks = '';

      foreach ($languages AS $language) {
        $languageLinks .= "<a href=\"moderate.php?do=phrases&do2=view&interfaceId={$request['interfaceId']}&languageCode={$language['languageCode']}\">{$language['languageName']}</a><br />";
      }

      echo container('Choose an Language', $languageLinks);

      break;

      case 'view':
      $phrases2 = $database->select(array(
        "{$sqlPrefix}phrases" => "phraseName, languageCode, interfaceId, text",
      ), array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'interfaceId',
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $request['interfaceId'],
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
              'value' => $request['languageCode'],
            ),
          ),
        ),
      ));
      $phrases2 = $phrases2->getAsArray(true);

      foreach ($phrases2 AS $phrase) {
        if (strlen($phrase['text']) > 80) {
          $phrase['text'] = substr($phrase['text'], 0, 77) . '...';
        }

        $phrase['text'] = str_replace(array('<', '>'), array(' <', '> '), nl2br(htmlentities($phrase['text'])));

        $rows .= "<tr><td>$phrase[phraseName]</td><td>$phrase[text]</td><td align=\"center\"><a href=\"./moderate.php?do=phrases&do2=edit&phraseName=$phrase[phraseName]&interfaceId=$phrase[interfaceId]&languageCode=$phrase[languageCode]\"><img src=\"./images/document-edit.png\" /></td></tr>";
      }

      echo container('Edit Phrases','<table class="page rowHover" border="1">
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
      $phrase = $database->getPhrase($request['phraseName'], $request['languageCode'], $request['interfaceId']);

      echo container("Edit Phrase \"$phrase[phraseName]\"","<form action=\"./moderate.php?do=phrases&do2=edit2&phraseName=$phrase[phraseName]&languageCode=$phrase[languageCode]&interfaceId=$phrase[interfaceId]\" method=\"post\">
<label for=\"text\">New Value:</label><br />
<textarea name=\"text\" id=\"text\" style=\"width: 100%; height: 300px;\">$phrase[text]</textarea><br /><br />

<button type=\"submit\">Update</button>
</form>");
      break;

      case 'edit2':
      $phrase = $database->getPhrase($request['phraseName'], $request['languageCode'], $request['interfaceId']);

      $database->update("{$sqlPrefix}phrases", array(
        'text' => $request['text']
      ), array(
        'phraseName' => $phrase['phraseName'],
        'languageCode' => $phrase['languageCode'],
        'interfaceId' => $phrase['interfaceId'],
      ));

      $database->modLog('phraseEdit',$phrase['phraseName'] . '-' . $phrase['languageCode'] . '-' . $phrase['interfaceId']);
      $database->fullLog('phraseEdit',array('phrase' => $phrase));

      echo container('Phrase Updated','The phrase has been updated.<br /><br /><form action="./moderate.php?do=phrases&do2=view&languageCode=' . $request['languageCode'] . '&interfaceId=' . $request['interfaceId'] . '" method="POST"><button type="submit">Return</button></form>');
      break;
    }
  }
  else {
    echo 'You do not have permission to modify phrases.';
  }
}
?>