declare var fim_buildUsernameTag : any;
declare var fim_getUsernameDeferred : any;
declare var fim_buildRoomNameTag : any;
declare var fim_getRoomNameDeferred : any;
declare var fim_renderHandlebarsInPlace : any;
declare var fim_hashParse : any;
declare var fim_removeHashParameter : any;
declare var Resolver: any;

let standard = function() {
    this.userId = 0;
    this.sessionHash = "";
    this.anonId = "";
    this.activeLogin = {};
    this.worker = null;

    this.lastEvent = 0;

    this.notifications = {};

    return;
};

standard.prototype.initialLogin = function(options) {
    let oldFinish = options.finish;

    options.finish = (activeLogin) => {
        if (oldFinish) oldFinish(activeLogin);

        if (!this.anonId) {
            $.cookie('webpro_username', options.username, { expires : 14 });
            $.cookie('webpro_password', options.password, { expires : 14 });
        }

        if (!window.roomId) {
            window.roomId = activeLogin.userData.defaultRoomId ? activeLogin.userData.defaultRoomId : 1;
        }

        fim_renderHandlebarsInPlace($("#entry-template"));
        fim_hashParse(); // When a user logs in, the hash data (such as room and archive) is processed, and subsequently executed.
    };

    this.login(options);
};


standard.prototype.setUserData = function(userData) {
    // Message Formatting Parse
    let defaultFormatting = userData.messageFormatting.split(';');

    userData.messageFormattingObj = {};
    jQuery.each(defaultFormatting, function(index, value) {
        let pair = value.split(':');
        userData.messageFormattingObj[pair[0]] = pair[1];
    });

    // Set Core Data
    this.activeLogin.userData = window.activeLogin.userData = userData;
    this.userId = window.userId = userData.id;
    this.anonId = window.anonId = userData.anonId;
};

standard.prototype.setActiveLogin = function(activeLogin) {
    this.activeLogin = window.activeLogin = activeLogin;
    this.setUserData(activeLogin.userData);
};


/* Trigger a login using provided data. This will open a login form if necessary. */
standard.prototype.login = function(options) {
    if (options.start) options.start();

    fimApi.login({
        'grant_type' : options.grantType,
        'username' : options.username,
        'password' : options.password,
        'access_token' : options.sessionHash,
        'refresh_token' : options.refreshToken,
        'client_id' : 'WebPro'
    }, {
        end : (activeLogin) => {
            if ('userData' in activeLogin) {
                this.setActiveLogin(activeLogin);
            }
            else if (!this.activeLogin) { // If we already have an activeLogin, we can continue to use it. Otherwise, we must error.
                dia.error("The login did not return proper information. The page will reload in 3 seconds...");

                setTimeout(function() {
                    location.reload();
                }, 3000);
            }

            this.sessionHash = window.sessionHash = activeLogin.access_token;
            fim_removeHashParameter("sessionHash");

            this.createWorker(() => {
                if (options.finish)
                    options.finish(activeLogin);

                this.sendWorkerMessage({
                    eventName : 'login',
                    sessionHash : this.sessionHash
                });
            });

            if (activeLogin.expires && activeLogin.refresh_token) {
                $.cookie('webpro_refreshToken', activeLogin.refresh_token);

                setTimeout(() => {
                    this.login({
                        grantType : 'refresh_token',
                        refreshToken : activeLogin.refresh_token
                    });
                }, activeLogin.expires * 1000 / 2);
            }


            /* Room Navbar Contents */
            $('#navbar div[name=favRoomsList]').html('');
            $('#navbar div[name=officialRoomsList]').html('');
            $.when(Resolver.resolveRoomsFromIds(window.serverSettings.officialRooms.concat(this.activeLogin.userData.favRooms))).done((pairs) => {
                jQuery.each(pairs, (index, roomData) => {
                    let html = $('<a>').attr({
                        'href' : '#room=' + roomData.id,
                        'class' : 'dropdown-item'
                    }).text(roomData.name);

                    if (this.activeLogin.userData.favRooms.indexOf(roomData.id) != -1)
                        $('#navbar div[name=favRoomsList]').append(html.clone());

                    if (roomData.official)
                        $('#navbar div[name=officialRoomsList]').append(html.clone());
                });
            });


            /* Private Room Form */
            $('#privateRoomForm input[name=userName]').autocompleteHelper('users');
            $("#privateRoomForm").off('submit').on('submit', () => {
                let userName = $("#privateRoomForm input[name=userName]").val();
                let userId = $("#privateRoomForm input[name=userName]").attr('data-id');

                let whenUserIdAvailable = (userId) => {
                    window.location.hash = "#room=p" + [this.userId, userId].join(',');
                };

                if (!userId && userName)
                    whenUserIdAvailable(userId);
                else if (!userName)
                    dia.error('Please enter a username.');
                else {
                    $.when(Resolver.resolveUsersFromNames([userName]).then(function(pairs) {
                        whenUserIdAvailable(pairs[userName].id);
                    }));
                }

                // Don't submit the form
                return false;
            });
        },
        error: (data) => {
            if (options.error)
                options.error(data);
        }
    });
};


standard.prototype.sendWorkerMessage = function(event) {
    if (window.Worker) {
        if (this.worker)
            navigator.serviceWorker.controller.postMessage(event);
    }
    else {
        onmessage({data : event});
    }
};


standard.prototype.workerCallback = function(name, data) {
    console.log("received worker event", name, data);

    if (typeof window.openObjectInstance[name + "Handler"] === "function") {
        window.openObjectInstance[name + "Handler"](JSON.parse(data));
    }
    else if (typeof this[name + "Handler"] === "function") {
        this[name + "Handler"](JSON.parse(data));
    }
};


standard.prototype.createWorker = function(callback) {
    if (window.Worker) {
        if (!this.worker) {
            let cc = (event) => {
                console.log("controller change", event, navigator.serviceWorker.controller);

                navigator.serviceWorker.onmessage = (event) => {
                    this.workerCallback(event.data.name, event.data.data);
                };

                navigator.serviceWorker.controller.postMessage({
                    eventName: 'registerApi',
                    directory : fimApi.directory,
                    serverSettings: fimApi.serverSettings
                });

                callback();
            };
            navigator.serviceWorker.addEventListener('controllerchange',
                function() { cc(); }
            );

            this.worker = navigator.serviceWorker.register('serviceWorker.ts.js').then(function(registration) {
                console.log('Service worker registration succeeded:', registration, navigator.serviceWorker.controller);

                if (navigator.serviceWorker.controller)
                    cc(null);
            }).catch(function(error) {
                console.log('Service worker registration failed:', error);
            });

            navigator.serviceWorker.ready.then(function(reg) {
                console.log("key", Uint8Array.from(window.atob(this.serverSettings.pushPublicKey), c => c.charCodeAt(0)), this.serverSettings.pushPublicKey);
                if (Notification.permission === "granted") {
                    reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: Uint8Array.from(window.atob(this.serverSettings.pushPublicKey), c => c.charCodeAt(0))
                    }).then(function (sub) {
                        console.log('Endpoint URL: ', sub, sub.getKey('p256dh'), sub.getKey('auth'));
                        fimApi.pushSub(sub.endpoint,
                            btoa(String.fromCharCode.apply(null, new Uint8Array(sub.getKey('p256dh')))),
                            btoa(String.fromCharCode.apply(null, new Uint8Array(sub.getKey('auth')))));
                    }).catch(function (e) {
                        console.error('Unable to subscribe to push', e);
                    });
                }
            });
            //this.worker = new Worker('client/js/eventWorker.ts.js');
        }
    }
    else {
        if (typeof onmessage === "undefined") {
            postMessage = (event) => {
                this.workerCallback(event.name, event.data);
            };

            $.getScript('client/js/eventWorker.ts.js', callback);
        }
        else {
            callback();
        }
    }
};


standard.prototype.refreshApplicationHandler = function(event) {
    if ((new Date()).getTime() - window.lastCache > 60000) { // Only refresh if our cache is more than a minute out of date.
        fim_setHashParameter("nocache");
        window.location.reload(true);
    }
};



standard.prototype.logout = function() {
    // TODO: clear refresh token on server?

    if (window.openObjectInstance.close) {
        window.openObjectInstance.close();
    }

    this.sendWorkerMessage({
        eventName : 'logout'
    });

    $.cookie('webpro_username', null);
    $.cookie('webpro_password', null);
    $.cookie('webpro_refreshToken', null);

    fimApi.getActiveUsers(null, {close : true});

    $('#logout').parent().show();
    $('#login').parent().hide();

    window.popup.login();
};