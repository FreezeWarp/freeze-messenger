"use strict";

var fimApi = function() {
    this.requestDefaults = {
        'close' : false,
        'timerId' : 1,
        'refresh' : -1,
        'timeout' : 5000,
        'cache' : false,
        'begin' : function() {},
        'error' : function() {},
        'exception' : function () {},
        'each' : function() {},
        'reverseEach' : false,
        'end' : function() {},
        'async' : true,
//        'method' : null,
        'action' : null,
    };

    this.timers = {};

    this.done = function(requestSettings) {
        return function (json) {
            // This digs into the tree a bit to where the array is. Perhaps somewhat inelegant, but it will work for our purposes, and does so quite simply.
            var firstElement = json["firstIndex" in requestSettings ? requestSettings.firstIndex : Object.keys(json)[0]];
            //var secondElement = firstElement["secondIndex" in requestSettings ? requestSettings.secondIndex : Object.keys(firstElement)[0]];

            requestSettings.begin(firstElement);
            if (requestSettings.reverseEach) firstElement = firstElement.reverse();
            $.each(firstElement, function (index, value) {
                requestSettings.each(value);
            });

            requestSettings.end(firstElement);
        }
    };

    this.fail = function(requestSettings) {
        return function(response) {
            if (!("responseJSON" in response)) response.responseJSON = JSON.parse(response.responseText);

            if ("exception" in response.responseJSON)
                return requestSettings.exception(response.responseJSON.exception);
            else
                return requestSettings.error(response);
        }
    }

    this.registerDefaultExceptionHandler = function (exceptionHandler) {
        this.requestDefaults.exception = exceptionHandler;
    },

    this.getDefaultExceptionHandler = function () {
        return this.requestDefaults.exception;
    }

    return;
}



fimApi.prototype.login = function (params, requestSettings) {
        var params = fimApi.mergeDefaults(params, {
            'fim3_format' : 'json',
            'grant_type' : 'password',
            'username' : null,
            'password' : null,
            'access_token' : null,
            'client_id' : ''
        });

        if (params.username == '' && params.password == '')
            params.grant_type = 'anonymous';

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

        function login_query() {
            $.ajax({
                url: directory + 'validate.php',
                type: 'POST',
                data: params,
                timeout: requestSettings.timeout,
                cache: requestSettings.cache
            }).done(function(json) {
                requestSettings.end(json.login);
            }).fail(function(response) {
                requestSettings.error(response.responseJSON.exception);
            });
        }

        login_query();
}



/**
 * Obtains one or more users.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 *
 */
fimApi.prototype.getUsers = function(params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'info' : ['self', 'groups', 'profile'],
        'access_token' : window.sessionHash,
        'fim3_format' : 'json',
        'userIds' : null,
        'userNames' : null,
        'userNameSearch' : null
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    function getUsers_query() {
        $.ajax({
//            'async' : false,
            type: 'get',
            url: directory + 'api/user.php',
            data: params,
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
    }

    getUsers_query();
};


fimApi.prototype.getRooms = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(params, {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'id' : null,
            'roomIds' : null,
            'roomNames' : null,
            'roomNameSearch' : null,
            'permLevel' : null
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

        function getRooms_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/room.php',
                data: params,
                timeout: requestSettings.timeout,
                cache: requestSettings.cache
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
        }


        if (requestSettings.close) clearInterval(fimApi.timers['getRooms_' + requestSettings.timerId]);

        getRooms_query();
        if (requestSettings.refresh > -1) fimApi.timers['getRooms_' + requestSettings.timerId] = setInterval(getRooms_query, requestSettings.refresh);
};


    /*            var errStr = json.getMessages.errStr,
     errDesc = json.getMessages.errDesc,
     sentUserId = 0,
     messageCount = 0;

     if (errStr) {
     sentUserId = json.getMessages.activeUser.userId;

     if (errStr === 'noperm') {
     roomId = false; // Clear the internal roomId.

     if (sentUserId) { popup.selectRoom(); dia.error('You have been restricted access from this room. Please select a new room.'); } // You are still login, but permission has been denied for whatever reason.
     else { popup.login(); dia.error('You are no longer logged in. Please log-in.'); } // If the API no longer recognises the login, prompt a relogin.
     }
     else {
     roomId = false;
     dia.error(errDesc);
     }
     }
     else {
     */

/* Messages */
fimApi.prototype.getMessages = function(params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'access_token' : window.sessionHash,
        'roomId' : null,
        'userIds' : null,
        'messageIdEnd' : null,
        'messageIdStart' : null,
        'page' : null,
        'messageTextSearch' : null,
        'archive' : false,
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    function getMessages_query() {
        $.ajax({
            type: 'get',
            url: directory + 'api/message.php',
            data: params,
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
    }


    if (requestSettings.close) clearInterval(fimApi.timers['getMessages_' + requestSettings.timerId]);
    else {
        getMessages_query();
        if (requestSettings.refresh > -1) fimApi.timers['getMessages_' + requestSettings.timerId] = setInterval(getMessages_query, requestSettings.refresh);
    }
};



fimApi.prototype.sendMessage = function(roomId, params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'ignoreBlock' : false, // TODO
        'message' : null,
        'flag' : null
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/message.php?' + $.param({
            'access_token' : window.sessionHash,
            'roomId' : roomId
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
};



fimApi.prototype.editMessage = function(roomId, messageId, params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'ignoreBlock' : false, // TODO
        'message' : null,
        'flag' : null
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/message.php?' + $.param({
            '_action' : 'edit',
            'access_token': window.sessionHash,
            'id' : messageId,
            'roomId' : roomId
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
}


fimApi.prototype.deleteMessage = function(roomId, messageId, requestSettings) {
    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/message.php?' + $.param({
            '_action' : 'delete',
            'access_token': window.sessionHash,
            'id' : messageId,
            'roomId' : roomId
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
}



/* Unread Messages */

fimApi.prototype.getUnreadMessages = function(params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'access_token' : window.sessionHash,
        'fim3_format' : 'json',
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.mergeDefaults({
        'timeout': 30000,
        'refresh': 30000
    }, fimApi.requestDefaults));

    function getUnreadMessages_query() {
        $.ajax({
            type: 'get',
            url: directory + 'api/unreadMessages.php',
            data: params,
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
    }


    if (requestSettings.close) clearInterval(fimApi.timers['getUnreadMessages_' + requestSettings.timerId]);
    else {
        getUnreadMessages_query();
        if (requestSettings.refresh > -1) fimApi.timers['getUnreadMessages_' + requestSettings.timerId] = setInterval(getUnreadMessages_query, requestSettings.refresh);
    }
};



fimApi.prototype.getFiles = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(params, {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'userIds' : '',
            'fileIds' : ''
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);


        function getFiles_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/files.php',
                data: params,
                timeout: requestSettings.timeout,
                cache: requestSettings.cache
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
        }


        if (requestSettings.close) clearInterval(fimApi.timers['getFiles_' + requestSettings.timerId]);

        getFiles_query();
};



fimApi.prototype.getStats = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(params, {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'roomIds' : '',
            'number' : 10
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);


        function getStats_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/stats.php',
                data: params,
                timeout: requestSettings.timeout,
                cache: requestSettings.cache
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
        }


        if (requestSettings.close) clearInterval(fimApi.timers['getStats_' + requestSettings.timerId]);

        getStats_query();
        if (requestSettings.refresh > -1) fimApi.timers['getStats_' + requestSettings.timerId] = setInterval(getStats_query, requestSettings.refresh);
};



fimApi.prototype.getKicks = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(params, {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'roomIds' : null,
            'userIds' : null
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);


        function getKicks_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/getKicks.php',
                data: params,
                timeout: requestSettings.timeout,
                cache: requestSettings.cache
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
        }


        if (requestSettings.close) clearInterval(fimApi.timers['getKicks_' + requestSettings.timerId]);

        getKicks_query();
        if (requestSettings.refresh > -1) fimApi.timers['getKicks_' + requestSettings.timerId] = setInterval(getKicks_query, requestSettings.refresh);
};



fimApi.prototype.getCensorLists = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(params, {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'roomId' : null,
            'listIds' : null,
            'includeWords' : 1 // true
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);


        function getCensorLists_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/getCensorLists.php',
                data: params,
                timeout: requestSettings.timeout,
                cache: requestSettings.cache
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));;
        }


        if (requestSettings.close) clearInterval(fimApi.timers['getCensorLists_' + requestSettings.timerId]);

        getCensorLists_query();
        if (requestSettings.refresh > -1) fimApi.timers['getCensorLists_' + requestSettings.timerId] = setInterval(getCensorLists_query, requestSettings.refresh);
};



fimApi.prototype.getActiveUsers = function(params, requestSettings) {
    function getActiveUsers_query() {
        requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

        $.ajax({
            type: 'get',
            url: directory + 'api/userStatus.php',
            data: fimApi.mergeDefaults(params, {
                'access_token' : window.sessionHash,
                'fim3_format' : 'json',
                'roomIds' : null,
                'userIds' : null,
                'onlineThreshold' : null
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
    }


    if (!window.sessionHash)
        console.log("Get active users called without window.sessionHash being set. Please retry once set.");

    else if (requestSettings.close)
        clearInterval(fimApi.timers['getActiveUsers_' + requestSettings.timerId]);

    else {
        getActiveUsers_query();
        if (requestSettings.refresh > -1) {
            clearInterval(fimApi.timers['getActiveUsers_' + requestSettings.timerId]);
            fimApi.timers['getActiveUsers_' + requestSettings.timerId] = setInterval(getActiveUsers_query, requestSettings.refresh);
        }
    }
};


fimApi.prototype.acHelper = function(list) {
    return function acHelper_query(search, callback) {
        $.ajax({
            type: 'get',
            url: directory + 'api/acHelper.php',
            data: {
                'access_token' : window.sessionHash,
                'fim3_format' : 'json',
                'list' : list,
                'search' : search.term
            },
            success : function(json) {
                callback($.map(json.entries, function (value, key) {
                    return {
                        label: value,
                        value: key
                    };
                }));
            },

        });
    }
};


fimApi.prototype.kickUser = function(userId, roomId, length, requestSettings) {
    var params = {
        'access_token' : window.sessionHash,
        'fim3_format' : 'json',
        'roomId' : roomId,
        'userId' : userId,
        'length' : length,
        'action' : 'kickUser',
    };

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/moderate.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
};


fimApi.prototype.unkickUser = function(userId, roomId, requestSettings) {
    var params = {
        'access_token' : window.sessionHash,
        'fim3_format' : 'json',
        'roomId' : roomId,
        'userId' : userId,
        'action' : 'unkickUser',
    };

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/moderate.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
};


fimApi.prototype.markMessageRead = function(roomId, requestSettings) {
    var params = {
        'access_token' : window.sessionHash,
        'fim3_format' : 'json',
        'roomId' : roomId,
    };

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/markMessageRead.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
};

fimApi.prototype.editUserOptions = function(params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'access_token' : window.sessionHash,
        'fim3_format' : 'json',
        '_action' : "edit",
        'defaultFormatting' : null,
        'defaultColor' : null,
        'defaultHighlight' : null,
        'defaultRoomId' : null,
        'watchRooms' : null,
        'ignoreList': null,
        'profile': null,
        'defaultFontface': null,
        'parentalAge': null,
        'parentalFlags': null,
        'avatar' : null,
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/editUserOptions.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
};



fimApi.prototype.editRoom = function(id, params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'name' : null,
        'defaultPermissions' : null,
        'userPermissions' : null,
        'groupPermissions' : null,
        'censorLists' : null,
        'parentalAge': null,
        'parentalFlags': null,
        'hidden' : null,
        'official' : null,
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.mergeDefaults({
        'action': 'edit'
    }, fimApi.requestDefaults));

    $.ajax({
        url: directory + 'api/room.php?_action=' + requestSettings['action'] + '&access_token=' + window.sessionHash + (id ? '&id=' + id : ''),
        method: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
};


fimApi.prototype.createRoom = function(params, requestSettings) {
    fimApi.editRoom(null, params, fimApi.mergeDefaults({
        'action': 'create'
    }, requestSettings));
}


fimApi.prototype.deleteRoom = function(id, requestSettings) {
    fimApi.editRoom(id, null, fimApi.mergeDefaults({
        '_action': 'delete'
    }, requestSettings));
}

fimApi.prototype.undeleteRoom = function(id, requestSettings) {
    fimApi.editRoom(id, null, fimApi.mergeDefaults({
        '_action': 'undelete'
    }, requestSettings));
}



fimApi.prototype.editRoomPermissionUser = function(roomId, userId, permissionsArray) {
    var permissionsObj = {};
    permissionsObj['*' + userId] = permissionsArray;
    fimApi.editRoom({
        'roomId' : roomId,
        'userPermissions' : permissionsObj
    });
}

fimApi.prototype.editRoomPermissionGroup = function(roomId, groupId, permissionsArray) {
    var permissionsObj = {};
    permissionsObj['*' + groupId] = permissionsArray;
    fimApi.editRoom({
        'roomId' : roomId,
        'groupPermissions' : permissionsObj
    });
}


fimApi.prototype.editUserStatus = function(roomId, params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'status': null,
        'typing': null
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/userStatus.php?' + $.param({
            '_action' : 'edit',
            'access_token': window.sessionHash,
            'roomIds' : [roomId]
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
}



fimApi.prototype.ping = function(roomId, requestSettings) {
    this.editUserStatus(roomId, {}, requestSettings);
}



fimApi.prototype.changeAvatar = function(avatarHash, requestSettings) {
    this.editUserOptions({
        'avatarHash' : avatarHash,
    }, requestSettings);
};



fimApi.prototype.mergeDefaults = function(object, defaults) {
    var returnObject = {};

    for (var i in object) {
        if (!(i in defaults)) {
            throw 'Invalid data in object call: ' + i;
        }

        if (object[i] !== null) returnObject[i] = object[i];
    }

    for (var i in defaults) {
        if (!(i in returnObject) && defaults[i] !== null) returnObject[i] = defaults[i];
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



fimApi.prototype.jsonify = function(object, properties) {
    var returnObject = (object === undefined ? {} : Object.create(object));

    for (var i in properties) {
        if (properties[i] in returnObject) returnObject[properties[i]] = JSON.stringify(returnObject[properties[i]]);
    }

    return returnObject;
};