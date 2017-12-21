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

/*
 * This is WebPro's means of configuring FIM's data. The pages for individual actions are stored in the moderate/ directory.
*/

// Security to prevent loading of base moderate pages.
define('WEBPRO_INMOD', true);

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/moderateFunctions.php'); // Functions that are used solely by the moderate interfaces.
require(__DIR__ . '/../config.php');


/* This below bit hooks into the validate.php script to facilitate a seperate login. It is a bit cooky, though, and will need to be further tested. */
if (isset($_GET['do']) && $_GET['do'] === 'logout') {
    setcookie('webproModerate_accessToken', false);
    $ignoreLogin = true;
}

elseif (isset($_POST['webproModerate_userName'])) {
    $cr = new Http\CurlRequest($installUrl . '/validate.php', [], ['client_id' => 'WebProAdmin', 'grant_type' => 'password', 'username' => $_POST['webproModerate_userName'], 'password' => $_POST['webproModerate_password']]);

     try {
         $cr->executePOST();
         $result = $cr->getAsJson();

         if (isset($result['exception'])) {
             $message = 'An error occurred logging in: ' . $result['exception']['string'] . ' (' . $result['exception']['details'] . ')';
             $ignoreLogin = true;
         }
         else {
             setcookie('webproModerate_accessToken', $result['login']['access_token'], time() + (int) $result['login']['expires'] - 10);
             $hookLogin['accessToken'] = $result['login']['access_token'];
         }

     } catch (Exception $ex) {
        die('The request could not be completed (Server Error). Its response is below: ' . $cr->response);
     }
}

elseif (isset($_COOKIE['webproModerate_accessToken'])) {
    $hookLogin['accessToken'] = $_COOKIE['webproModerate_accessToken'];
}

else {
    $ignoreLogin = true;
}

// A hack to unset the access token when a script fails.
register_shutdown_function(function() {
    global $user, $ignoreLogin;

    if (!$user->id && !$ignoreLogin) {
        setcookie('webproModerate_accessToken', false);
        header('Location: ./');
    }
});

require('../global.php');
/*if (isset($_REQUEST['grant_type'])) {
    if ($user->id && $apiData['login']['access_token']) {
    }
    else {
        $message = 'Invalid login.';
    }
}*/

/* Here we require the backend. */
?><!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Freeze Messenger AdminCP</title>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="Joseph T. Parsons" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <!-- START Styles -->
    <link
            href="https://maxcdn.bootstrapcdn.com/bootswatch/4.0.0-beta.2/simplex/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-ofc00ja/z8wrU97EAHQRb4i4wsa/Zgr9JZll2R3KW33iqhFSfmVz/6xuWFx5pjcn"
            crossorigin="anonymous">

    <script defer src="https://use.fontawesome.com/releases/v5.0.1/js/all.js"></script>

    <style>
        table.table-align-middle td, table.table-align-middle th {
            vertical-align: middle;
        }
    </style>
    <!-- END Styles -->


    <!-- START Scripts -->
    <script>
        function windowDraw() {
            $('button, input[type=button], input[type=submit]').button();

            $('#mainMenu').accordion({
                autoHeight : false,
                active : Number($.cookie('webproModerate_menustate')) - 1,
                change: function(event, ui) {
                    var sid = ui.newHeader.children('a').attr('data-itemId');

                    $.cookie('webproModerate_menustate', sid, { expires: 14 });
                }
            });
        }

        $(document).ready(function() {
            if ($('#textXml').size()) {
                var editorXml = CodeMirror.fromTextArea(document.getElementById("textXml"), {
                    mode:  "text/html",
                    lineNumbers: true,
                    lineWrapping: true
                });
            }

            if ($('#textClike').size()) {
                var editorClike = CodeMirror.fromTextArea(document.getElementById("textClike"), {
                    mode:  "clike",
                    lineNumbers: true,
                    lineWrapping: true
                });
            }

            windowDraw();
        });


        $(window).bind('resize', windowDraw);


        var alert = function(text) {
            dia.info(text, "Alert");
        };
    </script>
    <!-- END Scripts -->

</head>
<body>
<div class="row">
    <div class="col-sm-3" style="min-width: 250px;">
        <?php if ($user->isValid()): ?>
        <div class="card">
            <h3 class="card-header">General Information</h3>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="index.php?do=main">Home</a>
                <?php echo ($user->hasPriv('modPrivs') ? '<a class="list-group-item list-group-item-action" href="index.php?do=log">View Logs</a>' : ''); ?>
                <a class="list-group-item list-group-item-action" href="index.php?do=copyright">Copyright</a>
                <a class="list-group-item list-group-item-action" href="index.php?do=logout">Logout</a>
            </div>

            <?php if ($user->hasPriv('modCensor') || $user->hasPriv('modPrivs')): ?>
                <h3 class="card-header">Engines</h3>
                <div class="list-group list-group-flush">
                    <?php echo ($user->hasPriv('modCensor') ? '<a class="list-group-item list-group-item-action" href="index.php?do=censor">Modify Censor</a>' : ''); ?>
                    <?php echo ($user->hasPriv('modPrivs') ? '<a class="list-group-item list-group-item-action" href="index.php?do=emoticons">Modify Emoticons</a>' : ''); ?>
                </div>
            <?php endif; ?>

            <?php if ($user->hasPriv('modPrivs')): ?>
                <h3 class="card-header">Advanced</h3>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action" href="index.php?do=users">User Editor</a>
                    <a class="list-group-item list-group-item-action" href="index.php?do=sessions">User Sessions</a>
                    <a class="list-group-item list-group-item-action" href="index.php?do=config">Configuration Editor</a>
                    <a class="list-group-item list-group-item-action" href="index.php?do=tools">Tools</a>
                    <a class="list-group-item list-group-item-action" href="index.php?do=phpinfo">PHP Info</a>
                </div>
            <?php endif ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="col">
        <?php
        if (!$user->id) {
            echo container('Please Login',(isset($message) ? $message : 'You have not logged in. Please login:') . '<br /><br />
            <form action="index.php" method="post" style="max-width: 500px;">
                <div class="row mb-2">
                    <div class="input-group col-lg-6">
                        <span class="input-group-addon">Username</span>
                        <input type="text" class="form-control" name="webproModerate_userName" />
                    </div>
    
                    <div class="input-group col-lg-6">
                        <span class="input-group-addon">Password</span>
                        <input type="password" class="form-control" name="webproModerate_password" id="password" />
                    </div>
                </div>

                <input type="submit" value="Login" class="form-control btn btn-success" />
            </form>
            ');
        }
        else {
            \Fim\Database::instance()->startTransaction();

            switch ($_GET['do'] ?? '') {
                case 'emoticons': require('./actions/emoticons.php'); break;
                case 'censor': require('./actions/censor.php'); break;

                case 'users': require('./actions/users.php'); break;
                case 'log': require('./actions/log.php'); break;
                case 'sessions': require('./actions/sessions.php'); break;
                case 'config': require('./actions/config.php'); break;
                case 'tools': require('./actions/tools.php'); break;
                case 'phpinfo': require('./actions/phpinfo.php'); break;

                case 'copyright': require('./actions/copyright.php'); break;
                default: require('./actions/main.php'); break;
            }

            \Fim\Database::instance()->accessLog('moderate', $request);

            \Fim\Database::instance()->endTransaction();
        }
        ?>
    </div>
</div>
</body>
</html>