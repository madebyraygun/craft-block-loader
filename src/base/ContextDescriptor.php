<?php

namespace madebyraygun\blockloader\base;

class ContextDescriptor
{
    public string $id;
    public int $order;
    public bool $cacheable;
    public string $fieldHandle;
    public string $handle;
    public array $context;

    public function __construct(
        string $id,
        string $fieldHandle,
        string $handle,
        int $order,
        bool $cacheable,
        array $context
    )
    {
        $this->id = $id;
        $this->fieldHandle = $fieldHandle;
        $this->order = $order;
        $this->handle = $handle;
        $this->cacheable = $cacheable;
        $this->context = $context;
    }
}
