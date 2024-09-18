<?php

namespace madebyraygun\blockloader\base;

use Craft;
use craft\elements\Entry;
use madebyraygun\blockloader\base\ContextQuery;
use madebyraygun\blockloader\base\BlocksFactory;
use Illuminate\Support\Collection;

class BlocksProvider
{
    private static ?Collection $cache = null;

    public static function init(array $blockClasses): void
    {
        ContextCache::attachEventHandlers();
        BlocksFactory::init($blockClasses);
    }

    public static function extractBlockDescriptors(Entry $entry, string $fieldHandle): Collection
    {
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
        return collect(self::$cache->get($fieldHandle) ?? []);
    }

    private static function updateCacheDescriptors(Entry $entry, string $fieldHandle, Collection $newDescriptors): void
    {
        // filter out non cacheable descriptors
        $newDescriptors = $newDescriptors->filter(fn($d) => $d->cacheable);
        $oldDescriptors = collect(self::$cache->get($fieldHandle) ?? []);
        // remove from oldDescriptors by looking into newDescriptors ids
        $oldDescriptors = $oldDescriptors->filter(fn($d) => !$newDescriptors->contains('id', $d->id));
        $updatedDescriptors = $newDescriptors->merge($oldDescriptors);
        self::$cache->put($fieldHandle, $updatedDescriptors);
        ContextCache::set($entry, self::$cache);
        self::$cache = null;
    }
}
