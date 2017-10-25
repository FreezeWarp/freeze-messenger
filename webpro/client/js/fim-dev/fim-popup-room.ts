declare var fimApi: any;
declare var $: any;
declare var messageIndex: any;
declare var directory: any;
declare var dia: any;
declare var fim_buildUsernameTag : any;
declare var fim_getUsernameDeferred : any;
declare var fim_messageFormat : any;
declare var fim_newMessage : any;
declare var windowDraw : any;
declare var EventSource : any;

interface popup {
    room : Object
};

popup.prototype.room = {
    options : {
        roomId : 0,
        intervalPing : false,
        lastEvent : 0,
        lastMessage : 0
    },

    init : function(options) {
        for (var i in options)
            this.options[i] = options[i];

        var intervalPing;


        $('#sendForm').bind('submit', (() => {
            var message = $('textarea#messageInput').val();

            if (message.length === 0) { dia.error('Please enter your message.'); }
            else {
                this.sendMessage(message); // Send the messaage
                $('textarea#messageInput').val(''); // Clear the textbox
            }

            return false;
        }));

        $('#messageInput').onEnter(function() {
            $('#sendForm').submit();

            return false;
        });


        fimApi.getRooms({
            'id' : this.options.roomId,
        }, {
            each : ((roomData) => {
                if (!roomData.permissions.view) { // If we can not view the room
                    window.roomId = false; // Set the global roomId false.
                    window.location.hash = "#rooms";
                    dia.error('You have been restricted access from this room. Please select a new room.');
                }

                else if (!roomData.permissions.post) { // If we can view, but not post
                    dia.error('You are not allowed to post in this room. You will be able to view it, though.');
                    this.disableSender();
                }

                else { // If we can both view and post.
                    this.enableSender();
                }


                if (roomData.permissions.view) { // If we can view the room...
                    window.roomId = roomData.id;

                    $('#roomName').html(roomData.name); // Update the room name.
                    $('#topic').html(roomData.topic); // Update the room topic.

                    /*** Get Messages ***/
                    this.populateMessages();
                }

                if (!(roomData.permissions.properties || roomData.permissions.grant)) {
                    $('#active-view-room #chatContainer button[name=editRoom]').hide();
                }
            }),

            exception : ((exception) => {
                if (exception.string === 'idNoExist') {
                    window.roomId = false; // Set the global roomId false.
                    window.location.hash = "#rooms";
                    dia.error('That room doesn\'t exist. Please select a room.');
                }
                else { fimApi.getDefaultExceptionHandler()(exception); }
            })
        });


        /* Populate Active Users for the Room */
        fimApi.getActiveUsers({
            'roomIds' : [this.options.roomId]
        }, {
            'refresh' : 15000,
            'timerId' : 1,
            'begin' : function() {
                $('#activeUsers').html('<ul></ul>');
            },
            'each' : function(user) {
                $('#activeUsers > ul').append($('<li>').append(fim_buildUsernameTag($('<span>'), user.id, fim_getUsernameDeferred(user.id), true)));
            }
        });
    },

    setRoom : function(roomId) {
        if (this.options.roomId != roomId) {
            this.init({
                'roomId' : roomId
            });
        }
    },

    eventListener : function() {
        var roomSource = new EventSource(directory + 'stream.php?queryId=' + this.options.roomId + '&streamType=room&lastEvent=' + this.options.lastEvent + '&lastMessage=' + this.options.lastMessage + '&access_token=' + window.sessionHash);
        var eventHandler = function(callback) {
            return ((event) => {
                if (event.id > this.options.lastEvent) {
                    this.options.lastEvent = event.id;
                }

                callback(JSON.parse(event.data));
            });
        };

        roomSource.addEventListener('newMessage', eventHandler(this.newMessageHandler), false);
        roomSource.addEventListener('topicChange', eventHandler(this.topicChangeHandler), false);
        roomSource.addEventListener('deletedMessage', eventHandler(this.deletedMessageHandler), false);
        roomSource.addEventListener('editedMessage', eventHandler(this.edittedMesageHandler), false);
    },


    newMessageHandler : function(active) {
        this.options.lastMessage = Math.max(this.options.lastMessage, active.id);

        fim_newMessage(this.options.roomId, Number(active.id), fim_messageFormat(active, 'list'));
    },

    deletedMessageHandler : function(active) {
        $('#message' + active.id).fadeOut();
    },

    topicChangeHandler : function(active) {
        $('#topic').html(active.param1);
        console.log('Event (Topic Change): ' + active.param1);
    },

    editedMessageHandler : function(active) {
        if ($('#message' + active.id).length > 0) {
            active.userId = $('#message' + active.id + ' .userName').attr('data-userid');
            active.time = $('#message' + active.id + ' .messageText').attr('data-time');

            fim_newMessage(this.options.roomId, Number(active.id), fim_messageFormat(active, 'list'));
        }
    },


    getMessagesFromFallback : function() {
        if (this.options.roomId) {
            fimApi.getEventsFallback({
                'streamType': 'room',
                'queryId': this.options.roomId,
                'lastEvent' : this.options.lastEvent
            }, {
                each: ((event) => {
                    if (Number(event.id) > Number(this.options.lastEvent)) {
                        this.options.lastEvent = event.id;
                    }

                    if (event.eventName == "newMessage") {
                        this.newMessageHandler(event.data);
                    }
                }),
                end: (() => {
                    if (window.requestSettings.serverSentEvents) {
                        this.eventListener();
                    }
                    else {
                        window.setTimeout((() => {
                            this.getMessagesFromFallback()
                        }), 3000);
                    }
                })
            });
        }
        else {
            console.log('Not requesting messages; room undefined.');
        }

        return false;
    },


    populateMessages : function() {
        $(document).ready((() => {
            // Clear the message list.
            $('#messageList').html('');

            window.requestSettings[this.options.roomId] = {
                lastMessage : null,
                firstRequest : true
            };
            messageIndex[this.options.roomId] = [];

            // Get New Messages
            if (this.options.roomId) {
                fimApi.getMessages({
                    'roomId': this.options.roomId,
                }, {
                    each: ((messageData) => {
                        fim_newMessage(Number(this.options.roomId), Number(messageData.id), fim_messageFormat(messageData, 'list'));
                    }),
                    end: (() => {
                        if (window.requestSettings.serverSentEvents) {
                            this.eventListener();
                        }
                        else {
                            this.getMessagesFromFallback();
                        }
                    })
                });
            }
            else {
                console.log('Not requesting messages; room undefined.');
            }

            if (this.options.intervalPing)
                clearInterval(this.options.intervalPing);

            fimApi.ping(this.options.roomId);
            this.options.intervalPing = window.setInterval((() => {
                fimApi.ping(this.options.roomId)
            }), 5 * 60 * 1000);

            windowDraw();
            //windowDynaLinks();
        }));
    },

    sendMessage : function(message, ignoreBlock, flag) {
        if (!this.options.roomId) {
            window.location.hash = '#rooms';
        }
        else {
            ignoreBlock = (ignoreBlock === 1 ? 1 : '');

            fimApi.sendMessage(this.options.roomId, {
                'ignoreBlock' : ignoreBlock,
                'message' : message,
                'flag' : (flag ? flag : '')
            }, {
                end : ((message) => {
                    if ("censor" in message && Object.keys(message.censor).length > 0) {
                        dia.info(Object.values(message.censor).join('<br /><br />'), "Censor warning: " + Object.keys(message.censor).join(', '));
                    }
                }),

                exception : ((exception) => {
                    if (exception.string === 'confirmCensor')
                        dia.confirm({
                            'text' : exception.details,
                            'true' : function() {
                                this.sendMessage(message, 1, flag);
                            }
                        }, "Censor Warning");
                    else if (exception.string === 'spaceMessage') {
                        dia.error("Too... many... spaces!")
                    }
                    else { fimApi.getDefaultExceptionHandler()(exception); }
                }),

                error : ((request) => {
                    if (window.settings.reversePostOrder)
                        $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.');
                    else
                        $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.');

                    window.setTimeout(function() { this.sendMessage(message) }, 5000);

                    return false;
                })
            });
        }
    },

    disableSender : function() {
        $('#messageInput').attr('disabled','disabled'); // Disable input boxes.
        $('#icon_url').button({ disabled : true }); // "
        $('#icon_submit').button({ disabled : true }); // "
        $('#icon_reset').button({ disabled : true }); // "
    },

    enableSender : function() {
        $('#messageInput').removeAttr('disabled'); // Make sure the input is not disabled.
        $('#icon_url').button({ disabled : false }); // "
        $('#icon_submit').button({ disabled : false }); // "
        $('#icon_reset').button({ disabled : false }); // "
    }
};