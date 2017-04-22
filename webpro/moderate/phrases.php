<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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
        'do2' => array(
            'cast' => 'string',
        ),

        'data' => array(
            'cast' => 'string',
        ),

        'languageCode' => array(
            'cast' => 'string',
        ),

        'phraseName' => array(
            'cast' => 'string',
        ),
    ));

    $config = json_decode(file_get_contents('client/data/config.json'), true);

    if ($user->hasPriv('modPrivs')) {
        switch ($request['do2']) {
            case 'lang':
            case false:
                foreach ($config['languages'] AS $code => $language) {
                    $languageLinks .= "<a href=\"moderate.php?do=phrases&do2=view&languageCode={$code}\">{$language}</a><br />";
                }

                echo container('Choose a Language', $languageLinks);

                break;

            case 'view':
                $phrases = json_decode(file_get_contents('client/data/language_' . $request['languageCode'] . '.json'), true);

                foreach ($phrases AS $phrase => $text) {
                    if (strlen($text) > 80) {
                        $text = substr($text, 0, 77) . '...';
                    }

                    $text = str_replace(array('<', '>'), array(' <', '> '), nl2br(htmlentities($text)));

                    $rows .= "<tr><td>$phrase</td><td>$text</td><td><a href=\"./moderate.php?do=phrases&do2=edit&phraseName=$phrase&languageCode=$request[languageCode]\"><img src=\"./images/document-edit.png\" /></td></tr>";
                }

                echo container('Edit Phrases','<table class="page rowHover">
  <thead>
    <tr class="ui-widget-header">
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
                $phrases = json_decode(file_get_contents('client/data/language_' . $request['languageCode'] . '.json'), true);
                $phraseText = $phrases[$request['phraseName']];

                echo container("Edit Phrase \"$request[phraseName]\"","<form action=\"./moderate.php?do=phrases&do2=edit2&phraseName=$request[phraseName]&languageCode=$request[languageCode]\" method=\"post\">
<label for=\"data\">New Value:</label><br />
<textarea name=\"data\" id=\"data\" style=\"width: 100%; height: 300px;\">$phraseText</textarea><br /><br />

<button type=\"submit\">Update</button>
</form>");
                break;

            case 'edit2':
                $phraseText = $request['data'];
                $phraseText = str_replace(array("\r","\n","\r\n"), '', $phraseText); // Remove new lines (required for JSON).
                $phraseText = preg_replace("/\>(\ +)/", ">", $phraseText); // Remove extra space between  (looks better).

                $phrases = json_decode(file_get_contents('client/data/language_' . $request['languageCode'] . '.json'), true);
                $phrases[$request['phraseName']] = $phraseText; // Update the JSON object with the new phrase data.

                file_put_contents('client/data/language_' . $request['languageCode'] . '.json', json_encode($phrases)) or die('Unable to write'); // Send the new JSON data to the server.

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