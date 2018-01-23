var fimApiInstance;
if (typeof fimApi == "undefined") {
    importScripts('fim-dev/fim-api.ts.js');
}
else {
    fimApiInstance = fimApi;
}
var roomSources = [];
var isBlurred = false;
var roomSource = /** @class */ (function () {
    function roomSource(roomId) {
        this.lastEvent = 0;
        this.roomId = roomId;
        if (fimApiInstance.serverSettings.requestMethods.serverSentEvents) {
            this.getEventsFromStream();
        }
        else {
            this.getEventsFromFallback();
        }
    }
    roomSource.prototype.eventHandler = function (eventName) {
        var _this = this;
        return function (event) {
            _this.lastEvent = Math.max(Number(_this.lastEvent), Number(event.lastEventId));
            postMessage({
                name: eventName,
                data: event.data
            });
        };
    };
    roomSource.prototype.getEventsFromStream = function () {
        var _this = this;
        this.eventSource = new EventSource(fimApiInstance.directory + 'stream.php?queryId=' + this.roomId + '&streamType=room&access_token=' + fimApiInstance.lastSessionHash);
        // If we get an error that causes the browser to close the connection, open a fallback connection instead
        this.eventSource.onerror = (function (e) {
            if (_this.eventSource.readyState === 2) {
                _this.eventSource = null;
                _this.getEventsFromFallback();
            }
        });
        var events = ['userStatusChange', 'newMessage', 'topicChange', 'deletedMessage', 'editedMessage'];
        for (i in events) {
            this.eventSource.addEventListener(events[i], this.eventHandler(events[i]), false);
        }
    };
    roomSource.prototype.getEventsFromFallback = function () {
        var _this = this;
        if (this.roomId) {
            fimApiInstance.getEventsFallback({
                'streamType': 'room',
                'queryId': this.roomId,
                'lastEvent': this.lastEvent
            }, {
                each: (function (event) {
                    _this.lastEvent = Math.max(Number(_this.options.lastEvent), Number(event.id));
                    _this.eventHandler(event.eventName)(event.data);
                }),
                end: (function () {
                    if (fimApiInstance.serverSettings.requestMethods.serverSentEvents) {
                        _this.getEventsFromStream();
                    }
                    else {
                        _this.eventTimeout = setTimeout((function () {
                            _this.getEventsFromFallback();
                        }), 2000);
                    }
                })
            });
        }
        else {
            console.log('Not requesting messages; room undefined.');
        }
        return false;
    };
    return roomSource;
}());
onmessage = function (event) {
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
};
