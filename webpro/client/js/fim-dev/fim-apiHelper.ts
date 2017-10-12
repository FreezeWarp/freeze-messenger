/**
 * Created by joseph on 21/04/17.
 */
declare var fimApi: any;
declare var $: any;

class Resolver {
    static cacheduserIds: Array<number> = [];
    static cacheduserNames: Array<string> = [];
    static cacheduserProperties: Array<any> = [];

    static waitinguserIds: Array<number> = [];
    static waitinguserNames: Array<string> = [];
    static waitinguserProperties: Array<any> = [];


    static cachedroomIds: Array<number> = [];
    static cachedroomNames: Array<string> = [];
    static cachedroomProperties: Array<any> = [];
    
    static waitingroomIds: Array<number> = [];
    static waitingroomNames: Array<string> = [];
    static waitingroomProperties: Array<any> = [];


    static cachedgroupIds: Array<number> = [];
    static cachedgroupNames: Array<string> = [];
    static cachedgroupProperties: Array<any> = [];

    static waitinggroupIds: Array<number> = [];
    static waitinggroupNames: Array<string> = [];
    static waitinggroupProperties: Array<any> = [];


    private static cacheEntry(type, entry) {
        //console.log(["resolveAddedToCache", type, entry]);

        if (Resolver["cached" + type + "Ids"].indexOf(entry.id) === -1) {
            Resolver["cached" + type + "Ids"].push(Number(entry.id));
            Resolver["cached" + type + "Names"].push(String(entry.name));
            Resolver["cached" + type + "Properties"].push(entry);
        }
    }

    private static resolve(type : string, property : string, items: Array<any>) {
        console.log(["resolve", type, property, items]);

        let deferred = $.Deferred();
        let returnData = {};
        let unresolvedItems = [];
        let unresolvedItemsWaiting = [];

        let propertyPlural = property.charAt(0).toUpperCase() + property.slice(1) + "s"; // e.g. if property = id, this creates "Ids"
        let typeProperty = type + propertyPlural;

        if (property == "id") {
            for (let i = 0; i < items.length; i++) {
                items[i] = Number(items[i]);
            }
        }

        // Process Each Item
        for (let item of items) {

            // If we already have a cached entry, return it.
            if (Resolver["cached" + typeProperty].indexOf(item) !== -1) {
                returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + typeProperty].indexOf(item)];

                console.log(["resolveFoundInCache", type, property, item, returnData[item]]);
            }

            // If we are waiting on a result for the entry, wait for it to appear in the cache.
            else if (Resolver["waiting" + typeProperty].indexOf(item) !== -1) {
                let retry = setInterval(function() {
                    console.log(["resolveWaitRetry", typeProperty, item, Resolver["cached" + typeProperty]]);

                    if (Resolver["cached" + typeProperty].indexOf(item) !== -1) {
                        clearInterval(retry);
                        console.log(["resolveFoundInCacheAfterWait", typeProperty, item, returnData[item]]);
                        returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + typeProperty].indexOf(item)];
                        unresolvedItemsWaiting.splice($.inArray(item, unresolvedItemsWaiting),1);
                    }
                }, 100);

                unresolvedItemsWaiting.push(item);
            }

            // Otherwise, add the item to the unresolved items list in preparation to query them.
            else {
                Resolver["waiting" + typeProperty].push(item);
                unresolvedItems.push(item);
            }
        }

        // Query the unresolved items all at once.
        if (unresolvedItems.length > 0) {
            let query = {};
            query[typeProperty] = unresolvedItems;

            fimApi["get" + type.charAt(0).toUpperCase() + type.slice(1) + "s"](query, {
                'each': function(entry) {
                    Resolver.cacheEntry(type, entry);
                    returnData[entry[property]] = entry;
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
        return Resolver.resolve("user", "id", ids);
    }

    public static resolveUsersFromNames(names: Array<string>) {
        return Resolver.resolve("user", "name", names);
    }

    public static resolveRoomsFromIds(ids: Array<number>) {
        return Resolver.resolve("room", "id", ids);
    }

    public static resolveRoomsFromNames(names: Array<string>) {
        return Resolver.resolve("room", "name", names);
    }

    public static resolveGroupsFromIds(ids: Array<number>) {
        return Resolver.resolve("group", "id", ids);
    }

    public static resolveGroupsFromNames(names: Array<string>) {
        return Resolver.resolve("group", "name", names);
    }
}