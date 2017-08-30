var Resolver = (function () {
    function Resolver() {
    }
    Resolver.cacheEntry = function (type, entry) {
        console.log(["resolveAddedToCache", type, entry]);
        if (Resolver["cached" + type + "Ids"].indexOf(entry[type + "Id"]) === -1) {
            Resolver["cached" + type + "Ids"].push(Number(entry[type + "Id"]));
            Resolver["cached" + type + "Names"].push(String(entry[type + "Name"]));
            Resolver["cached" + type + "Properties"].push(entry);
        }
    };
    Resolver.resolve = function (type, property, items) {
        console.log(["resolve", type, property, items]);
        var deferred = $.Deferred();
        var returnData = {};
        var unresolvedItems = [];
        var unresolvedItemsWaiting = [];
        if (property == "Ids") {
            for (var i = 0; i < items.length; i++) {
                items[i] = Number(items[i]);
            }
        }
        var _loop_1 = function (item) {
            // If we already have a cached entry, return it.
            if (Resolver["cached" + type + property].indexOf(item) !== -1) {
                returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + type + property].indexOf(item)];
                console.log(["resolveFoundInCache", type, property, item, returnData[item]]);
            }
            else if (Resolver["waiting" + type + property].indexOf(item) !== -1) {
                var retry_1 = setInterval(function () {
                    console.log(["resolveWaitRetry", type, property, item, Resolver["cached" + type + property]]);
                    if (Resolver["cached" + type + property].indexOf(item) !== -1) {
                        clearInterval(retry_1);
                        console.log(["resolveFoundInCacheAfterWait", type, property, item, returnData[item]]);
                        returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + type + property].indexOf(item)];
                        unresolvedItemsWaiting.splice($.inArray(item, unresolvedItemsWaiting), 1);
                    }
                }, 100);
                unresolvedItemsWaiting.push(item);
            }
            else {
                Resolver["waiting" + type + property].push(item);
                unresolvedItems.push(item);
            }
        };
        // Process Each Item
        for (var _i = 0, items_1 = items; _i < items_1.length; _i++) {
            var item = items_1[_i];
            _loop_1(item);
        }
        // Query the unresolved items all at once.
        if (unresolvedItems.length > 0) {
            var query = {};
            query[type + property] = unresolvedItems;
            fimApi["get" + type.charAt(0).toUpperCase() + type.slice(1) + "s"](query, {
                'each': function (entry) {
                    Resolver.cacheEntry(type, entry);
                    returnData[entry[type + property.slice(0, -1)]] = entry;
                },
                'end': function () {
                    unresolvedItems = [];
                }
            });
        }
        // Wait for all unresolved items that are waiting for entries to appear in cache to be processed.
        if (unresolvedItemsWaiting.length > 0 || unresolvedItems.length > 0) {
            var retry2_1 = setInterval(function () {
                console.log("unresolvedItemsWait");
                if (unresolvedItemsWaiting.length == 0 && unresolvedItems.length == 0) {
                    console.log("unresolvedItemsComplete");
                    clearInterval(retry2_1);
                    deferred.resolve(returnData);
                }
            }, 100);
        }
        else {
            deferred.resolve(returnData);
        }
        return deferred.promise();
    };
    Resolver.resolveUsersFromIds = function (ids) {
        return Resolver.resolve("user", "Ids", ids);
    };
    Resolver.resolveUsersFromNames = function (names) {
        return Resolver.resolve("user", "Names", names);
    };
    Resolver.resolveRoomsFromIds = function (ids) {
        return Resolver.resolve("room", "Ids", ids);
    };
    Resolver.resolveRoomsFromNames = function (names) {
        return Resolver.resolve("room", "Names", names);
    };
    Resolver.cacheduserIds = [];
    Resolver.cacheduserNames = [];
    Resolver.cacheduserProperties = [];
    Resolver.cachedroomIds = [];
    Resolver.cachedroomNames = [];
    Resolver.cachedroomProperties = [];
    Resolver.waitinguserIds = [];
    Resolver.waitinguserNames = [];
    Resolver.waitinguserProperties = [];
    Resolver.waitingroomIds = [];
    Resolver.waitingroomNames = [];
    Resolver.waitingroomProperties = [];
    return Resolver;
}());
//# sourceMappingURL=fim-apiHelper.js.map