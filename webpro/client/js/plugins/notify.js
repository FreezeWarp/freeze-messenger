var notify = {
    webkitNotifySupported: function() {
        return "Notification" in window;
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