$(document).ready(function() {
    var directory = window.location.pathname.split('/').splice(0, window.location.pathname.split('/').length - 2).join('/') + '/'; // splice returns the elements removed (and modifies the original array), in this case the first two; the rest should be self-explanatory

    $.ajax({
        url: directory + 'api/serverStatus.php',
        type: 'GET',
        timeout: 1000,
        dataType: 'json',
        success: function(json) {
            var registrationPolicies = json.serverStatus.registrationPolicies;

            $('#register_form').submit(function() {
                if ($('#userName').val().length === 0) {
                    dia.error('Please enter a username.');
                }
                else if ($('#password').val().length === 0) {
                    dia.error('Please enter a password.');
                }
                else if ($('#userName').val().length === 0) {
                    dia.error('Please enter a username.');
                }
                else if (registrationPolicies.emailRequired && $('#email').val().length === 0) {
                    dia.error('Please enter an email address.');
                }
                else if (registrationPolicies.ageRequired && ($('#birthday option:selected').val() == 0 || $('#birthmonth option:selected').val() == 0 || $('#birthyear option:selected').val() == 0)) {
                    dia.error('Please enter your date of birth.');
                }
                else if ($('#password').val() !== $('#passwordConfirm').val()) {
                    dia.error('The entered passwords do not match. Please retype them.');
                    $('#passwordConfirm').val('');
                }
                else {
                    $('#password').val(sha256.hex_sha256($('#password').val()));
                    return true;
                }

                return false;
            });
        }
    });
});