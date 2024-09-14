<?php

namespace madebyraygun\blockloader\base;

use craft\elements\Entry;
use Illuminate\Support\Collection;
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
}
