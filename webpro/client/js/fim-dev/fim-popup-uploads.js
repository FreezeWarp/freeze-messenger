popup.prototype.uploads = function () {
    this.options = {
        page: null
    };
    this.entryTemplate = Handlebars.compile($('#view-uploads-row').html());
    return;
};
popup.prototype.uploads.prototype.init = function (options) {
    var _this = this;
    // Defaults
    this.setPage(0);
    $('#active-view-uploads button[name=nextPage]').unbind('click').bind('click', (function () {
        fim_setHashParameter('page', Number(_this.options.page) + 1);
    }));
    $('#active-view-uploads button[name=prevPage]').unbind('click').bind('click', (function () {
        fim_setHashParameter('page', Number(_this.options.page) - 1);
    }));
};
popup.prototype.uploads.prototype.setPage = function (page) {
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
popup.prototype.uploads.prototype.retrieve = function () {
    var _this = this;
    $('#uploadsTableBody').html('');
    fimApi.getFiles({
        userIds: [window.userId],
        page: this.options.page
    }, {
        each: function (fileData) {
            $('#uploadsTableBody').append(_this.entryTemplate(fim_getHandlebarsPhrases({ fileData: fileData })));
        },
        end: function (files, metadata) {
            if (Object.keys(files).length == 0) {
                $('#active-view-uploads button[name=roomListNext]').attr('disabled', true);
                $('#uploadsTableBody').html(Handlebars.compile($('#view-uploads-emptyResultSet').html())(fim_getHandlebarsPhrases()));
            }
            else {
                $('#active-view-uploads button[name=roomListNext]').removeAttr('disabled');
            }
        }
    });
};
//# sourceMappingURL=fim-popup-uploads.js.map