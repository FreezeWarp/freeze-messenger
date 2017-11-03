<?php
require('config.php');
if (isset($_REQUEST['g-recaptcha-response'])) {
    $response = Http\CurlRequest::quickRunPOST('https://www.google.com/recaptcha/api/siteverify', [], [
        'secret' => $captchaSecret,
        'response' => $_REQUEST['g-recaptcha-response']
    ]);

    if ($response['success'] == 'true') {
        echo '"success"';
    }
}
else {
?>
<html>
<head>
    <title>reCAPTCHA demo: Explicit render after an onload callback</title>
    <script type="text/javascript">
        var onloadCallback = function() {
            grecaptcha.render('html_element', {
                'sitekey' : '<?php echo $captchaKey; ?>'
            });
        };
    </script>
</head>
<body>
<form action="?" method="POST">
    <div id="html_element"></div>
    <br>
    <input type="submit" value="Submit">
</form>
<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit"
        async defer>
</script>
</body>
</html>
<?php
}
?>