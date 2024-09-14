<?php

namespace madebyraygun\blockloader\base;

use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\elements\db\EntryQuery;
use Illuminate\Support\Collection;
use madebyraygun\blockloader\base\ContextDescriptor;
use madebyraygun\blockloader\base\ContextBlock;

class ContextQuery
{
    public Entry $entry;
    public string $fieldHandle;
    public array $blockClasses;
    public array $eagerFields;
    private Collection $settingsCollection;

    public function __construct(Entry $entry, string $fieldHandle, array $blockClasses)
    {
        $this->entry = $entry;
        $this->fieldHandle = $fieldHandle;
        $this->blockClasses = $blockClasses;
        $this->settingsCollection = $this->mapBlocksSettings($blockClasses);
        $this->eagerFields = $this->mapEagerFields();
    }

    public function getFieldValue(): mixed {
        return $this->entry->getFieldValue($this->fieldHandle);
    }

    public function getFieldClass(): string {
        return get_class($this->getFieldValue());
    }

    public function findContextBlockClass(string $handle): string|null {
        $settings = $this->settingsCollection->firstWhere('settings.blockHandle', $handle);
        if (!$settings) {
            return null;
        }
        return $settings['class'];
        // return new $blockClass($this->entry);
    }

    private function mapBlocksSettings(array $blockClasses): Collection {
        $result = collect([]);
        foreach ($blockClasses as $blockClass) {
            if (is_subclass_of($blockClass, ContextBlock::class)) {
                $ctxBlock = new $blockClass;
                $result->push([
                    'class' => $blockClass,
                    'settings' => $ctxBlock->settings
                ]);
            }
        }
        return $result;
    }

    private function getEagerFields(ContextBlockSettings $settings)
    {
        $fields = [];
        // $matrixHandle = $settings->matrixHandle;
        $blockHandle = $settings->blockHandle;
        foreach ($settings->eagerFields as $field) {
            $name = is_array($field) ? $field[0] ?? '' : $field;
            $prefixedField = $this->fieldHandle . '.' . $blockHandle . ':' . $name;
            if (is_array($field)) {
                // handle array fields with custom params
                $prefixedField = [$prefixedField, ...array_slice($field, 1)];
            }
            $fields[] = $prefixedField;
        }
        return $fields;
    }

    private function mapEagerFields(): array
    {
        return $this->settingsCollection
            ->pluck('settings')
            ->map(fn(ContextBlockSettings $s) => $this->getEagerFields($s))
            ->flatten()
            ->unique()
            ->toArray();
    }

    // public function extractBlocks(ContextBlock $block): array
    // {
    //     $matrixHandle = $block->settings->matrixHandle;
    //     $blockHandle = $block->settings->blockHandle;
    //     $collection = $this->matrixFields[$matrixHandle] ?? [];
    //     $blocks = array_filter($collection, function($block) use ($blockHandle) {
    //         return $block->type->handle === $blockHandle;
    //     });
    //     return array_values($blocks);
    // }


    // public static function queryFieldDescriptors(mixed $field, array $blockClasses): array
    // {
    //     $fieldType = get_class($field);
    //     $blocksSettings = self::mapBlocksSettings($blockClasses);
    //     switch ($fieldType) {
    //         case 'craft\ckeditor\data\FieldData':
    //             return self::getDescriptorsFromCkeditor($field, $blocksSettings);
    //         case 'craft\elements\db\EntryQuery':
    //             return self::getDescriptorsFromEntryQuery($field, $blocksSettings);
    //         default:
    //             return [];
    //     }
    // }


    // public static function getDescriptorsFromCkeditor(mixed $field, array $blocksSettings): array {
    //     $descriptors = [];
    //     $items = $field->getChunks(false)->all();
    //     foreach ($items as $item) {
    //         if ($item instanceof Entry) {
    //             $descriptors[] = new ContextDescriptor($item, $blocksSettings);
    //         }
    //     }
    //     return $descriptors;
    // }

    // public static function getDescriptorsFromEntryQuery(EntryQuery $entryQuery, array $blocksSettings): array
    // {
    //     $eagerFields = self::mapEagerFields($blocksSettings);
    //     $entries = $entryQuery->with($eagerFields)->all();
    //     $descriptors = $entries->map(fn(Entry $entry) => (

    //         new ContextDescriptor($entry->id,
    //     ));
    //     return $entries;
    // }

    // public static function mapBlocksSettings(array $blockClasses): array {
    //     $result = [];
    //     foreach ($blockClasses as $blockClass) {
    //         if (is_subclass_of($blockClass, ContextBlock::class)) {
    //             $ctxBlock = new $blockClass;
    //             $result[] = $ctxBlock->settings;
    //         }
    //     }
    //     return $result;
    // }

    // private static function getEagerFields(ContextBlockSettings $settings)
    // {
    //     $fields = [];
    //     $matrixHandle = $settings->matrixHandle;
    //     $blockHandle = $settings->blockHandle;
    //     foreach ($settings->eagerFields as $field) {
    //         $name = is_array($field) ? $field[0] ?? '' : $field;
    //         $prefixedField = $matrixHandle . '.' . $blockHandle . ':' . $name;
    //         if (is_array($field)) {
    //             // handle array fields with custom params
    //             $prefixedField = [$prefixedField, ...array_slice($field, 1)];
    //         }
    //         $fields[] = $prefixedField;
    //     }
    //     return $fields;
    // }

    // private static function mapMatrixHandles(array $blocksSettings)
    // {
    //     return array_unique(array_map(function(ContextBlockSettings $settings) {
    //         return $settings->matrixHandle;
    //     }, $blocksSettings));
    // }

    // private static function mapEagerFields(array $blocksSettings): array
    // {
    //     return array_unique(array_merge(...array_map(function(ContextBlockSettings $settings) {
    //         return static::getEagerFields($settings);
    //     }, $blocksSettings)));
    // }

    // private static function mapBlocksSettings(array $contextBlocks): array
    // {
    //     return array_map(function(ContextBlock $block) {
    //         return $block->settings;
    //     }, $contextBlocks);
    // }
}
