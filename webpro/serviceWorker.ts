/**
 * A generic event source provider.
 */
abstract class eventSource {
    /** @var An EventSource opened, if available. */
    eventSource : object;

    /** @var The last event ID we received in this room source. */
    lastEvent = 0;

    /** @var A return from setTimeout, set if we are using the fallback method. */
    eventTimeout : any;

    /** @var The number of times in a row a request has failed. */
    failureCount = 0;

    /** @var Whether this event source is currently monitoring for new events. */
    isOpen = true;

    clients = [];

    constructor() {
    }

    addClient(clientId) {
        console.log("add client", clientId, this.clients);

        if (this.clients.indexOf(clientId) === -1)
            this.clients.push(clientId);

        if (!this.isOpen) {
            this.isOpen = true;
            this.getEvents();
        }
    }

    removeClient(clientId) {
        console.log("remove client", clientId, this.clients);

        this.clients.splice(this.clients.indexOf(clientId), 1);

        if (this.clients.length === 0) {
            this.close();
        }
    }

    /**
     * Close the event source object, freeing up network resources.
     */
    close() {
        if (this.eventSource)
            this.eventSource.close();

        if (this.eventTimeout)
            clearTimeout(this.eventTimeout);

        this.isOpen = false;
    }

    /**
     * Generic callback when an event occurs.
     *
     * @param eventName The name of the event.
     * @returns {(event) => void}
     */
    eventHandler(eventName) {
        return (event) => {
            if (event.lastEventId)
                this.lastEvent = Math.max(Number(this.lastEvent), Number(event.lastEventId));

            if (isServiceWorker) {
                for (let i = 0; i < this.clients.length; i++) {
                    clients.get(this.clients[i]).then((client) => {
                        if (client) {
                            client.postMessage({
                                name: eventName,
                                data: event.data
                            });
                        }
                    })
                }
            }
            else {
                standard.workerCallback(eventName, event.data);
            }
        };
    }

    /**
     * Get events from whichever method is most appropriate. Reopens the connection, if needed.
     */
    getEvents() {
        this.isOpen = true;

        getFimApiInstance(() => {
            if (fimApiInstance.serverSettings.requestMethods.serverSentEvents
                && typeof(EventSource) !== "undefined") {
                this.getEventsFromStream();
            }
            else {
                this.getEventsFromFallback()
            }
        });
    }

    /**
     * Get events from an event stream.
     */
    abstract getEventsFromStream();

    /**
     * Get events from a FIM event stream with the given properties.
     *
     * @param streamType The type of the stream (e.g. user)
     * @param queryId The ID of the stream, if room
     * @param events A list of events to register callbacks for
     */
    getEventsFromStreamGenerator(streamType, queryId, events) {
        if (this.eventSource)
            this.eventSource.close();

        getFimApiInstance(() => {
            this.eventSource = new EventSource(fimApiInstance.directory + 'stream.php?streamType=' + streamType + '&lastEvent=' + this.lastEvent + (queryId ? '&queryId=' + queryId : '') + '&access_token=' + fimApiInstance.lastSessionHash);

            // If we get an error that causes the browser to close the connection, open a fallback connection instead
            this.eventSource.onerror = ((e) => {
                if (this.eventSource.readyState === 2) {
                    this.eventSource = null;

                    if (this.isOpen) {
                        this.eventTimeout = setTimeout((() => {
                            this.getEventsFromFallback();
                        }), 1000);
                    }
                }
            });

            for (i in events) {
                this.eventSource.addEventListener(events[i], this.eventHandler(events[i]), false);
            }
        });
    }

    /**
     * Get events from a stream fallback method.
     */
    abstract getEventsFromFallback();

    /**
     * Get events from a FIM fallback event stream with the given properties.
     *
     * @param streamType The type of the stream (e.g. user)
     * @param queryId The ID of the stream, if room
     */
    getEventsFromFallbackGenerator(streamType, queryId) {
        getFimApiInstance(() => {
            fimApiInstance.getEventsFallback({
                'streamType': streamType,
                'queryId': (queryId ? queryId : null),
                'lastEvent': this.lastEvent
            }, {
                'error' : (error) => {
                    console.error("error", error);
                    let retryTime = Math.min(30, 2 * this.failureCount++) * 1000;

                    this.eventHandler('streamFailed')({
                        data : JSON.stringify({
                            streamType: streamType,
                            queryId : queryId,
                            retryTime : retryTime,
                        })
                    });

                    if (this.isOpen) {
                        this.eventTimeout = setTimeout((() => {
                            this.getEventsFromFallback()
                        }), retryTime);
                    }
                },
                'each' : (event) => {
                    this.eventHandler(event.eventName)({
                        lastEventId : Number(event.id),
                        data : JSON.stringify(event.data)
                    });
                },
                'end' : () => {
                    if (this.isOpen) {
                        this.eventTimeout = setTimeout((() => {
                            this.getEvents()
                        }), 1000);
                    }

                    this.failureCount = 0;
                }
            });
        });
    }
}

/**
 * An event source providing room events.
 */
class roomSource extends eventSource {
    /** @var The roomId corresponding with this event source. */
    roomId : any;

    /** @var A return from setInterval, set when we start pinging the server. */
    pingInterval : any;

    constructor(roomId, clientId) {
        super();

        this.roomId = roomId;

        if (clientId)
            this.addClient(clientId);

        this.getEvents();
    }

    close() {
        super.close();

        getFimApiInstance(() => {
            fimApiInstance.exitRoom(this.roomId);
        })
    }

    getEventsFromStream() {
        this.getEventsFromStreamGenerator('room', this.roomId, ['userStatusChange', 'newMessage', 'topicChange', 'deletedMessage', 'editedMessage']);
    }

    getEventsFromFallback() {
        this.getEventsFromFallbackGenerator('room', this.roomId);
    }
}

/**
 * An event source providing user events.
 */
class userSource extends eventSource {

    constructor(clientId) {
        super();

        if (clientId)
            this.addClient(clientId);

        this.getEvents();
    }


    getEventsFromStream() {
        this.getEventsFromStreamGenerator('user', null, ['missedMessage', 'refreshApplication']);
    }

    getEventsFromFallback() {
        this.getEventsFromFallbackGenerator('user', null);
    }

}


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
let createNotification = function(data) {
    console.log("create notification", data);

    if (self.registration) {
        return self.registration.showNotification(data.roomName + ": " + data.userName, {
            tag: data.roomId,
            body: data.messageText,
            icon: data.userAvatar,
            vibrate: [100, 50, 100],
            renotify : true
        });
    }
    else {
        return new Notification(data.roomName + ": " + data.userName, {
            tag: data.roomId,
            body: data.messageText,
            icon: data.userAvatar,
            vibrate: [100, 50, 100],
            renotify : true
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
let shimJquery = function() {
    let cacheId = self.location.href.split('?_=')[1];

    // Juryrig the jQuery properties for jQuery
    var document = self.document = {parentNode: null, nodeType: 9, toString: function() {return "FakeDocument"}};
    var window = self.window = self;
    var fakeElement = Object.create(document);
    fakeElement.nodeType = 1;
    fakeElement.toString=function() {return "FakeElement"};
    fakeElement.parentNode = fakeElement.firstChild = fakeElement.lastChild = fakeElement;
    fakeElement.ownerDocument = document;

    document.head = document.body = fakeElement;
    document.ownerDocument = document.documentElement = document;
    document.getElementById = document.createElement = function() {return fakeElement;};
    document.createDocumentFragment = function() {return this;};
    document.getElementsByTagName = document.getElementsByClassName = function() {return [fakeElement];};
    document.getAttribute = document.setAttribute = document.removeChild =
        document.addEventListener = document.removeEventListener =
            function() {return null;};
    document.cloneNode = document.appendChild = function() {return this;};
    document.appendChild = function(child) {return child;};

    // Load AJAX-only version of jQuery
    importScripts('client/js/jquery.ajax.min.js?_=' + cacheId);
    var $ = jQuery;

    // Replace $.ajax with fetch polyfill
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

        return idbKeyval.get('serverSettings').then((serverSettings) => {
            fimApiInstance = new fimApi(serverSettings);
            callback();
        });
    }

    else {
        return callback();
    }
};


let idbKeyval;
let isServiceWorker = true;
let userSourceInstance = null;
let roomSources = {};
let fimApiInstance;

if (typeof fimApi === "undefined") {
    idbKeyval = (() => {
        let db;

        function getDB() {
            if (!db) {
                db = new Promise((resolve, reject) => {
                    const openreq = indexedDB.open('svgo-keyval', 1);

                    openreq.onerror = () => {
                        reject(openreq.error);
                    };

                    openreq.onupgradeneeded = () => {
                        // First time setup: create an empty object store
                        openreq.result.createObjectStore('keyval');
                    };

                    openreq.onsuccess = () => {
                        resolve(openreq.result);
                    };
                });
            }
            return db;
        }

        async function withStore(type, callback) {
            const db = await getDB();
            return new Promise((resolve, reject) => {
                const transaction = db.transaction('keyval', type);
                transaction.oncomplete = () => resolve();
                transaction.onerror = () => reject(transaction.error);
                callback(transaction.objectStore('keyval'));
            });
        }

        return {
            async get(key) {
                let req;
                await withStore('readonly', store => {
                    req = store.get(key);
                });
                return req.result;
            },
            set(key, value) {
                return withStore('readwrite', store => {
                    store.put(value, key);
                });
            },
            delete(key) {
                return withStore('readwrite', store => {
                    store.delete(key);
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
onmessage = (event) => {
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
            getFimApiInstance(() => {
                fimApiInstance.lastSessionHash = event.data.sessionHash;

                if (this.pingInterval)
                    clearInterval(this.pingInterval);

                this.pingInterval = setInterval((() => {
                    getFimApiInstance(() => {
                        if (Object.keys(this.roomSources).length > 0) {
                            fimApiInstance.editUserStatus(Object.keys(this.roomSources), {"status": ""});
                        }
                    });
                }), 60 * 1000);
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

            if (this.pingInterval) {
                clearInterval(this.pingInterval);
                this.pingInterval = false;
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
    let CACHE_NAME = 'freezemessenger-v1b4nightly';
    let cacheId = self.location.href.split('?_=')[1];

    let urlsToPrefetch = [
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
        event.waitUntil(
            caches.open(CACHE_NAME)
                .then(function(cache) {
                    cache.addAll(urlsToPrefetch.map(function(urlToPrefetch) {
                        return new Request(urlToPrefetch);
                    }));
                })
        );

        //event.waitUntil(self.skipWaiting()); // Activate worker immediately
    } catch (e) {
        console.log("Install error: ", e);
    }
});


/**
 * Fetch cached resources.
 */
self.addEventListener('fetch', function(event) {

    event.respondWith(
        caches.match(event.request).then(function(response) {
            return response || fetch(event.request);
        })
    );

});


/**
 * Inform if service worker activiation fails.
 */
self.addEventListener('activate', function (event) {
    try {
        event.waitUntil(self.clients.claim()); // Become available to all pages
    } catch (e) {
        console.log("Activate error: ", e);
    }
});


/**
 * Receive push notifications.
 */
self.addEventListener('push', function (event) {
    let data = event.data.json().data;
    console.info("push message", data, roomSources);

    if (!roomSources[String(data.roomId)] || !roomSources[String(data.roomId)].isOpen) {
        event.waitUntil(
            createNotification(data)
        );
    }
});


/**
 * Attempt to open a window for a room notification when the notification is clicked.
 */
self.addEventListener('notificationclick', function(event) {
    console.log('On notification click: ', event.notification.tag);
    // Android doesn't close the notification when you click on it
    // See: http://crbug.com/463146
    event.notification.close();

    // This looks to see if the current is already open and
    // focuses if it is
    event.waitUntil(
        clients.matchAll({
            type: "window"
        })
            .then(function(clientList) {
                for (var i = 0; i < clientList.length; i++) {
                    var client = clientList[i];
                    if (client.url.match(new RegExp('\/\#room=' + event.notification.tag)) && 'focus' in client)
                        return client.focus();
                }

                if (clients.openWindow) {
                    return getFimApiInstance(() => {
                        clients.openWindow(fimApiInstance.directory + '#room=' + event.notification.tag);
                    });
                }
            })
    );
});