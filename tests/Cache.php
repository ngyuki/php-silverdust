<?php
namespace ngyuki\Silverdust\Test;

use BadMethodCallException;
use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface
{
    private $data = [];

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }
        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->data[$key] = $value;
    }

    public function delete($key)
    {
        unset($this->data[$key]);
    }

    public function clear()
    {
        $this->data = [];
    }

    public function getMultiple($keys, $default = null)
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function deleteMultiple($keys)
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }
}
