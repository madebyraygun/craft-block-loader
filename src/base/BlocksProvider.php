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
        $descriptors = self::queryFieldDescriptors($contextQuery);
        //static::updateCacheIfNeeded($entry, $newDescriptors, $descriptors);
        return $descriptors;
    }

    public static function queryFieldDescriptors(ContextQuery $contextQuery): Collection
    {
        switch ($contextQuery->getFieldClass()) {
            case 'craft\ckeditor\data\FieldData':
                return self::getDescriptorsFromCkeditor($contextQuery);
            case 'craft\elements\db\EntryQuery':
                return self::getDescriptorsFromEntryQuery($contextQuery);
            default:
                return collect([]);
        }
    }

    public static function getDescriptorsFromCkeditor(ContextQuery $contextQuery): Collection {
        $field = $contextQuery->getFieldValue();
        $chunks = $field->getChunks(false);
        $wrappedChunks = $chunks->map(fn($chunk, int $idx) => [
            'order' => $idx,
            'data' => $chunk,
            'descriptor' => null
        ]);
        $entryChunks = self::transformEntryChunks($wrappedChunks, $contextQuery);
        $markupChunks = self::transformMarkupChunks($wrappedChunks, $contextQuery);
        return collect($entryChunks->merge($markupChunks))
            // ->map(fn($chunk) => $chunk['descriptor'])
            ->pluck('descriptor')
            ->filter()
            ->sort(fn($a, $b) => $a->order <=> $b->order)
            ->values();
    }

    public static function transformEntryChunks(Collection $chunks, ContextQuery $contextQuery): Collection
    {
        $entryChunks = $chunks->filter(fn($chunk) => $chunk['data']->getType() === 'entry');
        $entryIds = $entryChunks->map(fn($chunk) => $chunk['data']->entryId);
        $eagerFields = $contextQuery->eagerFields;
        $entries = collect(Entry::find()
            ->id($entryIds)
            ->with($eagerFields)
            ->siteId($contextQuery->entry->siteId)
            ->all()
        );
        return $entryChunks->transform(function($chunk) use ($entries, $contextQuery) {
            $entry = $entries->first(fn($entry) => $entry->id === $chunk['data']->entryId);
            if ($entry) {
                $cls = $contextQuery->findContextBlockClass($entry->type->handle);
                if (!empty($cls)) {
                    $contextBlock = new $cls($entry);
                    $context = $contextBlock->getContext($entry);
                    $descriptor = new ContextDescriptor(
                        $contextBlock->settings->blockHandle,
                        $chunk['order'],
                        $contextBlock->settings->cacheable,
                        $context
                    );
                    $chunk['descriptor'] = $descriptor;
                }
            }
            return $chunk;
        });
    }

    public static function transformMarkupChunks(Collection $chunks, ContextQuery $contextQuery): Collection
    {
        return $chunks
        ->filter(fn($chunk) => $chunk['data']->getType() === 'markup')
        ->transform(function($chunk) use ($contextQuery) {
            // generate a default context for the markup
            $context = [ 'content' => $chunk['data']->getHtml() ];
            // try find if there is class to handle this field
            $cls = $contextQuery->findContextBlockClass('markup');
            if ($cls) {
                $contextBlock = new $cls($contextQuery->entry);
                $context = $contextBlock->getMarkupContext($chunk['data']);
            }
            $descriptor = new ContextDescriptor('markup', $chunk['order'], true, $context);
            $chunk['descriptor'] = $descriptor;
            return $chunk;
        });
    }

    public static function getDescriptorsFromEntryQuery(ContextQuery $contextQuery): Collection
    {
        $eagerFields = $contextQuery->eagerFields;
        $field = $contextQuery->getFieldValue();
        $entries = $field
            ->with($eagerFields)
            ->siteId($contextQuery->entry->siteId)
            ->all();

        return collect($entries)->transform(function($entry) use ($contextQuery) {
            $entryType = $entry->type->handle;
            $cls = $contextQuery->findContextBlockClass($entryType);
            if (!empty($cls)) {
                $contextBlock = new $cls($entry);
                $context = $contextBlock->getContext($entry);
                return new ContextDescriptor(
                    $contextBlock->settings->blockHandle,
                    $entry->sortOrder,
                    $contextBlock->settings->cacheable,
                    $context
                );
            }
        })
        ->sort(fn($a, $b) => $a->order <=> $b->order)
        ->values();
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
