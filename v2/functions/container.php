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