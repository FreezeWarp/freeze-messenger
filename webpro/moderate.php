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

$reqPhrases = true;
$reqHooks = true;

define('WEBPRO_INMOD', true);


/* This below bit hooks into the validate.php script to facilitate a seperate login. It is a bit cooky, though, and will need to be further tested. */
if (isset($_POST['webproModerate_userName'])) {
  $hookLogin['userName'] = $_POST['webproModerate_userName'];
  $hookLogin['password'] = $_POST['webproModerate_password'];
}
elseif (isset($_COOKIE['webproModerate_sessionHash'])) {
  $hookLogin['sessionHash'] = $_COOKIE['webproModerate_sessionHash'];
  $hookLogin['userIdComp'] = $_COOKIE['webproModerate_userId'];
}


/* Here we require the backend. */
require('../global.php');


/* And this sets the cookie with the session hash if possible. */
if (isset($sessionHash)) {
  if (strlen($sessionHash) > 0) {
    setcookie('webproModerate_sessionHash',$sessionHash);
    setcookie('webproModerate_userId',$user['userId']);
  }
}


/* And this is the template. We should move it into the DB at some point. */
echo '<!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Freeze Messenger AdminCP</title>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="author" content="Joseph T. Parsons" />
  <link rel="icon" id="favicon" type="image/png" href="images/favicon.png" />
  <!--[if lte IE 9]>
  <link rel="shortcut icon" id="faviconfallback" href="images/favicon1632.ico" />
  <![endif]-->

  <!-- START Styles -->
  <link rel="stylesheet" type="text/css" href="./client/css/cupertino/jquery-ui-1.8.13.custom.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="./client/css/cupertino/fim.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="./client/css/stylesv2.css" media="screen" />

  <link rel="stylesheet" type="text/css" href="./client/codemirror/lib/codemirror.css">
  <link rel="stylesheet" type="text/css" href="./client/codemirror/mode/xml/xml.css">
  <link rel="stylesheet" type="text/css" href="./client/codemirror/mode/clike/clike.css">

  <style>
  body {
    padding: 5px;
    margin: 5px;
  }

  #moderateRight {
    float: right;
    overflow: auto;
  }

  #moderateLeft {
    float: left;
  }

  .CodeMirror {
    border: 1px solid white;
    background-color: white;
    color: black;
    width: 800px;
  }

  h1, h2 {
    text-align: center;
    font-family: sans-serif;
  }
  </style>
  <!-- END Styles -->


  <!-- START Scripts -->
  <script src="./client/js/jquery-1.6.1.min.js" type="text/javascript"></script>

  <script src="./client/js/jquery-ui-1.8.13.custom.min.js" type="text/javascript"></script>
  <script src="./client/js/jquery.plugins.js" type="text/javascript"></script>


  <script src="./client/codemirror/lib/codemirror.js"></script>
  <script src="./client/codemirror/mode/xml/xml.js"></script>
  <script src="./client/codemirror/mode/clike/clike.js"></script>

  <script>
  function windowDraw() {
    $(\'body\').css(\'min-height\', document.documentElement.clientHeight);
    $(\'#moderateRight\').css(\'height\', document.documentElement.clientHeight - 10);
    $(\'#moderateLeft\').css(\'height\', document.documentElement.clientHeight - 10);
    $(\'#moderateRight\').css(\'width\', Math.floor(document.documentElement.clientWidth * .74 - 10));
    $(\'#moderateLeft\').css(\'width\', Math.floor(document.documentElement.clientWidth * .25 - 10));
    $(\'button, input[type=button], input[type=submit]\').button();

    $(\'#mainMenu\').accordion({
      autoHeight : false
    });
  }

  $(document).ready(function() {
    windowDraw();

    var editorXml = CodeMirror.fromTextArea(document.getElementById("textXml"), {
      mode:  "xml"
    });

    var editorClike = CodeMirror.fromTextArea(document.getElementById("textClike"), {
      mode:  "clike"
    });
  });


  $(window).bind(\'resize\', windowDraw);


  var alert = function(text) {
    dia.info(text, "Alert");
  };
  </script>
  <!-- END Scripts -->

</head>
<body>
<div id="moderateLeft">
  <div id="mainMenu">
    <h3>Manage Customizations</h3>
    <ul>
      ' . ($user['adminDefs']['modTemplates'] ? '<li><a href="moderate.php?do=phrases">Modify Phrases</a></li>' : '') . '
      ' . ($user['adminDefs']['modTemplates'] ? '<li><a href="moderate.php?do=templates">Modify Templates</a></li>' : '') . '
      ' . ($user['adminDefs']['modPlugins'] ? '<li><a href="moderate.php?do=plugins">Modify Plugins</a></li>' : '') . '
      ' . ($user['adminDefs']['modHooks'] ? '<li><a href="moderate.php?do=hooks">Modify Hooks</a></li>' : '') . '
    </ul>

    <h3>Manage Engines</h3>
    <ul>
      ' . ($user['adminDefs']['modBBCode'] ? '<li><a href="moderate.php?do=bbcode">Modify BBCode</a></li>' : '') . '
      ' . ($user['adminDefs']['modCensor'] ? '<li><a href="moderate.php?do=censor">Modify Censor</a></li>' : '') . '
      ' . ($user['adminDefs']['modFiles'] ? '<li><a href="moderate.php?do=censor">Modify File Types</a></li>' : '') . '
    </ul>

    <h3>Manage Advanced</h3>
    <ul>
      ' . ($user['adminDefs']['modCore'] ? '<li><a href="moderate.php?do=conf">Configuration Editor</a></li>' : '') . '
      ' . ($user['adminDefs']['modCore'] ? '<li><a href="moderate.php?do=sys">System Check</a></li>' : '') . '
      ' . ($user['adminDefs']['modCore'] ? '<li><a href="moderate.php?do=phpinfo">PHP Info</a></li>' : '') . '
    </ul>
  </div>
</div>
<div id="moderateRight" class="ui-widget">';

eval(hook('moderateStart'));

if (!$user['userId']) {
  echo container('Please Login','You have not logged in. Please login:<br /><br />

  <form action="moderate.php" method="post">
    <table>
      <tr>
        <td>Username: </td>
        <td><input type="text" name="webproModerate_userName" /></td>
      </tr>
      <tr>
        <td>Password: </td>
        <td><input type="password" name="webproModerate_password" /></td>
      </tr>
      <tr>
        <td colspan="2" align="center"><input type="submit" value="Login" /></td>
      </tr>
    </table>
  </form>');
}
elseif ($user['adminDefs']) { // Check that the user is an admin.
  switch ($_GET['do']) {
    case 'phrases':
    require('./moderate/phrases.php');
    break;

    case 'hooks':
    require('./moderate/hooks.php');
    break;

    case 'templates':
    require('./moderate/templates.php');
    break;

    case 'censor':
    require('./moderate/censor.php');
    break;


    case 'bbcode':
    require('./moderate/bbcode.php');
    break;


    case 'phpinfo':
    if ($user['adminDefs']['modCore']) {
      ob_start();

      phpinfo();

      $phpinfo = ob_get_clean();
      //ob_flush();

      $phpinfo = str_replace(array('<body>','<html>','</html>','</body>'), '', $phpinfo);
      $phpinfo = preg_replace(array('/<\!DOCTYPE(.*?)>/', '/\<head\>(.*)\<\/head\>/ism'), '', $phpinfo);
      $phpinfo = str_replace(array('<table','class="p"','class="e"','class="h"','class="v"','class="r"'), array('<table class="page ui-widget" border="1"','class="ui-widget-header"','','class="ui-widget-header"','',''), $phpinfo);

      echo $phpinfo;
    }
    else {
      echo 'You do not have permission to view PHP info.';
    }
    break;


    case 'copyright':
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
    break;


    case 'sys':
    if ($user['adminDefs']['modCore']) {
      echo container('System Requirements & Status','<ul>
        <li>Database</li>
        <ul>
          <li>MySQL 5.0.5+</li>
          <li>MySQLi Extension (' . (extension_loaded('mysqli') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
        </ul>
        <li>PHP 5.2+ (' . (floatval(phpversion()) > 5.2 ? 'Looks Good' : 'Not Detected - Version ' . phpversion() . ' Installed') . ')</li>
        <ul>
          <li>MySQL Extension (' . (extension_loaded('mysql') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
          <li>Hash Extension (' . (extension_loaded('hash') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
          <li>Date/Time Extension (' . (extension_loaded('date') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
          <li>MCrypt Extension (' . (extension_loaded('mcrypt') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
          <li>PCRE Extension (' . (extension_loaded('pcre') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
          <li>Multibyte String Extension (' . (extension_loaded('mbstring') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
          <li>SimpleXML Extension (' . (extension_loaded('simplexml') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
          <li>APC Extension (' . (extension_loaded('apc') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
        </ul>
      </ul>');
    }
    else {
      echo 'You do not have permission to view system info.';
    }
    break;


    case 'conf':
    break;


    case 'plugins':
    echo container('To Be Continued...','Plugins will be added in FIMv3 Beta 4.');
    break;


    default:
    echo container('Welcome','<div style="text-align: center; font-size: 40px; font-weight: bold;">Welcome</div><br /><br />

Welcome to the FreezeMessenger control panel. Here you, as one of our well-served grandé and spectacular administrative staff, can perform every task needed to you during normal operation. Still, be careful: you can mess things up here!<br /><br />

To perform an action, click a link on the sidebar. Further instructions can be found in the documentation.');
    break;
  }
}
else {
  trigger_error('You do not have permission to access this page. Please login on the main chat and refresh.',E_USER_ERROR);
}

eval(hook('moderateEnd'));

echo '</div>
</body>
</html>';
?>