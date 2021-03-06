<?php

namespace App\Service;



use App\Entity\Stat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Simple\FilesystemCache;

class CacheHelperService
{
    private $cachePool;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->cachePool = new FilesystemCache();
        $this->entityManager = $entityManager;
    }

    public function getValue(string $key)
    {
        $data = null;
        if ($this->cachePool->hasItem($key)) {
            $cacheItem = $this->cachePool->get($key);
            $explodedCacheValue = explode('_date_', $cacheItem);
            $data = $explodedCacheValue[0];
        }

        return $data;
    }

    public function setValue(string $cacheKey, string $value, ?int $lifetime = null):void
    {
        $cacheItem = $this->cachePool->get($cacheKey);
        if (!$this->cachePool->hasItem($cacheKey)) {
            $value = $value . '_date_' . (new \DateTime())->getTimestamp();

        } else {
            $explodedCacheValue = explode('_date_', $cacheItem);
            $value = $value . '_date_' . $explodedCacheValue[1];
        }

        $this->cachePool->set($cacheKey, $value, $lifetime);
    }

    public function getCretedTimestamp(string $cacheKey): ?int
    {
        if (!$this->cachePool->hasItem($cacheKey)) {
            return null;
        }

        $cacheItem = $this->cachePool->get($cacheKey);
        $explodedCacheValue = explode('_date_', $cacheItem);

        return $explodedCacheValue[1];
    }

    public function setCreatedTimestamp(string $cacheKey, int $timestamp, ?int $lifetime = null): void
    {
        if (!$this->cachePool->hasItem($cacheKey)) {
            return;
        }

        $cacheItem = $this->cachePool->get($cacheKey);
        $explodedCacheValue = explode('_date_', $cacheItem);

        $this->clearCacheByKey($cacheKey);

        $this->cachePool->set($explodedCacheValue[0] . '_date_' . $timestamp, $cacheItem, $lifetime);

    }

    public function clearCacheByKey(string $cacheKey)
    {
        $this->cachePool->deleteItem($cacheKey);
    }


}