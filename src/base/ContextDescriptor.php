<?php

namespace madebyraygun\blockloader\base;

class ContextDescriptor
{
    public int $order;
    public bool $cacheable;
    public string $handle;
    public array $context;

    public function __construct(
        string $handle,
        int $order,
        bool $cacheable,
        array $context
    )
    {
        $this->order = $order;
        $this->handle = $handle;
        $this->cacheable = $cacheable;
        $this->context = $context;
    }
}
