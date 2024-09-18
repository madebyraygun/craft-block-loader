<?php

namespace madebyraygun\blockloader\base;

use Craft;
use craft\elements\Entry;
use madebyraygun\blockloader\base\ContextQuery;
use madebyraygun\blockloader\base\BlocksFactory;
use Illuminate\Support\Collection;

class BlocksProvider
{
    private static $cache = null;

    public static function init(array $blockClasses): void
    {
        ContextCache::attachEventHandlers();
        BlocksFactory::init($blockClasses);
    }

    public static function extractBlockDescriptors(Entry $entry, string $fieldHandle): Collection
    {
        $result = collect([]);
        $fieldDescriptors = self::getCachedDescriptors($entry, $fieldHandle);
        $contextQuery = new ContextQuery($entry, $fieldHandle);
        $contextQuery->setCachedDescriptors($fieldDescriptors);
        $result = $contextQuery->queryDescriptors();
        self::updateCacheDescriptors($entry, $fieldHandle, $result);
        return $result;
    }

    private static function getCachedDescriptors(Entry $entry, string $fieldHandle): Collection
    {
        self::$cache = ContextCache::get($entry) ?? collect([]);
        return self::$cache->filter(fn($d) => $d->fieldHandle === $fieldHandle);
    }

    private static function updateCacheDescriptors(Entry $entry, string $fieldHandle, Collection $descriptors): void
    {
        $descriptors = $descriptors->filter(fn($d) => $d->fieldHandle !== $fieldHandle);
        $cachedDescriptors = self::$cache->merge($descriptors);
        ContextCache::set($entry, $cachedDescriptors);
        self::$cache = null;
    }
}
