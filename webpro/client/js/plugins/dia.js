/* Start Dia -- Simplified jQueryUI Dialogues
 * Joseph T. Parsons
 * http://www.gnu.org/licenses/gpl.html */
var dia = {
    exception: function(exception) {
        $('#modal-dynamicException .modal-title').html('Exception: ' + exception.string);
        $('#modal-dynamicException .modal-body').html(exception.details);
        $('#modal-dynamicException').modal();

        console.log("Stack trace for " + exception.string + ":");
        console.log(exception.trace);
    },

    error: function (message) {
        console.log('Error: ' + message);

        $('#modal-dynamicError .modal-body').html(message);
        $('#modal-dynamicError').modal();
    },

    info: function (message, type) {
        $.notify({
            message : message
        }, {
            type : (type ? type : "info"),
            placement: {
                from : 'top',
                align : 'center',
            },
            delay : 3000,
        });
    },

    confirm: function (options, title) {
        $('#modal-dynamicConfirm .modal-title').text(title);
        $('#modal-dynamicConfirm .modal-body').html(options.text);

        $('#modal-dynamicConfirm button[name=confirm]').off('click').on('click', function() {
            if (typeof options['true'] !== 'undefined') options['true']();

            $('#modal-dynamicConfirm').modal('hide');
        });

        $('#modal-dynamicConfirm button[name=cancel]').off('click').on('click', function() {
            if (typeof options['false'] !== 'undefined') options['false']();

            $('#modal-dynamicConfirm').modal('hide');
        });

        $('#modal-dynamicConfirm').modal();
    },

    // Supported options: autoShow (true), id, content, width (600), oF, cF
    full: function (options) {
        $('#modal-dynamicFull .modal-title').text(options.title);
        $('#modal-dynamicFull .modal-body').html(options.content);

        $('#modal-dynamicFull').modal();

        $('#modal-dynamicFull .modal-footer').html('');
        jQuery.each(options.buttons, function(buttonName, buttonAction) {
            $('#modal-dynamicFull .modal-footer').append($('<button>').attr('class', 'btn btn-secondary').text(buttonName).click(function() {
                buttonAction();
                $('#modal-dynamicFull').modal('hide');
                return false;
            }));
        });

        if (typeof options.oF !== 'undefined') options.oF();

        if (typeof options.cF !== 'undefined') {
            $('#modal-dynamicFull').on('hidden.bs.modal', function () {
                options.cF();
            });
        }
    }
};