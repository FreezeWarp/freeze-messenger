/**
 * This object is used to handle the "list" interface that is used for adding and removing objects from lists through the interface.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


/**
 * Creates an "entry list" form control, which contains a box for adding new entries, and separate controls listing every element in the list with a button for removing each element from the list.
 *
 * @param target The jQuery tag that the entry list will be created in.
 * @param {Object} options A list of options to use.
 * @param {Array} options.default A list of the IDs belonging to the default list entries.
 *
 * @returns {autoEntry}
 */
let autoEntry = function(target, options) {
    this.options = options;

    if (!this.options.name)
        throw new Error("No name provided.");

    // Create the autocomplete input element (which will be bound with the autocomplete helper only once it's inside the <form>)
    this.autocompleteInput = $('<input>').attr({
        type : "text",
        name : this.options.name + 'Bridge',
        id : this.options.name + 'Bridge',
        'class' : 'ui-autocomplete-input form-control',
        autocomplete : 'off'
    });

    // Create the submit button
    this.autocompleteSubmit = $('<button>').attr({
        'class' : 'btn btn-success'
    }).text('Add');

    // Create the form containing the entry list
    this.autocompleteForm = $('<form>').attr('class', 'input-group').append(
        $('<span>').attr('class', 'input-group-prepend').append(this.autocompleteSubmit),
        this.autocompleteInput
    );

    // Bind the autocomplete helper only once the input is inside of the <form> DOM element
    this.autocompleteInput.autocompleteHelper(this.options.list);

    // Bind the form submit event
    this.autocompleteForm.submit((event) => {
        if (!this.autocompleteInput.attr('data-id')) {
            $('button', event.target).popover({
                placement : "bottom",
                content : "Invalid Entry",
                trigger : "manual"
            }).popover('show');

            setTimeout(() => {
                $('button', event.target).popover('dispose')
            }, 1000);
        }
        else {
            this.addEntry(this.autocompleteInput.attr('data-id'), this.autocompleteInput.attr('data-value'));
            this.autocompleteInput.val("");
        }

        return false;
    });

    // Add the entry adder to the target
    target.append(this.autocompleteForm);

    // Add the field itself, which will store the IDs of the entry list as a comma-separated list
    this.autocompleteValue = $('<input type="hidden" name="' + this.options.name + '" id="' + this.options.name + '">');
    this.autocompleteValue.insertAfter(target);

    // Add the "list" div, which will display each list element
    this.autocompleteDiv = $('<div>').attr('id', this.options.name + 'List');
    this.autocompleteDiv.insertAfter(target);

    // Display the default entries, if available
    if ('default' in options) {
        this.displayEntries(options['default']);
    }

    // Return the list for chaining
    return this;
};


autoEntry.prototype = {
    /**
     * Register/replace the callback to use when a new entry is added.
     *
     * @param {function} onAdd
     */
    setOnAdd : function(onAdd) {
        this.options.onAdd = onAdd;
    },


    /**
     * Add a new entry to the list.
     *
     * @param id The ID of the entry.
     * @param name The name of the entry.
     * @param suppressEvents If true, the onAdd event will not be triggered.
     */
    addEntry : function(id : Number, name : String, suppressEvents = false) {

        let resolver;
        if (!id && name) {
            resolver = $.when(this.options.resolveFromNames([name])).then(function(data) {
                id = data[name].id;
            });
        }

        else if (!name && id) {
            resolver = $.when(this.options.resolveFromIds([id]).then(function(data) {
                name = data[id].name;//
            }));
        }

        $.when(resolver).then(() => {
            if (!id) {
                dia.error("Invalid entry.");//
            }

            else if ($("#" + this.options.name + "SubList" + id).length) {
                this.autocompleteSubmit.popover({
                    placement : "bottom",
                    content : "Duplicate Entry",
                    trigger : "manual"
                }).popover('show');

                setTimeout(function() {
                    this.autocompleteSubmit.popover('dispose')
                }, 1000);

                this.autocompleteInput.val('');
            }

            else {
                let usernameDeferred = fim_getUsernameDeferred(id);

                let nameTag = $('<span class="input-group-text">');
                if (this.options.list === "users") {
                    nameTag = fim_buildUsernameTag(nameTag, id, usernameDeferred, false, false, true);
                }
                else {
                    nameTag.text(name);
                }

                let avatarTag = false;
                if (this.options.list === "users") {
                    avatarTag = $('<span>');
                    avatarTag = fim_buildUsernameTag(avatarTag, id, usernameDeferred, false, true, false);
                }

                this.autocompleteValue.val(this.autocompleteValue.val() + "," + id);

                this.autocompleteDiv.append(
                    $("<div>").attr({
                        'id' : this.options.name + "SubList" + id,
                        'class' : 'input-group input-group-sm m-1',
                        'style' : 'width: auto; white-space: nowrap'
                    }).append(
                        $('<span>').attr({
                            'class' : 'input-group-prepend',
                            'style' : 'display: inline-block; vertical-align: top'
                        }).append(
                            $('<button>').attr({
                                'type' : 'button',
                                'class' : 'btn btn-danger'
                            }).text('×').click(() => {
                                this.removeEntry(id)
                            })
                        ),

                        $('<span>').attr({
                            'class' : 'input-group-append',
                        }).append(nameTag),

                        (avatarTag
                                ? $('<span>').attr({
                                    'class' : 'input-group-append input-group-addon-img'
                                }).append(avatarTag)
                                : ''
                        )
                    )
                );

                if (!suppressEvents && this.options.onAdd) this.options.onAdd(id);
            }
        });
    },


    /**
     * Remove the given ID from the list.
     *
     * @param id
     */
    removeEntry : function(id) {
        let options = this.options;

        this.autocompleteValue.val(this.autocompleteValue.val().replace(new RegExp("(^|,)" + id + "(,|$)"), "$1$2").replace(/^,|(,),|,$/,'$1'));

            $("#" + this.options.name + "SubList" + id).fadeOut(500, function() {
            $(this).remove();

            if (typeof options.onRemove === 'function') {
                options.onRemove(id);
            }
        });
    },


    /**
     * Add a list of new entries to the entry list.
     *
     * @param string The list of entries, either an Array or a comma-separated string.
     */
    displayEntries : function(string) {
        let entryList;

        if (typeof string === 'object')
            entryList = string;

        // If non-empty string, build from comma-separated list
        else if (typeof string === 'string' && string.length > 0)
            entryList = string.split(',');

        else
            entryList = [];

        $.when(this.options.resolveFromIds(entryList)).then((entries) => {
            for (let i = 0; i < entryList.length; i++) {
                this.addEntry(entryList[i], entries[entryList[i]].name, true);
            }
        });
    },


    /**
     * @returns {Array} The list of IDs currently in the entry list.
     */
    getList : function() {
        return this.autocompleteValue.val().split(',').filter(Number);
    }
};