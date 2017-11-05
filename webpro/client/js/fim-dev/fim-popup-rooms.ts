declare var fimApi: any;
declare var $: any;
declare var messageIndex: any;
declare var directory: any;
declare var dia: any;
declare var fim_buildUsernameTag : any;
declare var fim_getUsernameDeferred : any;
declare var fim_setHashParameter : any;
declare var fim_getHandlebarsPhrases : any;
declare var windowDraw : any;
declare var Handlebars : any;

popup.prototype.rooms = function() {
    this.options = {
        roomNameSearch : null,
        page : null,
        permFilter : null
    };

    this.entryTemplate = Handlebars.compile($('#view-rooms-row').html());

    return;
}

popup.prototype.rooms.prototype.init = function(options) {
    // Defaults
    this.setPage(0);
    this.setPermFilter('view');
    this.setRoomNameSearch('');

    $('form#roomSearch [name=roomNameSearch], form#roomSearch [name=permFilter]').unbind('change').bind('change', function() {
        fim_setHashParameter($(this).attr('name'), $(this).val());
    });

    $('#active-view-rooms button[name=roomListNext]').unbind('click').bind('click', (() => {
        fim_setHashParameter('page', (Number(this.options.page)) + 1)
    }));

    $('#active-view-rooms button[name=roomListPrev]').unbind('click').bind('click', (() => {
        fim_setHashParameter('page', this.options.page - 1)
    }));
};

popup.prototype.rooms.prototype.setPermFilter = function(permFilter) {
    if (this.options.permFilter !== permFilter) {
        this.options.permFilter = permFilter;
        $('form#roomSearch [name=permFilter]').val(permFilter);
    }
};

popup.prototype.rooms.prototype.setRoomNameSearch = function(roomNameSearch) {
    if (this.options.roomNameSearch !== roomNameSearch) {
        this.options.roomNameSearch = roomNameSearch;
        $('form#roomSearch [name=roomNameSearch]').val(roomNameSearch);
    }
};

popup.prototype.rooms.prototype.setPage = function(page) {
    if (this.options.page !== page) {
        this.options.page = page;

        if (this.options.page <= 0) {
            this.options.page = 0;
            $('#active-view-rooms button[name=roomListPrev]').attr('disabled', true);
        }
        else {
            $('#active-view-rooms button[name=roomListPrev]').removeAttr('disabled');
        }
    }
};

popup.prototype.rooms.prototype.retrieve = function() {
    $('#roomTableHtml').html('');

    fimApi.getRooms(this.options, {
        'each' : ((roomData) => {
            $('#roomTableHtml').append(this.entryTemplate(fim_getHandlebarsPhrases(roomData)))
        }),
        'end' : ((rooms, metadata) => {
            if (Object.keys(rooms).length == 0) {
                $('#active-view-rooms button[name=roomListNext]').attr('disabled', true);

                $('#roomTableHtml').append('<tr><td colspan="3">No Results Found</td></tr>');
            }
            else {
                $('#active-view-rooms button[name=roomListNext]').removeAttr('disabled');
                $('#active-view-rooms button[name=roomListNext]').unbind('click').bind('click', (() => {
                    fim_setHashParameter('page', (Number(metadata.nextPage)));
                }));
            }
        })
    });
};

popup.prototype.rooms.prototype.update = function (option, value) {
    this.options[option] = value;
};
