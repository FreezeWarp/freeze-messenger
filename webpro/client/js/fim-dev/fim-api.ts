/*
 * $.ajax-to-fetch polyfill for Service Workers
 * Still requires a minimal jQuery version to be included, for $.each, $.param, etc.
 */
if (typeof XMLHttpRequest === 'undefined') {
    $.ajax = function (options) {
        let params = {
            method : options.type ? options.type.toUpperCase() : 'GET',
        };

        if (options.data && params.method !== 'GET') {
            params['body'] = new URLSearchParams($.param(options.data));
        }

        let url = options.url + (params.method === 'GET' && options.data ? '?' + $.param(options.data) : '');

        console.log("request", options, url, params);

        let promise = jQuery.Deferred();

        $.when(fetch(url, params)).then(function(response) {
            let contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            }
            else {
                promise.reject("Could not parse JSON.");
            }
        })
            .then(function(json) { promise.resolve(json); })
            .catch(function(error) { promise.reject(error); });;

        return promise.promise();
    }
}


let fimApi = function(serverSettings) {
    this.directory = serverSettings.installUrl;
    this.serverSettings = serverSettings;

    this.lastSessionHash = '';

    this.requestDefaults = {
        'close' : false,
        'timerId' : 1,
        'refresh' : false,
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

    this.done = function(requestSettings, firstIndex) {
        return function (json) {
            // This digs into the tree a bit to where the array is. Perhaps somewhat inelegant, but it will work for our purposes, and does so quite simply.

            try {
                let firstElement = json[firstIndex ? firstIndex : Object.keys(json)[0]];

                requestSettings.begin(firstElement);

                if (requestSettings.reverseEach) firstElement = firstElement.reverse();
                $.each(firstElement, function (index, value) {
                    requestSettings.each(value);
                });

                requestSettings.end(firstElement, json.metadata);
            } catch (e) {
                console.log("Failed to parse information: ", json, e);
            }
        }
    };

    this.fail = function(requestSettings, callback) {
        return function(response) {
            if (!("responseJSON" in response)
                && ("responseText" in response)
                && response.responseText.slice(0,1) === '{')
                response.responseJSON = JSON.parse(response.responseText);

            if (!("responseJSON" in response)) {
                console.log("Unable to parse failure response.", response);
            }
            else if ("exception" in response.responseJSON) {
                console.log("Server Exception", JSON.stringify(response.responseJSON));
                return requestSettings.exception(response.responseJSON.exception, callback);
            }

            return requestSettings.error(response, callback);
        }
    };

    this.timer = ((requestSettings, name, query) => {
        if (requestSettings.close) {
            console.log("close " + name + '_' + requestSettings.timerId, this.timers)
            clearInterval(this.timers[name + '_' + requestSettings.timerId]);
            delete this.timers[name + '_' + requestSettings.timerId];
        }
        else {
            query(requestSettings);

            if (requestSettings.refresh > 0) {
                clearInterval(this.timers[name + '_' + requestSettings.timerId]);
                this.timers[name + '_' + requestSettings.timerId] = setInterval(function() {
                    query(requestSettings)
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

    this.mergeDefaults = function(object, defaults) {
        var returnObject = {};

        for (var i in object) {
            if (!(i in defaults)) {
                throw 'Invalid data in object call: ' + i;
            }

            if (object[i] !== null && object[i] !== undefined) returnObject[i] = object[i];
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

    this.jsonify = function(object, properties) {
        var returnObject = (object === undefined ? {} : Object.create(object));

        for (var i in properties) {
            if (properties[i] in returnObject) returnObject[properties[i]] = JSON.stringify(returnObject[properties[i]]);
        }

        return returnObject;
    };

    return;
};



fimApi.prototype.login = function (params, requestSettings) {
    params = this.mergeDefaults(params, {
        'grant_type' : 'password',
        'username' : null,
        'password' : null,
        'access_token' : null,
        'refresh_token' : null,
        'client_id' : ''
    });

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    return $.ajax({
        url: this.directory + 'validate.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done((json) => {
        if (json.login.access_token) {
            this.lastSessionHash = json.login.access_token;
        }
        requestSettings.end(json.login);
    }).fail(this.fail(requestSettings));
};



/**
 * Obtains one or more users.
 */
fimApi.prototype.getUsers = function(params, requestSettings) {
    params = this.mergeDefaults(params, {
        'info' : ['self', 'groups', 'profile'],
        'access_token' : this.lastSessionHash,
        'id' : null,
        'userIds' : null,
        'userNames' : null,
        'userNameSearch' : null
    });

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        type: 'get',
        url: this.directory + 'api/user.php',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.getUsers(params, requestSettings)
    })));
};

fimApi.prototype.createUser = function(params, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    return $.ajax({
        type: 'post',
        url: this.directory + 'api/user.php',
        data: this.mergeDefaults(params, {
            'name' : null,
            'password' : null,
            'birthDate' : null,
            'email' : null
        }),
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.getUsers(params, requestSettings)
    })));
};


fimApi.prototype.getRooms = function(params, requestSettings) {
    params = this.mergeDefaults(params, {
        'access_token' : this.lastSessionHash,
        'id' : null,
        'roomIds' : null,
        'roomNames' : null,
        'roomNameSearch' : null,
        'permFilter' : null,
        'page' : null
    });

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        type: 'get',
        url: this.directory + 'api/room.php',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.getRooms(params, requestSettings)
    })));
};



fimApi.prototype.getEventsFallback = function(params, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    this.timer(requestSettings, "getEventsFallback", ((requestSettings) => {
        $.ajax({
            type: 'get',
            url: this.directory + 'stream.php',
            data: this.mergeDefaults(params, {
                'fallback' : true,
                'access_token' : this.lastSessionHash,
                'streamType' : null,
                'queryId' : null,
                'lastEvent' : null,
                'lastMessage' : null,
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done((response) => {
            this.done(requestSettings)(response);
        }).fail(((response) => {
            if (requestSettings.refresh) {
                this.getEventsFallback(null, {close : true});
            }

            this.fail(requestSettings, (() => {
                this.getEventsFallback(params, requestSettings)
            }))(response);
        }));
    }));
};

/* Messages */
fimApi.prototype.getMessages = function(params, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    let getMessages_query = (requestSettings) => {
        $.ajax({
            type: 'get',
            url: this.directory + 'api/message.php',
            data: this.mergeDefaults(params, {
                'access_token' : this.lastSessionHash,
                'roomId' : null,
                'id' : null,
                'userIds' : null,
                'messageIdEnd' : null,
                'messageIdStart' : null,
                'page' : null,
                'messageTextSearch' : null,
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(this.done(requestSettings)).fail(
            this.fail(requestSettings, (() => {
                this.getMessages(params, requestSettings)
            }))
        );
    }

    this.timer(requestSettings, "getMessages", getMessages_query);
};



fimApi.prototype.sendMessage = function(roomId, params, requestSettings) {
    params = this.mergeDefaults(params, {
        'access_token' : this.lastSessionHash,
        'ignoreBlock' : false, // TODO
        'message' : null,
        'flag' : null
    });

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/message.php?' + $.param({
            'roomId' : roomId
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.sendMessage(roomId, params, requestSettings)
    })));
};



fimApi.prototype.editMessage = function(roomId, messageId, params, requestSettings) {
    params = this.mergeDefaults(params, {
        'ignoreBlock' : false, // TODO
        'message' : null,
        'flag' : null
    });

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/message.php?' + $.param({
            '_action' : 'edit',
            'access_token': this.lastSessionHash,
            'id' : messageId,
            'roomId' : roomId
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.editMessage(roomId, messageId, params, requestSettings)
    })));
}


fimApi.prototype.deleteMessage = function(roomId, messageId, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/message.php?' + $.param({
            '_action' : 'delete',
            'access_token': this.lastSessionHash,
            'id' : messageId,
            'roomId' : roomId
        }),
        type: 'POST',
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.deleteMessage(roomId, messageId, requestSettings)
    })));
};



/* Unread Messages */

fimApi.prototype.getUnreadMessages = function(params, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.mergeDefaults({
        'timeout': 30000,
        'refresh': 30000
    }, this.requestDefaults));

    let getUnreadMessages_query = (requestSettings) => {
        $.ajax({
            type: 'get',
            url: this.directory + 'api/unreadMessages.php',
            data: this.mergeDefaults(params, {
                'access_token' : this.lastSessionHash,
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(this.done(requestSettings)).fail(((response) => {
            if (requestSettings.refresh) {
                this.getUnreadMessages(null, {close : true});
            }

            this.fail(requestSettings, (() => {
                this.getUnreadMessages(params, requestSettings)
            }))(response);
        }));
    };

    fimApi.timer(requestSettings, "getUnreadMessages", getUnreadMessages_query);
};



fimApi.prototype.getFiles = function(params, requestSettings) {
    params = this.mergeDefaults(params, {
        access_token : this.lastSessionHash,
        userIds : [],
        fileIds : [],
        page : 0
    });

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        type: 'get',
        url: this.directory + 'api/file.php',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.getFiles(params. requestSettings)
    })));
};


fimApi.prototype.deleteFile = function(fileId, requestSettings) {
    let requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        type: 'post',
        url: this.directory + 'api/file.php?' + $.param({
            'access_token' : this.lastSessionHash,
            '_action' : 'delete',
            'id' : fileId,
        }),
        timeout: requestSettings.timeout
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.deleteFile(fileId, requestSettings)
    })));
};



fimApi.prototype.getStats = function(params, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        type: 'get',
        url: this.directory + 'api/stats.php',
        data: this.mergeDefaults(params, {
            'access_token' : this.lastSessionHash,
            'roomId' : null,
            'number' : 10
        }),
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings, 'roomStats')).fail(this.fail(requestSettings, (() => {
        this.getStats(params, requestSettings)
    })));
};



fimApi.prototype.getKicks = function(params, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        type: 'get',
        url: this.directory + 'api/kick.php',
        data: this.mergeDefaults(params, {
            'access_token' : this.lastSessionHash,
            'roomId' : null,
            'userId' : null
        }),
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.getKicks(params, requestSettings)
    })));
};



fimApi.prototype.getCensorLists = function(params, requestSettings) {
    params = this.mergeDefaults(params, {
        'access_token' : this.lastSessionHash,
        'roomId' : null,
        'listIds' : null,
        'includeWords' : 1 // true
    });

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);


    $.ajax({
        type: 'get',
        url: this.directory + 'api/getCensorLists.php',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.getCensorLists(params, requestSettings)
    })));
};



fimApi.prototype.getActiveUsers = function(params, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    let getActiveUsers_query = (requestSettings) => {
        $.ajax({
            type: 'get',
            url: this.directory + 'api/userStatus.php',
            data: this.mergeDefaults(params, {
                'access_token' : this.lastSessionHash,
                'roomIds' : null,
                'userIds' : null,
                'onlineThreshold' : null
            }),
            timeout: requestSettings.timeout,
            cache: requestSettings.cache
        }).done(this.done(requestSettings, 'users')).fail(((response) => {
            if (requestSettings.refresh) {
                this.getActiveUsers(null, {close : true});
            }

            this.fail(requestSettings, (() => {
                this.getActiveUsers(params, requestSettings)
            }))(response);
        }));
    }

    fimApi.timer(requestSettings, "getActiveUsers", getActiveUsers_query);
};


fimApi.prototype.getGroups = function(params, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        type: 'get',
        url: this.directory + 'api/group.php',
        data: this.mergeDefaults(params, {
            'access_token' : this.lastSessionHash,
            'groupIds' : null,
            'groupNames' : null,
            'groupNameSearch' : null
        }),
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.getGroups(params, requestSettings)
    })));
};


fimApi.prototype.acHelper = function(list) {
    return (search, callback) => {
        $.ajax({
            type: 'get',
            url: this.directory + 'api/acHelper.php',
            data: {
                'access_token' : this.lastSessionHash,
                'list' : list,
                'search' : search.term
            },
            success : (json) => {
                callback($.map(json.entries, (value, key) => {
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
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/kick.php?' + $.param({
            'access_token' : this.lastSessionHash,
            'roomId' : roomId,
            'userId' : userId
        }),
        type: 'POST',
        data: {
            'length' : length
        },
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.kickUser(userId, roomId, length, requestSettings)
    })));
};


fimApi.prototype.unkickUser = function(userId, roomId, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/kick.php?' + $.param({
            'access_token' : this.lastSessionHash,
            '_action' : 'delete',
            'roomId' : roomId,
            'userId' : userId
        }),
        type: 'POST',
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.unkickUser(userId, roomId, requestSettings)
    })));
};


fimApi.prototype.markMessageRead = function(roomId, requestSettings) {
    let params = {
        'access_token' : this.lastSessionHash,
        '_action' : 'delete',
        'roomId' : roomId,
    };

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/unreadMessages.php',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.markMessageRead(roomId, requestSettings)
    })));
};

fimApi.prototype.editUserOptions = function(action, params, requestSettings) {
    params = this.mergeDefaults(params, {
        'defaultFormatting' : null,
        'defaultColor' : null,
        'defaultHighlight' : null,
        'defaultRoomId' : null,
        'favRooms' : null,
        'ignoreList': null,
        'friendsList': null,
        'profile': null,
        'defaultFontface': null,
        'parentalAge': null,
        'parentalFlags': null,
        'avatar' : null,
        'privacyLevel' : null
    });

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/userOptions.php?' + $.param({
            'access_token' : this.lastSessionHash,
            '_action' : (action ? action : "edit")
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.editUserOptions(action, params, requestSettings)
    })));
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

fimApi.prototype.editRoom = function(id, action, params, requestSettings) {
    params = this.mergeDefaults(params, {
        'access_token' : this.lastSessionHash,
        'name' : null,
        'defaultPermissions' : null,
        'userPermissions' : null,
        'groupPermissions' : null,
        'censorLists' : null,
        'parentalAge': null,
        'parentalFlags': null,
        'hidden' : null,
        'official' : null
    });

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/room.php?' + $.param(this.mergeDefaults({}, {
            'id' : id,
            '_action' : action
        })),
        method: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.editRoom(id, params, requestSettings)
    })));
};


fimApi.prototype.createRoom = function(params, requestSettings) {
    this.editRoom(null, 'create', params, requestSettings);
};


fimApi.prototype.deleteRoom = function(id, requestSettings) {
    this.editRoom(id, 'delete', {}, requestSettings);
};

fimApi.prototype.undeleteRoom = function(id, requestSettings) {
    this.editRoom(id, 'undelete', {}, requestSettings);
};



fimApi.prototype.editRoomPermission = function(action, param, roomId, paramId, permissionsArray, requestSettings) {
    let requestHead = {
        '_action' : action,
        'access_token' : this.lastSessionHash,
        'roomId' : roomId,
    };
    requestHead[param + 'Id'] = paramId;
    
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/roomPermission.php?' + $.param(requestHead),
        method: 'POST',
        data: {
            'permissions' : permissionsArray
        },
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, (() => {
        this.editRoomPermission(action, param, roomId, paramId, permissionsArray, requestSettings)
    })));
};

fimApi.prototype.editRoomPermissionUser = function(roomId, userId, permissionsArray, requestSettings) {
    this.editRoomPermission('edit', 'user', roomId, userId, permissionsArray, requestSettings);
};

fimApi.prototype.editRoomPermissionGroup = function(roomId, groupId, permissionsArray, requestSettings) {
    this.editRoomPermission('edit', 'group', roomId, groupId, permissionsArray, requestSettings);
};

fimApi.prototype.deleteRoomPermissionUser = function(roomId, userId, permissionsArray, requestSettings) {
    this.editRoomPermission('delete', 'user', roomId, userId, permissionsArray, requestSettings);
};

fimApi.prototype.deleteRoomPermissionGroup = function(roomId, groupId, permissionsArray, requestSettings) {
    this.editRoomPermission('delete', 'group', roomId, groupId, permissionsArray, requestSettings);
};

fimApi.prototype.createRoomPermissionUser = function(roomId, userId, permissionsArray, requestSettings) {
    this.editRoomPermission('create', 'user', roomId, userId, permissionsArray, requestSettings);
};

fimApi.prototype.createRoomPermissionGroup = function(roomId, groupId, permissionsArray, requestSettings) {
    this.editRoomPermission('create', 'group', roomId, groupId, permissionsArray, requestSettings);
};



fimApi.prototype.editUserStatus = function(roomIds, params, requestSettings) {
    params = this.mergeDefaults(params, {
        'status': null,
        'typing': null
    });

    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/userStatus.php?' + $.param({
            '_action' : 'edit',
            'access_token': this.lastSessionHash,
            'roomIds' : roomIds
        }),
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, () => {
        this.editUserStatus(roomIds, params, requestSettings)
    }));
};



fimApi.prototype.ping = function(roomId, requestSettings) {
    this.editUserStatus([roomId], {"status" : ""}, requestSettings);
};

fimApi.prototype.exitRoom = function(roomId, requestSettings) {
    this.editUserStatus([roomId], {"status" : "offline"}, requestSettings);
};

fimApi.prototype.startedTyping = function(roomId, requestSettings) {
    if (this.serverSettings.rooms.typingStatus)
        this.editUserStatus([roomId], {"typing" : true}, requestSettings);
};

fimApi.prototype.stoppedTyping = function(roomId, requestSettings) {
    if (this.serverSettings.rooms.typingStatus)
        this.editUserStatus([roomId], {"typing" : false}, requestSettings);
};



fimApi.prototype.changeAvatar = function(avatarHash, requestSettings) {
    this.editUserOptions({
        'avatarHash' : avatarHash,
    }, requestSettings);
};



fimApi.prototype.pushSub = function(endpoint, p256dh, auth, requestSettings) {
    requestSettings = this.mergeDefaults(requestSettings, this.requestDefaults);

    $.ajax({
        url: this.directory + 'api/webpushSubscribe.php',
        type: 'POST',
        data: {
            'access_token': this.lastSessionHash,
            'endpoint' : endpoint,
            'p256dh' : p256dh,
            'auth' : auth,
        },
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(this.done(requestSettings)).fail(this.fail(requestSettings, () => {
        this.pushSub(endpoint, requestSettings)
    }));
};
