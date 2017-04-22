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
  echo container('<h1>FreezeMessenger and WebPro Copyright and License</h1>', 'FreezeMessenger is Copyright &copy; 2011 by Joseph T. Parsons. It is distributed under the GNU General Public License, version 3:
<blockquote>This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.<br /><br />
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.<br /><br />You should have received a copy of the GNU General Public License along with this program.  If not, see <a href="http://www.gnu.org/licenses/">&lt;http://www.gnu.org/licenses/&gt;</a>.</blockquote><br /><br />

A copy of the GNU License should be found under <a href="../LICENSE">LICENSE</a>; it is printed below (scroll to see the entire content):
<blockquote style="max-height: 400px; overflow: auto;">' . nl2br(file_get_contents('../LICENSE')) . '</blockquote><br /><br />

<h1>WebPro Incl. Works</h1>
<ul>
  <li>jQuery, jQueryUI, and all jQueryUI Themeroller Themes &copy; <a href="http://jquery.org/" target="_BLANK">The jQuery Project.</a></li>
  <li>jGrowl &copy; 2009 <a href="http://stanlemon.net/projects/jgrowl.html">Stan Lemon.</a></li>
  <ul>
    <li>Substantial Modifications to Support Live() Method by Joseph T. Parons</li>
  </ul>
  <li>jQuery Cookie Plugin &copy; <a href="https://github.com/carhartl/jquery-cookie">2006 Klaus Hartl.</a></li>
  <li>EZPZ Tooltip &copy; 2009 <a href="http://theezpzway.com/2009/3/17/jquery-plugin-ezpz-tooltip">Mike Enriquez</a>.</li>
  <ul>
    <li>Substantial Modifications to Support Live() Method by Joseph T. Parons</li>
  </ul>
  <li>Context Menu &copy; 2008 <a href="http://abeautifulsite.net/blog/2008/09/jquery-context-menu-plugin/">Cory S.N. LaViska</a></li>
  <ul>
    <li>Substantial Modifications to Support Alternate Click Method by Joseph T. Parons</li>
  </ul>
  <li>jQTubeUtil &copy; 2010 <a href="http://www.tikku.com/jquery-jqtube-util">Nirvana Tikku</a></li>
  <li>ColorPicker &copy; <a href="http://www.eyecon.ro/colorpicker/">Stefan Petr</a></li>
</ul>');
}
?>