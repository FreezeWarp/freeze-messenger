self.addEventListener('install', function(event) {
    event.waitUntil(self.skipWaiting()); // Activate worker immediately
});

self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim()); // Become available to all pages
});


self.addEventListener('push', function(event) {
    let data = event.data.json().data;
    console.log("push message", data, roomSources);

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
let directory = '';
let userSourceInstance = null;
let roomSources = [];
let isBlurred = false;
let serverSettings = {};
let lastSessionHash = '';

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
        this.clients.push(clientId);

        if (!this.isOpen) {
            this.isOpen = true;
            this.getEvents();
        }
    }

    removeClient(clientId) {
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
            console.log("new event", eventName, event, this.clients);

            this.lastEvent = Math.max(Number(this.lastEvent), Number(event.lastEventId));

            if (isServiceWorker) {
                for (i in this.clients) {
                    clients.get(this.clients[i]).then((client) => {
                        if (client) {
                            client.postMessage({
                                name: eventName,
                                data: event.data
                            });
                        }
                        else {
                            this.removeClient(this.clients[i]);
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

        if (serverSettings.requestMethods.serverSentEvents
            && typeof(EventSource) !== "undefined"
            && false) {
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

        this.eventSource = new EventSource(directory + 'stream.php?streamType=' + streamType + '&lastEvent=' + this.lastEvent + (queryId ? '&queryId=' + queryId : '') + '&access_token=' + lastSessionHash);

        // If we get an error that causes the browser to close the connection, open a fallback connection instead
        this.eventSource.onerror = ((e) => {
            console.log("event source error");
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
        fetch(directory + "stream.php?fallback=1&streamType=" + streamType + (queryId ? "&queryId=" + queryId : '') + "&lastEvent=" + this.lastEvent + "&access_token=" + lastSessionHash)
            .catch((error) => {
                console.log("error", error);
                let retryTime = Math.min(30, 2 * this.failureCount++) * 1000;

                this.eventHandler('streamFailed')({
                    lastEventId : 0,
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
            })
            .then(response => response.json())
            .then((data) => {
                for (i in data['events']) {
                    this.eventHandler(data['events'][i].eventName)({
                        lastEventId : Number(data['events'][i].id),
                        data : JSON.stringify(data['events'][i].data)
                    });
                }

                if (this.isOpen) {
                    this.eventTimeout = setTimeout((() => {
                        this.getEvents()
                    }), 1000);
                }

                this.failureCount = 0;
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
        /*fimApiInstance.ping(this.roomId);
        this.pingInterval = window.setInterval((() => {
            fimApiInstance.ping(this.roomId);
        }), 60 * 1000);*/
    }

    close() {
        super.close();

        if (this.pingInterval)
            clearInterval(this.pingInterval);

        //fimApiInstance.exitRoom(this.roomId);
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
    console.log("service work message", event);

    switch (event.data.eventName) {
        case 'registerApi':
            serverSettings = event.data.serverSettings;
            directory = event.data.directory;
            isServiceWorker = event.data.isServiceWorker;
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
            else if (event.source && event.source.id)
                roomSources[String(event.data.roomId)].addClient(event.source.id);
            else
                roomSources[String(event.data.roomId)].getEvents();
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