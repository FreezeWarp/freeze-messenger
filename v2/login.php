<?php
require_once('global.php');
require_once('functions/container.php');
require_once('templateStart.php');

if ($flag) {
  switch($flag) {
    case 'nouser': $message .= 'No user exists with that user title. Is it possible that you have changed your name recently?'; break; // No user with that username exists.
    case 'nopass': $message .= 'You appeared to have entered a wrong password. Remeber, passwords are case sensitive.'; break; // The password is wrong.
  }

    echo container('Unsuccessful Login',$message);
}

  echo container('Login to Victory Road Chat','<div id="normalLogin">
  Hello. Please Enter Your Login Credentials Below:<br />

    <form action="/index.php" method="post" style="text-align: center; display: block;">
    <label for="username">Username:</label><br />
    <input type="text" name="username" placeholder="Please enter your username." /><br /><br />

      <label for="password">Password:</label><br />
    <input type="password" name="password" placeholder="Please enter your password." /><br /><br />

      <label for="rememberme">Remember Me for One Week?:</label>
    <input type="checkbox" name="rememberme" id="rememberme" /><br /><br />

      <button type="submit">Launch</button><button type="reset">Start Over</button><button type="button" onclick="$(\'#normalLogin\').slideUp(); $(\'#secureLogin\').slideDown();">Secure Login</button>
  </form>
</div>

<div id="secureLogin" style="display: none;">
  Below you can login directly to the forums via SSL encyption:<br /><br />

  <span style="middle" onclick="$(this).html(\'<iframe src=&quot;https://www.victoryroad.net/login.php?do=vrimLogin&quot; style=&quot;width: 300px; height: 150px;&quot;>iFrames are disabled.</iframe>\');"><a href="javascript:void(0);">[Display Login]</a></span><br /><br />

  Once you have logged in, <a href="/">click here</a>.
</div>');

  echo container('Guest Links','<ul>
  <li><a href="/index.php?action=online">Who\'s Online</a></li>
  <li><a href="/viewRooms.php">Room List</a></li>
  <li><a href="/archive.php">Archives</a></li>
  <ul>
    <li><a href="/index.php?action=archive&roomid=1">Main</a></li>
  </ul>
</ul>');

require_once('templateEnd.php');