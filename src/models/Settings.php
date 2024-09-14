<?php

namespace madebyraygun\blockloader\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public string $blocksPath = '';

    // set defaults
    public function init(): void
    {
        parent::init();
        $this->blocksPath = Craft::getAlias('@root') . '/modules/blocks';
    }


    public function rules(): array
    {
        return [
            ['blocksPath', 'required'],
            ['blocksPath', 'string']
        ];
    }
}
