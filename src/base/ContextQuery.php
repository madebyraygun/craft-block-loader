<?php

namespace madebyraygun\blockloader\base;

use craft\elements\ElementCollection;
use craft\elements\Entry;

class ContextQuery
{
    /**
     * A collection of matrix fields after eager loading.
     * The key is the matrix handle. All matrix blocks are eager loaded.
     * @var array<string, ElementCollection>
     */
    private array $matrixFields;

    public function __construct(Entry $entry, array $blocks)
    {
        $this->matrixFields = $this->fetch($entry, $blocks);
    }

    public function findMatrixBlocks(ContextBlock $block): array
    {
        $matrixHandle = $block->settings->matrixHandle;
        $blockHandle = $block->settings->blockHandle;
        $collection = $this->matrixFields[$matrixHandle] ?? [];
        $blocks = array_filter($collection, function($block) use ($blockHandle) {
            return $block->type->handle === $blockHandle;
        });
        return array_values($blocks);
    }

    private static function fetch(Entry $entry, array $contextBlocks): array
    {
        $blocksSettings = static::mapBlocksSettings($contextBlocks);
        $eagerFields = static::mapEagerFields($blocksSettings);
        $matrixHandles = static::mapMatrixHandles($blocksSettings);
        $matrixFields = static::queryMatrixFields($entry, $matrixHandles, $eagerFields);
        return $matrixFields;
    }

    private static function queryMatrixFields(Entry $entry, array $matrixHandles, array $eagerFields)
    {
        $result = [];
        foreach ($matrixHandles as $type) {
            if (isset($entry->$type)) {
                $blocks = $entry->$type->with($eagerFields)->all();
                $result[$type] = $blocks;
            }
        }
        return $result;
    }

    private static function getEagerFields(ContextBlockSettings $settings)
    {
        $fields = [];
        $matrixHandle = $settings->matrixHandle;
        $blockHandle = $settings->blockHandle;
        foreach ($settings->eagerFields as $field) {
            $name = is_array($field) ? $field[0] ?? '' : $field;
            $prefixedField = $matrixHandle . '.' . $blockHandle . ':' . $name;
            if (is_array($field)) {
                // handle array fields with custom params
                $prefixedField = [$prefixedField, ...array_slice($field, 1)];
            }
            $fields[] = $prefixedField;
        }
        return $fields;
    }

    private static function mapMatrixHandles(array $blocksSettings)
    {
        return array_unique(array_map(function(ContextBlockSettings $settings) {
            return $settings->matrixHandle;
        }, $blocksSettings));
    }

    private static function mapEagerFields(array $blocksSettings): array
    {
        return array_unique(array_merge(...array_map(function(ContextBlockSettings $settings) {
            return static::getEagerFields($settings);
        }, $blocksSettings)));
    }

    private static function mapBlocksSettings(array $contextBlocks): array
    {
        return array_map(function(ContextBlock $block) {
            return $block->settings;
        }, $contextBlocks);
    }
}
