<?php

namespace madebyraygun\blockloader;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\Entry;
use craft\events\DefineBehaviorsEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\utilities\ClearCaches;
use madebyraygun\blockloader\base\BlocksProvider;
use madebyraygun\blockloader\base\PluginLogTrait;
use madebyraygun\blockloader\behaviors\BlocksLoaderBehavior;
use madebyraygun\blockloader\helpers\ClassFinder;
use madebyraygun\blockloader\models\Settings;
use madebyraygun\blockloader\services\BlocksFileCache;
use yii\base\Event;

/**
 * craft-block-loader Plugin
 *
 * @method static Plugin getInstance()
 */
class Plugin extends BasePlugin
{
    public static $plugin;

    public static $pluginHandle = 'block-loader';

    public string $schemaVersion = '1.0.0';

    use PluginLogTrait;

    public static function config(): array
    {
        return [
            'components' => [
                'cache' => BlocksFileCache::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;
        Craft::setAlias('@madebyraygun/blockloader', $this->getBasePath());

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'madebyraygun\\blockloader\\console\\controllers';
        } else {
            $this->controllerNamespace = 'madebyraygun\\blockloader\\controllers';
        }

        $this->registerLogger();

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->behaviors['blocksloader'] = BlocksLoaderBehavior::class;
            });

        Event::on(
            ClearCaches::class,
            ClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
            function(RegisterCacheOptionsEvent $event) {
                $cachePath = '`' . $this->cache->getRelativePath() . '`';
                $event->options[] = [
                    'key' => 'block-loader',
                    'label' => 'Blocks context data',
                    'info' => 'Cached data for blocks contexts in: ' . $cachePath,
                    'action' => function() {
                        $this->cache->flush();
                    },
                ];
            }
        );

        Craft::$app->onInit(function() {
            $settings = $this->getSettings();
            $blocksNamespace = $settings['blocksNamespace'];
            $scanNewClasses = $settings['scanNewClasses'];
            $classes = ClassFinder::getClasses($blocksNamespace, $scanNewClasses);
            BlocksProvider::init($classes);
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
