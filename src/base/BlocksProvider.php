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
        $cachedDescriptors = ContextCache::get($entry) ?? collect([]);
        $fieldDescriptors = $cachedDescriptors->filter(fn($d) => $d->fieldHandle === $fieldHandle);
        $cachedDescriptors = $cachedDescriptors->filter(fn($d) => $d->fieldHandle !== $fieldHandle);

        $contextQuery = new ContextQuery($entry, $fieldHandle);
        $contextQuery->setCachedDescriptors($fieldDescriptors);
        $fieldDescriptors = $contextQuery->queryDescriptors();

        $newFieldDescriptors = $fieldDescriptors->filter(fn($d) => $d->cacheable === true);
        $cachedDescriptors = $cachedDescriptors->merge($newFieldDescriptors);
        ContextCache::set($entry, $cachedDescriptors);
        return $fieldDescriptors;
    }
}
