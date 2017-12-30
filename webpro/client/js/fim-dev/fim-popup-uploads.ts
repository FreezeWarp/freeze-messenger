declare var fimApi: any;
declare var $: any;
declare var messageIndex: any;
declare var directory: any;
declare var dia: any;
declare var fim_buildUsernameTag : any;
declare var fim_getUsernameDeferred : any;
declare var fim_setHashParameter : any;
declare var fim_getHandlebarsPhrases : any;
declare var Handlebars : any;
declare var popup : any;

popup.prototype.uploads = function() {
    this.options = {
        page : null,
    };

    this.entryTemplate = Handlebars.compile($('#view-uploads-row').html());

    return;
}

popup.prototype.uploads.prototype.init = function(options) {
    // Defaults
    this.setPage(0);

    $('#active-view-uploads button[name=nextPage]').unbind('click').bind('click', (() => {
        fim_setHashParameter('page', Number(this.options.page) + 1)
    }));

    $('#active-view-uploads button[name=prevPage]').unbind('click').bind('click', (() => {
        fim_setHashParameter('page', Number(this.options.page) - 1)
    }));
};

popup.prototype.uploads.prototype.setPage = function(page) {
    if (this.options.page !== page) {
        console.log("set page 2", page, this.options);
        this.options.page = page;

        if (this.options.page <= 0) {
            console.log("zero page", page);
            this.options.page = 0;
            $('#active-view-uploads button[name=prevPage]').attr('disabled', true);
        }
        else {
            $('#active-view-uploads button[name=prevPage]').removeAttr('disabled');
        }
    }
};

popup.prototype.uploads.prototype.retrieve = function() {
    $('#uploadsTableBody').html('');

    fimApi.getFiles({
        userIds : [window.userId],
        page : this.options.page
    }, {
        each: (fileData) => {
            console.log("file data", fileData);
            let userTag = $('<span>'),
                userTagPromise = fim_buildUsernameTagPromise(userTag, fileData.userId, fim_getUsernameDeferred(fileData.userId)),
                roomTag = $('<span>'),
                roomTagPromise = fim_buildRoomNameTagPromise(roomTag, fileData.roomId, fim_getRoomNameDeferred(fileData.roomId));

            $.when(userTagPromise, roomTagPromise).then(() => {
                $('#uploadsTableBody').append(this.entryTemplate(fim_getHandlebarsPhrases({fileData : fileData, userTag : userTag, roomTag : roomTag})));
            });

        },
        end : (files, metadata) => {
            if (Object.keys(files).length == 0) {
                $('#active-view-uploads button[name=roomListNext]').attr('disabled', true);

                $('#uploadsTableBody').html(
                    Handlebars.compile($('#view-uploads-emptyResultSet').html())(fim_getHandlebarsPhrases())
                );
            }
            else {
                $('#active-view-uploads button[name=roomListNext]').removeAttr('disabled');
            }
        }
    });
};