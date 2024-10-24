<?php

namespace madebyraygun\blockloader\behaviors;

use Illuminate\Support\Collection;
use madebyraygun\blockloader\base\BlocksProvider;
use yii\base\Behavior;

class BlocksLoaderBehavior extends Behavior
{
    public function blocksFromField(string $fieldHandle): Collection
    {
        if (!$this->owner->getFieldValue($fieldHandle)) {
            return collect([]);
        }
        return BlocksProvider::extractBlockDescriptors($this->owner, $fieldHandle);
    }
}
