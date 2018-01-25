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
var fimApiInstance;
if (typeof fimApi == "undefined") {
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
    importScripts("jquery.ajax.min.js");
    // Load fim-api
    importScripts('fim-dev/fim-api.ts.js');
}
else {
    fimApiInstance = fimApi;
}
var userSourceInstance = null;
var roomSources = [];
var isBlurred = false;
/**
 * A generic event source provider.
 */
var eventSource = /** @class */ (function () {
    function eventSource() {
        /** @var The last event ID we received in this room source. */
        this.lastEvent = 0;
        /** @var The number of times in a row a request has failed. */
        this.failureCount = 0;
    }
    /**
     * Close the event source object, freeing up network resources.
     */
    eventSource.prototype.close = function () {
        if (this.eventSource)
            this.eventSource.close();
        if (this.eventTimeout)
            window.clearTimeout(this.eventTimeout);
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
            console.log("new event", eventName, event);
            _this.lastEvent = Math.max(Number(_this.lastEvent), Number(event.lastEventId));
            postMessage({
                name: eventName,
                data: event.data
            });
        };
    };
    /**
     * Get events from whichever method is most appropriate.
     */
    eventSource.prototype.getEvents = function () {
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
        this.eventSource = new EventSource(fimApiInstance.directory + 'stream.php?' + $.param(fimApiInstance.mergeDefaults({}, {
            'streamType': streamType,
            'lastEvent': this.lastEvent,
            'queryId': queryId,
            'access_token': fimApiInstance.lastSessionHash
        })));
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
        fimApiInstance.getEventsFallback({
            'streamType': streamType,
            'queryId': queryId,
            'lastEvent': this.lastEvent
        }, {
            each: (function (event) {
                _this.eventHandler(event.eventName)({
                    lastEventId: Number(event.id),
                    data: JSON.stringify(event.data)
                });
            }),
            end: function () {
                _this.eventTimeout = setTimeout((function () {
                    _this.getEvents();
                }), 1000);
                _this.failureCount = 0;
            },
            error: function () {
                var retryTime = Math.min(30, 2 * _this.failureCount++) * 1000;
                postMessage({
                    name: 'streamFailed',
                    data: JSON.stringify({
                        streamType: streamType,
                        queryId: queryId,
                        retryTime: retryTime
                    })
                });
                _this.eventTimeout = setTimeout((function () {
                    _this.getEventsFromFallback();
                }), retryTime);
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
    function roomSource(roomId) {
        var _this = _super.call(this) || this;
        _this.roomId = roomId;
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
            window.clearInterval(this.pingInterval);
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
    switch (event.data.eventName) {
        case 'registerApi':
            fimApiInstance = new fimApi(event.data.serverSettings);
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
            roomSources[event.data.roomId] = new roomSource(event.data.roomId);
            break;
        case 'unlistenRoom':
            roomSources[event.data.roomId].close();
            break;
        case 'blur':
            isBlurred = true;
            break;
        case 'unblur':
            isBlurred = false;
            break;
    }
};
