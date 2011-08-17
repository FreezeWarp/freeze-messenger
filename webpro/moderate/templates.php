<?php
if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  if ($user['adminDefs']['modTemplates']) {
    switch ($_GET['do2']) {
      case false:
      case 'view':
      $templates2 = $database->select(array(
        "{$sqlPrefix}templates" => "templateId, templateName, vars, data",
      ));
      $templates2 = $templates2->getAsArray(true);

      foreach ($templates2 AS $template) {
        $rows .= "<tr><td>$template[templateName]</td><td><a href=\"./moderate.php?do=templates&do2=edit&templateId=$template[templateId]\">Edit</td></tr>";
      }

      echo container('Templates','<table class="page rowHover" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>Template</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
      break;

      case 'edit':
      $template = $database->select(array(
          "{$sqlPrefix}templates" => "templateId, templateName, vars, data",
        ),
        array(
          'both' => array(
            array(
              'type' => 'e',
              'left' => array(
                'type' => 'column',
                'value' => 'templateId',
              ),
              'right' => array(
                'type' => 'int',
                'value' => (int) $_GET['templateId'],
              ),
            ),
          ),
        ),
        false,
        false,
        1
      );
      $template = $template->getAsArray(false);

      echo container("Edit Hook '$template[templateName]'","<form action=\"./moderate.php?do=templates&do2=edit2&templateId=$template[templateId]\" method=\"post\">
  <label for=\"vars\">Vars:</label><br />
  <input type=\"text\" name=\"vars\" value=\"$template[vars]\" /><br /><br />

  <label for=\"text\">New Value:</label><br />
  <textarea name=\"text\" id=\"textXml\" style=\"width: 100%; height: 300px;\">$template[data]</textarea><br /><br />

  <button type=\"submit\">Update</button>
</form>");
      break;

      case 'edit2':
      $templateId = $_GET['templateId'];
      $text = $_POST['text'];
      $vars = $_POST['vars'];

      $database->update(array(
        'text' => $text,
        'vars' => $vars
      ),
      "{$sqlPrefix}templates",
      array(
        'templateId' => (int) $templateId,
      ));

      $database->modLog('templateEdit',$templateId);

      echo container('Updated','The template has been updated.<br /><br /><form action="Return" method="POST"><button type="submit">Return</button></form>');
      break;
    }
  }
  else {
    echo 'You do not have permission to modify templates.';
  }
}
?>