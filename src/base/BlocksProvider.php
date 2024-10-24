<?php

namespace madebyraygun\blockloader\base;

use craft\elements\Entry;
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
        $cacheSet = new ContextCacheSet($entry, $fieldHandle);
        $cacheSet->read();
        $contextQuery = new ContextQuery($entry, $fieldHandle);
        $contextQuery->setContextCache($cacheSet);
        $result = $contextQuery->queryDescriptors();
        $cacheSet->add($result);
        $cacheSet->write();
        return $result;
    }
}
