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

        for (let item of items) {
            if (Resolver["cached" + type + property].indexOf(item) !== -1) {
                returnData[item] = Resolver["cached" + type + "Properties"][Resolver["cached" + type + property].indexOf(item)];

                console.log(["resolveFoundInCache", type, property, item, returnData[item]]);
            }
            else {
                unresolvedItems.push(item);
            }
        }

        if (unresolvedItems.length > 0) {
            let query = {};
            query[type + property] = unresolvedItems;
            fimApi["get" + type.charAt(0).toUpperCase() + type.slice(1) + "s"](query, {
                'each': function(entry) {
                    Resolver.cacheEntry(type, entry);
                    returnData[entry[type + property.slice(0, -1)]] = entry;
                },
                'end': function() {
                    deferred.resolve(returnData);
                }
            })
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