/**
 * Created by joseph on 21/04/17.
 */
declare var fimApi: any;
declare var $: any;

class Resolver {
    static cacheduserIds: Array<number> = [];
    static cacheduserNames: Array<string> = [];
    static cacheduserProperties: Array<any> = [];

    static cachedroomIds: Array<number> = [];
    static cachedroomNames: Array<string> = [];
    static cachedroomProperties: Array<any> = [];

    static waitinguserIds: Array<number> = [];
    static waitinguserNames: Array<string> = [];
    static waitinguserProperties: Array<any> = [];

    static waitingroomIds: Array<number> = [];
    static waitingroomNames: Array<string> = [];
    static waitingroomProperties: Array<any> = [];


    private static cacheEntry(type, entry) {
        console.log(["resolveAddedToCache", type, entry]);

        if (Resolver["cached" + type + "Ids"].indexOf(entry[type + "Id"]) === -1) {
            Resolver["cached" + type + "Ids"].push(Number(entry[type + "Id"]));
            Resolver["cached" + type + "Names"].push(String(entry[type + "Name"]));
            Resolver["cached" + type + "Properties"].push(entry);
        }
    }

    private static resolve(type : string, property : string, items: Array<any>) {
        console.log(["resolve", type, property, items]);

        let deferred = $.Deferred();
        let returnData = {};
        let unresolvedItems = [];
        let unresolvedItemsWaiting = [];

        if (property == "Ids") {
            for (let i = 0; i < items.length; i++) {
                items[i] = Number(items[i]);
            }
        }

        // Process Each Item
        for (let item of items) {

            // If we already have a cached entry, return it.
            if (Resolver["cached" + type + property].indexOf(item) !== -1) {
                returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + type + property].indexOf(item)];

                console.log(["resolveFoundInCache", type, property, item, returnData[item]]);
            }

            // If we are waiting on a result for the entry, wait for it to appear in the cache.
            else if (Resolver["waiting" + type + property].indexOf(item) !== -1) {
                let retry = setInterval(function() {
                    console.log(["resolveWaitRetry", type, property, item, Resolver["cached" + type + property]]);

                    if (Resolver["cached" + type + property].indexOf(item) !== -1) {
                        clearInterval(retry);
                        console.log(["resolveFoundInCacheAfterWait", type, property, item, returnData[item]]);
                        returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + type + property].indexOf(item)];
                        unresolvedItemsWaiting.splice($.inArray(item, unresolvedItemsWaiting),1);
                    }
                }, 100);

                unresolvedItemsWaiting.push(item);
            }

            // Otherwise, add the item to the unresolved items list in preparation to query them.
            else {
                Resolver["waiting" + type + property].push(item);
                unresolvedItems.push(item);
            }
        }

        // Query the unresolved items all at once.
        if (unresolvedItems.length > 0) {
            let query = {};
            query[type + property] = unresolvedItems;
            fimApi["get" + type.charAt(0).toUpperCase() + type.slice(1) + "s"](query, {
                'each': function(entry) {
                    Resolver.cacheEntry(type, entry);
                    returnData[entry[type + property.slice(0, -1)]] = entry;
                },
                'end': function() {
                    unresolvedItems = [];
                }
            })
        }

        // Wait for all unresolved items that are waiting for entries to appear in cache to be processed.
        if (unresolvedItemsWaiting.length > 0 || unresolvedItems.length > 0) {
            let retry2 = setInterval(function() {
                console.log("unresolvedItemsWait");

                if (unresolvedItemsWaiting.length == 0 && unresolvedItems.length == 0) {
                    console.log("unresolvedItemsComplete");
                    clearInterval(retry2);
                    deferred.resolve(returnData);
                }
            }, 100);
        }
        else {
            deferred.resolve(returnData);
        }

        return deferred.promise();
    }

    public static resolveUsersFromIds(ids: Array<number>) {
        return Resolver.resolve("user", "Ids", ids);
    }

    public static resolveUsersFromNames(names: Array<string>) {
        return Resolver.resolve("user", "Names", names);
    }

    public static resolveRoomsFromIds(ids: Array<number>) {
        return Resolver.resolve("room", "Ids", ids);
    }

    public static resolveRoomsFromNames(names: Array<string>) {
        return Resolver.resolve("room", "Names", names);
    }
}