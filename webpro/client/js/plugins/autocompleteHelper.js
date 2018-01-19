jQuery.fn.extend({
    autocompleteHelper: function (resourceName, defaultId) {
        var _this = $('<input class="js-typeahead form-control" type="search" autocomplete="off" />');

        if (this.attr('name'))
            _this.attr('name', this.attr('name'));
        if (this.attr('id'))
            _this.attr('id', this.attr('id'));

        var container = $('<div class="typeahead__container form-control-container">').append(
            $('<div class="typeahead__field">').append(
                $('<span class="typeahead__query">').append(
                    _this
                )
            )
        );

        this.replaceWith(container);

        _this.typeahead({
            display: ['name'],
            order: 'asc',
            minLength: 1,
            dynamic: true,
            source: {
                users: {
                    ajax: {
                        url: fimApi.directory + 'api/acHelper.php',
                        data: {
                            'access_token': fimApi.lastSessionHash,
                            'list': resourceName,
                            'search': '{{query}}'
                        },
                        path: 'entries'
                    }
                }
            },
            template: function (query, item) {
                if (item.avatar)
                    return '<span class="userName userNameAvatar"><img src="{{avatar}}" style="max-width: 20px; max-height: 20px;"/> <span>{{name}}</span></span>';
                else
                    return '<span>{{name}}</span>';
            },
            callback: {
                onClick: function (node, a, item, event) {
                    $(node).val(item.name);
                    $(node).attr('data-id', item.id);
                    $(node).attr('data-value', item.name);
                    $(node).removeClass('is-invalid');

                    $(node).trigger('autocompleteChange').change();
                },
                onCancel: function (node, event) {
                    $(node).attr('data-id', '');
                    $(node).attr('data-value', '');
                    $(node).removeClass('is-invalid');

                    $(node).trigger('autocompleteChange').change();
                }
            }
        });
        _this.on('change', function (event) {
            console.log('change', event)
        });


        _this.off('keyup.autocompleteHelper').on('keyup.autocompleteHelper', function (event) {
            if ($(event.target).attr('data-value') != $(event.target).val()) {
                $(event.target).removeClass('is-invalid');
                $(event.target).attr('data-id', '');
                $(event.target).attr('data-value', '');
            }
        });


        function resolveInput(target, callback) {
            if (!target.val()) {
                target.attr('data-id', '');
                target.attr('data-value', '');

                target.removeClass('is-invalid');

                target.trigger('autocompleteChange');

                if (callback) callback();//
            }
            else {
                $.when(Resolver.resolveFromName(resourceName, target.val())).then(function (pairs) {
                    if (pairs[target.val()]) {
                        target.attr('data-value', pairs[target.val()].name);
                        target.attr('data-id', pairs[target.val()].id);

                        target.removeClass('is-invalid');

                        target.trigger('autocompleteChange');

                        if (callback) callback();
                        //target.popover('dispose');
                    }
                    else {
                        target.attr('data-id', '');
                        target.attr('data-value', '');

                        target.addClass('is-invalid');

                        if (callback) callback();
                    }
                });
            }
        }


        // Catch form submissions in order to resolve manually-inputted data
        _this.closest('form').off('submit.autocompleteHelper').on('submit.autocompleteHelper', function (event) {

            if (_this.val() && !(_this.attr('data-id')) && !_this.hasClass('is-invalid')) {
                console.log("fetcher invalid");
                event.stopImmediatePropagation();

                // Refire the submit when we've resolved the text.
                resolveInput(_this, function () {
                    // Re-trigger the event
                    $(event.target).trigger('submit');
                });

                // Prevent the event from continuing (since we have to wait on a promise before we can finish this callback)
                return false;
            }

            return true;
        });


        // Catch change events in order to resolve manually-inputted data
        /*_this.off('change.autocompleteHelper').on('change.autocompleteHelper', function(event) {
            resolveInput($(event.target));
        });*/


        // Set the initial value of the form field, if needed
        if (defaultId) {
            $.when(Resolver.resolveFromId(resourceName, defaultId)).then(function (pairs) {
                _this.val(pairs[defaultId].name);
                _this.attr('data-value', pairs[defaultId].name);
                _this.attr('data-id', defaultId);
            });
        }
        else {
            _this.val('');
            _this.attr('data-id', '');
            _this.attr('data-value', '');
        }


        return _this;
    }
});