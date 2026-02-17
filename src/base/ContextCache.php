<?php

namespace madebyraygun\blockloader\base;

use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\events\ModelEvent;
use Illuminate\Support\Collection;
use madebyraygun\blockloader\Plugin;
use yii\base\Event;

class ContextCache
{
    private static $CACHE = null;

    private static function getKey(Element $element): string
    {
        return strval($element->id) . '-' . strval($element->siteId);
    }

    public static function set(Entry $entry, Collection $descriptors): void
    {
        $cacheEnabled = Plugin::$plugin->getSettings()['enableCaching'];
        if (Craft::$app->request->isPreview || !$cacheEnabled) {
            return;
        }
        $key = static::getKey($entry);
        try {
            $serialized = serialize($descriptors->toArray());
        } catch (\Exception $e) {
            Craft::warning("Block loader cache skipped for entry {$entry->id}: {$e->getMessage()}", __METHOD__);
            return;
        }
        Plugin::$plugin->cache->set($key, $serialized);
    }

    public static function get(Entry $entry): ?Collection
    {
        $cacheEnabled = Plugin::$plugin->getSettings()['enableCaching'];
        if (!Craft::$app->request->isPreview && static::$CACHE === null && $cacheEnabled) {
            $key = static::getKey($entry);
            $content = Plugin::$plugin->cache->get($key);
            if (!empty($content)) {
                static::$CACHE = collect(unserialize($content));
            }
        }
        return static::$CACHE;
    }

    public static function clear(Element $element): void
    {
        $key = static::getKey($element);
        Plugin::$plugin->cache->delete($key);
    }

    public static function filterCacheableDescriptors(Collection $descriptors): Collection
    {
        return $descriptors->filter(fn($descriptor) => $descriptor->cacheable);
    }

    public static function clearRelations(mixed $element): void
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
            if ($entry->owner && !in_array($entry->ownerId, $cleanedIds)) {
                static::clear($entry->owner);
            }
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
