<?php
/**
 * Created by PhpStorm.
 * User: joseph
 * Date: 30/01/18
 * Time: 18:37
 */

namespace Cache\Driver;
use Cache\CacheAddFallbackTrait;
use Cache\CacheSetFallbackTrait;
use Cache\DriverInterface;

class Dynamodb implements DriverInterface
{
    use CacheAddFallbackTrait;
    use CacheSetFallbackTrait;

    /**
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    private $instance;

    /** The table used to store cache entries. */
    const tableName = "fim_keyValue";

    /** The name of the column storing keys. */
    const keyColumnName = "key";

    /** The name of the column storing values. */
    const valueColumnName = "value";


    public static function available() : bool {
        return true;
    }

    public static function getCacheType(): string {
        return DriverInterface::CACHE_TYPE_DISTRIBUTED;
    }


    public function __construct($server) {
        $server = array_merge([
            'endpoint' => 'http://localhost:8000',
            'region' => 'us-west-2',
            'version' => 'latest',
        ], $server);

        $sdk = new \Aws\Sdk(\Fim\Utilities::arrayFilterKeys($server, ['endpoint', 'region', 'version']));

        $this->instance = $sdk->createDynamoDb();
    }


    public function get($index) {
        $value = $this->instance->getItem([
            'Key' => [
                self::keyColumnName => [
                    'S' => $index
                ]
            ],
            'TableName' => self::tableName
        ])['Item'][self::valueColumnName] ?? false;

        if ($value)
            return json_decode($value);
        else false;
    }

    public function set($index, $value, $ttl = 3600) {
        $client->updateItem([
            'ExpressionAttributeNames' => [
                '#AT' => 'AlbumTitle',
                '#Y' => 'Year',
            ],
            'ExpressionAttributeValues' => [
                ':t' => [
                    'S' => 'Louder Than Ever',
                ],
                ':y' => [
                    'N' => '2015',
                ],
            ],
            'Key' => [
                'Artist' => [
                    'S' => 'Acme Band',
                ],
                'SongTitle' => [
                    'S' => 'Happy Day',
                ],
            ],
            'ReturnValues' => 'ALL_NEW',
            'TableName' => 'Music',
            'UpdateExpression' => 'SET #Y = :y, #AT = :t',
        ]);

        return $this->instance->set($index, $value, $ttl);
    }

    public function exists($index) : bool {
        return count($this->instance->getItem([
            'Key' => [
                self::keyColumnName => [
                    'S' => $index
                ]
            ],
            'TableName' => self::tableName
        ])) > 0;
    }

    public function inc($index, int $amt = 1) {
        $value = $this->get($index);

        if ($value)
            return $this->set($index, $value + 1);
        else return false;
    }


    public function clear($index) {
        return $this->instance->deleteItem([
            'Key' => [
                self::keyColumnName => [
                    'S' => $index
                ]
            ],
            'TableName' => self::tableName
        ])
    }

    public function clearAll() {
        return $this->instance->flushDb();
    }

    public function dump() {
        $keys = [];

        foreach ($this->instance->getKeys('*') AS $key) {
            $keys[$key]['value'] = $this->get($key);
            $keys[$key]['ttl'] = $this->instance->ttl($key);
        }

        return $keys;
    }
}