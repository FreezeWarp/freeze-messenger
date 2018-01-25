let fimApiInstance;

if (typeof fimApi == "undefined") {
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
    importScripts("jquery.ajax.min.js");

    // Load fim-api
    importScripts('fim-dev/fim-api.ts.js');
}
else {
    fimApiInstance = fimApi;
}

let userSourceInstance = null;
let roomSources = [];
let isBlurred = false;

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

    constructor() {
    }

    /**
     * Close the event source object, freeing up network resources.
     */
    close() {
        if (this.eventSource)
            this.eventSource.close();

        if (this.eventTimeout)
            window.clearTimeout(this.eventTimeout);
    }

    /**
     * Generic callback when an event occurs.
     *
     * @param eventName The name of the event.
     * @returns {(event) => void}
     */
    eventHandler(eventName) {
        return (event) => {
            console.log("new event", eventName, event);

            this.lastEvent = Math.max(Number(this.lastEvent), Number(event.lastEventId));

            postMessage({
                name : eventName,
                data : event.data
            });
        };
    }

    /**
     * Get events from whichever method is most appropriate.
     */
    getEvents() {
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

        this.eventSource = new EventSource(fimApiInstance.directory + 'stream.php?' + $.param(fimApiInstance.mergeDefaults({}, {
            'streamType': streamType,
            'lastEvent' : this.lastEvent,
            'queryId' : queryId,
            'access_token' : fimApiInstance.lastSessionHash
        })));

        // If we get an error that causes the browser to close the connection, open a fallback connection instead
        this.eventSource.onerror = ((e) => {
            if (this.eventSource.readyState === 2) {
                this.eventSource = null;

                this.eventTimeout = setTimeout((() => {
                    this.getEventsFromFallback();
                }), 1000);
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
        fimApiInstance.getEventsFallback({
            'streamType': streamType,
            'queryId': queryId,
            'lastEvent' : this.lastEvent
        }, {
            each: ((event) => {
                this.eventHandler(event.eventName)({
                    lastEventId : Number(event.id),
                    data : JSON.stringify(event.data)
                });
            }),
            end: () => {
                this.eventTimeout = setTimeout((() => {
                    this.getEvents()
                }), 1000);

                this.failureCount = 0;
            },
            error : () => {
                let retryTime = Math.min(30, 2 * this.failureCount++) * 1000;

                postMessage({
                    name : 'streamFailed',
                    data : JSON.stringify({
                        streamType: streamType,
                        queryId : queryId,
                        retryTime : retryTime,
                    }),
                });

                this.eventTimeout = setTimeout((() => {
                    this.getEventsFromFallback()
                }), retryTime);
            },
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

    constructor(roomId) {
        super();

        this.roomId = roomId;

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
            window.clearInterval(this.pingInterval);

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