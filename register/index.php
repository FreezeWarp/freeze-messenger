<!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<!-- Note: Installation Backend @ Worker.php -->
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
    Here you can register for a FreezeMessenger account easily.<br /><br />

    <form onsubmit="return false;" name="register_form" id="register_form">
      <table border="1" class="page">
        <tr>
          <td><strong>Username</strong></td>
          <td><input type="text" name="register_userName" /><br /><small>This name will be displayed whenever you make a post.</small></td>
        </tr>
        <tr>
          <td><strong>Password</strong></td>
          <td><input id="password" type="password" name="register_password" /><br /><small>Your password must be between 4 and 100 characters</small></td>
        </tr>
        <tr>
          <td><strong>Password (Again)</strong></td>
          <td><input id="passwordConfirm" type="password" name="register_passwordConfirm" /><br /><small>Retype your password to confirm its accuracy</small></td>
        </tr>
        <tr>
          <td><strong>Email</strong></td>
          <td><input id="email" type="text" name="register_email" /><br /><small>Retype your password to confirm its accuracy</small></td>
        </tr>
        <tr>
          <td><strong>Date of Birth</strong></td>
          <td>
            <div name="datepicker" id="datepicker"></div>
            <script type="text/javascript">
            $(document).ready(function() {
              var date = new Date();
              
              $("#datepicker").datepicker({
                changeMonth: true,
                changeYear: true,
                yearRange: "1900:" + date.getFullYear(),
                onChangeMonthYear: function(year, month, inst) {
                  $("#datepicker").datepicker('setDate',month + '/01/' + year);
                }
              });
            });
            </script>
            <small>Select your month, year, and day of birth in the above calendar.</small>
          </td>
        </tr>    
      </table>
    </form><br /><br />

    <div style="height: 30px;">
      <form onsubmit="return false;">
        <button style="float: left;" type="button" onclick="$('#part3').slideUp(); $('#part2').slideDown(); windowDraw();">&larr; Back</button>
        <button style="float: right;" type="button" onclick="if ($('#password').val() == $('#passwordConfirm').val()) { $.get('./worker.php?phase=2',$('#db_connect_form').serialize() + '&' + $('#config_form').serialize(),function(data) { if (data == 'success') { $('#part3').slideUp(); $('#part4').slideDown(); } else { dia.error(data); } } ); windowDraw(); } else { dia.error('Passwords do not match.'); } ">Finish &rarr;</button>
      </form>
    </div>
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
