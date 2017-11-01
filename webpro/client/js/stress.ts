/* START WebPro
 * Note that: WebPro is not optimised for large sets of rooms. It can handle around 1,000 "normal" rooms. */

declare var fimApi: any;
declare var $: any;

let directory = window.location.pathname.split('/').splice(0, window.location.pathname.split('/').length - 2).join('/') + '/'; // splice returns the elements removed (and modifies the original array), in this case the first two; the rest should be self-explanatory

let numUsersPerRoom = 10;
let percentUsersReuse = .5;
let numRooms = 4;

let adminSessionToken = "";

class User {
    constructor(public username: String, public password : String) {}
    sessionToken : String;
    id : Number;
    lastIds : {} = {};

    newMessageHandler(roomId, eventId, messageData) {
        $('table#room' + roomId + ' > tbody > tr[name=message' + messageData.id + '] > td:eq(' + ($('table#room' + roomId + ' > thead > tr:eq(0) > th[name=user' + this.id + ']').index() - 1) + ')').css('background-color', (Number(this.id) % 2 == 0 ? 'blue' : 'green'));

        if (eventId > this.lastIds[Number(roomId).toString()]) {
            this.lastIds[Number(roomId).toString()] = eventId;
        }
    }

    getMessages(roomId) {
        if (!this.lastIds[Number(roomId).toString()])
            this.lastIds[Number(roomId).toString()] = false;

        if (Number(this.id) % 2 == 0) {
            let roomSource = new EventSource(directory + 'stream.php?queryId=' + roomId + '&streamType=room&access_token=' + this.sessionToken);
            roomSource.addEventListener('newMessage', ((messageData) => {
                this.newMessageHandler(roomId, messageData.lastEvent, JSON.parse(messageData.data));
            }), false);
        }
        else {
            window.setInterval(() => {
                this.getMessagesOnce(roomId)
            }, 5000);
        }
    },

    getMessagesOnce(roomId) {
        fimApi.getEventsFallback({
            'access_token' : this.sessionToken,
            'lastEvent' : this.lastIds[Number(roomId).toString()] ? this.lastIds[Number(roomId).toString()] : null,
            'streamType' : 'room',
            'queryId' : roomId,
        }, {
            each: ((messageData) => {
                this.newMessageHandler(roomId, messageData.id, messageData.data);
            }),
            error : (() => {
                console.log(this.username + ' failed to get messages in room ' + roomId);
            })
        });
    }
}

class Room {
    constructor(public name : String) {}
    id : Number;
}

$.when(
    $.ajax({
        url: 'client/js/fim-dev/fim-api.js',
        dataType: 'script'
    }),
).then(function() {
    fimApi = new fimApi();

    $(document).ready(function() {
        let numUniqueUsers = numUsersPerRoom * (1 - percentUsersReuse);
        let numCommonUsers = numUsersPerRoom * percentUsersReuse;
        let usersToCreate = numRooms * numUniqueUsers // This is the calculation of how many unique users there are per room, times the number of rooms.
            + numCommonUsers; // This is the calculation of how many reused users there are.

        // Create users objects.
        let users: User[] = [];
        for (let i = 0; i < usersToCreate; i++) {
            users.push(new User(Math.random().toString(36).slice(2), 'password'));
        }

        // Create & Login Users
        let userCreationQueries: any[] = [];
        userCreationQueries.push(fimApi.login({
            'username' : 'admin',
            'password' : 'admin',
            'client_id' : 'WebPro',
            'grant_type' : 'password',
        }, {
            error : function() {
                $('body').append($('<div>').text('Failed to login as admin.'));
            },
            end : function(login) { console.log(login);
                $('body').append($('<div>').text('Logged in as admin.'));
                adminSessionToken = login.access_token;
            }
        }));

        users.forEach(function(user) {
            // Create the user
            let createUser = fimApi.createUser({
                'name' : user.username,
                'password' : user.password,
                'birthDate' : 338751374,
                'email' : 'testuser@example.com'
            }, {
                error : function() {
                    $('body').append($('<div>').text('Failed to create user.'));
                },
                end : function() {
                    $('body').append($('<div>').text('Created user.'));
                }
            });

            // Register Login Deferred
            userCreationQueries.push(createUser.then(function() {
                return fimApi.login({
                    'username' : user.username,
                    'password' : user.password,
                    'client_id' : 'WebPro',
                    'grant_type' : 'password',
                }, {
                    error : function() {
                        $('body').append($('<div>').text('Failed to login as user.'));
                    },
                    end : function(login) {
                        $('body').append($('<div>').text('Logged in as user.'));
                        user.sessionToken = login.access_token;
                        user.id = login.userData.id;
                    }
                });
            }));
        });

        // Create Rooms
        let rooms : Room[] = [];
        for (let i = 0; i < numRooms; i++) {
            rooms.push(new Room(Math.random().toString(36).slice(2)));
        }

        $.when.apply($, userCreationQueries).then(function() {
            rooms.forEach(function(room, index) {
                $('table > thead > tr:eq(0)').append($('<th>').text(room.name));
                $('table > tbody > tr:eq(0)').append($('<td>').attr('valign', 'top'));

                fimApi.createRoom({
                    'access_token' : adminSessionToken,
                    'name' : room.name,
                    'defaultPermissions' : ['view', 'post']
                }, {
                    error : function() {
                        $('body').append($('<p>Failed to create room.</p>'));
                    },
                    end : function(createdRoom) {
                        room.id = createdRoom.id;

                        $('#tabs').append($('<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#room' + room.id + '-tabContent" role="tab">' + room.name + '</a></li>'));
                        $('#tab-content').append($('<div class="tab-pane" id="room' + room.id + '-tabContent"></div>').append($('<table style="table-layout: fixed;" id="room' + room.id + '" border="1"><thead><tr><th style="width: 300px">User</th></tr></thead><tbody><tr></tr></tbody></table>')));

                        let userCallback = function(user) {
                            $('table#room' + room.id + ' > thead > tr:eq(0)').append($('<th name="user' + user.id + '" style="width: 100px">').text(user.username));
                            user.getMessages(room.id);

                            window.setInterval(function() {
                                fimApi.sendMessage(room.id, {
                                    'access_token' : user.sessionToken,
                                    'message' : 'Hello',
                                }, {
                                    error : function() {
                                        $('table#room' + room.id).append($('<tr>').append('<td>').text(user.username + ' failed to send message').css('background-color', 'red'));
                                    },
                                    end: function (messageData) {
                                        var tr = $('<tr name="message' + messageData.id + '">').append($('<th>').text(user.username + ' sent message'));
                                        for (var i = 0; i < numCommonUsers + numUniqueUsers; i++) {
                                            tr.append($('<td>'))
                                        }
                                        $('table#room' + room.id).append(tr);
                                    },
                                });
                            }, 5000);
                        };

                        // These users subscribe to every room.
                        users.slice(0, numCommonUsers).forEach(userCallback);

                        // These users subscribe only to this room.
                        users.slice(numCommonUsers + index * numUniqueUsers, numCommonUsers + (index + 1) * numUniqueUsers).forEach(userCallback);
                    }
                });
            });
        });

    })
}, function() {
    $('body').text('Loading failed. Please refresh.');
});