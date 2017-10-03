var Resolver = (function () {
    function Resolver() {
    }
    Resolver.cacheEntry = function (type, entry) {
        console.log(["resolveAddedToCache", type, entry]);
        if (Resolver["cached" + type + "Ids"].indexOf(entry.id) === -1) {
            Resolver["cached" + type + "Ids"].push(Number(entry.id));
            Resolver["cached" + type + "Names"].push(String(entry.name));
            Resolver["cached" + type + "Properties"].push(entry);
        }
    };
    Resolver.resolve = function (type, property, items) {
        console.log(["resolve", type, property, items]);
        var deferred = $.Deferred();
        var returnData = {};
        var unresolvedItems = [];
        var unresolvedItemsWaiting = [];
        var propertyPlural = property.charAt(0).toUpperCase() + property.slice(1) + "s"; // e.g. if property = id, this creates "Ids"
        var typeProperty = type + propertyPlural;
        if (property == "id") {
            for (var i = 0; i < items.length; i++) {
                items[i] = Number(items[i]);
            }
        }
        var _loop_1 = function (item) {
            // If we already have a cached entry, return it.
            if (Resolver["cached" + typeProperty].indexOf(item) !== -1) {
                returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + typeProperty].indexOf(item)];
                console.log(["resolveFoundInCache", type, property, item, returnData[item]]);
            }
            else if (Resolver["waiting" + typeProperty].indexOf(item) !== -1) {
                var retry_1 = setInterval(function () {
                    console.log(["resolveWaitRetry", typeProperty, item, Resolver["cached" + typeProperty]]);
                    if (Resolver["cached" + typeProperty].indexOf(item) !== -1) {
                        clearInterval(retry_1);
                        console.log(["resolveFoundInCacheAfterWait", typeProperty, item, returnData[item]]);
                        returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + typeProperty].indexOf(item)];
                        unresolvedItemsWaiting.splice($.inArray(item, unresolvedItemsWaiting), 1);
                    }
                }, 100);
                unresolvedItemsWaiting.push(item);
            }
            else {
                Resolver["waiting" + typeProperty].push(item);
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
            query[typeProperty] = unresolvedItems;
            fimApi["get" + type.charAt(0).toUpperCase() + type.slice(1) + "s"](query, {
                'each': function (entry) {
                    Resolver.cacheEntry(type, entry);
                    returnData[entry[property]] = entry;
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
        return Resolver.resolve("user", "id", ids);
    };
    Resolver.resolveUsersFromNames = function (names) {
        return Resolver.resolve("user", "name", names);
    };
    Resolver.resolveRoomsFromIds = function (ids) {
        return Resolver.resolve("room", "id", ids);
    };
    Resolver.resolveRoomsFromNames = function (names) {
        return Resolver.resolve("room", "name", names);
    };
    Resolver.resolveGroupsFromIds = function (ids) {
        return Resolver.resolve("group", "id", ids);
    };
    Resolver.resolveGroupsFromNames = function (names) {
        return Resolver.resolve("group", "name", names);
    };
    Resolver.cacheduserIds = [];
    Resolver.cacheduserNames = [];
    Resolver.cacheduserProperties = [];
    Resolver.waitinguserIds = [];
    Resolver.waitinguserNames = [];
    Resolver.waitinguserProperties = [];
    Resolver.cachedroomIds = [];
    Resolver.cachedroomNames = [];
    Resolver.cachedroomProperties = [];
    Resolver.waitingroomIds = [];
    Resolver.waitingroomNames = [];
    Resolver.waitingroomProperties = [];
    Resolver.cachedgroupIds = [];
    Resolver.cachedgroupNames = [];
    Resolver.cachedgroupProperties = [];
    Resolver.waitinggroupIds = [];
    Resolver.waitinggroupNames = [];
    Resolver.waitinggroupProperties = [];
    return Resolver;
}());
//# sourceMappingURL=fim-apiHelper.js.map