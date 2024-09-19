<?php

namespace madebyraygun\blockloader;

use Craft;
use Composer\Autoload\ClassLoader;
use yii\base\Event;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\DefineBehaviorsEvent;
use craft\elements\Entry;
use madebyraygun\blockloader\behaviors\BlocksLoaderBehavior;
use madebyraygun\blockloader\base\BlocksProvider;
use madebyraygun\blockloader\base\PluginLogTrait;
use madebyraygun\blockloader\models\Settings;

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

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;
        Craft::setAlias('@madebyraygun/block-loader', $this->getBasePath());

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'madebyraygyn\\blockloader\\console\\controllers';
        } else {
            $this->controllerNamespace = 'madebyraygyn\\blockloader\\controllers';
        }

        $this->registerLogger();

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->behaviors['blocksloader'] = BlocksLoaderBehavior::class;
            });

        Craft::$app->onInit(function() {
            $settings = $this->getSettings();
            $blocksNamespace = $settings['blocksNamespace'];
            $classes = $this->getClassesFromAutoload($blocksNamespace);
            BlocksProvider::init($classes);
        });
    }

    private function getClassesFromAutoload(string $namespace): array
    {
        if (empty($namespace)) {
            return [];
        }
        $spl_af = spl_autoload_functions();
        $classLoader = null;
        foreach ($spl_af as $func) {
            if (is_array($func) && $func[0] instanceof ClassLoader) {
                $classLoader = $func[0];
                break;
            }
        }
        if (empty($classLoader)) {
            return [];
        }
        $classMap = $classLoader->getClassMap();
        $arr = array_filter($classMap, function($key) use ($namespace) {
            // starts with namespace
            return strpos($key, $namespace) === 0;
        }, ARRAY_FILTER_USE_KEY);
        return array_keys($arr);
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
