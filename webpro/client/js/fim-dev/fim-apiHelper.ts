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


    static cachedroomIds: Array<any> = [];
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
            Resolver["cached" + type + "Ids"].push(String(entry.id));
            Resolver["cached" + type + "Names"].push(String(entry.name));
            Resolver["cached" + type + "Properties"].push(entry);
        }
    }

    private static getCapital(thing : string) {
        return thing.charAt(0).toUpperCase() + thing.slice(1);
    }

    private static getPlural(thing : string) {
        return Resolver.getCapital(thing) + "s";
    }

    private static resolve(type : string, property : string, items: Array<any>) {
        //console.log(["resolve", type, property, items]);

        let deferred = $.Deferred();
        let returnData = {};
        let unresolvedItems = [];
        let unresolvedItemsWaiting = [];

        let propertyPlural = Resolver.getPlural(property); // e.g. if property = id, this creates "Ids"
        let typeProperty = type + propertyPlural;

        if (property == "id") {
            for (let i = 0; i < items.length; i++) {
                items[i] = String(items[i]);
            }
        }

        // Process Each Item
        for (let item of items) {
            if (property === "id" || property === "name") {
                item = String(item);
            }

            // If we already have a cached entry, return it.
            if (Resolver["cached" + typeProperty].indexOf(item) !== -1) {
                returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + typeProperty].indexOf(item)];

                //console.log(["resolveFoundInCache", type, property, item, returnData[item]]);
            }

            // If we are waiting on a result for the entry, wait for it to appear in the cache.
            else if (Resolver["waiting" + typeProperty].indexOf(item) !== -1) {
                let retry = setInterval(function() {
                    console.log(["resolveWaitRetry", typeProperty, item, Resolver["cached" + typeProperty]]);

                    if (Resolver["cached" + typeProperty].indexOf(item) !== -1) {
                        clearInterval(retry);
                        //console.log(["resolveFoundInCacheAfterWait", typeProperty, item, returnData[item]]);
                        returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + typeProperty].indexOf(item)];
                        unresolvedItemsWaiting.splice($.inArray(item, unresolvedItemsWaiting),1);
                    }
                    else if (Resolver["waiting" + typeProperty].indexOf(item) === -1) {
                        clearInterval(retry);
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

            fimApi["get" + Resolver.getPlural(type)](query, {
                'each': function(entry) {
                    Resolver.cacheEntry(type, entry);
                    returnData[entry[property]] = entry;
                },
                'end': function() {
                    jQuery.each(unresolvedItems, function(index, item) {
                        Resolver["waiting" + typeProperty].splice($.inArray(item, Resolver["waiting" + typeProperty]),1);
                    });

                    unresolvedItems = [];
                }
            })
        }

        // Wait for all unresolved items that are waiting for entries to appear in cache to be processed.
        if (unresolvedItemsWaiting.length > 0 || unresolvedItems.length > 0) {
            let retry2 = setInterval(function() {
                //console.log("unresolvedItemsWait");

                if (unresolvedItemsWaiting.length == 0 && unresolvedItems.length == 0) {
                    //console.log("unresolvedItemsComplete");
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

    public static resolveRoomsFromIds(ids: Array<any>) {
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

    public static resolveFromId(type : string, id : number) {
        return Resolver["resolve" + Resolver.getCapital(type) + "FromIds"]([id]);
    }

    public static resolveFromName (type : string, name : string) {
        return Resolver["resolve" + Resolver.getCapital(type) + "FromNames"]([name]);
    }
}