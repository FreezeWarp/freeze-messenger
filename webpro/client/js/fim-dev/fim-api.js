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
        'autoId' : false
    };

    this.timers = {};

    this.done = function(requestSettings, firstIndex) {
        return function (json) {
            // This digs into the tree a bit to where the array is. Perhaps somewhat inelegant, but it will work for our purposes, and does so quite simply.
            var firstElement = json[firstIndex ? firstIndex : Object.keys(json)[0]];
            //var secondElement = firstElement["secondIndex" in requestSettings ? requestSettings.secondIndex : Object.keys(firstElement)[0]];

            requestSettings.begin(firstElement);
            if (requestSettings.reverseEach) firstElement = firstElement.reverse();
            $.each(firstElement, function (index, value) {
                requestSettings.each(value);
            });

            requestSettings.end(firstElement);
        }
    };

    this.fail = function(requestSettings, callback) {
        return function(response) {
            if (!("responseJSON" in response)) response.responseJSON = JSON.parse(response.responseText);

            if ("exception" in response.responseJSON) {
                if (response.responseJSON.exception.details == 'The access token provided has expired') {
                    standard.login({
                        'username' : $.cookie('webpro_username'),
                        'password' : $.cookie('webpro_username'),
                        'finish' : callback
                    });
                }
                else {
                    return requestSettings.exception(response.responseJSON.exception);
                }
            }
            else
                return requestSettings.error(response);
        }
    };

    this.timer = function(requestSettings, name, query) {
        if (requestSettings.close) {
            console.log("close " + name + '_' + requestSettings.timerId, fimApi.timers)
            clearInterval(fimApi.timers[name + '_' + requestSettings.timerId]);
            delete fimApi.timers[name + '_' + requestSettings.timerId];
        }
        else {
            query(requestSettings);

            if (requestSettings.refresh > -1) {
                clearInterval(fimApi.timers[name + '_' + requestSettings.timerId]);
                fimApi.timers[name + '_' + requestSettings.timerId] = setInterval(function() {
                    query(requestSettings)
                }, requestSettings.refresh);
            }
        }
    };

    this.registerDefaultExceptionHandler = function (exceptionHandler) {
        this.requestDefaults.exception = exceptionHandler;
    };

    this.getDefaultExceptionHandler = function () {
        return this.requestDefaults.exception;
    };

    return;
};



fimApi.prototype.login = function (params, requestSettings) {
        var params = fimApi.mergeDefaults(params, {
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
        }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
            fimApi.getUsers(params, requestSettings)
        }));
    }

    getUsers_query();
};


fimApi.prototype.getRooms = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(params, {
            'access_token' : window.sessionHash,
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
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
                fimApi.getRooms(params, requestSettings)
            }));
        }

        getRooms_query();
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
    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    function getMessages_query(requestSettings) {
        if (requestSettings.autoId && window.requestSettings.firstRequest) {
            requestSettings.reverseEach = true;
        }

        $.ajax({
            type: 'get',
            url: directory + 'api/message.php',
            data: fimApi.mergeDefaults(params, {
                'access_token' : window.sessionHash,
                'roomId' : null,
                'userIds' : null,
                'messageIdEnd' : null,
                'messageIdStart' : (requestSettings.autoId && window.requestSettings.lastMessage ? window.requestSettings.lastMessage + 1 : null),
                'page' : null,
                'messageTextSearch' : null,
                'archive' : (requestSettings.autoId ? window.requestSettings.firstRequest : false)
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(function(response) {
            if (requestSettings.autoId) {
                if (window.requestSettings.firstRequest)
                    window.requestSettings.firstRequest = false;

                for (var i in response["messages"]) {
                    if (response["messages"][i]["id"])
                        window.requestSettings.lastMessage = (!(Number.isNaN(Number(window.requestSettings.lastMessage))) ? Math.max(response["messages"][i]["id"], window.requestSettings.lastMessage) : response["messages"][i]["id"]);
                }
            }

            fimApi.done(requestSettings)(response);
        }).fail(function(response) {
            if (requestSettings.refresh) {
                fimApi.getMessages(null, {close : true});
            }

            fimApi.fail(requestSettings, function() {
                fimApi.getMessages(params, requestSettings)
            })(response);
        });
    }

    fimApi.timer(requestSettings, "getMessages", getMessages_query);
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
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
        fimApi.sendMessage(roomId, params, requestSettings)
    }));
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
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
        fimApi.editMessage(roomId, messageId, params, requestSettings)
    }));
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
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
        fimApi.deleteMessage(roomId, messageId, requestSettings)
    }));
}



/* Unread Messages */

fimApi.prototype.getUnreadMessages = function(params, requestSettings) {
    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.mergeDefaults({
        'timeout': 30000,
        'refresh': 30000
    }, fimApi.requestDefaults));

    function getUnreadMessages_query(requestSettings) {
        $.ajax({
            type: 'get',
            url: directory + 'api/unreadMessages.php',
            data: fimApi.mergeDefaults(params, {
                'access_token' : window.sessionHash,
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(fimApi.done(requestSettings)).fail(function(response) {
            if (requestSettings.refresh) {
                fimApi.getUnreadMessages(null, {close : true});
            }

            fimApi.fail(requestSettings, function() {
                fimApi.getUnreadMessages(params, requestSettings)
            })(response);
        });
    }

    fimApi.timer(requestSettings, "getUnreadMessages", getUnreadMessages_query);
};



fimApi.prototype.getFiles = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(params, {
            'access_token' : window.sessionHash,
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
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
                fimApi.getFiles(params. requestSettings)
            }));
        }


        if (requestSettings.close) clearInterval(fimApi.timers['getFiles_' + requestSettings.timerId]);

        getFiles_query();
};



fimApi.prototype.getStats = function(params, requestSettings) {
    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        type: 'get',
        url: directory + 'api/stats.php',
        data: fimApi.mergeDefaults(params, {
            'access_token' : window.sessionHash,
            'roomIds' : null,
            'number' : 10
        }),
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(fimApi.done(requestSettings, 'roomStats')).fail(fimApi.fail(requestSettings, function() {
        fimApi.getStats(params, requestSettings)
    }));
};



fimApi.prototype.getKicks = function(params, requestSettings) {
    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    function getKicks_query() {
        $.ajax({
            type: 'get',
            url: directory + 'api/kick.php',
            data: fimApi.mergeDefaults(params, {
                'access_token' : window.sessionHash,
                'roomId' : null,
                'userId' : null
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
            fimApi.getKicks(params, requestSettings)
        }));
    }

    getKicks_query();
};



fimApi.prototype.getCensorLists = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(params, {
            'access_token' : window.sessionHash,
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
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
                fimApi.getCensorLists(params, requestSettings)
            }));
        }

        getCensorLists_query();
};



fimApi.prototype.getActiveUsers = function(params, requestSettings) {
    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    function getActiveUsers_query(requestSettings) {
        $.ajax({
            type: 'get',
            url: directory + 'api/userStatus.php',
            data: fimApi.mergeDefaults(params, {
                'access_token' : window.sessionHash,
                'roomIds' : null,
                'userIds' : null,
                'onlineThreshold' : null
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(fimApi.done(requestSettings, 'users')).fail(function(response) {
            if (requestSettings.refresh) {
                fimApi.getActiveUsers(null, {close : true});
            }

            fimApi.fail(requestSettings, function() {
                fimApi.getActiveUsers(params, requestSettings)
            })(response);
        });
    }

    fimApi.timer(requestSettings, "getActiveUsers", getActiveUsers_query);
};


fimApi.prototype.acHelper = function(list) {
    return function acHelper_query(search, callback) {
        $.ajax({
            type: 'get',
            url: directory + 'api/acHelper.php',
            data: {
                'access_token' : window.sessionHash,
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
            }
        });
    }
};


fimApi.prototype.kickUser = function(userId, roomId, length, requestSettings) {
    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/kick.php?' + $.param({
            'access_token' : window.sessionHash,
            'roomId' : roomId,
            'userId' : userId
        }),
        type: 'POST',
        data: {
            'length' : length
        },
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
        fimApi.kickUser(userId, roomId, length, requestSettings)
    }));
};


fimApi.prototype.unkickUser = function(userId, roomId, requestSettings) {
    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/kick.php?' + $.param({
            'access_token' : window.sessionHash,
            '_action' : 'delete',
            'roomId' : roomId,
            'userId' : userId
        }),
        type: 'POST',
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
        fimApi.unkickUser(userId, roomId, requestSettings)
    }));
};


fimApi.prototype.markMessageRead = function(roomId, requestSettings) {
    var params = {
        'access_token' : window.sessionHash,
        'roomId' : roomId,
    };

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/markMessageRead.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
        fimApi.markMessageRead(roomId, requestSettings)
    }));
};

fimApi.prototype.editUserOptions = function(action, params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'defaultFormatting' : null,
        'defaultColor' : null,
        'defaultHighlight' : null,
        'defaultRoomId' : null,
        'watchRooms' : null,
        'favRooms' : null,
        'ignoreList': null,
        'profile': null,
        'defaultFontface': null,
        'parentalAge': null,
        'parentalFlags': null,
        'avatar' : null,
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/userOptions.php?' + $.param({
            'access_token' : window.sessionHash,
            '_action' : (action ? action : "edit")
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
        fimApi.editUserOptions(action, params, requestSettings)
    }));
};


fimApi.prototype.favRoom = function(roomId) {
    this.editUserOptions('create', {
        'favRooms' : [roomId]
    });
};

fimApi.prototype.unfavRoom = function(roomId) {
    this.editUserOptions('delete', {
        'favRooms' : [roomId]
    });
};

fimApi.prototype.watchRoom = function(roomId) {
    this.editUserOptions('create', {
        'watchRooms' : [roomId]
    });
};

fimApi.prototype.unwatchRoom = function(roomId) {
    this.editUserOptions('delete', {
        'watchRooms' : [roomId]
    });
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
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
        fimApi.editRoom(id, params, requestSettings)
    }));
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
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings, function() {
        fimApi.editUserStatus(roomId, params, requestSettings)
    }));
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