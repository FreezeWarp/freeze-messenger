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
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : new P(function (resolve) { resolve(result.value); }).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = y[op[0] & 2 ? "return" : op[0] ? "throw" : "next"]) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [0, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var _this = this;
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
        if (this.clients.indexOf(clientId) === -1)
            this.clients.push(clientId);
        if (!this.isOpen) {
            this.isOpen = true;
            this.getEvents();
        }
    };
    eventSource.prototype.removeClient = function (clientId) {
        console.log("remove client", clientId, this.clients);
        var index = this.clients.indexOf(clientId);
        if (index > -1)
            this.clients.splice(this.clients.indexOf(clientId), 1);
        if (this.clients.length === 0)
            this.close();
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
        var _this = this;
        this.isOpen = true;
        getFimApiInstance(function () {
            if (fimApiInstance.serverSettings.requestMethods.serverSentEvents
                && typeof (EventSource) !== "undefined") {
                _this.getEventsFromStream();
            }
            else {
                _this.getEventsFromFallback();
            }
        });
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
        getFimApiInstance(function () {
            _this.eventSource = new EventSource(fimApiInstance.directory + 'stream.php?streamType=' + streamType + '&lastEvent=' + _this.lastEvent + (queryId ? '&queryId=' + queryId : '') + '&access_token=' + fimApiInstance.lastSessionHash);
            // If we get an error that causes the browser to close the connection, open a fallback connection instead
            _this.eventSource.onerror = (function (e) {
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
                _this.eventSource.addEventListener(events[i], _this.eventHandler(events[i]), false);
            }
        });
    };
    /**
     * Get events from a FIM fallback event stream with the given properties.
     *
     * @param streamType The type of the stream (e.g. user)
     * @param queryId The ID of the stream, if room
     */
    eventSource.prototype.getEventsFromFallbackGenerator = function (streamType, queryId) {
        var _this = this;
        getFimApiInstance(function () {
            fimApiInstance.getEventsFallback({
                'streamType': streamType,
                'queryId': (queryId ? queryId : null),
                'lastEvent': _this.lastEvent
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
        return _this;
    }
    roomSource.prototype.close = function () {
        var _this = this;
        _super.prototype.close.call(this);
        getFimApiInstance(function () {
            fimApiInstance.exitRoom(_this.roomId);
        });
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
    function userSource(clientId) {
        var _this = _super.call(this) || this;
        if (clientId)
            _this.addClient(clientId);
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
/**
 * Create a system notification with the given data.
 *
 * @param data {object} An object containing event data.
 * @param data.roomName {string}
 * @param data.roomId {string}
 * @param data.userName {string}
 * @param data.userId {int}
 * @param data.userAvatar {string}
 * @param data.messageText {string}
 *
 * @returns {any}
 */
var createNotification = function (data) {
    console.log("create notification", data);
    if (self.registration) {
        return self.registration.showNotification(data.roomName + ": " + data.userName, {
            tag: data.roomId,
            body: data.messageText,
            icon: data.userAvatar,
            vibrate: [100, 50, 100],
            renotify: true
        });
    }
    else {
        return new Notification(data.roomName + ": " + data.userName, {
            tag: data.roomId,
            body: data.messageText,
            icon: data.userAvatar,
            vibrate: [100, 50, 100],
            renotify: true
        });
    }
};
/**
 * Load minimal jQuery for API purposes. This entails:
 * 1.) Creating basic DOM structure.
 * 2.) Loading jquery.ajax script.
 * 3.) Replacing $.ajax with fetch-based polyfill (since we can use XHR here)
 * 4.) Loading fimApi
 */
var shimJquery = function () {
    var cacheId = self.location.href.split('?_=')[1];
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
    importScripts('client/js/jquery.ajax.min.js?_=' + cacheId);
    var $ = jQuery;
    // Replace $.ajax with fetch polyfill
    $.ajax = function (options) {
        var params = {
            method: options.type ? options.type.toUpperCase() : 'GET'
        };
        if (options.data && params.method !== 'GET') {
            params['body'] = new URLSearchParams($.param(options.data));
        }
        var url = options.url + (params.method === 'GET' && options.data ? '?' + $.param(options.data) : '');
        console.log("request", options, url, params);
        var promise = jQuery.Deferred();
        $.when(fetch(url, params)).then(function (response) {
            var contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            }
            else {
                promise.reject("Could not parse JSON.");
            }
        })
            .then(function (json) { promise.resolve(json); })["catch"](function (error) { promise.reject(error); });
        ;
        return promise.promise();
    };
    // Load fim-api
    importScripts('client/js/fim-dev/fim-api.ts.js?_=' + cacheId);
};
/**
 * Get a fresh fimApi instance, usable when the service worker was destructed.
 *
 * @param callback Function to execute once fimApi instance has been created
 * @returns {any}
 */
function getFimApiInstance(callback) {
    if (!fimApiInstance) {
        shimJquery();
        return idbKeyval.get('serverSettings').then(function (serverSettings) {
            fimApiInstance = new fimApi(serverSettings);
            callback();
        });
    }
    else {
        return callback();
    }
}
/**
 * Send pings to all currently listened-to rooms.
 */
function sendPings() {
    var _this = this;
    getFimApiInstance(function () {
        var openRooms = [];
        for (i in _this.roomSources) {
            if (_this.roomSources[i].isOpen) {
                openRooms.push(i);
            }
        }
        if (openRooms.length > 0)
            fimApiInstance.editUserStatus(openRooms, { "status": "" });
    });
}
var idbKeyval;
var isServiceWorker = true;
var userSourceInstance = null;
var roomSources = {};
var fimApiInstance;
if (typeof fimApi === "undefined") {
    idbKeyval = (function () {
        var db;
        function getDB() {
            if (!db) {
                db = new Promise(function (resolve, reject) {
                    var openreq = indexedDB.open('svgo-keyval', 1);
                    openreq.onerror = function () {
                        reject(openreq.error);
                    };
                    openreq.onupgradeneeded = function () {
                        // First time setup: create an empty object store
                        openreq.result.createObjectStore('keyval');
                    };
                    openreq.onsuccess = function () {
                        resolve(openreq.result);
                    };
                });
            }
            return db;
        }
        function withStore(type, callback) {
            return __awaiter(this, void 0, void 0, function () {
                var db;
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0: return [4 /*yield*/, getDB()];
                        case 1:
                            db = _a.sent();
                            return [2 /*return*/, new Promise(function (resolve, reject) {
                                    var transaction = db.transaction('keyval', type);
                                    transaction.oncomplete = function () { return resolve(); };
                                    transaction.onerror = function () { return reject(transaction.error); };
                                    callback(transaction.objectStore('keyval'));
                                })];
                    }
                });
            });
        }
        return {
            get: function (key) {
                return __awaiter(this, void 0, void 0, function () {
                    var req;
                    return __generator(this, function (_a) {
                        switch (_a.label) {
                            case 0: return [4 /*yield*/, withStore('readonly', function (store) {
                                    req = store.get(key);
                                })];
                            case 1:
                                _a.sent();
                                return [2 /*return*/, req.result];
                        }
                    });
                });
            },
            set: function (key, value) {
                return withStore('readwrite', function (store) {
                    store.put(value, key);
                });
            },
            "delete": function (key) {
                return withStore('readwrite', function (store) {
                    store["delete"](key);
                });
            }
        };
    })();
    shimJquery();
}
else {
    isServiceWorker = false;
    fimApiInstance = fimApi;
}
/**
 * Receive message events.
 *
 * @param {MessageEvent} event
 */
onmessage = function (event) {
    console.log("message to service worker", isServiceWorker, event.data.eventName, event.data, event);
    switch (event.data.eventName) {
        /*
         * Upload new server settings for API usage.
         */
        case 'registerApi':
            if (isServiceWorker) {
                fimApiInstance = new fimApi(event.data.serverSettings);
                idbKeyval.set('serverSettings', event.data.serverSettings);
            }
            break;
        /*
         * Register a new session hash from recent login.
         */
        case 'login':
            getFimApiInstance(function () {
                fimApiInstance.lastSessionHash = event.data.sessionHash;
                if (_this.pingInterval)
                    clearInterval(_this.pingInterval);
                _this.pingInterval = setInterval(sendPings, 60 * 1000);
            });
            if (!userSourceInstance)
                userSourceInstance = new userSource(event.source && event.source.id ? event.source.id : false);
            else if (event.source && event.source.id)
                userSourceInstance.addClient(event.source.id);
            else
                userSourceInstance.getEvents();
            break;
        /*
         * Unregister a client.
         */
        case 'logout':
            if (event.source && event.source.id)
                userSourceInstance.removeClient(event.source.id);
            else if (roomSources[String(event.data.roomId)])
                userSourceInstance.close();
            if (_this.pingInterval) {
                clearInterval(_this.pingInterval);
                _this.pingInterval = false;
            }
            break;
        /*
         * Start listening for events in a room.
         */
        case 'listenRoom':
            if (!roomSources[String(event.data.roomId)])
                roomSources[String(event.data.roomId)] = new roomSource(event.data.roomId, (event.source && event.source.id ? event.source.id : false));
            else if (event.source && event.source.id)
                roomSources[String(event.data.roomId)].addClient(event.source.id);
            else
                roomSources[String(event.data.roomId)].getEvents();
            sendPings();
            break;
        /*
         * Stop listening to events in a room.
         */
        case 'unlistenRoom':
            if (event.source && event.source.id)
                roomSources[String(event.data.roomId)].removeClient(event.source.id);
            else if (roomSources[String(event.data.roomId)])
                roomSources[String(event.data.roomId)].close();
            break;
        /*
         * Display a notification.
         */
        case 'requestNotification':
            createNotification(event.data);
            break;
    }
};
/**
 * At install, cache resources needed to load interface.
 */
self.addEventListener('install', function (event) {
    var CACHE_NAME = 'freezemessenger-v1b4nightly';
    var cacheId = self.location.href.split('?_=')[1];
    var urlsToPrefetch = [
        './',
        'serviceWorker.ts.js?_=' + cacheId,
        'client/js/jquery.ajax.min.js?_=' + cacheId,
        'client/js/fim-dev/fim-api.ts.js?_=' + cacheId,
        'client/css/styles.css?_=' + cacheId,
        'client/css/bootstrap.css?_=' + cacheId,
        'client/js/jquery.plugins.min.js?_=' + cacheId,
        'client/js/fim-all.js?_=' + cacheId,
        'client/js/paint.min.js?_=' + cacheId,
        'client/data/language_enGB.json?_=' + cacheId,
        'client/data/config.json?_=' + cacheId,
        '../api/serverStatus.php?_=' + cacheId,
        'https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.10/handlebars.min.js',
        'https://code.jquery.com/jquery-3.2.1.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js',
        'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js',
        'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css',
    ];
    try {
        event.waitUntil(caches.open(CACHE_NAME)
            .then(function (cache) {
            cache.addAll(urlsToPrefetch.map(function (urlToPrefetch) {
                return new Request(urlToPrefetch);
            }));
        }));
        //event.waitUntil(self.skipWaiting()); // Activate worker immediately
    }
    catch (e) {
        console.log("Install error: ", e);
    }
});
/**
 * Fetch cached resources.
 */
self.addEventListener('fetch', function (event) {
    event.respondWith(caches.match(event.request).then(function (response) {
        return response || fetch(event.request);
    }));
});
/**
 * Inform if service worker activiation fails.
 */
self.addEventListener('activate', function (event) {
    try {
        event.waitUntil(self.clients.claim()); // Become available to all pages
    }
    catch (e) {
        console.log("Activate error: ", e);
    }
});
/**
 * Receive push notifications.
 */
self.addEventListener('push', function (event) {
    var data = event.data.json().data;
    console.info("push message", data, roomSources);
    if (!roomSources[String(data.roomId)] || !roomSources[String(data.roomId)].isOpen) {
        event.waitUntil(createNotification(data));
    }
});
/**
 * Attempt to open a window for a room notification when the notification is clicked.
 */
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
            return getFimApiInstance(function () {
                clients.openWindow(fimApiInstance.directory + '#room=' + event.notification.tag);
            });
        }
    }));
});
