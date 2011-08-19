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
require('../functions/fim_html.php');


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
    $(\'.CodeMirror\').css(\'width\', Math.floor(document.documentElement.clientWidth * .74 - 50));
    $(\'#moderateLeft\').css(\'width\', Math.floor(document.documentElement.clientWidth * .25 - 10));
    $(\'button, input[type=button], input[type=submit]\').button();

    $(\'#mainMenu\').accordion({
      autoHeight : false,
      active : Number($.cookie(\'webproModerate_menustate\')) - 1,
      change: function(event, ui) {
        var sid = ui.newHeader.children(\'a\').attr(\'data-itemId\');

        $.cookie(\'webproModerate_menustate\', sid, { expires: 14 });
      }
    });
  }

  $(document).ready(function() {
    if ($(\'#textXml\').size()) {
      var editorXml = CodeMirror.fromTextArea(document.getElementById("textXml"), {
        mode:  "xml"
      });
    }

    if ($(\'#textClike\').size()) {
      var editorClike = CodeMirror.fromTextArea(document.getElementById("textClike"), {
        mode:  "clike"
      });
    }

    windowDraw();
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
    <h3><a href="#" data-itemId="1">Manage Customizations</a></h3>
    <ul>
      ' . ($user['adminDefs']['modTemplates'] ? '<li><a href="moderate.php?do=phrases">Modify Phrases</a></li>' : '') . '
      ' . ($user['adminDefs']['modTemplates'] ? '<li><a href="moderate.php?do=templates">Modify Templates</a></li>' : '') . '
      ' . ($user['adminDefs']['modPlugins'] ? '<li><a href="moderate.php?do=plugins">Modify Plugins</a></li>' : '') . '
      ' . ($user['adminDefs']['modHooks'] ? '<li><a href="moderate.php?do=hooks">Modify Hooks</a></li>' : '') . '
    </ul>

    <h3><a href="#" data-itemId="2">Manage Engines</a></h3>
    <ul>
      ' . ($user['adminDefs']['modBBCode'] ? '<li><a href="moderate.php?do=bbcode">Modify BBCode</a></li>' : '') . '
      ' . ($user['adminDefs']['modCensor'] ? '<li><a href="moderate.php?do=censor">Modify Censor</a></li>' : '') . '
      ' . ($user['adminDefs']['modFiles'] ? '<li><a href="moderate.php?do=ftypes">Modify File Types</a></li>' : '') . '
    </ul>

    <h3><a href="#" data-itemId="3">Manage Advanced</a></h3>
    <ul>
      ' . ($user['adminDefs']['modCore'] ? '<li><a href="moderate.php?do=admin">Admin Permissions</a></li>' : '') . '
      ' . ($user['adminDefs']['modCore'] ? '<li><a href="moderate.php?do=config">Configuration Editor</a></li>' : '') . '
      ' . ($user['adminDefs']['modCore'] ? '<li><a href="moderate.php?do=sys">System Check</a></li>' : '') . '
      ' . ($user['adminDefs']['modCore'] ? '<li><a href="moderate.php?do=phpinfo">PHP Info</a></li>' : '') . '
      <li><a href="moderate.php?do=copyright">Copyright</a></li>
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
    case 'phrases': require('./moderate/phrases.php'); break;
    case 'hooks': require('./moderate/hooks.php'); break;
    case 'templates': require('./moderate/templates.php'); break;
    case 'censor': require('./moderate/censor.php'); break;
    case 'bbcode': require('./moderate/bbcode.php'); break;
    case 'ftypes': require('./moderate/ftypes.php'); break;
    case 'phpinfo': require('./moderate/phpinfo.php'); break;
    case 'copyright': require('./moderate/copyright.php'); break;
    case 'sys': require('./moderate/status.php'); break;
    case 'config': require('./moderate/config.php'); break;
    case 'plugins': require('./moderate/plugins.php'); break;
    default: require('./moderate/main.php'); break;
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