self.addEventListener('install', function(event) {
    event.waitUntil(self.skipWaiting()); // Activate worker immediately
});

self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim()); // Become available to all pages
});


self.addEventListener('push', function(event) {
    let data = event.data.json().data;
    console.info("push message", data, roomSources);

    if (!roomSources[String(data.roomId)] || !roomSources[String(data.roomId)].isOpen) {
        event.waitUntil(
            self.registration.showNotification(data.roomName + ": " + data.userName, {
                body: data.messageText,
                icon: data.userAvatar,
                vibrate: [100, 50, 100],
                data: {
                    dateOfArrival: Date.now(),
                    primaryKey: '2'
                },
            })
        );
    }
});

let isServiceWorker = false;
let userSourceInstance = null;
let roomSources = [];
let isBlurred = false;

let fimApiInstance;

if (typeof fimApi === "undefined") {
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
                postMessage({
                    name: eventName,
                    data: event.data
                });
            }
        };
    }

    /**
     * Get events from whichever method is most appropriate. Reopens the connection, if needed.
     */
    getEvents() {
        this.isOpen = true;

        if (fimApiInstance.serverSettings.requestMethods.serverSentEvents
            && typeof(EventSource) !== "undefined") {
            this.getEventsFromStream();
        }
        else {
            this.getEventsFromFallback()
        }
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

        this.eventSource = new EventSource(fimApiInstance.directory + 'stream.php?streamType=' + streamType + '&lastEvent=' + this.lastEvent + (queryId ? '&queryId=' + queryId : '') + '&access_token=' + fimApiInstance.lastSessionHash);

        // If we get an error that causes the browser to close the connection, open a fallback connection instead
        this.eventSource.onerror = ((e) => {
            console.error("event source error");

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
        // todo: without fetch?
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

        // Send Pings
        fimApiInstance.ping(this.roomId);
        this.pingInterval = window.setInterval((() => {
            fimApiInstance.ping(this.roomId);
        }), 60 * 1000);
    }

    close() {
        super.close();

        if (this.pingInterval)
            clearInterval(this.pingInterval);

        fimApiInstance.exitRoom(this.roomId);
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

    constructor() {
        super();

        this.getEvents();
    }


    getEventsFromStream() {
        this.getEventsFromStreamGenerator('user', null, ['missedMessage', 'refreshApplication']);
    }

    getEventsFromFallback() {
        this.getEventsFromFallbackGenerator('user', null);
    }

}

onmessage = (event) => {
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
    }
};