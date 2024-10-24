<?php

namespace madebyraygun\blockloader\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use craft\elements\Entry;
use yii\console\ExitCode;
use madebyraygun\blockloader\base\ContextCache;
use madebyraygun\blockloader\models\ExpireCacheModel;
use madebyraygun\blockloader\records\ExpireCacheRecord;

/**
 * Expire Cache Controller
 */
class ExpireCacheController extends Controller
{
    public $defaultAction = 'all';

    /**
     * Checks for expired entries and clears the related block caches
     */
    public function actionAll(): int
    {
        $entries = Entry::find()
            ->status('expired')
            ->all();

        if (empty($entries)) {
            $this->stdout("No expired entries found", Console::FG_GREEN);
        }

        foreach ($entries as $expired) {
            $entryId = $expired->id;
            $exists = ExpireCacheRecord::find()->where(['entryId' => $entryId])->one();
            if ($exists) {
                $this->stderr('Cache already cleared for entry ID ' . $entryId . PHP_EOL, Console::FG_RED);
                continue;
            }
            ContextCache::clear($expired);
            $record = new ExpireCacheRecord();
            $model = new ExpireCacheModel(
                [
                    'entryId' => $entryId
                ]
            );
            if (!$model->validate()) {
                $this->stderr('Validation failed for entry ID ' . $entryId . PHP_EOL, Console::FG_RED);
                return ExitCode::DATAERR;
            }
            $record->entryId = $entryId;
            $record->save();
            $this->stdout("Cache cleared for {$entryId}", Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
