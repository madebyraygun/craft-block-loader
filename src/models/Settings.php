<?php

namespace madebyraygun\blockloader\models;

use craft\base\Model;

class Settings extends Model
{
    public string $blocksNamespace = '';

    public bool $scanNewClasses = false;

    public bool $enableCaching = false;

    // set defaults
    public function init(): void
    {
        parent::init();
        $this->blocksNamespace = 'modules/blocks';
        $this->scanNewClasses = false;
        $this->enableCaching = true;
    }


    public function rules(): array
    {
        return [
            ['blocksNamespace', 'required'],
            ['blocksNamespace', 'string'],
            ['scanNewClasses', 'boolean'],
            ['enableCaching', 'boolean'],
        ];
    }
}
