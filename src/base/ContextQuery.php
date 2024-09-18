<?php

namespace madebyraygun\blockloader\base;

use craft\elements\Entry;
use craft\elements\db\EntryQuery;
use Illuminate\Support\Collection;

class ContextQuery
{
    public Entry $entry;
    public string $fieldHandle;
    public array $eagerFields;
    public Collection $cachedDescriptors;

    public function __construct(Entry $entry, string $fieldHandle)
    {
        $this->entry = $entry;
        $this->fieldHandle = $fieldHandle;
        $this->eagerFields = $this->mapEagerFields();
        $this->cachedDescriptors = collect([]);
    }

    public function getFieldValue(): mixed {
        return $this->entry->getFieldValue($this->fieldHandle);
    }

    public function getFieldClass(): string {
        return get_class($this->getFieldValue());
    }

    public function setCachedDescriptors(Collection $descriptors): void {
        //TODO: if settings in the controllers have changed, we may need to reset the cache
        $this->cachedDescriptors = $descriptors;
    }

    public function queryDescriptors(): Collection
    {
        $result = [];
        switch ($this->getFieldClass()) {
            case 'craft\ckeditor\data\FieldData':
                $result = self::getDescriptorsFromCkeditor();
                break;
            case 'craft\elements\db\EntryQuery':
                $result = self::getDescriptorsFromEntryQuery();
                break;
            default:
                $result =  collect([]);
        }
        $newCache = $this->cachedDescriptors->filter(fn($d) => !$result->contains('id', $d->id));
        $result = $result->merge($newCache);
        return $result
            ->sort(fn($a, $b) => $a->order <=> $b->order)
            ->values();
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
        return $entryChunks->merge($markupChunks)->filter();
    }

    private function transformEntryChunks(Collection $chunks): Collection
    {
        $entryChunks = $chunks->filter(fn($chunk) => $chunk['data']->getType() === 'entry');
        $entryIds = $entryChunks->pluck('data.entryId')->toArray();
        $entries = $this->queryEntries(Entry::find(), $entryIds);
        $descriptors = $entries->map(function($entry) use ($entryChunks) {
            $contextBlock = BlocksFactory::createFromEntry($entry);
            $chunk = $entryChunks->first(fn($chunk) => $chunk['data']->entryId === $entry->id);
            if (!empty($contextBlock)) {
                $context = $contextBlock->getContext($entry);
                return new ContextDescriptor(
                    $entry->id,
                    $this->fieldHandle,
                    $contextBlock->settings->templateHandle,
                    $chunk['order'],
                    $contextBlock->settings->cacheable,
                    $context
                );
            }
        });
        $this->cachedDescriptors = $this->cachedDescriptors->filter(fn($d) => !$descriptors->contains('id', $d->id));
        return $descriptors->merge($this->cachedDescriptors);
    }

    private function transformMarkupChunks(Collection $chunks): Collection
    {
        $chunks = $chunks->filter(fn($chunk) => $chunk['data']->getType() === 'markup');
        // remove cached chunks
        $chunks = $chunks->filter(fn($chunk) => !$this->cachedDescriptors->contains('id', 'markup:' . $chunk['order']));
        $descriptors = $chunks->map(function($chunk) {
            // generate a default context for the markup
            $context = [ 'content' => $chunk['data']->getHtml() ];
            $templateHandle = 'markup';
            $cacheable = false;
            // try find if there is class to handle this field
            $contextBlock = BlocksFactory::create('markup');
            if ($contextBlock) {
                $contextBlock->setEntry($this->entry);
                $context = $contextBlock->getMarkupContext($chunk['data']);
                $templateHandle = $contextBlock->settings->templateHandle;
                $cacheable = $contextBlock->settings->cacheable;
            }
            $id = 'markup:' . $chunk['order'];
            return new ContextDescriptor(
                $id,
                $this->fieldHandle,
                $templateHandle,
                $chunk['order'],
                $cacheable,
                $context
            );
        });
        return $descriptors;
    }

    private function getDescriptorsFromEntryQuery(): Collection
    {
        $field = $this->getFieldValue();
        $entries = $this->queryEntries($field);
        $descriptors = $entries->map(function($entry) {
            $contextBlock = BlocksFactory::createFromEntry($entry);
            if (!empty($contextBlock)) {
                $context = $contextBlock->getContext($entry);
                return new ContextDescriptor(
                    $entry->id,
                    $this->fieldHandle,
                    $contextBlock->settings->templateHandle,
                    $entry->sortOrder,
                    $contextBlock->settings->cacheable,
                    $context
                );
            }
        });
        return $descriptors;
    }

    private function queryEntries(EntryQuery $query, array $includeIds = []): Collection
    {
        $excludes = $this->cachedDescriptors->pluck('id')->toArray();
        $idParam = ['not', ...$excludes];
        if (!empty($includeIds)) {
            // switch rule to include only by Ids
            // remove from IncludeIds what is in excludes (cache)
            $idParam = array_filter($includeIds, fn($id) => !in_array(strval($id), $excludes));
            if (empty($idParam)) {
                return collect([]);
            }
        }
        return collect($query
            ->id($idParam)
            ->siteId($this->entry->siteId)
            ->with($this->eagerFields)
            ->all());
    }

    private function getEagerFields(ContextBlockSettings $settings)
    {
        $fields = [];
        $childFieldHandle = $settings->fieldHandle;
        foreach ($settings->eagerFields as $field) {
            $name = is_array($field) ? $field[0] ?? '' : $field;
            $prefixedField = $this->fieldHandle . '.' . $childFieldHandle . ':' . $name;
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
