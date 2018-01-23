let fimApiInstance;

if (typeof fimApi == "undefined") {
    importScripts('fim-dev/fim-api.ts.js');
}
else {
    fimApiInstance = fimApi;
}

let roomSources = [];
let isBlurred = false;

class roomSource {
    eventSource : object;
    lastEvent = 0;
    roomId : any;
    eventTimeout : any;

    constructor(roomId) {
        this.roomId = roomId;

        if (fimApiInstance.serverSettings.requestMethods.serverSentEvents) {
            this.getEventsFromStream();
        }
        else {
            this.getEventsFromFallback();
        }
    }

    eventHandler(eventName) {
        return (event) => {
            this.lastEvent = Math.max(Number(this.lastEvent), Number(event.lastEventId));

            postMessage({
                name : eventName,
                data : event.data
            });
        };
    }

    getEventsFromStream() {
        this.eventSource = new EventSource(fimApiInstance.directory + 'stream.php?queryId=' + this.roomId + '&streamType=room&access_token=' + fimApiInstance.lastSessionHash);

        // If we get an error that causes the browser to close the connection, open a fallback connection instead
        this.eventSource.onerror = ((e) => {
            if (this.eventSource.readyState === 2) {
                this.eventSource = null;
                this.getEventsFromFallback();
            }
        });

        let events = ['userStatusChange', 'newMessage', 'topicChange', 'deletedMessage', 'editedMessage'];
        for (i in events) {
            this.eventSource.addEventListener(events[i], this.eventHandler(events[i]), false);
        }
    }

    getEventsFromFallback() {
        if (this.roomId) {
            fimApiInstance.getEventsFallback({
                'streamType': 'room',
                'queryId': this.roomId,
                'lastEvent' : this.lastEvent
            }, {
                each: ((event) => {
                    this.lastEvent = Math.max(Number(this.options.lastEvent), Number(event.id));
                    this.eventHandler(event.eventName)(event.data);
                }),
                end: (() => {
                    if (fimApiInstance.serverSettings.requestMethods.serverSentEvents) {
                        this.getEventsFromStream();
                    }
                    else {
                        this.eventTimeout = setTimeout((() => {
                            this.getEventsFromFallback()
                        }), 2000);
                    }
                })
            });
        }
        else {
            console.log('Not requesting messages; room undefined.');
        }

        return false;
    }
}

onmessage = function(event) {
    switch (event.data.eventName) {
        case 'registerApi':
            fimApiInstance = new fimApi(event.data.serverSettings);
            fimApiInstance.lastSessionHash = event.data.sessionHash;
            break;

        case 'listenRoom':
            roomSources[event.data.roomId] = new roomSource(event.data.roomId);
            break;

        case 'blur':
            isBlurred = true;
            break;

        case 'unblur':
            isBlurred = false;
            break;

        case 'unlistenRoom':
            roomSources[event.data.roomId] = null;
            if (this.eventTimeout) {
                window.clearTimeout(this.eventTimeout);
            }
            break;
    }
}