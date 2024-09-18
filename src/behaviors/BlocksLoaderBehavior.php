<?php

namespace madebyraygun\blockloader\behaviors;

use Craft;
use yii\base\Behavior;
use madebyraygun\blockloader\base\BlocksProvider;
use Illuminate\Support\Collection;

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
