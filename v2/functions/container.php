<?php
function container($title,$content,$class = 'page') {
  global $containerId;
  $containerId ++;

  $return = "    <tr>
      <td>
        <div>$content</div>
      </td>
    </tr>
";

  $return = "<table class=\"$class ui-widget\">
  <thead>
    <tr class=\"hrow ui-widget-header ui-corner-top\">
      <td>$title</td>
    </tr>
  </thead>
  <tbody class=\"ui-widget-content ui-corner-bottom\">
$return  </tbody>
</table>

";

  return $return;
}
?>