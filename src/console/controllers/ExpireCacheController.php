<?php

namespace madebyraygun\blockloader\console\controllers;

use craft\console\Controller;
use craft\elements\Entry;
use craft\helpers\Console;
use madebyraygun\blockloader\base\ContextCache;
use madebyraygun\blockloader\models\ExpireCacheModel;
use madebyraygun\blockloader\records\ExpireCacheRecord;
use yii\console\ExitCode;

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
        $lastRun = ExpireCacheRecord::find()->orderBy('dateCleared DESC')->limit(1)->one();
        $lastRunDate = $lastRun ? strtotime($lastRun->dateCleared) : null;
        $entries = Entry::find()
            ->status('expired');

        if ($lastRunDate) {
            $entries->expiryDate('>= ' . date("Y-m-d H:i:s", $lastRunDate));
        }
        $entries = $entries->all();
        if (empty($entries)) {
            $this->stdout('No new expired entries found' . PHP_EOL, Console::FG_GREEN);
        }

        foreach ($entries as $expired) {
            $entryId = $expired->id;
            $exists = ExpireCacheRecord::find()->where(['entryId' => $entryId])->one();
            ContextCache::clear($expired);
            $model = new ExpireCacheModel(
                [
                    'entryId' => $entryId,
                    'dateCleared' => new \DateTime(),
                ]
            );
            if (!$model->validate()) {
                $this->stderr('Validation failed for entry ID ' . $entryId . PHP_EOL, Console::FG_RED);
                return ExitCode::DATAERR;
            }
            $record = $exists ?: new ExpireCacheRecord();
            $record->entryId = $entryId;
            $record->dateCleared = $model->dateCleared;
            $record->save();
            $this->stdout('Cache cleared for ' . $entryId . PHP_EOL, Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
