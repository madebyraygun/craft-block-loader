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

    public function __construct(ContextBlock $block, Entry $matrixBlock)
    {
        $block = clone $block;
        $context = $block->getContext($matrixBlock);
        $this->matrixId = $matrixBlock->contentId;
        $this->blockHandle = $matrixBlock->type->handle;
        $this->order = $matrixBlock->sortOrder;
        $this->handle = $block->settings->contextHandle;
        $this->cacheable = $block->settings->cacheable;
        $this->context = $context;
    }
}
