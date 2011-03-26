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

  $return = "<table class=\"$class\">
  <thead>
    <tr class=\"hrow\">
      <td>$title</td>
    </tr>
  </thead>
  <tbody>
$return  </tbody>
</table><br />

";

  return $return;
}

function button($text,$url,$postVars = false) {
  return '<form method="post" action="' . $url . '" style="display: inline;">
  <button type="submit">' . $text . '</button>
</form>';
}
?>