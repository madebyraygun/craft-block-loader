<?php

namespace madebyraygun\blockloader\base;

use craft\elements\Entry;
use Illuminate\Support\Collection;

class ContextQuery
{
    public Entry $entry;
    public string $fieldHandle;
    public array $eagerFields;

    public function __construct(Entry $entry, string $fieldHandle)
    {
        $this->entry = $entry;
        $this->fieldHandle = $fieldHandle;
        $this->eagerFields = $this->mapEagerFields();
    }

    public function getFieldValue(): mixed {
        return $this->entry->getFieldValue($this->fieldHandle);
    }

    public function getFieldClass(): string {
        return get_class($this->getFieldValue());
    }

    public function queryDescriptors(): Collection
    {
        switch ($this->getFieldClass()) {
            case 'craft\ckeditor\data\FieldData':
                return self::getDescriptorsFromCkeditor();
            case 'craft\elements\db\EntryQuery':
                return self::getDescriptorsFromEntryQuery();
            default:
                return collect([]);
        }
    }

    private function getDescriptorsFromCkeditor(): Collection {
        $field = $this->getFieldValue();
        $chunks = $field->getChunks(false);
        $wrappedChunks = $chunks->map(fn($chunk, int $idx) => [
            'order' => $idx,
            'data' => $chunk,
            'descriptor' => null
        ]);
        $entryChunks = self::transformEntryChunks($wrappedChunks);
        $markupChunks = self::transformMarkupChunks($wrappedChunks);
        return collect($entryChunks->merge($markupChunks))
            ->pluck('descriptor')
            ->filter()
            ->sort(fn($a, $b) => $a->order <=> $b->order)
            ->values();
    }

    private function transformEntryChunks(Collection $chunks): Collection
    {
        $entryChunks = $chunks->filter(fn($chunk) => $chunk['data']->getType() === 'entry');
        $entryIds = $entryChunks->map(fn($chunk) => $chunk['data']->entryId);
        $eagerFields = $this->eagerFields;
        $entries = collect(Entry::find()
            ->id($entryIds)
            ->with($eagerFields)
            ->siteId($this->entry->siteId)
            ->all()
        );
        return $entryChunks->transform(function($chunk) use ($entries) {
            $entry = $entries->first(fn($entry) => $entry->id === $chunk['data']->entryId);
            if ($entry) {
                $contextBlock = BlocksFactory::createFromEntry($entry);
                if (!empty($cls)) {
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

    private function transformMarkupChunks(Collection $chunks): Collection
    {
        return $chunks
        ->filter(fn($chunk) => $chunk['data']->getType() === 'markup')
        ->transform(function($chunk) {
            // generate a default context for the markup
            $context = [ 'content' => $chunk['data']->getHtml() ];
            // try find if there is class to handle this field
            $contextBlock = BlocksFactory::create('markup');
            if ($contextBlock) {
                $contextBlock->setEntry($this->entry);
                $context = $contextBlock->getMarkupContext($chunk['data']);
            }
            $descriptor = new ContextDescriptor('markup', $chunk['order'], true, $context);
            $chunk['descriptor'] = $descriptor;
            return $chunk;
        });
    }

    private function getDescriptorsFromEntryQuery(): Collection
    {
        $eagerFields = $this->eagerFields;
        $field = $this->getFieldValue();
        $entries = $field
            ->with($eagerFields)
            ->siteId($this->entry->siteId)
            ->all();

        return collect($entries)->transform(function($entry) {
            $contextBlock = BlocksFactory::createFromEntry($entry);
            if (!empty($contextBlock)) {
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

    private function getEagerFields(ContextBlockSettings $settings)
    {
        $fields = [];
        $fieldHandle = $settings->fieldHandle;
        foreach ($settings->eagerFields as $field) {
            $name = is_array($field) ? $field[0] ?? '' : $field;
            $prefixedField = $this->fieldHandle . '.' . $fieldHandle . ':' . $name;
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
        return BlocksFactory::getContextSettings()
            ->pluck('settings')
            ->map(fn(ContextBlockSettings $s) => $this->getEagerFields($s))
            ->flatten()
            ->unique()
            ->toArray();
    }
}
