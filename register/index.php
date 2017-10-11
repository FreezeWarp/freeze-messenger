<?php
$ignoreLogin = true;
require('../global.php');
require('./phrases.php');
$lang = 'enGB'; // TODO
$success = false;
?><!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2014 Joseph T. Parsons. -->
<!-- TODO: Localisation for Dates -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Freeze Messenger Registration</title>
    <meta name="robots" content="noindex, nofollow"/>
    <meta name="author" content="Joseph T. Parsons"/>
    <link rel="icon" id="favicon" type="image/png" href="images/favicon.png"/>
    <!--[if lte IE 9]>
    <link rel="shortcut icon" id="faviconfallback" href="images/favicon1632.ico"/>
    <![endif]-->

    <!-- START Styles -->
    <link rel="stylesheet" type="text/css" href="../webpro/client/css/absolution/jquery-ui-1.8.16.custom.css"
          media="screen"/>
    <link rel="stylesheet" type="text/css" href="../webpro/client/css/absolution/fim.css" media="screen"/>
    <link rel="stylesheet" type="text/css" href="../webpro/client/css/stylesv2.css" media="screen"/>
    <style>
        h1 {
            margin: 0px;
            padding: 5px;
        }

        .main {
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            display: block;
            border: 1px solid black;
        }

        .ui-widget {
            font-size: 12px;
        }

        .ui-widget-content {
            padding: 5px;
        }

        table td {
            padding: 5px;
        }

        table.page td {
            border-bottom: 1px solid black;
        }

        table.page tr:last-child td {
            border-bottom: none;
        }

        tbody tr:nth-child(2n) {
            background: #efefef !important;
        }
    </style>
    <!-- END Styles -->

    <!-- START Scripts -->
    <script src="../webpro/client/js/jquery-1.6.2.min.js" type="text/javascript"></script>

    <script src="../webpro/client/js/jquery-ui-1.8.16.custom.min.js" type="text/javascript"></script>
    <script src="../webpro/client/js/jquery.plugins.js" type="text/javascript"></script>

    <script>
        function windowDraw() {
            $('body').css('min-height', window.innerHeight);
        }

        $(document).ready(function () {
            windowDraw();
            $('button, input[type=button], input[type=submit]').button();
        });
        window.onwindowDraw = windowDraw;

        var alert = function (text) {
            dia.info(text, "Alert");
        };
    </script>
    <!-- END Scripts -->
</head>

<body class="ui-widget">
<div id="part1" class="main">
    <h1 class="ui-widget-header">FreezeMessenger: User Registration</h1>
    <div class="ui-widget-content">

        <?php
        if (isset($_REQUEST['formSubmitted'])) {
            $result = Http\CurlRequest::quickRunPost($installUrl . '/api/user.php', [], [
                'name'      => $_POST['name'],
                'email'     => $_POST['email'],
                'password'  => $_POST['password'],
                'birthDate' => $_POST['birthDay'] && $_POST['birthMonth'] && $_POST['birthYear']
                    ? mktime(null, null, null, $_POST['birthMonth'], $_POST['birthDay'], $_POST['birthYear'])
                    : null
            ]);

            if (isset($result['exception'])) {
                echo 'Error "' . $result['exception']['string'] . '": ' . $result['exception']['details'] . '<br /><br />';
            }

            elseif (!isset($result['user'])) {
                echo 'An unknown error occurred.<br /><br />';
            }

            else {
                $success = true;
                ?>
                <h1 class="ui-widget-header">Registration Complete!</h1>
                <p>You have now registered as <?php echo $_POST['name']; ?>. <a href="../">Click here to
                        continue to the messenger.</a></p>
                <?php
            }
        }

        if (!$success) {
            echo $phrases[$lang]['stage1introduction']; ?><br/><br/>

            <!-- Javascript is NOT required, but interfaces naturally work better with it.
              -- This script, to be stable, must work without it, however. -->
            <script type="text/javascript" src="register.js"></script>

            <form name="register_form" id="register_form" action="index.php" method="post">
                <table class="page">
                    <tr>
                        <td><strong><?php echo $phrases[$lang]['stage1formUserNameLabel']; ?></strong></td>
                        <td><input id="userName" type="text" name="name" value="<?php echo $_POST['name'] ?? ''; ?>" required /><br/>
                            <small><?php echo $phrases[$lang]['stage1formUserNameBlurb']; ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $phrases[$lang]['stage1formPasswordLabel']; ?></strong></td>
                        <td><input id="password" type="password" name="password"/><br/>
                            <small><?php echo $phrases[$lang]['stage1formPasswordBlurb']; ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $phrases[$lang]['stage1formPasswordAgainLabel']; ?></strong></td>
                        <td><input id="passwordConfirm" type="password"/><br/>
                            <small><?php echo $phrases[$lang]['stage1formPasswordAgainBlurb']; ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $phrases[$lang]['stage1formEmailLabel']; ?></strong></td>
                        <td><input id="email" type="text" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" /><br/>
                            <small><?php echo $phrases[$lang]['stage1formEmailBlurb']; ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $phrases[$lang]['stage1formBirthDateLabel']; ?></strong></td>
                        <td>
                            <div name="datepicker" id="datepicker"></div>
                            <?php
                            echo fimHtml_buildSelect('birthDay', array_merge([""], range(1, 31)), $_POST['birthDay'] ?? 0);
                            echo fimHtml_buildSelect('birthMonth', array_merge([""], $phrases[$lang]['months']), $_POST['birthMonth'] ?? 0);
                            echo fimHtml_buildSelect('birthYear', array_combine(
                                array_merge([0], range(intval(date('Y')) - fimConfig::$ageMinimum, intval(date('Y')) - 150)),
                                array_merge([""], range(intval(date('Y')) - fimConfig::$ageMinimum, intval(date('Y')) - 150))
                            ), $_POST['birthYear'] ?? 0);
                            ?>
                            <br/>
                            <small><?php echo $phrases[$lang]['stage1formBirthDateBlurb']; ?></small>
                        </td>
                    </tr>
                </table>
                <br/>

                <div style="height: 30px;">
                    <input style="float: right;" type="submit" value="Finish &rarr;"/>
                    <input type="hidden" name="formSubmitted" value="true"/>
                </div>
            </form>

        <?php } ?>

    </div>
</div>
</body>
</html>