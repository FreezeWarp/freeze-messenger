popup.prototype.rooms = function () {
    this.options = {
        roomNameSearch: null,
        page: null,
        permFilter: null
    };
    this.entryTemplate = Handlebars.compile($('#view-rooms-row').html());
    return;
};
popup.prototype.rooms.prototype.init = function (options) {
    var _this = this;
    // Defaults
    this.setPage(0);
    this.setPermFilter('view');
    this.setRoomNameSearch('');
    $('form#roomSearch [name=roomNameSearch], form#roomSearch [name=permFilter]').unbind('change').bind('change', function () {
        fim_setHashParameter($(this).attr('name'), $(this).val());
    });
    $('#active-view-rooms button[name=roomListNext]').unbind('click').bind('click', (function () {
        fim_setHashParameter('page', (Number(_this.options.page)) + 1);
    }));
    $('#active-view-rooms button[name=roomListPrev]').unbind('click').bind('click', (function () {
        fim_setHashParameter('page', _this.options.page - 1);
    }));
};
popup.prototype.rooms.prototype.setPermFilter = function (permFilter) {
    if (this.options.permFilter !== permFilter) {
        this.options.permFilter = permFilter;
        $('form#roomSearch [name=permFilter]').val(permFilter);
    }
};
popup.prototype.rooms.prototype.setRoomNameSearch = function (roomNameSearch) {
    if (this.options.roomNameSearch !== roomNameSearch) {
        this.options.roomNameSearch = roomNameSearch;
        $('form#roomSearch [name=roomNameSearch]').val(roomNameSearch);
    }
};
popup.prototype.rooms.prototype.setPage = function (page) {
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
popup.prototype.rooms.prototype.retrieve = function () {
    var _this = this;
    $('#roomTableHtml').html('');
    fimApi.getRooms(this.options, {
        'each': (function (roomData) {
            $('#roomTableHtml').append(_this.entryTemplate(fim_getHandlebarsPhrases(roomData)));
        }),
        'end': (function (rooms, metadata) {
            if (Object.keys(rooms).length == 0) {
                $('#active-view-rooms button[name=roomListNext]').attr('disabled', true);
                $('#roomTableHtml').append('<tr><td colspan="3">No Results Found</td></tr>');
            }
            else {
                $('#active-view-rooms button[name=roomListNext]').removeAttr('disabled');
                $('#active-view-rooms button[name=roomListNext]').unbind('click').bind('click', (function () {
                    fim_setHashParameter('page', (Number(metadata.nextPage)));
                }));
            }
        })
    });
};
popup.prototype.rooms.prototype.update = function (option, value) {
    this.options[option] = value;
};
//# sourceMappingURL=fim-popup-rooms.js.map