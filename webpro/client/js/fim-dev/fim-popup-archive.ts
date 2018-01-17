declare var fimApi: any;
declare var $: any;
declare var $l: any;
declare var jQuery: any;
declare var fim_messagePreview: any;
declare var directory: any;
declare var dia: any;
declare var fim_buildUsernameTag : any;
declare var fim_getUsernameDeferred : any;
declare var fim_messageFormat : any;
declare var fim_renderHandlebarsInPlace : any;
declare var fim_atomicRemoveHashParameterSetHashParameter : any;
declare var fim_buildMessageLine : any;
declare var fim_dateFormat : any;
declare var Debounce: any;
declare var EventSource : any;

popup.prototype.archive = {
    options : {
        searchText : '',
        resultLimit : 40,
        searchUser : 0,
        firstMessage : null,
        page : 0,
        roomId : 0
    },

    messageData : {},

    init : function(options) {
        for (i in options)
            this.options[i] = options[i];


        $('#active-view-archive form#archiveSearch input[name=searchText]').unbind('change').bind('change', (event) => {
            this.update('searchText', $(event.target).val());
            this.retrieve();
        });

        $('#active-view-archive form#archiveSearch input[name=searchUser]').unbind('change').bind('change', (event) => {
            this.update('searchUser', $(event.target).attr('data-id'));
            this.retrieve();
        }).autocompleteHelper('users');


        $('#active-view-archive button[name=archiveNext]').unbind('click').bind('click', () => {
            this.nextPage();
        });
        $('#active-view-archive button[name=archivePrev]').unbind('click').bind('click', () => {
            this.prevPage();
        });

        $('#active-view-archive button[name=export]').unbind('click').bind('click', () => {
            popup.exportArchive();
        });


        this.retrieve();
    },

    setRoom : function(roomId) {
        if (this.options.roomId != roomId) {
            this.options.roomId = roomId;
            this.retrieve();
        }
    },

    setFirstMessage : function(firstMessage) {
        this.options.firstMessage = firstMessage;
        this.options.lastMessage = null;
        this.retrieve();
    },

    setLastMessage : function(lastMessage) {
        this.options.lastMessage = lastMessage;
        this.options.firstMessage = null;
        this.retrieve();
    },

    nextPage : function () {
        fim_atomicRemoveHashParameterSetHashParameter('firstMessage', 'lastMessage', $('#active-view-archive table.messageTable tr:last-child > td > span.messageText').attr('data-messageid'));
    },

    prevPage : function () {
        fim_atomicRemoveHashParameterSetHashParameter('lastMessage', 'firstMessage', $('#active-view-archive table.messageTable tr:first-child > td > span.messageText').attr('data-messageid'));
    },

    retrieve : function() {
        fimApi.getMessages({
            'roomId' : this.options.roomId,
            'userIds' : [this.options.searchUser],
            'messageTextSearch' : this.options.searchText,
            'messageIdStart' : this.options.firstMessage,
            'messageIdEnd' : this.options.lastMessage,
            'page' : this.options.page
        }, {
            'reverseEach' : (this.options.firstMessage ? true : false),
            'end' : (messages) => {
                $('#active-view-archive table.messageTable > tbody').html('');
                $('#active-view-archive button[name=archivePrev]').prop('disabled', false);

                this.messageData = {};

                jQuery.each(messages, (index, messageData) => {
                    let usernameDeferred = fim_getUsernameDeferred(messageData.userId);

                    $('#active-view-archive table.messageTable > tbody').append(
                        $('<tr style="word-wrap: break-word;">').attr({
                            'id': 'archiveMessage' + messageData.id
                        }).append(
                            $('<td>').append(
                                fim_buildUsernameTag($('<span class="userName userNameTable"></span>'), messageData.userId, usernameDeferred, messageData.anonId)
                            ),
                            $('<td class="d-none d-sm-table-cell">').text(fim_dateFormat(messageData.time)),
                            $('<td>').append(
                                fim_buildMessageLine(messageData.text, messageData.flag, messageData.id, Number(messageData.userId), this.options.roomId, messageData.time, usernameDeferred)
                            ),
                            $('<td class="d-none d-md-table-cell">').append(
                                $('<a href="#archive#room=' + this.options.roomId + '#lastMessage=' + messageData.id + '">Show</a>')
                            )
                        )
                    );

                    this.messageData[messageData.id] = messageData;
                });

                if (messages.length < 2) {
                    if (this.options.firstMessage)
                        $('#active-view-archive button[name=archivePrev]').prop('disabled', true);

                    if (this.options.lastMessage)
                        $('#active-view-archive button[name=archiveNext]').prop('disabled', true);
                }
                else {
                    if (this.options.firstMessage)
                        $('#active-view-archive button[name=archiveNext]').prop('disabled', false);

                    if (this.options.lastMessage)
                        $('#active-view-archive button[name=archivePage]').prop('disabled', false);
                }
            },
        });
    },

    update : function (option, value) {
        this.options[option] = value;
    }
};