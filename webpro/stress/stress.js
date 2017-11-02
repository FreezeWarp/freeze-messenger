/* START WebPro
 * Note that: WebPro is not optimised for large sets of rooms. It can handle around 1,000 "normal" rooms. */
var numUsersPerRoom = 20;
var percentUsersReuse = .75;
var numRooms = 4;
var numUniqueUsers = numUsersPerRoom * (1 - percentUsersReuse);
var numCommonUsers = numUsersPerRoom * percentUsersReuse;
var usersToCreate = numRooms * numUniqueUsers // This is the calculation of how many unique users there are per room, times the number of rooms.
    + numCommonUsers; // This is the calculation of how many reused users there are.
var adminSessionToken = "";
var fimApi = require('./fim-api.js').fimApi("http://localhost/messenger/");
var jsdom = require("jsdom");
var JSDOM = jsdom.JSDOM;
var window = new JSDOM("").window;
var $ = require('jquery')(window);
var app = require('http').createServer(function (req, res) {
    console.log("request listener fired");
    fs.readFile(__dirname + '/index.html', function (err, data) {
        if (err) {
            res.writeHead(500);
            return res.end('Error loading index.html');
        }
        res.writeHead(200);
        res.end(data);
    });
});
var io = require('socket.io')(app);
var fs = require('fs');
var EventSource = require('eventsource');
app.listen(3033);
var User = (function () {
    function User(username, password) {
        this.username = username;
        this.password = password;
        this.lastIds = {};
    }
    User.prototype.newMessageHandler = function (roomId, eventId, messageData) {
        socket.emit('command', "$('table#room" + roomId + " > tbody > tr[name=message" + messageData.id + "] > td:eq(' + ($('table#room" + roomId + " > thead > tr:eq(0) > th[name=user" + this.id + "]').index() - 1) + ')').css('background-color', '" + (Number(this.id) % 2 == 0 ? 'blue' : 'green') + "')");
        if (Number(eventId) > Number(this.lastIds[Number(roomId).toString()])) {
            this.lastIds[Number(roomId).toString()] = eventId;
        }
    };
    User.prototype.getMessages = function (roomId) {
        var _this = this;
        if (!this.lastIds[Number(roomId).toString()])
            this.lastIds[Number(roomId).toString()] = false;
        if (Number(this.id) % 2 == 0) {
            var roomSource = new EventSource(fimApi.directory + 'stream.php?queryId=' + roomId + '&streamType=room&access_token=' + this.sessionToken);
            roomSource.addEventListener('newMessage', (function (messageData) {
                _this.newMessageHandler(roomId, messageData.lastEvent, JSON.parse(messageData.data));
            }), false);
        }
        else {
            setInterval(function () {
                _this.getMessagesOnce(roomId);
            }, 3000);
        }
    };
    User.prototype.getMessagesOnce = function (roomId) {
        var _this = this;
        fimApi.getEventsFallback({
            'access_token': this.sessionToken,
            'lastEvent': this.lastIds[Number(roomId).toString()] ? this.lastIds[Number(roomId).toString()] : null,
            'streamType': 'room',
            'queryId': roomId
        }, {
            each: (function (messageData) {
                _this.newMessageHandler(roomId, messageData.id, messageData.data);
            }),
            error: (function () {
                console.log(_this.username + ' failed to get messages in room ' + roomId);
            })
        });
    };
    return User;
}());
var Room = (function () {
    function Room(name) {
        this.name = name;
    }
    return Room;
}());
var socket;
io.on('connection', function (s) {
    socket = s;
    // Create users objects.
    var users = [];
    for (var i = 0; i < usersToCreate; i++) {
        users.push(new User(Math.random().toString(36).slice(20), 'password'));
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
            socket.emit('command', "$('body').append($('<div>').text('Failed to login as admin.'))");
        },
        end: function (login) {
            socket.emit('command', "$('body').append($('<div>').text('Logged in as admin.'))");
            adminSessionToken = login.access_token;
        }
    }));
    users.forEach(function (user) {
        console.log("Creating user.");
        // Create the user
        var createUser = fimApi.createUser({
            'name': user.username,
            'password': user.password,
            'birthDate': 338751374,
            'email': 'testuser@example.com'
        }, {
            error: function () {
                socket.emit('command', "$('body').append($('<div>').text('Failed to create user.'))");
            },
            end: function () {
                socket.emit('command', "$('body').append($('<div>').text('Created user.'))");
            }
        });
        // Register Login Deferred
        userCreationQueries.push(createUser.done(function () {
            return fimApi.login({
                'username': user.username,
                'password': user.password,
                'client_id': 'WebPro',
                'grant_type': 'password'
            }, {
                error: function () {
                    socket.emit('command', "$('body').append($('<div>').text('Failed to login as user.'))");
                },
                end: function (login) {
                    socket.emit('command', "$('body').append($('<div>').text('Logged in as user.'))");
                    user.sessionToken = login.access_token;
                    user.id = login.userData.id;
                }
            });
        }));
    });
    // Create Rooms
    var rooms = [];
    for (var i = 0; i < numRooms; i++) {
        rooms.push(new Room(Math.random().toString(36).slice(20)));
    }
    $.when.apply($, userCreationQueries).then(function () {
        rooms.forEach(function (room, index) {
            //socket.emit("command", "$('table > thead > tr:eq(0)').append($('<th>').text('" + room.name + "')); $('table > tbody > tr:eq(0)').append($('<td>').attr('valign', 'top'));");
            console.log("creating room");
            fimApi.createRoom({
                'access_token': adminSessionToken,
                'name': room.name,
                'defaultPermissions': ['view', 'post']
            }, {
                error: function () {
                    console.log("room failed");
                    socket.emit("command", "$('body').append($('<p>Failed to create room.</p>'))");
                },
                end: function (createdRoom) {
                    room.id = createdRoom.id;
                    socket.emit("command", "$('#tabs').append($('<li class=\"nav-item\"><a class=\"nav-link\" data-toggle=\"tab\" href=\"#room" + room.id + "-tabContent\" role=\"tab\">" + room.name + "</a></li>')); $('#tab-content').append($('<div class=\"tab-pane\" id=\"room" + room.id + "-tabContent\"></div>').append($('<table style=\"table-layout: fixed;\" id=\"room" + room.id + "\" border=\"1\"><thead><tr><th style=\"width: 300px\">User</th></tr></thead><tbody><tr></tr></tbody></table>')))", function () {
                        var userCallback = function (user) {
                            socket.emit("command", "$('table#room" + room.id + " > thead > tr:eq(0)').append($('<th name=\"user" + user.id + "\" style=\"width: 100px\">').text('" + user.username + "'))");
                            user.getMessages(room.id);
                            setInterval(function () {
                                fimApi.sendMessage(room.id, {
                                    'access_token': user.sessionToken,
                                    'message': 'Hello'
                                }, {
                                    error: function () {
                                        socket.emit("command", "$('table#room" + room.id + "').append($('<tr>').append('<td>').text('" + user.username + " failed to send message').css('background-color', 'red'));");
                                    },
                                    end: function (messageData) {
                                        socket.emit("command", "{" +
                                            "var tr = $('<tr name=\"message" + messageData.id + "\">').append($('<th>').text('" + user.username + " sent message'));" +
                                            "for (var i = 0; i < " + (numCommonUsers + numUniqueUsers) + "; i++) {" +
                                            "tr.append($('<td>'))" +
                                            "}" +
                                            "$('table#room" + room.id + "').append(tr);" +
                                            "}");
                                    }
                                });
                            }, 5000);
                        };
                        // These users subscribe to every room.
                        users.slice(0, numCommonUsers).forEach(userCallback);
                        // These users subscribe only to this room.
                        users.slice(numCommonUsers + index * numUniqueUsers, numCommonUsers + (index + 1) * numUniqueUsers).forEach(userCallback);
                    });
                }
            });
        });
    });
});
//# sourceMappingURL=stress.js.map