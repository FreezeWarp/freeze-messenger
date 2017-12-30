declare var fimApi: any;
declare var $: any;
declare var $l: any;
declare var jQuery: any;
declare var fim_messagePreview: any;
declare var directory: any;
declare var dia: any;
declare var fim_buildUsernameTag : any;
declare var fim_buildUsernameTagPromise : any;
declare var fim_getUsernameDeferred : any;
declare var fim_getRoomNameDeferred : any;
declare var fim_buildRoomNameTagPromise : any;
declare var fim_renderHandlebarsInPlace : any;
declare var fim_getHandlebarsPhrases : any;
declare var Handlebars : any;

interface popup {
    kicks : Object
};

popup.prototype.kicks = function() {
    this.options = {
        userId : null,
        roomId : null
    };

    this.entryTemplate = Handlebars.compile($('#view-kicks-row').html());
};

popup.prototype.kicks.prototype.init = function() {
};

popup.prototype.kicks.prototype.retrieve = function() {
    jQuery.each(['user', 'room'], (index, attr) => {
        $('#active-view-kicks form#kicksSearch input[name=' + attr + ']')
            .autocompleteHelper(attr + 's', this.options[attr + "Id"])
            .off('autocompleteChange')
            .on('autocompleteChange', function () {
                console.log('change event');
                fim_setHashParameter(attr, $(this).attr('data-id'));
            })
        ;
    });

    $('#active-view-kicks table > tbody').html('');

    fimApi.getKicks(this.options, {
        'each' : (kick) => {
            var userTag = $('<span>'),
                userTagPromise = fim_buildUsernameTagPromise(userTag, kick.userId, fim_getUsernameDeferred(kick.userId));

            jQuery.each(kick.kicks, (kickId, kickData) => {
                var kickerTag = $('<span>'),
                    kickerTagPromise = fim_buildUsernameTagPromise(kickerTag, kickData.kickerId, fim_getUsernameDeferred(kickData.kickerId)),
                    roomTag = $('<span>'),
                    roomTagPromise = fim_buildRoomNameTagPromise(roomTag, kickData.roomId, fim_getRoomNameDeferred(kickData.roomId));

                $.when(userTagPromise, kickerTagPromise, roomTagPromise).then(() => {
                    $('#kickedUsers').append(this.entryTemplate(fim_getHandlebarsPhrases({kick : Object.assign({}, kick, kickData), userTag : userTag, kickerTag : kickerTag, roomTag : roomTag})));
                });
            });
        }
    });
};

popup.prototype.kicks.prototype.setRoom = function(room) {
    if (!room) {
        this.options.roomId = null;
    }
    else if (this.options.roomId !== room) {
        this.options.roomId = room;
    }
};

popup.prototype.kicks.prototype.setUser = function(user) {
    if (!user) {
        this.options.userId = null;
    }
    else if (this.options.roomId !== user) {
        this.options.userId = user;
    }
};