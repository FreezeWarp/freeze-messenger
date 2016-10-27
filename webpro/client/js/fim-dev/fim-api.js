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
        'end' : function() {}
    };

    this.timers = {};

    this.done = function(requestSettings) {
        return function (json) {
            // This digs into the tree a bit to where the array is. Perhaps somewhat inelegant, but it will work for our purposes, and does so quite simply.
            var firstElement = json["firstIndex" in requestSettings ? requestSettings.firstIndex : Object.keys(json)[0]];
            //var secondElement = firstElement["secondIndex" in requestSettings ? requestSettings.secondIndex : Object.keys(firstElement)[0]];

            console.log(firstElement);
            console.log(requestSettings);
            requestSettings.begin(firstElement);
            if (requestSettings.reverseEach) firstElement = firstElement.reverse();
            $.each(firstElement, function (index, value) {
                requestSettings.each(value);
            });

            requestSettings.end(firstElement);
        }
    };

    this.fail = function(requestSettings) {
        return function(response) { console.log(response);
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
            'username' : '',
            'password' : '',
            'client_id' : ''
        });

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
            }).fail(function(response) { console.log(response); console.log(this);
                requestSettings.error(response.responseJSON.exception);
            });
        }

        login_query();
}



    /**
     * Obtains one or more users.
     * This function is async.
     *
     * @param object userId - The ID of the user to obtain info of.
     *
     * @author Jospeph T. Parsons <josephtparsons@gmail.com>
     * @copyright Joseph T. Parsons 2014
     *
     */
fimApi.prototype.getUsers = function(params, requestSettings, async) {
        var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['userIds']), {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'userIds' : '',
            'userNames' : '',
            'userNameSearch' : ''
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

        function getUsers_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/getUsers.php',
                data: params,
                timeout: requestSettings.timeout,
                cache: requestSettings.cache
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
        }
};


fimApi.prototype.getRooms = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['roomIds']), {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'roomIds' : '',
            'roomNames' : '',
            'roomNameSearch' : '',
            'permLevel' : ''
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

        function getRooms_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/getRooms.php',
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

fimApi.prototype.getMessages = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['userIds', 'messageIds']), {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'roomId' : '',
            'userIds' : '',
            'messageIds' : '',
            'messageIdEnd' : 0,
            'messageIdStart' : 0,
            'page' : 0,
            'search' : '',
            'archive' : 0,
            'messageHardLimit' : 25,
            'sortOrder' : 'asc',
            'initialRequest' : false,
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

        if (params.initialRequest) {
            requestSettings.reverseEach = true;
            params.sortOrder = 'desc';
            params.archive = 1;
            params.messageIdEnd = 0;
            params.messageIdStart = 0;
        }

        function getMessages_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/getMessages.php',
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



fimApi.prototype.getFiles = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['userIds', 'fileIds']), {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'userIds' : '',
            'fileIds' : ''
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);


        function getFiles_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/getFiles.php',
                data: params,
                timeout: requestSettings.timeout,
                cache: requestSettings.cache
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
        }


        if (requestSettings.close) clearInterval(fimApi.timers['getFiles_' + requestSettings.timerId]);

        getFiles_query();
};



fimApi.prototype.getStats = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['roomIds']), {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'roomIds' : '',
            'number' : 10
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);


        function getStats_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/getStats.php',
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
        var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['roomIds', 'userIds']), {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'roomIds' : '',
            'userIds' : ''
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
},

fimApi.prototype.getCensorLists = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['roomIds', 'listIds']), {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'roomIds' : '',
            'listIds' : '',
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
        var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['roomIds', 'userIds']), {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'roomIds' : null,
            'userIds' : null,
            'onlineThreshold' : null
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

        function getActiveUsers_query() {
            $.ajax({
                type: 'get',
                url: directory + 'api/getActiveUsers.php',
                data: params,
                timeout: requestSettings.timeout,
                cache: requestSettings.cache
            }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
        }


        if (requestSettings.close) clearInterval(fimApi.timers['getActiveUsers_' + requestSettings.timerId]);
        else {
            getActiveUsers_query();
            if (requestSettings.refresh > -1) fimApi.timers['getActiveUsers_' + requestSettings.timerId] = setInterval(getActiveUsers_query, requestSettings.refresh);
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
                    callback(json.acHelper.entries);
                    console.log(json);
                }
            });
        }
};



fimApi.prototype.editFile = function(params, requestSettings) {
        var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['parentalFlags']), {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'action' : 'create',
            'daataEncode' : 'base64',
            'uploadMethod' : 'raw',
            'autoInsert' : true,
            'roomId' : null,
            'fileName' : '',
            'fileData' : '',
            'parentalFlags' : '[]',
            'parentalAge' : 0,
            'md5hash' : null
        });

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

        $.ajax({
            url : directory + 'api/editFile.php',
            type : 'POST',
            data: params,
            timeout: requestSettings.timeout,
            cache: requestSettings.cache,
        }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
}



fimApi.prototype.sendMessage = function(params, requestSettings) {console.log(params);
        var params = fimApi.mergeDefaults(params, {
            'access_token' : window.sessionHash,
            'fim3_format' : 'json',
            'roomId' : null,
            'ignoreBlock' : false, // TODO
            'message' : null,
            'flag' : null
        }); console.log(params);

        var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

        $.ajax({
            url: directory + 'api/sendMessage.php',
            type: 'POST',
            data: params,
            timeout: requestSettings.timeout,
            cache: requestSettings.cache,
        }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));;
};



fimApi.prototype.editUserOptions = function(params, requestSettings) {
    var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['parentalFlags']), {
        'access_token': window.sessionHash,
        'fim3_format': 'json',
        'action': 'create',
        'daataEncode': 'base64',
        'uploadMethod': 'raw',
        'autoInsert': true,
        'roomId': null,
        'fileName': '',
        'fileData': '',
        'parentalFlags': '[]',
        'parentalAge': 0,
        'md5hash': null,
        'avatarHash' : null
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/editFile.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
}



fimApi.prototype.editMessage = function(params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'access_token': window.sessionHash,
        'fim3_format': 'json',
        'messageId' : null,
        'action': null,
        'text': null,
        'flag': null,
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/editMessage.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
}


fimApi.prototype.editUserStatus = function(params, requestSettings) {
    var params = fimApi.mergeDefaults(params, {
        'access_token': window.sessionHash,
        'fim3_format': 'json',
        'roomId' : null,
        'status': null,
        'typing': null,
    }); console.log(params);

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
        url: directory + 'api/editUserStatus.php',
        type: 'POST',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache,
    }).done(fimApi.done(requestSettings)).fail(fimApi.fail(requestSettings));
}


fimApi.prototype.ping = function(roomId, requestSettings) {
    this.editUserStatus({
        'roomId' : roomId
    }, requestSettings);
}



fimApi.prototype.changeAvatar = function(avatarHash, requestSettings) {
    this.editUserOptions({
        'avatarHash' : avatarHash,
    }, requestSettings);
};



fimApi.prototype.mergeDefaults = function(object, defaults) {
    var returnObject = (object === undefined ? {} : Object.create(object));

    for (var i in defaults) {
        if (!(i in returnObject) && defaults[i] !== null) returnObject[i] = defaults[i];
    }

    /*** START STRICT CODE -- NOT NECCESSARY IN PRODUCTION ***/
    for (var i in returnObject) {
        if (!(i in defaults)) {
            throw 'Invalid data in object call: ' + i;
        }
    }
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