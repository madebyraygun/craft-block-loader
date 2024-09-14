<?php

namespace madebyraygun\blockloader\base;

use Craft;
use craft\elements\Entry;
use madebyraygun\blockloader\base\ContextDescriptor;
use madebyraygun\blockloader\base\ContextQuery;
use Illuminate\Support\Collection;

class BlocksProvider
{
    private static $blockClasses = [];

    public static function init(array $blockClasses): void
    {
        ContextCache::attachEventHandlers();
        static::$blockClasses = $blockClasses;
    }

    public static function extractBlockDescriptors(Entry $entry, string $fieldHandle): Collection
    {
        //     $entry = $context['entry'] ?? null;
        //     $cachedDescriptors = ContextCache::get($entry);
        //     $prototypeBlocks = static::getPrototypeBlocks($entry, $blockClasses);
        //     $newDescriptors = static::getNewDescriptors($entry, $prototypeBlocks, $cachedDescriptors);
        //     $descriptors = array_merge($newDescriptors, $cachedDescriptors);
        //     static::updateCacheIfNeeded($entry, $newDescriptors, $descriptors);
        //     static::setBlockDescriptors($context, $descriptors);
        $contextQuery = new ContextQuery($entry, $fieldHandle, static::$blockClasses);
        //$cachedDescriptors = ContextCache::get($entry);
        // filter out cached descriptors
        $descriptors = $contextQuery->queryDescriptors();
        //static::updateCacheIfNeeded($entry, $newDescriptors, $descriptors);
        return $descriptors;
    }

    /**
     * Update the cache if new descriptors were created.
     * @param Entry $entry the entry
     * @param array $newDescriptors the new descriptors
     * @param array $descriptors the descriptors
     * @return void
     */
    private static function updateCacheIfNeeded($entry, $newDescriptors, $descriptors): void
    {
        $newDescriptors = ContextCache::filterCacheableDescriptors($newDescriptors);
        if (!empty($newDescriptors)) {
            ContextCache::set($entry, $descriptors);
        }
    }

    /**
     * Filter out prototype blocks that are already cached.
     * @param array $descriptors the cached block descriptors
     * @param array $blocks the blocks to filter
     */
    public static function excludeCachedBlocks(array $descriptors, array $blocks): array
    {
        $result = $blocks;
        foreach ($descriptors as $descriptor) {
            $result = array_filter($result, function($block) use ($descriptor) {
                return $block->settings->fieldHandle !== $descriptor->fieldHandle;
            });
        }
        return $result;
    }
}
