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
    try {
        event.waitUntil(self.skipWaiting()); // Activate worker immediately
    }
    catch (e) {
        console.log("Install error: ", e);
    }
});
self.addEventListener('activate', function (event) {
    try {
        event.waitUntil(self.clients.claim()); // Become available to all pages
    }
    catch (e) {
        console.log("Activate error: ", e);
    }
});
self.addEventListener('push', function (event) {
    var data = event.data.json().data;
    console.info("push message", data, roomSources);
    if (!roomSources[String(data.roomId)] || !roomSources[String(data.roomId)].isOpen) {
        event.waitUntil(createNotification(data));
    }
});
var createNotification = function (data) {
    console.log("create notification", data);
    if (self.registration) {
        return self.registration.showNotification(data.roomName + ": " + data.userName, {
            tag: data.roomId,
            body: data.messageText,
            icon: data.userAvatar,
            vibrate: [100, 50, 100]
        });
    }
    else {
        return new Notification(data.roomName + ": " + data.userName, {
            tag: data.roomId,
            body: data.messageText,
            icon: data.userAvatar,
            vibrate: [100, 50, 100]
        });
    }
};
self.addEventListener('notificationclick', function (event) {
    console.log('On notification click: ', event.notification.tag);
    // Android doesn't close the notification when you click on it
    // See: http://crbug.com/463146
    event.notification.close();
    // This looks to see if the current is already open and
    // focuses if it is
    event.waitUntil(clients.matchAll({
        type: "window"
    })
        .then(function (clientList) {
        for (var i = 0; i < clientList.length; i++) {
            var client = clientList[i];
            if (client.url.match(new RegExp('\/\#room=' + event.notification.tag)) && 'focus' in client)
                return client.focus();
        }
        if (clients.openWindow) {
            return clients.openWindow(fimApiInstance.directory + '#room=' + event.notification.tag);
        }
    }));
});
var isServiceWorker = false;
var userSourceInstance = null;
var roomSources = [];
var isBlurred = false;
var fimApiInstance;
if (typeof fimApi === "undefined") {
    // Juryrig the jQuery properties for jQuery
    var document = self.document = { parentNode: null, nodeType: 9, toString: function () { return "FakeDocument"; } };
    var window = self.window = self;
    var fakeElement = Object.create(document);
    fakeElement.nodeType = 1;
    fakeElement.toString = function () { return "FakeElement"; };
    fakeElement.parentNode = fakeElement.firstChild = fakeElement.lastChild = fakeElement;
    fakeElement.ownerDocument = document;
    document.head = document.body = fakeElement;
    document.ownerDocument = document.documentElement = document;
    document.getElementById = document.createElement = function () { return fakeElement; };
    document.createDocumentFragment = function () { return this; };
    document.getElementsByTagName = document.getElementsByClassName = function () { return [fakeElement]; };
    document.getAttribute = document.setAttribute = document.removeChild =
        document.addEventListener = document.removeEventListener =
            function () { return null; };
    document.cloneNode = document.appendChild = function () { return this; };
    document.appendChild = function (child) { return child; };
    // Load AJAX-only version of jQuery
    importScripts("client/js/jquery.ajax.min.js");
    var $ = jQuery;
    // Load fim-api
    importScripts('client/js/fim-dev/fim-api.ts.js');
}
else {
    fimApiInstance = fimApi;
}
/**
 * A generic event source provider.
 */
var eventSource = /** @class */ (function () {
    function eventSource() {
        /** @var The last event ID we received in this room source. */
        this.lastEvent = 0;
        /** @var The number of times in a row a request has failed. */
        this.failureCount = 0;
        /** @var Whether this event source is currently monitoring for new events. */
        this.isOpen = true;
        this.clients = [];
    }
    eventSource.prototype.addClient = function (clientId) {
        console.log("add client", clientId, this.clients);
        this.clients.push(clientId);
        if (!this.isOpen) {
            this.isOpen = true;
            this.getEvents();
        }
    };
    eventSource.prototype.removeClient = function (clientId) {
        console.log("remove client", clientId, this.clients);
        this.clients.splice(this.clients.indexOf(clientId), 1);
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
        this.isOpen = false;
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
            if (event.lastEventId)
                _this.lastEvent = Math.max(Number(_this.lastEvent), Number(event.lastEventId));
            if (isServiceWorker) {
                for (var i = 0; i < _this.clients.length; i++) {
                    clients.get(_this.clients[i]).then(function (client) {
                        if (client) {
                            client.postMessage({
                                name: eventName,
                                data: event.data
                            });
                        }
                    });
                }
            }
            else {
                standard.workerCallback(eventName, event.data);
            }
        };
    };
    /**
     * Get events from whichever method is most appropriate. Reopens the connection, if needed.
     */
    eventSource.prototype.getEvents = function () {
        this.isOpen = true;
        if (fimApiInstance.serverSettings.requestMethods.serverSentEvents
            && typeof (EventSource) !== "undefined") {
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
        this.eventSource = new EventSource(fimApiInstance.directory + 'stream.php?streamType=' + streamType + '&lastEvent=' + this.lastEvent + (queryId ? '&queryId=' + queryId : '') + '&access_token=' + fimApiInstance.lastSessionHash);
        // If we get an error that causes the browser to close the connection, open a fallback connection instead
        this.eventSource.onerror = (function (e) {
            console.error("event source error");
            if (_this.eventSource.readyState === 2) {
                _this.eventSource = null;
                if (_this.isOpen) {
                    _this.eventTimeout = setTimeout((function () {
                        _this.getEventsFromFallback();
                    }), 1000);
                }
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
        fimApiInstance.getEventsFallback({
            'streamType': streamType,
            'queryId': (queryId ? queryId : null),
            'lastEvent': this.lastEvent
        }, {
            'error': function (error) {
                console.error("error", error);
                var retryTime = Math.min(30, 2 * _this.failureCount++) * 1000;
                _this.eventHandler('streamFailed')({
                    data: JSON.stringify({
                        streamType: streamType,
                        queryId: queryId,
                        retryTime: retryTime
                    })
                });
                if (_this.isOpen) {
                    _this.eventTimeout = setTimeout((function () {
                        _this.getEventsFromFallback();
                    }), retryTime);
                }
            },
            'each': function (event) {
                _this.eventHandler(event.eventName)({
                    lastEventId: Number(event.id),
                    data: JSON.stringify(event.data)
                });
            },
            'end': function () {
                if (_this.isOpen) {
                    _this.eventTimeout = setTimeout((function () {
                        _this.getEvents();
                    }), 1000);
                }
                _this.failureCount = 0;
            }
        });
    };
    return eventSource;
}());
/**
 * An event source providing room events.
 */
var roomSource = /** @class */ (function (_super) {
    __extends(roomSource, _super);
    function roomSource(roomId, clientId) {
        var _this = _super.call(this) || this;
        _this.roomId = roomId;
        if (clientId)
            _this.addClient(clientId);
        _this.getEvents();
        // Send Pings
        fimApiInstance.ping(_this.roomId);
        _this.pingInterval = window.setInterval((function () {
            fimApiInstance.ping(_this.roomId);
        }), 60 * 1000);
        return _this;
    }
    roomSource.prototype.close = function () {
        _super.prototype.close.call(this);
        if (this.pingInterval)
            clearInterval(this.pingInterval);
        fimApiInstance.exitRoom(this.roomId);
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
onmessage = function (event) {
    console.log("message to service worker", event.data.eventName, event.data, event);
    switch (event.data.eventName) {
        case 'registerApi':
            if (!fimApiInstance)
                fimApiInstance = new fimApi(event.data.serverSettings);
            isServiceWorker = event.data.isServiceWorker;
            break;
        case 'login':
            fimApiInstance.lastSessionHash = event.data.sessionHash;
            if (userSourceInstance)
                userSourceInstance.close();
            userSourceInstance = new userSource();
            break;
        case 'logout':
            userSourceInstance.close();
            break;
        case 'listenRoom':
            if (!roomSources[String(event.data.roomId)])
                roomSources[String(event.data.roomId)] = new roomSource(event.data.roomId, (event.source && event.source.id ? event.source.id : false));
            else if (event.source && event.source.id)
                roomSources[String(event.data.roomId)].addClient(event.source.id);
            else
                roomSources[String(event.data.roomId)].getEvents();
            break;
        case 'unlistenRoom':
            if (event.source && event.source.id)
                roomSources[String(event.data.roomId)].removeClient(event.source.id);
            else if (roomSources[String(event.data.roomId)])
                roomSources[String(event.data.roomId)].close();
            break;
        case 'blur':
            isBlurred = true;
            break;
        case 'unblur':
            isBlurred = false;
            break;
        case 'requestNotification':
            createNotification(event.data);
            break;
    }
};
