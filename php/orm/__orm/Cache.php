<?php

class Cache
{
    private $cache = [];

    function __construct()
    {
    }

    public function store($key, $data, $ttl = 3600)
    {
        $this->cache[$key] = [
            'data' => $data,
            'ttl' => time() + $ttl,
        ];
    }

    public function retrieve($key)
    {
        if (isset($this->cache[$key])) {
            $cacheItem = $this->cache[$key];
            if ($cacheItem['ttl'] > time()) {
                return $cacheItem['data'];
            } else {
                unset($this->cache[$key]);
            }
        }
        return null;
    }

    public function invalidate($key)
    {
        unset($this->cache[$key]);
    }
}