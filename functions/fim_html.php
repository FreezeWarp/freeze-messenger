<?php

/**
 */
function fimHtml_buildSelect($selectName, $selectArray, $selectedItem) {
  $code = "<select name=\"$selectName\">";

  foreach ($selectArray AS $key => $value) {
    $code .= "<option value=\"$key\"" . ($key === $selectedItem ? ' selected="selected"' : '') . ">$value</option>";
  }

  $code .= '</select>';
}

?>