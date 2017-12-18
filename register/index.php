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
    <link
            href="https://maxcdn.bootstrapcdn.com/bootswatch/4.0.0-beta.2/simplex/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-ofc00ja/z8wrU97EAHQRb4i4wsa/Zgr9JZll2R3KW33iqhFSfmVz/6xuWFx5pjcn"
            crossorigin="anonymous">

    <script src="https://code.jquery.com/jquery-3.2.1.min.js"
            integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
            crossorigin="anonymous"></script>
</head>

<body>
<div class="card mx-auto" style="max-width: 800px;">
    <h1 class="card-header">FreezeMessenger: User Registration</h1>
    <div class="card-body">
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
                <table class="table table-striped">
                    <tr>
                        <td style="vertical-align: middle"><strong><?php echo $phrases[$lang]['stage1formUserNameLabel']; ?></strong></td>
                        <td><input id="userName" type="text" name="name" value="<?php echo $_POST['name'] ?? ''; ?>" required /><br/>
                            <small class="text-muted"><?php echo $phrases[$lang]['stage1formUserNameBlurb']; ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle"><strong><?php echo $phrases[$lang]['stage1formPasswordLabel']; ?></strong></td>
                        <td><input id="password" type="password" name="password"/><br/>
                            <small class="text-muted"><?php echo $phrases[$lang]['stage1formPasswordBlurb']; ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle"><strong><?php echo $phrases[$lang]['stage1formPasswordAgainLabel']; ?></strong></td>
                        <td><input id="passwordConfirm" type="password"/><br/>
                            <small class="text-muted"><?php echo $phrases[$lang]['stage1formPasswordAgainBlurb']; ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle"><strong><?php echo $phrases[$lang]['stage1formEmailLabel']; ?></strong></td>
                        <td><input id="email" type="text" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" /><br/>
                            <small class="text-muted"><?php echo $phrases[$lang]['stage1formEmailBlurb']; ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle"><strong><?php echo $phrases[$lang]['stage1formBirthDateLabel']; ?></strong></td>
                        <td>
                            <div name="datepicker" id="datepicker"></div>
                            <?php
                            echo fimHtml_buildSelect('birthDay', array_merge([""], range(1, 31)), $_POST['birthDay'] ?? 0);
                            echo fimHtml_buildSelect('birthMonth', array_merge([""], $phrases[$lang]['months']), $_POST['birthMonth'] ?? 0);
                            echo fimHtml_buildSelect('birthYear', array_combine(
                                array_merge([0], range(intval(date('Y')) - \Fim\Config::$ageMinimum, intval(date('Y')) - 150)),
                                array_merge([""], range(intval(date('Y')) - \Fim\Config::$ageMinimum, intval(date('Y')) - 150))
                            ), $_POST['birthYear'] ?? 0);
                            ?>
                            <br/>
                            <small class="text-muted"><?php echo $phrases[$lang]['stage1formBirthDateBlurb']; ?></small>
                        </td>
                    </tr>
                </table>
                <br/>

                <div class="text-right">
                    <input class="btn btn-success" type="submit" value="Register &rarr;"/>
                    <input type="hidden" name="formSubmitted" value="true"/>
                </div>
            </form>

        <?php } ?>

    </div>
</div>
</body>
</html>