var __extends = (this && this.__extends) || (function () {
    var extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
self.addEventListener('install', function (event) {
    console.log("install listener");
    event.waitUntil(self.skipWaiting()); // Activate worker immediately
    return true;
});
self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim()); // Become available to all pages
});
self.addEventListener('push', function (event) {
    var data = event.data.json().data;
    console.log("push message", data);
    if (!roomSources[String(data.roomId)]) {
        event.waitUntil(self.registration.showNotification(data.roomName + ": " + data.userName, {
            body: data.messageText,
            icon: data.userAvatar,
            vibrate: [100, 50, 100],
            data: {
                dateOfArrival: Date.now(),
                primaryKey: '2'
            }
        }));
    }
});
var directory = '';
var userSourceInstance = null;
var roomSources = [];
var isBlurred = false;
var serverSettings = {};
var lastSessionHash = '';
/**
 * A generic event source provider.
 */
var eventSource = /** @class */ (function () {
    function eventSource() {
        /** @var The last event ID we received in this room source. */
        this.lastEvent = 0;
        /** @var The number of times in a row a request has failed. */
        this.failureCount = 0;
        this.clients = [];
    }
    eventSource.prototype.addClient = function (clientId) {
        this.clients.push(clientId);
        if (!this.eventSource && !this.eventTimeout) {
            this.getEvents();
        }
    };
    eventSource.prototype.removeClient = function (clientId) {
        this.clients.splice(clientId, 1);
        if (this.clients.length === 0) {
            this.close();
        }
    };
    /**
     * Close the event source object, freeing up network resources.
     */
    eventSource.prototype.close = function () {
        if (this.eventSource)
            this.eventSource.close();
        if (this.eventTimeout)
            clearTimeout(this.eventTimeout);
    };
    /**
     * Generic callback when an event occurs.
     *
     * @param eventName The name of the event.
     * @returns {(event) => void}
     */
    eventSource.prototype.eventHandler = function (eventName) {
        var _this = this;
        return function (event) {
            console.log("new event", eventName, event, _this.clients);
            _this.lastEvent = Math.max(Number(_this.lastEvent), Number(event.lastEventId));
            for (i in _this.clients) {
                clients.get(_this.clients[i]).then(function (client) {
                    if (client) {
                        client.postMessage({
                            name: eventName,
                            data: event.data
                        });
                    }
                    else {
                        _this.removeClient(_this.clients[i]);
                    }
                });
            }
        };
    };
    /**
     * Get events from whichever method is most appropriate.
     */
    eventSource.prototype.getEvents = function () {
        if (serverSettings.requestMethods.serverSentEvents
            && typeof (EventSource) !== "undefined"
            && false) {
            this.getEventsFromStream();
        }
        else {
            this.getEventsFromFallback();
        }
    };
    /**
     * Get events from a FIM event stream with the given properties.
     *
     * @param streamType The type of the stream (e.g. user)
     * @param queryId The ID of the stream, if room
     * @param events A list of events to register callbacks for
     */
    eventSource.prototype.getEventsFromStreamGenerator = function (streamType, queryId, events) {
        var _this = this;
        if (this.eventSource)
            this.eventSource.close();
        this.eventSource = new EventSource(directory + 'stream.php?streamType=' + streamType + '&lastEvent=' + this.lastEvent + (queryId ? '&queryId=' + queryId : '') + '&access_token=' + lastSessionHash);
        // If we get an error that causes the browser to close the connection, open a fallback connection instead
        this.eventSource.onerror = (function (e) {
            if (_this.eventSource.readyState === 2) {
                _this.eventSource = null;
                _this.eventTimeout = setTimeout((function () {
                    _this.getEventsFromFallback();
                }), 1000);
            }
        });
        for (i in events) {
            this.eventSource.addEventListener(events[i], this.eventHandler(events[i]), false);
        }
    };
    /**
     * Get events from a FIM fallback event stream with the given properties.
     *
     * @param streamType The type of the stream (e.g. user)
     * @param queryId The ID of the stream, if room
     */
    eventSource.prototype.getEventsFromFallbackGenerator = function (streamType, queryId) {
        var _this = this;
        // todo: without fetch?
        fetch(directory + "stream.php?fallback=1&streamType=" + streamType + "&queryId=" + queryId + "&lastEvent=" + this.lastEvent + "&access_token=" + lastSessionHash)["catch"](function (error) {
            console.log("error", error);
            var retryTime = Math.min(30, 2 * _this.failureCount++) * 1000;
            _this.eventHandler('streamFailed')({
                lastEventId: 0,
                data: JSON.stringify({
                    streamType: streamType,
                    queryId: queryId,
                    retryTime: retryTime
                })
            });
            _this.eventTimeout = setTimeout((function () {
                _this.getEventsFromFallback();
            }), retryTime);
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
            for (i in data['events']) {
                console.log("hi", i, data['events'][i]);
                _this.eventHandler(data['events'][i].eventName)({
                    lastEventId: Number(data['events'][i].id),
                    data: JSON.stringify(data['events'][i].data)
                });
            }
            _this.eventTimeout = setTimeout((function () {
                _this.getEvents();
            }), 1000);
            _this.failureCount = 0;
        });
    };
    return eventSource;
}());
/**
 * An event source providing room events.
 */
var roomSource = /** @class */ (function (_super) {
    __extends(roomSource, _super);
    function roomSource(roomId) {
        var _this = _super.call(this) || this;
        _this.roomId = roomId;
        _this.getEvents();
        return _this;
        // Send Pings
        /*fimApiInstance.ping(this.roomId);
        this.pingInterval = window.setInterval((() => {
            fimApiInstance.ping(this.roomId);
        }), 60 * 1000);*/
    }
    roomSource.prototype.close = function () {
        _super.prototype.close.call(this);
        if (this.pingInterval)
            clearInterval(this.pingInterval);
        //fimApiInstance.exitRoom(this.roomId);
    };
    roomSource.prototype.getEventsFromStream = function () {
        this.getEventsFromStreamGenerator('room', this.roomId, ['userStatusChange', 'newMessage', 'topicChange', 'deletedMessage', 'editedMessage']);
    };
    roomSource.prototype.getEventsFromFallback = function () {
        this.getEventsFromFallbackGenerator('room', this.roomId);
    };
    return roomSource;
}(eventSource));
/**
 * An event source providing user events.
 */
var userSource = /** @class */ (function (_super) {
    __extends(userSource, _super);
    function userSource() {
        var _this = _super.call(this) || this;
        _this.getEvents();
        return _this;
    }
    userSource.prototype.getEventsFromStream = function () {
        this.getEventsFromStreamGenerator('user', null, ['missedMessage', 'refreshApplication']);
    };
    userSource.prototype.getEventsFromFallback = function () {
        this.getEventsFromFallbackGenerator('user', null);
    };
    return userSource;
}(eventSource));
self.onmessage = function (event) {
    console.log("service work message", event);
    switch (event.data.eventName) {
        case 'registerApi':
            serverSettings = event.data.serverSettings;
            directory = event.data.directory;
            break;
        case 'login':
            lastSessionHash = event.data.sessionHash;
            if (userSourceInstance)
                userSourceInstance.close();
            userSourceInstance = new userSource();
            break;
        case 'logout':
            userSourceInstance.close();
            break;
        case 'listenRoom':
            if (!roomSources[String(event.data.roomId)])
                roomSources[String(event.data.roomId)] = new roomSource(event.data.roomId);
            if (event.source && event.source.id)
                roomSources[String(event.data.roomId)].addClient(event.source.id);
            break;
        case 'unlistenRoom':
            if (event.source && event.source.id)
                roomSources[String(event.data.roomId)].removeClient(event.source.id);
            else
                roomSources[String(event.data.roomId)].close();
            break;
        case 'blur':
            isBlurred = true;
            break;
        case 'unblur':
            isBlurred = false;
            break;
    }
};
