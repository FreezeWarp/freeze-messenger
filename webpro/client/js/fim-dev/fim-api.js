"use strict";

window.fimApi = {
  requestDefaults : {
    'close' : false,
    'timerId' : 1,
    'refresh' : -1,
    'timeout' : 5000,
    'cache' : false,
    'begin' : function() {},
    'callback' : function() {},
    'error' : function() {},
    'each' : function() {},
    'end' : function() {}
  },

  timers : {},



  login : function(params, requestSettings) {
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
        requestSettings.callback(json.login);
      }).fail(function(response) { console.log(response); console.log(this);
        requestSettings.error(response.responseJSON.exception);
      });
    }

    login_query();
  },



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
  getUsers : function(params, requestSettings, async) {
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
      }).done(function(json) {
        /*$.each(json.getUsers.users, function(index, value) { console.log(value);
          if (!(value.userId in window.userData)) window.userData[value.userId] = value;
          else {
            for (var prop in value) window.userData[value.userId][prop] = value[prop];
          }

          callback(value);
        });*/

        requestSettings.begin(json);
        $.each(json.getRooms.rooms, function(index, value) { requestSettings.each(value); });
        requestSettings.end(json);
      });
    }
  },


  getRooms : function(params, requestSettings) {
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
      }).done(function(json) {
        requestSettings.begin(json);
        $.each(json.getRooms.rooms, function(index, value) { requestSettings.each(value); });
        requestSettings.end(json);
      });
    }


    if (requestSettings.close) clearInterval(fimApi.timers['getRooms_' + requestSettings.timerId]);

    getRooms_query();
    if (requestSettings.refresh > -1) fimApi.timers['getRooms_' + requestSettings.timerId] = setInterval(getRooms_query, requestSettings.refresh);
  },


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
  getMessages : function(params, requestSettings) {
    var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['userIds', 'messageIds']), {
      'access_token' : window.sessionHash,
      'fim3_format' : 'json',
      'roomId' : '',
      'userIds' : '',
      'messageIds' : '',
      'messageIdEnd' : 0,
      'messageIdStart' : 0,
      'search' : 0,
      'archive' : 0,
      'sortOrder' : 'asc'
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);


    function getMessages_query() {
      $.ajax({
        type: 'get',
        url: directory + 'api/getMessages.php',
        data: params,
        timeout: requestSettings.timeout,
        cache: requestSettings.cache
      }).done(function(json) {
        requestSettings.begin(json);
        $.each(json.getMessages.messages, function(index, value) { requestSettings.each(value); });
        requestSettings.end(json);
      });
    }


    if (requestSettings.close) clearInterval(fimApi.timers['getMessages_' + requestSettings.timerId]);
    else {
      getMessages_query();
      if (requestSettings.refresh > -1) fimApi.timers['getMessages_' + requestSettings.timerId] = setInterval(getMessages_query, requestSettings.refresh);
    }
  },



  getFiles : function(params, requestSettings) {
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
      }).done(function(json) {
        requestSettings.begin(json);
        $.each(json.getFiles.files, function(index, value) { requestSettings.each(value); });
        requestSettings.end(json);
      });
    }


    if (requestSettings.close) clearInterval(fimApi.timers['getFiles_' + requestSettings.timerId]);

    getFiles_query();
    if (requestSettings.refresh > -1) fimApi.timers['getFiles_' + requestSettings.timerId] = setInterval(getFiles_query, requestSettings.refresh);
  },



  getStats : function(params, requestSettings) {
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
      }).done(function(json) {
        requestSettings.begin(json);
        $.each(json.getStats.roomStats, function(index, value) { requestSettings.each(value); });
        requestSettings.end(json);
      });
    }


    if (requestSettings.close) clearInterval(fimApi.timers['getStats_' + requestSettings.timerId]);

    getStats_query();
    if (requestSettings.refresh > -1) fimApi.timers['getStats_' + requestSettings.timerId] = setInterval(getStats_query, requestSettings.refresh);
  },



  getKicks : function(params, requestSettings) {
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
      }).done(function(json) {
        requestSettings.begin(json);
        $.each(json.getKicks.kicks, function(index, value) { requestSettings.each(value); });
        requestSettings.end(json);
      });
    }


    if (requestSettings.close) clearInterval(fimApi.timers['getKicks_' + requestSettings.timerId]);

    getKicks_query();
    if (requestSettings.refresh > -1) fimApi.timers['getKicks_' + requestSettings.timerId] = setInterval(getKicks_query, requestSettings.refresh);
  },



  getCensorLists : function(params, requestSettings) {
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
      }).done(function(json) {
        requestSettings.begin(json);
        $.each(json.getCensorLists.lists, function(index, value) { requestSettings.each(value); });
        requestSettings.end(json);
      });
    }


    if (requestSettings.close) clearInterval(fimApi.timers['getCensorLists_' + requestSettings.timerId]);

    getCensorLists_query();
    if (requestSettings.refresh > -1) fimApi.timers['getCensorLists_' + requestSettings.timerId] = setInterval(getCensorLists_query, requestSettings.refresh);
  },



  getActiveUsers : function(params, requestSettings) {
    var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['roomIds', 'userIds']), {
      'access_token' : window.sessionHash,
      'fim3_format' : 'json',
      'roomIds' : '',
      'userIds' : '',
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
      }).done(function(json) {
        requestSettings.begin(json);
        $.each(json.getActiveUsers.users, function(index, value) { requestSettings.each(value); });
        requestSettings.end(json);
      });
    }


    if (requestSettings.close) clearInterval(fimApi.timers['getActiveUsers_' + requestSettings.timerId]);
    else {
      getActiveUsers_query();
      if (requestSettings.refresh > -1) fimApi.timers['getActiveUsers_' + requestSettings.timerId] = setInterval(getActiveUsers_query, requestSettings.refresh);
    }
  },


  acHelper : function(list) {
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
  },



  editFile : function(params, requestSettings) {
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
      data : params,
      cache : false,
      timeout : requestSettings.timeout,
      success : function(json) {
        requestSettings.callback(json);
      },
      error : function(json) {
        requestSettings.error(json);
      }
    });
  },


  sendMessage : function(params, requestSettings) {
    var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['parentalFlags']), {
      'access_token' : window.sessionHash,
      'fim3_format' : 'json',
      'roomId' : null,
      'confirmed' : false, // TODO
      'message' : null,
      'flag' : null
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    $.ajax({
      url: directory + 'api/sendMessage.php',
      type: 'POST',
      data: params,
      cache: false,
      timeout : requestSettings.timeout,
      success : function(json) {
        requestSettings.callback(json);
      },
      error : function(json) {
        requestSettings.error(json);
      }
    });
  },



  mergeDefaults : function(object, defaults) {
    for (var i in defaults) {
      if (!(i in object)) object[i] = defaults[i];
    }

    /*** START STRICT CODE -- NOT NECCESSARY IN PRODUCTION ***/
    for (var i in object) {
      if (!(i in defaults)) {
        throw 'Invalid data in object call: ' + i;
      }
    }
    /*** END STRICT CODE ***/

    return object;
  },



  jsonify : function(object, properties) {
    for (var i in properties) {
      if (properties[i] in object) object[properties[i]] = JSON.stringify(object[properties[i]]);
    }

    return object;
  }
}