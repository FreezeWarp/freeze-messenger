<?php
if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  echo '<h1>FreezeMessenger and WebPro Copyright and License</h1>';
  echo 'FreezeMessenger is Copyright &copy; 2011 by Joseph T. Parsons. It is distributed under the GNU General Public License, version 3:
<blockquote>This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.<br /><br />
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.<br /><br />You should have received a copy of the GNU General Public License along with this program.  If not, see <a href="http://www.gnu.org/licenses/">&lt;http://www.gnu.org/licenses/&gt;</a>.</blockquote><br /><br />

A copy of the GNU License should be found under <a href="../LICENSE">LICENSE</a>; it is printed below (scroll to see the entire content):
<blockquote style="max-height: 600px; overflow: auto;">' . nl2br(file_get_contents('../LICENSE')) . '</blockquote><br /><br />

<h1>WebPro Incl. Works</h1>
<ul>
  <li>jQuery, jQueryUI, and all jQueryUI Themeroller Themes &copy; <a href="http://jquery.org/" target="_BLANK">The jQuery Project.</a></li>
  <li>jGrowl &copy; 2009 Stan Lemon.</li>
  <li>jQuery Cookie Plugin &copy; 2006 Klaus Hartl</li>
  <li>EZPZ Tooltip &copy; 2009 Mike Enriquez</li>
  <li>Beeper &copy; 2009 Patrick Mueller</li>
  <li>Context Menu &copy; 2008 Cory S.N. LaViska</li>
  <li>jQTubeUtil &copy; 2010 Nirvana Tikku</li>
</ul>';
}
?>