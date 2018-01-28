var notify = {
    webkitNotifySupported: function() {
        return "Notification" in window
            // Chrome now disables notifications is non-secure origins
            && !(window.chrome && !window.isSecureContext);
    },

    pushNotifySupported : function() {
        return "Notification" in window
            && "serviceWorker" in navigator
            && window.isSecureContext;
    },

    webkitNotifyRequest: function () {
        window.Notification.requestPermission();
    },

    webkitNotify: function (icon, title, notifyData) {
        if (window.Notification.permission != "granted") {
            notify.webkitNotifyRequest();
        }
        else {
            new window.Notification(title, {
                body : notifyData,
                icon : icon,
                tag : "fm-notify"
            });
        }
    }
};