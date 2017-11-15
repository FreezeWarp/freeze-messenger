/**
 * This object is used to handle the "list" interface that is used for adding and removing objects from lists through the interface.
 *
 * @param string id - The video's unique ID.
 *
 * @todo Pictures in dropdowns, updated interface for user lists
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
var autoEntry = function(target, options) {
    this.options = options;
    var _this = this;

    target.append(
        $('<form>').attr('class', 'input-group').append(
            $('<span>').attr('class', 'input-group-btn').append(
                $('<button>').attr({
                    'class' : 'btn btn-success'
                }).text('Add')
            ),

            $('<input>').attr({
                type : "text",
                name : this.options.name + 'Bridge',
                id : this.options.name + 'Bridge',
                class : 'ui-autocomplete-input form-control',
                autocomplete : 'off'
            }).autocompleteHelper(this.options.list)
        ).submit(function() {
            var input = $("input[name=" + _this.options.name + 'Bridge]', this);

            _this.addEntry(input.attr('data-id'), input.val());
            input.val("");

            return false;
        })
    );

    $('<input type="hidden" name="' + this.options.name + '" id="' + this.options.name + '">').insertAfter(target);
    $('<div>').attr('id', this.options.name + 'List').insertAfter(target);

    if ('default' in options) {
        this.displayEntries(options.default);
    }

    return this;
}

autoEntry.prototype = {
    setOnAdd : function(onAdd) {
        this.options.onAdd = onAdd;
    },

    addEntry : function(id, name, suppressEvents) {

        var _this = this;
        var id = id;
        var name = name;

        if (!id && name) {
            var resolver = $.when(this.options.resolveFromNames([name])).then(function(data) {
                id = data[name].id;
            });
        }

        else if (!name && id) {
            var resolver = $.when(this.options.resolveFromIds([id]).then(function(data) {
                name = data[id].name;
            }));
        }

        $.when(resolver).then(function() {
            if (!id) {
                dia.error("Invalid entry.");
            }
            else if ($("span #" + _this.options.name + "SubList" + id).length) {
                dia.info("Duplicate entry.");

                $("#" + _this.options.name + "Bridge").val('');
            }
            else {
                // this whole thing is TODO; I just wanna get a proof-of-concept
                var nameTag = $('<span>');
                if (_this.options.list === "users") {
                    nameTag = fim_buildUsernameTag(nameTag, id, fim_getUsernameDeferred(id), false, true);
                }
                else {
                    nameTag.text(name);
                }

                var avatarTag = false;
                if (_this.options.list === "users") {
                    avatarTag = $('<span>');
                    avatarTag = fim_buildUsernameTag(avatarTag, id, fim_getUsernameDeferred(id), true, false);
                }

                $("#" + _this.options.name).val($("#" + _this.options.name).val() + "," + id);

                $("#" + _this.options.name + "List").append(
                    $("<div>").attr({
                        'id' : _this.options.name + "SubList" + id,
                        'class' : 'input-group input-group-sm m-1',
                        'style' : 'display: inline-block; width: auto; white-space: nowrap'
                    }).append(
                        $('<span>').attr({
                            'class' : 'input-group-btn',
                            'style' : 'display: inline-block; vertical-align: top'
                        }).append(
                            $('<button>').attr({
                                'type' : 'button',
                                'class' : 'btn btn-danger'
                            }).text('×').click(function () {
                                _this.removeEntry(id)
                            })
                        ),

                        $('<span>').attr({
                            'class' : 'input-group-addon',
                            'style' : 'display: inline-block'
                        }).append(nameTag),

                        (avatarTag
                            ? $('<span>').attr({
                                'class' : 'input-group-addon input-group-addon-img',
                                'style' : 'display: inline-block'
                            }).append(avatarTag)
                            : ''
                        )
                    )
                );

                if (!suppressEvents && _this.options.onAdd) _this.options.onAdd(id);
            }
        });
    },

    removeEntry : function(id) {
        var options = this.options;

        $("#" + this.options.name).val($("#" + this.options.name).val().replace(new RegExp("(^|,)" + id + "(,|$)"), "$1$2").replace(/^,|(,),|,$/,'$1'));

        $("#" + this.options.name + "SubList" + id).fadeOut(500, function() {
            $(this).remove();

            if (typeof options.onRemove === 'function') {
                options.onRemove(id);
            }
        });
    },

    displayEntries : function(string) {
        var entryList;

        if (typeof string === 'object') { entryList = string; }
        else if (typeof string === 'string' && string.length > 0) { entryList = string.split(','); } // String is a string and not empty.
        else { entryList = []; }

        var _this = this;
        $.when(this.options.resolveFromIds(entryList)).then(function(entries) {
            for (var i = 0; i < entryList.length; i++) {
                _this.addEntry(entryList[i], entries[entryList[i]].name, true);
            }
        });
    },

    getList : function() {
        return $("#" + this.options.name).val().split(',').filter(Number);
    }
};