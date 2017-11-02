"use strict";
var jsdom = require("jsdom");
var JSDOM = jsdom.JSDOM;
var window = new JSDOM("", {
    url: "http://localhost/"
}).window;
var $ = require('jquery')(window);
var fimApi = function (directory) {
    var _this = this;
    this.directory = directory;
    this.lastSessionHash = '';
    this.requestDefaults = {
        'close': false,
        'timerId': 1,
        'refresh': false,
        'timeout': 5000,
        'cache': false,
        'begin': function () { },
        'error': function () { },
        'exception': function () { },
        'each': function () { },
        'reverseEach': false,
        'end': function () { },
        'async': true,
        //        'method' : null,
        'action': null
    };
    this.timers = {};
    this.done = function (requestSettings, firstIndex) {
        return function (json) {
            // This digs into the tree a bit to where the array is. Perhaps somewhat inelegant, but it will work for our purposes, and does so quite simply.
            try {
                var firstElement = json[firstIndex ? firstIndex : Object.keys(json)[0]];
                requestSettings.begin(firstElement);
                if (requestSettings.reverseEach)
                    firstElement = firstElement.reverse();
                $.each(firstElement, function (index, value) {
                    requestSettings.each(value);
                });
                requestSettings.end(firstElement, json.metadata);
            }
            catch (e) {
                console.log("Failed to parse information: " + json);
            }
        };
    };
    this.fail = function (requestSettings, callback) {
        return function (response) {
            if (!("responseJSON" in response) && ("responseText" in response) && response.responseText.slice(0, 1) === '{')
                response.responseJSON = JSON.parse(response.responseText);
            if (!("responseJSON" in response)) {
                console.log("Unable to parse failure response.");
            }
            else if ("exception" in response.responseJSON) {
                console.log("Server Exception", JSON.stringify(response.responseJSON));
                if (response.responseJSON.exception.details == 'The access token provided has expired') {
                    /* TODO
                    if ($.cookie('webpro_username')) {
                        standard.login({
                            'username' : $.cookie('webpro_username'),
                            'password' : $.cookie('webpro_username'),
                            'finish' : callback
                        });
                    }
                    else {
                        standard.logout();
                        dia.error("Your login has expired. Please login again.");
                    } */
                }
                else {
                    return requestSettings.exception(response.responseJSON.exception);
                }
            }
            return requestSettings.error(response);
        };
    };
    this.timer = (function (requestSettings, name, query) {
        if (requestSettings.close) {
            console.log("close " + name + '_' + requestSettings.timerId, _this.timers);
            clearInterval(_this.timers[name + '_' + requestSettings.timerId]);
            delete _this.timers[name + '_' + requestSettings.timerId];
        }
        else {
            query(requestSettings);
            if (requestSettings.refresh > 0) {
                clearInterval(_this.timers[name + '_' + requestSettings.timerId]);
                _this.timers[name + '_' + requestSettings.timerId] = setInterval(function () {
                    query(requestSettings);
                }, requestSettings.refresh);
            }
        }
    });
    this.registerDefaultExceptionHandler = function (exceptionHandler) {
        this.requestDefaults.exception = exceptionHandler;
    };
    this.getDefaultExceptionHandler = function () {
        return this.requestDefaults.exception;
    };
    return;
};
fimApi.prototype.login = function (params, requestSettings) {
    var params = this.mergeDefaults(params, {
        'grant_type': 'password',
        'username': null,
        'password': null,
        'access_token': null,
        'refresh_token': null,
        'client_id': ''
    });
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    return $.ajax({
        url: this.directory + 'validate.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(function (json) {
        requestSettings.end(json.login);
    }).fail(function (response) {
        requestSettings.error(response.responseJSON.exception);
    });
};
/**
 * Obtains one or more users.
 */
fimApi.prototype.getUsers = function (params, requestSettings) {
    var params = this.mergeDefaults(params, {
        'info': ['self', 'groups', 'profile'],
        'access_token': this.lastSessionHash,
        'id': null,
        'userIds': null,
        'userNames': null,
        'userNameSearch': null
    });
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    function getUsers_query() {
        var _this = this;
        $.ajax({
            //            'async' : false,
            type: 'get',
            url: this.directory + 'api/user.php',
            data: params,
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
            _this.getUsers(params, requestSettings);
        })));
    }
    getUsers_query();
};
fimApi.prototype.createUser = function (params, requestSettings) {
    var _this = this;
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    return $.ajax({
        type: 'post',
        url: this.directory + 'api/user.php',
        data: this.mergeDefaults(params, {
            'name': null,
            'password': null,
            'birthDate': null,
            'email': null
        }),
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
        _this.getUsers(params, requestSettings);
    })));
};
fimApi.prototype.getRooms = function (params, requestSettings) {
    var params = this.mergeDefaults(params, {
        'access_token': this.lastSessionHash,
        'id': null,
        'roomIds': null,
        'roomNames': null,
        'roomNameSearch': null,
        'permFilter': null,
        'page': null
    });
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    function getRooms_query() {
        var _this = this;
        $.ajax({
            type: 'get',
            url: this.directory + 'api/room.php',
            data: params,
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
            _this.getRooms(params, requestSettings);
        })));
    }
    getRooms_query();
};
fimApi.prototype.getEventsFallback = function (params, requestSettings) {
    var _this = this;
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    this.timer(requestSettings, "getEventsFallback", (function (requestSettings) {
        $.ajax({
            type: 'get',
            url: _this.directory + 'stream.php',
            data: _this.mergeDefaults(params, {
                'fallback': true,
                'access_token': _this.lastSessionHash,
                'streamType': null,
                'queryId': null,
                'lastEvent': null,
                'lastMessage': null
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(function (response) {
            _this.done(requestSettings)(response);
        }).fail((function (response) {
            if (requestSettings.refresh) {
                _this.getEventsFallback(null, { close: true });
            }
            _this.fail(requestSettings, (function () {
                _this.getEventsFallback(params, requestSettings);
            }))(response);
        }));
    }));
};
/* Messages */
fimApi.prototype.getMessages = function (params, requestSettings) {
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    function getMessages_query(requestSettings) {
        $.ajax({
            type: 'get',
            url: this.directory + 'api/message.php',
            data: this.mergeDefaults(params, {
                'access_token': this.lastSessionHash,
                'roomId': null,
                'userIds': null,
                'messageIdEnd': null,
                'messageIdStart': null,
                'page': null,
                'messageTextSearch': null,
                'archive': false
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(function (response) {
            this.done(requestSettings)(response);
        }).fail(function (response) {
            var _this = this;
            this.fail(requestSettings, (function () {
                _this.getMessages(params, requestSettings);
            }))(response);
        });
    }
    this.timer(requestSettings, "getMessages", getMessages_query);
};
fimApi.prototype.sendMessage = function (roomId, params, requestSettings) {
    var _this = this;
    var params = this.mergeDefaults(params, {
        'access_token': this.lastSessionHash,
        'ignoreBlock': false,
        'message': null,
        'flag': null
    });
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        url: this.directory + 'api/message.php?' + $.param({
            'roomId': roomId
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
        _this.sendMessage(roomId, params, requestSettings);
    })));
};
fimApi.prototype.editMessage = function (roomId, messageId, params, requestSettings) {
    var _this = this;
    var params = this.mergeDefaults(params, {
        'ignoreBlock': false,
        'message': null,
        'flag': null
    });
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        url: this.directory + 'api/message.php?' + $.param({
            '_action': 'edit',
            'access_token': this.lastSessionHash,
            'id': messageId,
            'roomId': roomId
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
        _this.editMessage(roomId, messageId, params, requestSettings);
    })));
};
fimApi.prototype.deleteMessage = function (roomId, messageId, requestSettings) {
    var _this = this;
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        url: this.directory + 'api/message.php?' + $.param({
            '_action': 'delete',
            'access_token': this.lastSessionHash,
            'id': messageId,
            'roomId': roomId
        }),
        type: 'POST',
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
        _this.deleteMessage(roomId, messageId, requestSettings);
    })));
};
/* Unread Messages */
fimApi.prototype.getUnreadMessages = function (params, requestSettings) {
    var requestSettings = this.mergeDefaults(requestSettings, this.mergeDefaults({
        'timeout': 30000,
        'refresh': 30000
    }, this.requestDefaults));
    function getUnreadMessages_query(requestSettings) {
        var _this = this;
        $.ajax({
            type: 'get',
            url: this.directory + 'api/unreadMessages.php',
            data: this.mergeDefaults(params, {
                'access_token': this.lastSessionHash
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(this.done(requestSettings)).fail((function (response) {
            if (requestSettings.refresh) {
                _this.getUnreadMessages(null, { close: true });
            }
            _this.fail(requestSettings, (function () {
                _this.getUnreadMessages(params, requestSettings);
            }))(response);
        }));
    }
    fimApi.timer(requestSettings, "getUnreadMessages", getUnreadMessages_query);
};
fimApi.prototype.getFiles = function (params, requestSettings) {
    var params = this.mergeDefaults(params, {
        'access_token': this.lastSessionHash,
        'userIds': '',
        'fileIds': ''
    });
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    function getFiles_query() {
        var _this = this;
        $.ajax({
            type: 'get',
            url: this.directory + 'api/files.php',
            data: params,
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
            _this.getFiles(params.requestSettings);
        })));
    }
    if (requestSettings.close)
        clearInterval(this.timers['getFiles_' + requestSettings.timerId]);
    getFiles_query();
};
fimApi.prototype.getStats = function (params, requestSettings) {
    var _this = this;
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        type: 'get',
        url: this.directory + 'api/stats.php',
        data: this.mergeDefaults(params, {
            'access_token': this.lastSessionHash,
            'roomId': null,
            'number': 10
        }),
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings, 'roomStats')).fail(this.fail(requestSettings, (function () {
        _this.getStats(params, requestSettings);
    })));
};
fimApi.prototype.getKicks = function (params, requestSettings) {
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    function getKicks_query() {
        var _this = this;
        $.ajax({
            type: 'get',
            url: this.directory + 'api/kick.php',
            data: this.mergeDefaults(params, {
                'access_token': this.lastSessionHash,
                'roomId': null,
                'userId': null
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
            _this.getKicks(params, requestSettings);
        })));
    }
    getKicks_query();
};
fimApi.prototype.getCensorLists = function (params, requestSettings) {
    var params = this.mergeDefaults(params, {
        'access_token': this.lastSessionHash,
        'roomId': null,
        'listIds': null,
        'includeWords': 1 // true
    });
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    function getCensorLists_query() {
        var _this = this;
        $.ajax({
            type: 'get',
            url: this.directory + 'api/getCensorLists.php',
            data: params,
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
            _this.getCensorLists(params, requestSettings);
        })));
    }
    getCensorLists_query();
};
fimApi.prototype.getActiveUsers = function (params, requestSettings) {
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    function getActiveUsers_query(requestSettings) {
        var _this = this;
        $.ajax({
            type: 'get',
            url: this.directory + 'api/userStatus.php',
            data: this.mergeDefaults(params, {
                'access_token': this.lastSessionHash,
                'roomIds': null,
                'userIds': null,
                'onlineThreshold': null
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(this.done(requestSettings, 'users')).fail((function (response) {
            if (requestSettings.refresh) {
                _this.getActiveUsers(null, { close: true });
            }
            _this.fail(requestSettings, (function () {
                _this.getActiveUsers(params, requestSettings);
            }))(response);
        }));
    }
    fimApi.timer(requestSettings, "getActiveUsers", getActiveUsers_query);
};
fimApi.prototype.getGroups = function (params, requestSettings) {
    var _this = this;
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        type: 'get',
        url: this.directory + 'api/group.php',
        data: this.mergeDefaults(params, {
            'access_token': this.lastSessionHash,
            'groupIds': null,
            'groupNames': null,
            'groupNameSearch': null
        }),
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
        _this.getGroups(params, requestSettings);
    })));
};
fimApi.prototype.acHelper = function (list) {
    return function acHelper_query(search, callback) {
        $.ajax({
            type: 'get',
            url: this.directory + 'api/acHelper.php',
            data: {
                'access_token': this.lastSessionHash,
                'list': list,
                'search': search.term
            },
            success: function (json) {
                callback($.map(json.entries, function (value, key) {
                    return {
                        label: value,
                        value: key
                    };
                }));
            }
        });
    };
};
fimApi.prototype.kickUser = function (userId, roomId, length, requestSettings) {
    var _this = this;
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        url: this.directory + 'api/kick.php?' + $.param({
            'access_token': this.lastSessionHash,
            'roomId': roomId,
            'userId': userId
        }),
        type: 'POST',
        data: {
            'length': length
        },
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
        _this.kickUser(userId, roomId, length, requestSettings);
    })));
};
fimApi.prototype.unkickUser = function (userId, roomId, requestSettings) {
    var _this = this;
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        url: this.directory + 'api/kick.php?' + $.param({
            'access_token': this.lastSessionHash,
            '_action': 'delete',
            'roomId': roomId,
            'userId': userId
        }),
        type: 'POST',
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
        _this.unkickUser(userId, roomId, requestSettings);
    })));
};
fimApi.prototype.markMessageRead = function (roomId, requestSettings) {
    var _this = this;
    var params = {
        'access_token': this.lastSessionHash,
        'roomId': roomId
    };
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        url: this.directory + 'api/markMessageRead.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
        _this.markMessageRead(roomId, requestSettings);
    })));
};
fimApi.prototype.editUserOptions = function (action, params, requestSettings) {
    var _this = this;
    var params = this.mergeDefaults(params, {
        'defaultFormatting': null,
        'defaultColor': null,
        'defaultHighlight': null,
        'defaultRoomId': null,
        'watchRooms': null,
        'favRooms': null,
        'ignoreList': null,
        'friendsList': null,
        'profile': null,
        'defaultFontface': null,
        'parentalAge': null,
        'parentalFlags': null,
        'avatar': null,
        'privacyLevel': null
    });
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        url: this.directory + 'api/userOptions.php?' + $.param({
            'access_token': this.lastSessionHash,
            '_action': (action ? action : "edit")
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
        _this.editUserOptions(action, params, requestSettings);
    })));
};
fimApi.prototype.favRoom = function (roomId) {
    this.editUserOptions('create', {
        'favRooms': [roomId]
    });
};
fimApi.prototype.unfavRoom = function (roomId) {
    this.editUserOptions('delete', {
        'favRooms': [roomId]
    });
};
fimApi.prototype.watchRoom = function (roomId) {
    this.editUserOptions('create', {
        'watchRooms': [roomId]
    });
};
fimApi.prototype.unwatchRoom = function (roomId) {
    this.editUserOptions('delete', {
        'watchRooms': [roomId]
    });
};
fimApi.prototype.editRoom = function (id, action, params, requestSettings) {
    var _this = this;
    var params = this.mergeDefaults(params, {
        'access_token': this.lastSessionHash,
        'name': null,
        'defaultPermissions': null,
        'userPermissions': null,
        'groupPermissions': null,
        'censorLists': null,
        'parentalAge': null,
        'parentalFlags': null,
        'hidden': null,
        'official': null
    });
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        url: this.directory + 'api/room.php?' + $.param(this.mergeDefaults({}, {
            'id': id,
            '_action': action
        })),
        method: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (function () {
        _this.editRoom(id, params, requestSettings);
    })));
};
fimApi.prototype.createRoom = function (params, requestSettings) {
    this.editRoom(null, 'create', params, requestSettings);
};
fimApi.prototype.deleteRoom = function (id, requestSettings) {
    this.editRoom(id, 'delete', {}, requestSettings);
};
fimApi.prototype.undeleteRoom = function (id, requestSettings) {
    this.editRoom(id, 'undelete', {}, requestSettings);
};
fimApi.prototype.editRoomPermissionUser = function (roomId, userId, permissionsArray) {
    var permissionsObj = {};
    permissionsObj['*' + userId] = permissionsArray;
    this.editRoom(roomId, 'edit', {
        'userPermissions': permissionsObj
    });
};
fimApi.prototype.editRoomPermissionGroup = function (roomId, groupId, permissionsArray) {
    var permissionsObj = {};
    permissionsObj['*' + groupId] = permissionsArray;
    this.editRoom(roomId, 'edit', {
        'groupPermissions': permissionsObj
    });
};
fimApi.prototype.editUserStatus = function (roomId, params, requestSettings) {
    var params = this.mergeDefaults(params, {
        'status': null,
        'typing': null
    });
    var requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);
    $.ajax({
        url: this.directory + 'api/userStatus.php?' + $.param({
            '_action': 'edit',
            'access_token': this.lastSessionHash,
            'roomIds': [roomId]
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, function () {
        this.editUserStatus(roomId, params, requestSettings);
    }));
};
fimApi.prototype.ping = function (roomId, requestSettings) {
    this.editUserStatus(roomId, {}, requestSettings);
};
fimApi.prototype.changeAvatar = function (avatarHash, requestSettings) {
    this.editUserOptions({
        'avatarHash': avatarHash
    }, requestSettings);
};
fimApi.prototype.mergeDefaults = function (object, defaults) {
    var returnObject = {};
    for (var i in object) {
        if (!(i in defaults)) {
            throw 'Invalid data in object call: ' + i;
        }
        if (object[i] !== null && object[i] !== undefined)
            returnObject[i] = object[i];
    }
    for (var i in defaults) {
        if (!(i in returnObject) && defaults[i] !== null)
            returnObject[i] = defaults[i];
    }
    /* Debug Data */
    /*    console.log("===BEGIN AJAX QUERY===");
        console.log("Original Object: ");
        console.log(object);
        console.log("New Object: ");
        console.log(returnObject);*/
    /*** END STRICT CODE ***/
    return returnObject;
};
fimApi.prototype.jsonify = function (object, properties) {
    var returnObject = (object === undefined ? {} : Object.create(object));
    for (var i in properties) {
        if (properties[i] in returnObject)
            returnObject[properties[i]] = JSON.stringify(returnObject[properties[i]]);
    }
    return returnObject;
};
module.exports.fimApi = function (directory) {
    return new fimApi(directory);
};
//# sourceMappingURL=fim-api.js.map