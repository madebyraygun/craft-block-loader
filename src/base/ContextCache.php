<?php

namespace madebyraygun\blockloader\base;

use Craft;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\events\ModelEvent;
use yii\base\Event;

class ContextCache
{
    private static $CACHE = null;

    private static function getKey(Entry $entry): string
    {
        return "page_blocks_$entry->id";
    }

    public static function set(Entry $entry, array $blockDescriptors): void
    {
        if (Craft::$app->request->isPreview) {
            return;
        }
        $key = static::getKey($entry);
        $blockDescriptors = static::filterCacheableDescriptors($blockDescriptors);
        Craft::$app->cache->set($key, serialize($blockDescriptors));
    }

    public static function get(Entry $entry): array|null
    {
        if (!Craft::$app->request->isPreview && static::$CACHE === null) {
            $key = static::getKey($entry);
            $content = Craft::$app->cache->get($key);
            if (!empty($content)) {
                static::$CACHE = unserialize($content);
            }
        }
        return static::$CACHE ?? [];
    }

    public static function clear(Entry $entry): void
    {
        $key = static::getKey($entry);
        Craft::$app->cache->delete($key);
    }

    public static function filterCacheableDescriptors(array $descriptors): array
    {
        return array_filter($descriptors, function(ContextDescriptor $descriptor) {
            return $descriptor->cacheable;
        });
    }

    private static function clearRelations(mixed $element): void
    {
        $entries = Entry::find()
            ->relatedTo($element)
            ->all();

        $cleanedIds = [];

        foreach ($entries as $entry) {
            // skip if entry was already cleaned
            if (in_array($entry->id, $cleanedIds)) {
                continue;
            }
            $cleanedIds[] = $entry->id;
            static::clear($entry);
        }
    }

    public static function attachEventHandlers(): void
    {
        // Handle Asset saves
        Event::on(Asset::class, Asset::EVENT_AFTER_SAVE, function(ModelEvent $event) {
            $asset = $event->sender;
            static::clearRelations($asset);
        });

        // Handle Entry saves
        Event::on(Entry::class, Entry::EVENT_AFTER_SAVE, function(ModelEvent $event) {
            $entry = $event->sender;
            static::clear($entry);
            static::clearRelations($entry);
        });
    }
}
