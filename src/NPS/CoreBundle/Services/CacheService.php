<?php
namespace NPS\CoreBundle\Services;

use Predis\Client;

/**
 * A common front for caching. Must be compatible with Predis\Client
 */
class CacheService
{
    private $redis;
    private $ttl = 0;
    private $forceMiss = false;

    /**
     * Constructor
	 * @param Client $redis      Client
	 * @param int    $defaultTtl default title
     */
    public function __construct(Client $redis, $defaultTtl = 0)
    {
        $this->redis = $redis;
        $this->ttl = intval($defaultTtl);
    }

    /**
     * Makes the cache always return false (forces cache miss)
     * @param boolean $miss Whether to force miss or not
     */
    public function setForceMiss($miss)
    {
        $this->forceMiss = $miss;
    }

    /**
     * Wrapper for mset
	 * @param array $array [description]
	 * @param int   $ttl   [Default null]
     */
    public function mset($array, $ttl = null)
    {
        //do manual multiset
        if (empty($ttl)) {
            $ttl = $this->ttl;
        }
        $this->redis->multi();
        foreach ($array as $k => $v) {
            $this->setex($k, $ttl, $v);
        }
        $this->redis->exec();
    }

    /**
     * Wrapper for mget
	 * @param array $keys [description]
	 * 
	 * @return array
     */
    public function mget($keys)
    {
        if ($this->forceMiss) {
            $res = array_fill(0, count($keys), null);
        } else {
            $res = $this->redis->mget($keys);
        }

        return $res;
    }

    /**
     * Wrapper for get
	 * @param string $key [Default '']
	 * 
	 * @return array
     */
    public function get($key = '')
    {
        if ($this->forceMiss) {
            $res = null;
        } else {
            $res = $this->redis->get($key);
        }

        return $res;
    }

    /**
     * Wrapper for set
	 * @param string $key   [description]
	 * @param string $value [description]
	 * 
	 * @return array
     */
    public function set($key, $value)
    {
        if ($this->ttl > 0) {
            $res = $this->redis->setex($key, $this->ttl, $value);
        }

        return $res;
    }

    /**
     * Wrapper for setex
	 * @param string $key   key of new registry
	 * @param int    $ttl   key timeout (in seconds)
	 * @param string $value value to save
	 * 
	 * @return array
     */
    public function setex($key, $ttl, $value)
    {
        if (empty($ttl)) {
            $ttl = $this->ttl;
        }
        if ($ttl > 0) {
            $res = $this->redis->setex($key, $ttl, $value);
        }

        return $res;
    }
}
