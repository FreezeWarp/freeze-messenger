<?php
require_once('../global.php');

echo '<strong>A Quick Introduction</strong><br />
Victory Road Instant Messenger is a program, still in early development, which allows for fast and effective communication through the community. No more slow-response PMs, and no having to worry about who uses what service: its all here, and free. Simply sign up for Victory Road and you\'re good to go.<br /><br />

<strong>Rules</strong><br />
In no part of the chat, whether it be in a public, private, official, or nonofficial room you may not:
<ul>
<li>Promote or inflict hatespeech.</li>
<li>Post, link to, or encourage illegal material.</li>
<li>Encourage or enable another member to do any of the above.</li>
</ul>

<strong>Formatting Messages</strong><br />
Messages support basic IM-style formatting. Standard formatting can be acheived via use of:
<ul>
<li><em>+[text]+</em> - Bold a message.</li>
<li><em>_[text]_</em> - Underline a message.</li>
<li><em> /[text]/ </em> - Italicize a message.</li>
<li><em>=[text]=</em> - Strikethrough a message.</li>
</ul>

<strong>The Different Sections of a Chat</strong><br />
In the main chat window, there are three seperate sections. On the upper-left you will find a list of official available rooms. Other, user-created rooms, can be seen by clicking "<img src="/images/view-list-details.png" class="standard" alt="Veiw All Rooms" />" (you can also change the topic here). On the lower-left you will find the text entry area. Here you enter messages into the chat, which then appear with the rest of the conversation found in the third section to the right.<br /><br />

<strong>Creating a Room</strong><br />
By clicking "<img src="/images/document-new.png" class="standard" alt="Create Room" />" you will be able to create your own room. These are useful for simply talking with one or more friends, or, should you wish, even creating a full-fledged group with additional moderators and everything. Either way, here is where you can find more granular control over who you talk to and how. Keep in mind that you must still follow the rules best possible: the main one being no hatespeech or illegal content.<br /><br />

<strong>Talking to Strangers</strong><br />
In general, we recommend being at least 13 years of age if you wish to be a part of any conversation not listed as a "Public" channel. Regardless, avoid dislosing personally identifiable information such as your name, picture, birthday, or location. Additionally, by default all rooms marked as mature are disabled unless you change your user options.<br /><br />

<strong>Technical Requirements</strong><br />
At present you will need a fairly modern browser to fully experience VRIM. We personally recommend on of these browsers:
<ul>
<li><a href="http://www.google.com/chrome" target="_BLANK">Chrome 9+</a></li>
<li><a href="http://www.mozilla.com/en-US/firefox/" target="_BLANK">Firefox 3.6+</a></li>
<li><a href="http://www.opera.com/download/" target="_BLANK">Opera 11+</a></li>
</ul>

Additionally, users of Internet Explorer are <em>highly</em> recommended to upgrade to the latest <a href="http://windows.microsoft.com/ie9" target="_BLANK">Internet Explorer 9.0</a> to fully enjoy VRIM\'s experience.<br /><br />

<strong>General Problem Solving</strong>
<ul>
<li>Having the same room open in two tabs/windows causes refresh issues. If some messages are not appearing, check to make sure you don\'t have the sae room open twice.</li>
<li></li>
</ul>

<strong>Debug Information</strong><br />
Below is basic information useful for submitting bug reports:<br /><br />
<em>User Agent</em>: ' . $_SERVER['HTTP_USER_AGENT'] . '<br />
<em>Style Cookie</em>: ' . $_COOKIE['bbstyleid'] . '<br />
<em>Sessionhash Cookie</em>: ' . $_COOKIE['bbsessionhash'] . '<br />';
?>