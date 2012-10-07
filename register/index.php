<!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<!-- TODO: Localisation for Dates -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Freeze Messenger Registration</title>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="author" content="Joseph T. Parsons" />
  <link rel="icon" id="favicon" type="image/png" href="images/favicon.png" />
  <!--[if lte IE 9]>
  <link rel="shortcut icon" id="faviconfallback" href="images/favicon1632.ico" />
  <![endif]-->

  <!-- START Styles -->
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/start/jquery-ui-1.8.16.custom.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/start/fim.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/stylesv2.css" media="screen" />
  <style>
  h1 {
    margin: 0px;
  }

  .main {
    width: 800px;
    margin-left: auto;
    margin-right: auto;
    display: block;
  }
  </style>
  <!-- END Styles -->

  <!-- START Scripts -->
  <script src="../webpro/client/js/jquery-1.6.2.min.js" type="text/javascript"></script>

  <script src="../webpro/client/js/jquery-ui-1.8.16.custom.min.js" type="text/javascript"></script>
  <script src="../webpro/client/js/jquery.plugins.js" type="text/javascript"></script>
  <script src="../webpro/client/js/encrypt.js" type="text/javascript"></script>
  <script>
  function windowDraw() {
    $('body').css('min-height',window.innerHeight);
  }

  $(document).ready(function() {
    windowDraw();
    $('button, input[type=button], input[type=submit]').button();
  });
  window.onwindowDraw = windowDraw;

  var alert = function(text) {
    dia.info(text,"Alert");
  };
  </script>
  <style>
  .ui-widget {
    font-size: 12px;
  }
  </style>
  <!-- END Scripts -->
</head>
<body class="ui-widget">
<div id="part1" class="main">
  <h1 class="ui-widget-header">FreezeMessenger: User Registration</h1>
  <div class="ui-widget-content">
    <?php
    switch (intval($_GET['stage'])) {
      case 0:
      case 1:
      require('../global.php');
      require('./phrases.php');
      $lang = 'enGB'; // TODO
      ?>
      Here you can register for a FreezeMessenger account easily.<br /><br />

      <!-- Javascript is NOT required, but interfaces naturally work better with it. This script, to be stable, must work without it, however. -->
      <script type="text/javascript" src="register.js"></script>

      <form name="register_form" id="register_form" action="index.php?stage=2" method="post">
        <table border="1" class="page">
          <tr>
            <td><strong>Username</strong></td>
            <td><input id="userName" type="text" name="userName" /><br /><small>This name will be displayed whenever you make a post.</small></td>
          </tr>
          <tr>
            <td><strong>Password</strong></td>
            <td><input id="password" type="password" name="password" /><br /><small>Your password must be between 4 and 100 characters</small></td>
          </tr>
          <tr>
            <td><strong>Password (Again)</strong></td>
            <td><input id="passwordConfirm" type="password" /><br /><small>Retype your password to confirm its accuracy</small></td>
          </tr>
          <tr>
            <td><strong>Email</strong></td>
            <td><input id="email" type="text" name="email" /><br /><small>Retype your password to confirm its accuracy</small></td>
          </tr>
          <tr>
            <td><strong>Date of Birth</strong></td>
            <td>
              <div name="datepicker" id="datepicker"></div>
              <select id="birthday" name="birthday">
                <option value="0"></option>
                <?php
                for ($day = 1; $day <= 31; $day++) {
                  echo '<option value=' . $day . '>' . $day . '</option>';
                }
                ?>
              </select>
              <select id="birthmonth" name="birthmonth">
                <option value="0"></option>
                <option value="1"><?php echo $phrases[$lang]['month01']; ?></option>
                <option value="2"><?php echo $phrases[$lang]['month02']; ?></option>
                <option value="3"><?php echo $phrases[$lang]['month03']; ?></option>
                <option value="4"><?php echo $phrases[$lang]['month04']; ?></option>
                <option value="5"><?php echo $phrases[$lang]['month05']; ?></option>
                <option value="6"><?php echo $phrases[$lang]['month06']; ?></option>
                <option value="7"><?php echo $phrases[$lang]['month07']; ?></option>
                <option value="8"><?php echo $phrases[$lang]['month08']; ?></option>
                <option value="9"><?php echo $phrases[$lang]['month09']; ?></option>
                <option value="10"><?php echo $phrases[$lang]['month10']; ?></option>
                <option value="11"><?php echo $phrases[$lang]['month11']; ?></option>
                <option value="12"><?php echo $phrases[$lang]['month12']; ?></option>
              </select>
              <select id="birthyear" name="birthyear">
                <option value="0"></option>
                <?php
                for ($year = (intval(date('Y')) - ($config['ageMaximum'] + 1)); $year <= (intval(date('Y')) - $config['ageMinimum']); $year++) {
                  echo '<option value=' . $year . '>' . $year . '</option>';
                }
                ?>
              </select>
              <small>Select your month, year, and day of birth in the above calendar. (Note that if your year does not appear, you are not allowed to register.)</small>
            </td>
          </tr>
        </table>

        <div style="height: 30px;">
          <input style="float: right;" type="submit" value="Finish &rarr;" />
          <input type="hidden" name="stage" value="2" />
          <input type="hidden" name="passwordEncrypt" value="sha256" />
        </div>
      </form><br /><br />
      <?php
      break;
      case 2:
      require('../config.php'); // We do NOT want to require global for a couple of reasons, the biggest one being this file simply doesn't require it. All CURL requests require config.php, however.
      require('../functions/fim_curl.php');
//echo mktime(null, null, null, $_POST['birthmonth'], $_POST['birthday'], $_POST['birthyear']); die();
      $crA = array(
        'apiVersion' => '3',
        'passwordEncrypt' => 'sha256',
        'userName' => $_POST['userName'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
      );

      if ($_POST['birthmonth'] && $_POST['birthday'] && $_POST['birthyear']) { // Only send a birthdate if provided. We wouldn't normally do it this way, but because older persons will have a negative unix timestamp, we have to omit the birthyear rather than provide a value of "0".
        $crA['birthdate'] = mktime(null, null, null, $_POST['birthmonth'], $_POST['birthday'], $_POST['birthyear']);
      }

      $cr = new curlRequest($crA, '/api/sendUser.php');
      $result = json_decode($cr->execute(), true);

      if (!$result) {
        echo 'The request could not be completed. (Server Error)';
      }
      elseif ($result['sendUser']['errStr']) {
        echo '<form action="" onsubmit="window.history.back(); return false;" action="./index.php?stage=2">Error "' . $result['sendUser']['errStr'] . '": ' . $result['sendUser']['errDesc'] . '<br /><br /><input type="submit" value="Go back." /></form>';
      }
      else {
        echo 'You are now registered as "' . $result['sendUser']['activeUser']['userName'] . '".<br /><br /><a href="../">Return to chat interface.</a>';
      }
    }


?>

  </div>
</div>


<div id="part4" style="display: none;" class="main">
  <h1 class="ui-widget-header">Freezemessenger Registration: All Done!</h1>
  <div class="ui-widget-content">
    You have now registered. Click here to
  </div>
</div>
</body>
</html>
