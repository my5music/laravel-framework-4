<?php namespace Illuminate\Cache;

use http\Exception\RuntimeException;
use Illuminate\Redis\Database as Redis;

class RedisStore extends TaggableStore implements StoreInterface
{
    /**
     * The Redis database connection.
     *
     * @var \Illuminate\Redis\Database
     */
    protected $redis;

    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The Redis connection that should be used.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new Redis store.
     *
     * @param \Illuminate\Redis\Database $redis
     * @param string $prefix
     * @param string $connection
     * @return void
     */
    public function __construct(Redis $redis, $prefix = '', $connection = 'default')
    {
        $this->redis = $redis;
        $this->connection = $connection;
        $this->prefix = '' !== $prefix ? $prefix . ':' : '';
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        try {
            $serialized = $this->connection()->get($this->prefix . $key);

            if (!is_string($serialized)) {
                return null;
            }

            if (extension_loaded('igbinary')) {
                $value = igbinary_unserialize($serialized);
                $value = false === $value ? null : $value;
            } elseif (is_serialized($serialized)) {
                $value = unserialize($serialized);
                $value = false === $value ? null : $value;
            } else {
                $value = null;
            }

            return $value;
        } catch (\Predis\Connection\ConnectionException $e) {}

        return null;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key
     * @param mixed $value
     * @param int $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        if (extension_loaded('igbinary')) {
            $serialized = igbinary_serialize($value);
        } else {
            $serialized = serialize($value);
        }

        if (!is_string($serialized)) {
            return;
        }

        $minutes = max(1, $minutes);

        try {
            $this->connection()->setex($this->prefix . $key, $minutes * 60, $serialized);
        } catch (\Predis\Connection\ConnectionException $e) {}
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        throw new RuntimeException('Not implemented.');
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        throw new RuntimeException('Not implemented.');
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->put($key, $value, 1000000);
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return void
     */
    public function forget($key)
    {
        try {
            $this->connection()->del($this->prefix . $key);
        } catch (\Predis\Connection\ConnectionException $e) {}
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        try {
            $this->connection()->flushdb();
        } catch (\Predis\Connection\ConnectionException $e) {}
    }

    /**
     * Begin executing a new tags operation.
     *
     * @param array|mixed $names
     * @return \Illuminate\Cache\RedisTaggedCache
     */
    public function tags($names)
    {
        return new RedisTaggedCache($this, new TagSet($this, is_array($names) ? $names : func_get_args()));
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Predis\ClientInterface
     */
    public function connection()
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Set the connection name to be used.
     *
     * @param string $connection
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the Redis database instance.
     *
     * @return \Illuminate\Redis\Database
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}
