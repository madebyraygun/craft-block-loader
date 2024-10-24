<?php

namespace madebyraygun\blockloader\services;

use Craft;
use craft\helpers\FileHelper;
use yii\caching\FileCache;

class BlocksFileCache extends FileCache
{
    public function init(): void
    {
        parent::init();
        $this->cachePath = Craft::getAlias('@runtime/cache_blocks');
    }

    public function getRelativePath(): string
    {
        return FileHelper::relativePath($this->cachePath, Craft::getAlias('@root'));
    }
}
