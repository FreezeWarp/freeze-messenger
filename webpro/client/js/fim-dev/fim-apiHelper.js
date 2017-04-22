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
        for (var _i = 0, items_1 = items; _i < items_1.length; _i++) {
            var item = items_1[_i];
            if (Resolver["cached" + type + property].indexOf(item) !== -1) {
                returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + type + property].indexOf(item)];
                console.log(["resolveFoundInCache", type, property, item, returnData[item]]);
            }
            else {
                unresolvedItems.push(item);
            }
        }
        if (unresolvedItems.length > 0) {
            var query = {};
            query[type + property] = unresolvedItems;
            fimApi["get" + type.charAt(0).toUpperCase() + type.slice(1) + "s"](query, {
                'each': function (entry) {
                    Resolver.cacheEntry(type, entry);
                    returnData[entry[type + property.slice(0, -1)]] = entry;
                },
                'end': function () {
                    deferred.resolve(returnData);
                }
            });
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
    return Resolver;
}());
//# sourceMappingURL=fim-apiHelper.js.map