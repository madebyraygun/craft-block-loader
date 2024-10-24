<?php

namespace madebyraygun\blockloader\models;

use craft\base\Model;

class ExpireCacheModel extends Model
{
    public int $entryId;
    public \DateTime $dateCleared;

    public function rules(): array
    {
        return [
            [['entryId', 'dateCleared'], 'required'],
        ];
    }
}
