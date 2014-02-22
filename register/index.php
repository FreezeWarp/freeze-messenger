<!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2014 Joseph T. Parsons. -->
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
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/absolution/jquery-ui-1.8.16.custom.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/absolution/fim.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/stylesv2.css" media="screen" />
  <style>
  h1 {
    margin: 0px;
    padding: 5px;
  }
  .main {
    width: 800px;
    margin-left: auto;
    margin-right: auto;
    display: block;
  }

  .ui-widget {
    font-size: 12px;
  }
  .ui-widget-content {
    padding: 5px;
  }
  table.page {
    border: 1px solid black;
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
  <!-- END Scripts -->
</head>

<body class="ui-widget">
<div id="part1" class="main">
  <h1 class="ui-widget-header">FreezeMessenger: User Registration</h1>
  <div class="ui-widget-content">

    <?php
    $stage = (isset($_GET['stage']) ? intval($_GET['stage']) : 1);

    switch ($stage) {
      case 0: case 1:
      require('../global.php');
      require('./phrases.php');
      $lang = 'enGB'; // TODO

      echo $phrases[$lang]['stage1introduction'] . '<br /><br />

      <!-- Javascript is NOT required, but interfaces naturally work better with it. This script, to be stable, must work without it, however. -->
      <script type="text/javascript" src="register.js"></script>

      <form name="register_form" id="register_form" action="index.php?stage=2" method="post">
        <table class="page">
          <tr>
            <td><strong>' . $phrases[$lang]['stage1formUserNameLabel'] . '</strong></td>
            <td><input id="userName" type="text" name="userName" /><br /><small>' . $phrases[$lang]['stage1formUserNameBlurb'] . '</small></td>
          </tr>
          <tr>
            <td><strong>' . $phrases[$lang]['stage1formPasswordLabel'] . '</strong></td>
            <td><input id="password" type="password" name="password" /><br /><small>' . $phrases[$lang]['stage1formPasswordBlurb'] . '</small></td>
          </tr>
          <tr>
            <td><strong>' . $phrases[$lang]['stage1formPasswordAgainLabel'] . '</strong></td>
            <td><input id="passwordConfirm" type="password" /><br /><small>' . $phrases[$lang]['stage1formPasswordAgainBlurb'] . '</small></td>
          </tr>
          <tr>
            <td><strong>' . $phrases[$lang]['stage1formEmailLabel'] . '</strong></td>
            <td><input id="email" type="text" name="email" /><br /><small>' . $phrases[$lang]['stage1formEmailBlurb'] . '</small></td>
          </tr>
          <tr>
            <td><strong>' . $phrases[$lang]['stage1formBirthDateLabel'] . '</strong></td>
            <td>
              <div name="datepicker" id="datepicker"></div>
              <select id="birthday" name="birthday">
                <option value="0"></option>';

                for ($day = 1; $day <= 31; $day++) {
                  echo '<option value=' . $day . '>' . $day . '</option>';
                }

              echo '</select>
              <select id="birthmonth" name="birthmonth">
                <option value="0"></option>
                <option value="1">' . $phrases[$lang]['month01'] . '</option>
                <option value="2">' . $phrases[$lang]['month02']. '</option>
                <option value="3">' . $phrases[$lang]['month03']. '</option>
                <option value="4">' . $phrases[$lang]['month04']. '</option>
                <option value="5">' . $phrases[$lang]['month05']. '</option>
                <option value="6">' . $phrases[$lang]['month06']. '</option>
                <option value="7">' . $phrases[$lang]['month07']. '</option>
                <option value="8">' . $phrases[$lang]['month08']. '</option>
                <option value="9">' . $phrases[$lang]['month09']. '</option>
                <option value="10">' . $phrases[$lang]['month10']. '</option>
                <option value="11">' . $phrases[$lang]['month11']. '</option>
                <option value="12">' . $phrases[$lang]['month12']. '</option>
              </select>
              <select id="birthyear" name="birthyear">
                <option value="0"></option>';

                for ($year = (intval(date('Y')) - ($config['ageMaximum'] + 1)); $year <= (intval(date('Y')) - $config['ageMinimum']); $year++) {
                  echo '<option value=' . $year . '>' . $year . '</option>';
                }

              echo '</select><br />
              <small>' . $phrases[$lang]['stage1formBirthDateBlurb'] . '</small>
            </td>
          </tr>
        </table><br />

        <div style="height: 30px;">
          <input style="float: right;" type="submit" value="Finish &rarr;" />
          <input type="hidden" name="stage" value="2" />
          <input type="hidden" name="passwordEncrypt" value="sha256" />
        </div>
      </form>';
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
