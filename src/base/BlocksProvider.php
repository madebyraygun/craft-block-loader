<?php

namespace madebyraygun\blockloader\base;

use Craft;
use craft\elements\Entry;
use madebyraygun\blockloader\base\ContextQuery;
use madebyraygun\blockloader\base\BlocksFactory;
use Illuminate\Support\Collection;

class BlocksProvider
{
    public static function init(array $blockClasses): void
    {
        ContextCache::attachEventHandlers();
        BlocksFactory::init($blockClasses);
    }

    public static function extractBlockDescriptors(Entry $entry, string $fieldHandle): Collection
    {
        $cachedDescriptors = ContextCache::get($entry);
        $contextQuery = new ContextQuery($entry, $fieldHandle);
        $descriptors = $contextQuery->queryDescriptors($cachedDescriptors);
        ContextCache::set($entry, $descriptors);
        return $descriptors;
    }
}
