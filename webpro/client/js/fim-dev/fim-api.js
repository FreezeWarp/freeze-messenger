fimApi = {
  requestDefaults : {
    'close' : false,
    'timerId' : 1,
    'refresh' : -1,
    'timeout' : 5000,
    'begin' : function() {},
    'each' : function() {},
    'end' : function() {}
  },

  /**
   * Obtains one or more users.
   * This function is async.
   *
   * @param object userId - The ID of the user to obtain info of.
   *
   * @author Jospeph T. Parsons <josephtparsons@gmail.com>
   * @copyright Joseph T. Parsons 2012
   *
   */
  getUsers : function(params, callback, async) {
    var data = {
      'fim3_sessionHash' : window.sessionHash,
      'fim3_userId' :  window.userId,
      'fim3_format' : 'json'
    };

    if (typeof async === 'undefined') async = true;

    if ('userIds' in params) data['users'] = JSON.stringify(params.userIds);
    else if ('userNames' in params) data['userNames'] = JSON.stringify(params.userNames);
    else throw "getUser() function requires either userIds or userNames in params"; // Error

    $.ajax({
      type: 'get',
      url: directory + 'api/getUsers.php',
      data: data,
      timeout: 5000,
      cache: false,
      async: async
    }).done(function(json) {
      $.each(json.getUsers.users, function(index, value) { console.log(value);
        if (!(value.userId in window.userData)) window.userData[value.userId] = value;
        else {
          for (prop in value) window.userData[value.userId][prop] = value[prop];
        }

        callback(value);
      });
    });
  },


  getRooms : function(params, callbackEach, callbackEnd) {
    var data = {
      'fim3_sessionHash' : window.sessionHash,
      'fim3_userId' :  window.userId,
      'fim3_format' : 'json'
    };

    if ('roomIds' in params) data['rooms'] = JSON.stringify(params.roomIds);
    else if ('roomNames' in params) data['roomNames'] = JSON.stringify(params.roomNames);
  //  else throw "getRooms() function requires either roomIds or roomNames in params"; // Error

    $.ajax({
      type: 'get',
      url: directory + 'api/getRooms.php',
      data: data,
      timeout: 5000,
      cache: false
    }).done(function(json) {
      $.each(json.getRooms.rooms, function(index, value) { console.log(value);
        callbackEach(value);
      });

      callbackEnd();
    });
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
  getMessages : function(params, callback) {
    var data = {
      'fim3_sessionHash' : window.sessionHash,
      'fim3_userId' :  window.userId,
      'fim3_format' : 'json',
      'archive' : (('archive' in params) ? params.archive : 1)
    };

    /* TODO: Uh... shouldn't this all just be a mergey thing? */
    if ('roomId' in params) data['roomId'] = params.roomId;
    else throw "getMessages() function requires roomId in params";

    if ('userIds' in params) data['users'] = JSON.stringify(params.userIds);

    if ('search' in params) data['search'] = params.search;

    if ('messageIdEnd' in params) data['messageIdEnd'] = params.messageIdEnd;
    if ('messageIdStart' in params) data['messageIdStart'] = params.messageIdStart;

    $.ajax({
      type: 'get',
      url: directory + 'api/getMessages.php',
      data: data,
      timeout: 5000,
      cache: false
    }).done(function(json) {
      $.each(json.getMessages.messages, function(index, value) { console.log(value);
        callback(value);
      });
    });
  },



  getFiles : function(params, callback) {
    var data = {
      'fim3_sessionHash' : sessionHash,
      'fim3_userId' :  window.userId,
      'fim3_format' : 'json'
    };

    if ('userIds' in params) data['users'] = JSON.stringify(params.userIds);
    else throw "getFiles() function requires userId in params"; // Error

    $.ajax({
      url: directory + 'api/getFiles.php',
      data: data,
      type: 'get',
      timeout: 5000,
      cache: false
    }).done(function(json) {
      $.each(json.getFiles.files, function(index, value) { callback(value); });
    });
  },



  getStats : function(params, callback) {
    var data = {
      'fim3_sessionHash' : window.sessionHash,
      'fim3_userId' :  window.userId,
      'fim3_format' : 'json',
      'number' : 10
    };


    if ('roomIds' in params) data['rooms'] = JSON.stringify(params.roomIds);
    else throw "getStats() function requires roomId"; // Error


    $.ajax({
      url: directory + 'api/getStats.php',
      data: data,
      timeout: 5000,
      type: 'get',
      cache: false
    }).done(function(json) {
      $.each(json.getStats.roomStats, function(index, value) { callback(value); });
    });
  },



  getKicks : function(params, callback) {
    var data = {
      'fim3_sessionHash' : window.sessionHash,
      'fim3_userId' :  window.userId,
      'fim3_format' : 'json'
    };


    if ('roomIds' in params) data['rooms'] = JSON.stringify(params.roomIds);
    else if ('userIds' in params) data['users'] = JSON.stringify(params.userIds);
    else throw "getKicks() function requires roomIds or userIds"; // Error


    $.ajax({
      type: 'get',
      url: directory + 'api/getKicks.php',
      data: data,
      timeout: 5000,
      cache: false
    }).done(function(json) {
      $.each(json.getKicks.kicks, function(index, value) { callback(value); });
    });
  },



  getCensorLists : function(params, callback) {
    var data = {
      'fim3_sessionHash' : window.sessionHash,
      'fim3_userId' :  window.userId,
      'fim3_format' : 'json'
    };


    if ('roomIds' in params) data['rooms'] = JSON.stringify(params.roomIds);
  //  else throw "getCensorLists() function requires roomIds"; // Error


    $.ajax({
      type: 'get',
      url: directory + 'api/getCensorLists.php',
      data: data,
      timeout: 5000,
      cache: false
    }).done(function(json) {
      $.each(json.getCensorLists.lists, function(index, value) { callback(value); });
    });
  },



  getActiveUsers : function(params, requestSettings) {
    var params = fimApi.mergeDefaults(fimApi.jsonify(params, ['roomIds', 'userIds']), {
      'fim3_sessionHash' : window.sessionHash,
      'fim3_userId' :  window.userId,
      'fim3_format' : 'json',
      'roomIds' : '',
      'userIds' : ''
    });

    var requestSettings = fimApi.mergeDefaults(requestSettings, fimApi.requestDefaults);

    function getActiveUsers_query() {
      $.ajax({
        type: 'get',
        url: directory + 'api/getActiveUsers.php',
        data: params,
        timeout: requestSettings.timeout,
        cache: false
      }).done(function(json) {
        requestSettings.begin(json);
        $.each(json.getActiveUsers.users, function(index, value) { requestSettings.each(value); });
        requestSettings.end(json);
      });
    }


    if (requestSettings.close) clearInterval('getActiveUsers_' + requestSettings.timerId);


    getActiveUsers_query();
    if (requestSettings.refresh > -1) timers['getActiveUsers_' + requestSettings.timerId] = setInterval(getActiveUsers_query, requestSettings.refresh);
  },



  mergeDefaults : function(object, defaults) {
    for (i in defaults) {
      if (!(i in object)) object[i] = defaults[i];
    }

    /*** START STRICT CODE -- NOT NECCESSARY IN PRODUCTION ***/
    for (i in object) {
      if (!(i in defaults)) {
        throw 'Invalid data in object call.';
      }
    }
    /*** END STRICT CODE ***/

    return object;
  },



  jsonify : function(object, properties) {
    for (i in properties) {
      if (i in object) object[i] = JSON.stringify(object[i]);
    }

    return object;
  }
}