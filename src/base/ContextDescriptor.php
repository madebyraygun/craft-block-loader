<?php

namespace madebyraygun\blockloader\base;

use craft\elements\Entry;

class ContextDescriptor
{
    public int $matrixId;
    public int $order;
    public bool $cacheable;
    public string $handle;
    public string $blockHandle;
    public array $context;

    public function __construct(ContextBlock $block, Entry $matrixEntry)
    {
        $block = clone $block;
        $context = $block->getContext($matrixEntry);
        $this->matrixId = $matrixEntry->id;
        $this->blockHandle = $matrixEntry->type->handle;
        $this->order = $matrixEntry->sortOrder;
        $this->handle = $block->settings->contextHandle;
        $this->cacheable = $block->settings->cacheable;
        $this->context = $context;
    }
}
