<?php


namespace Digitick\Foundation\Fuse\Cache;


use Psr\SimpleCache\CacheInterface;

class ApcCacheManager implements CacheInterface
{
    public function __construct()
    {
        if (!extension_loaded("apc") && !extension_loaded("apcu")) {
            throw new \RuntimeException("This cache manager need APC or APCU extension.");
        }
    }

    public function delete($key)
    {
        apcu_delete($key);
    }

    public function clear()
    {
        apcu_clear_cache();
    }

    public function getMultiple($keys, $default = null)
    {
        throw new \RuntimeException('Not implemented');
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new \RuntimeException('Not implemented');
    }

    public function deleteMultiple($keys)
    {
        throw new \RuntimeException('Not implemented');
    }

    public function has($key)
    {
        return apcu_exists($key);
    }

    public function get($key, $default = null)
    {
        $data = apc_fetch($key);
        if ($data === FALSE) {
            return $default;
        }

        return $data;
    }

    public function set($key, $value, $ttl = null)
    {
        apc_store($key, $value, $ttl);
    }


}