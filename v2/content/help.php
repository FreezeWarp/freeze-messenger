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

require_once('../global.php'); // Used for everything.

echo '<script type="text/javascript">
$(document).ready(function() {
  $(\'#help\').accordion({clearStyle: true});
});
</script>

<div id="help" style="height: 400px;">
<h3><a href="#">A Quick Introduction</a></h3>
<div>
<!-- ...Well, not yet, but close! -->
FreezeMessenger 2.0 is an advanced AJAX-based online webmessenger and Instant Messenger substitute created to allow anybody to easily communicate with anybody else all across the web. It is highly sophisticated, supporting all modern browsers and utilizing various cutting-edge features in each. It also boasts a relatively client and server footprint
</div>

<h3><a href="#">Rules</a></h3>
<div>In no part of the chat, whether it be in a public, private, official, or nonofficial room you may not:
<ul>
<li>Promote or inflict hatespeech.</li>
<li>Post, link to, or encourage illegal material.</li>
<li>Encourage or enable another member to do any of the above.</li>
</ul></div>

<h3><a href="#">Formatting Messages</a></h3>
<div>The following tags are enabled for formatting:

<ul>
' . ($bbcode['shortCode'] ? '<li><em>+[text]+</em> - Bold a message.</li>
<li><em>_[text]_</em> - Underline a message.</li>
<li><em> /[text]/ </em> - Italicize a message.</li>
<li><em>=[text]=</em> - Strikethrough a message.</li>' : '') . ($bbcode['buis'] ? '
<li><em>[b][text][/b]</em> - Bold a message.</li>
<li><em>[u][text][/u]</em> - Underline a message.</li>
<li><em>[i][text][/i]</em> - Italicize a message.</li>
<li><em>[s][text][/s]</em> - Strikethrough a message.</li>' : '') . ($bbcode['link'] ? '
<li><em>[url]http://example.com/[/url]</em> - Link a URL.</li>
<li><em>[url=http://example.com/]Example[/url]</em> - Link a URL</li>' : '') . ($bbcode['image'] ? '
<li><em>[img]http://example.com/image.png[/img]</em> - Link an Image.</li>
<li><em>[img=":P"]http://example.com/image.png[/img]</em> - Link an image with alt text.</li>' : '') . ($bbcode['video'] ? '
<li><em>[youtube]{youtubeCode}[/youtube]</em> - Include a Youtube video.</li>' : '') . '
</ul></div>

<h3><a href="#">Users Under the Legal Age</a></h3>
<div>We take no responsibility for any harrassment, hate speach, or other issues users may encounter, however we will do our best to stop them if they are reported to proper administrative staff. Users will not be allowed to see mature rooms unless they have specified the "Disable Parental Controls" option in their user settings, and are encouraged to only talk to people privately whom they know.<br /><br />

Keep in mind all content is heavily encrytped for privacy. Private conversations may only be viewed by server administration when neccessary, but can not be accessed by chat staff.</div>

<h3><a href="#">Technical Requirements</a></h3>
<div>FreezeMessenger 2.0, at its early state, receives no browser testing. It is only guarenteed to work with these browsers:
<ul>
<li><a href="http://www.google.com/chrome" target="_BLANK">Chrome 10+</a></li>
</ul>

In the future, the following should also be supported:
<ul>
<li><a href="http://windows.microsoft.com/ie9" target="_BLANK">Internet Explorer 9+</a></li>
<li><a href="http://www.mozilla.com/en-US/firefox/" target="_BLANK">Firefox 4+</a></li>
<li><a href="http://www.opera.com/download/" target="_BLANK">Opera 11+</a></li>
<li><a href="http://www.apple.com/safari/" target="_BLANK">Safari 5</a></li>
</ul>

Users of any other browser will not receive support. Users of Windows XP and older should try <a href="http://www.mozilla.com/en-US/firefox/">Firefox</a> or <a href="<a href="http://www.opera.com/download/" target="_BLANK">Opera</a>, as Internet Explorer 8 <strong>is not</strong> supported.</div>

<h3><a href="#">FAQs</a></h3>
<div>
<ul>
  <li></li>
</ul></div>

<h3><a href="#">Debug Information</a></h3>
<div>Below is basic information useful for submitting bug reports:<br /><br />
<em>User Agent</em>: ' . $_SERVER['HTTP_USER_AGENT'] . '<br />
<em>Style Cookie</em>: ' . $_COOKIE['bbstyleid'] . '<br />
<em>Sessionhash Cookie</em>: ' . $_COOKIE['bbsessionhash'] . '<br />
<em>BBCode Array</em>: ' . print_r($bbcode,true) . '<br />
<em>Parse Flags</em>: ' . ($parseFlags ? 'On' : 'Off') . '<br />
<em>Login Method</em>: ' . $loginMethod . '<br />
</div>';
?>