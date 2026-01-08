<?php

namespace Weglot\Client\Caching;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class Cache implements CacheInterface
{
    /**
     * @var CacheItemPoolInterface|null
     */
    protected $itemPool;

    /**
     * @var int
     */
    protected $expire = 604800; // 7 days (= 60 * 60 * 24 * 7)

    /**
     * @param null $itemPool
     */
    public function __construct($itemPool = null)
    {
        $this->setItemPool($itemPool);
    }

    /**
     * @param CacheItemPoolInterface|null $itemPool
     *
     * @return $this
     */
    public function setItemPool($itemPool)
    {
        $this->itemPool = $itemPool;

        return $this;
    }

    /**
     * @return CacheItemPoolInterface|null
     */
    public function getItemPool()
    {
        return $this->itemPool;
    }

    /**
     * @param int $expire
     *
     * @return $this
     */
    public function setExpire($expire)
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * @return int
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * @return bool
     */
    public function enabled()
    {
        return null !== $this->itemPool;
    }

    /**
     * @return string
     */
    public function generateKey(array $data)
    {
        return 'wg_'.sha1(json_encode($data));
    }

    /**
     * @param string $key
     *
     * @return CacheItemInterface
     *
     * @throws InvalidArgumentException
     */
    public function get($key)
    {
        return $this->getItemPool()->getItem($key);
    }

    /**
     * @return CacheItemInterface
     *
     * @throws InvalidArgumentException
     */
    public function getWithGenerate(array $data)
    {
        $key = $this->generateKey($data);

        return $this->get($key);
    }

    /**
     * @return bool
     */
    public function save(CacheItemInterface $item)
    {
        $item->expiresAfter($this->getExpire());

        return $this->getItemPool()->save($item);
    }
}
