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



//Layout-Specific Processing, Mainly the Creation of Specific Stylings to be Used
$layout = $_REQUEST['layout'];



// Generate the theme, based on:
// $_REQUEST[style] -> $_REQUST[s][style] -> $user[themeOfficialAjax] -> $defaultTheme -> 4
// (cookie/get/post specified)               (user specified)            (admin spec.)    (hard coded default)

$theme = ($_REQUEST['style'] ? $_REQUEST['style']
  : ($_REQUEST['s']['style'] ? $_REQUEST['s']['style']
    : ($user['themeOfficialAjax'] ? $user['themeOfficialAjax']
      : ($defaultTheme ? $defaultTheme : 4))));

$styles = array(
  1 => 'ui-darkness',
  2 => 'ui-lightness',
  3 => 'redmond',
  4 => 'cupertino',
  5 => 'dark-hive',
  6 => 'start',
  7 => 'vader',
  8 => 'trontastic',
  9 => 'humanity',
);

$style = $styles[$theme];



// Output Headers
header('Content-type: text/html; charset=utf-8');



// And, We're Off
echo template('templateStart');
?>