/* START WebPro
 * Note that: WebPro is not optimised for large sets of rooms. It can handle around 1,000 "normal" rooms. */
var directory = window.location.pathname.split('/').splice(0, window.location.pathname.split('/').length - 2).join('/') + '/'; // splice returns the elements removed (and modifies the original array), in this case the first two; the rest should be self-explanatory
var numUsersPerRoom = 20;
var percentUsersReuse = .5;
var numRooms = 2;
var adminSessionToken = "";
var User = (function () {
    function User(username, password) {
        this.username = username;
        this.password = password;
        this.lastIds = {};
    }
    return User;
}());
var Room = (function () {
    function Room(name) {
        this.name = name;
    }
    return Room;
}());
$.when($.ajax({
    url: 'client/js/fim-dev/fim-api.js',
    dataType: 'script'
})).then(function () {
    fimApi = new fimApi();
    $(document).ready(function () {
        var users = [];
        var numUniqueUsers = numUsersPerRoom * (1 - percentUsersReuse);
        var numCommonUsers = numUsersPerRoom * percentUsersReuse;
        var usersToCreate = numRooms * numUniqueUsers // This is the calculation of how many unique users there are per room, times the number of rooms.
            + numCommonUsers; // This is the calculation of how many reused users there are.
        // Create users objects.
        for (var i = 0; i < usersToCreate; i++) {
            users.push(new User(Math.random().toString(36).slice(2), 'password'));
        }
        // Create & Login Users
        var userCreationQueries = [];
        userCreationQueries.push(fimApi.login({
            'username': 'admin',
            'password': 'admin',
            'client_id': 'WebPro',
            'grant_type': 'password'
        }, {
            error: function () {
                $('body').append($('<div>').text('Failed to login as admin.'));
            },
            end: function (login) {
                console.log(login);
                $('body').append($('<div>').text('Logged in as admin.'));
                adminSessionToken = login.access_token;
            }
        }));
        users.forEach(function (user) {
            // Create the user
            var createUser = fimApi.createUser({
                'name': user.username,
                'password': user.password,
                'birthDate': 338751374,
                'email': 'testuser@example.com'
            }, {
                error: function () {
                    $('body').append($('<div>').text('Failed to create user.'));
                },
                end: function () {
                    $('body').append($('<div>').text('Created user.'));
                }
            });
            // Register Login Deferred
            userCreationQueries.push(createUser.then(function () {
                return fimApi.login({
                    'username': user.username,
                    'password': user.password,
                    'client_id': 'WebPro',
                    'grant_type': 'password'
                }, {
                    error: function () {
                        $('body').append($('<div>').text('Failed to login as user.'));
                    },
                    end: function (login) {
                        $('body').append($('<div>').text('Logged in as user.'));
                        user.sessionToken = login.access_token;
                    }
                });
            }));
        });
        // Create Rooms
        var rooms = [];
        for (var i = 0; i < numRooms; i++) {
            rooms.push(new Room(Math.random().toString(36).slice(2)));
        }
        $.when.apply($, userCreationQueries).then(function () {
            rooms.forEach(function (room, index) {
                fimApi.createRoom({
                    'access_token': adminSessionToken,
                    'name': room.name,
                    'defaultPermissions': ['view', 'post']
                }, {
                    error: function () {
                        $('body').append($('<div>').text('Failed to create room.'));
                    },
                    end: function (createdRoom) {
                        $('body').append($('<div>').text('Created room.'));
                        room.id = createdRoom.id;
                        // These users subscribe to every room.
                        users.slice(0, numCommonUsers).forEach(function (user) {
                            user.lastIds[Number(room.id).toString()] = false;
                            window.setInterval(function () {
                                fimApi.getMessages({
                                    'access_token': user.sessionToken,
                                    'messageIdStart': user.lastIds[Number(room.id).toString()] ? user.lastIds[Number(room.id).toString()] : null,
                                    'roomId': room.id
                                }, {
                                    refresh: 3000,
                                    each: function (messageData) {
                                        $('body').append($('<div>').text(user.username + ' received message in ' + room.name + ': ' + messageData.text));
                                        if (messageData.id > user.lastIds[Number(room.id).toString()]) {
                                            user.lastIds[Number(room.id).toString()] = messageData.id;
                                        }
                                    }
                                });
                            }, 3000);
                            window.setInterval(function () {
                                fimApi.sendMessage(room.id, {
                                    'access_token': user.sessionToken,
                                    'message': 'Hello'
                                }, {
                                    error: function () {
                                        $('body').append($('<div>').text(user.username + ' failed to send message in ' + room.name));
                                    },
                                    end: function (messageData) {
                                        $('body').append($('<div>').text(user.username + ' sent message in ' + room.name));
                                    }
                                });
                            }, 5000);
                        });
                        // These users subscribe only to this room.
                        users.slice(numCommonUsers + index * numUniqueUsers, numCommonUsers + 2 * index * numUniqueUsers).forEach(function (user) {
                            user.lastIds[Number(room.id).toString()] = false;
                            window.setInterval(function () {
                                fimApi.getMessages({
                                    'access_token': user.sessionToken,
                                    'messageIdStart': user.lastIds[Number(room.id).toString()] ? user.lastIds[Number(room.id).toString()] : null,
                                    'roomId': room.id
                                }, {
                                    refresh: 3000,
                                    each: function (messageData) {
                                        $('body').append($('<div>').text(user.username + ' received message in ' + room.name + ': ' + messageData.text));
                                        if (messageData.id > user.lastIds[Number(room.id).toString()]) {
                                            user.lastIds[Number(room.id).toString()] = messageData.id;
                                        }
                                    }
                                });
                            }, 3000);
                        });
                    }
                });
            });
        });
    });
}, function () {
    $('body').text('Loading failed. Please refresh.');
});
//# sourceMappingURL=stress.js.map