<?php


namespace Digitick\Foundation\Fuse\Cache;


interface CacheManagerInterface
{
    public function exists($key);

    public function get($key);

    public function set($key, $data, $ttl = 0);
}