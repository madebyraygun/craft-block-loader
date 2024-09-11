<?php

namespace madebyraygun\blockloader;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use madebyraygun\blockloader\base\BlocksProvider;
use madebyraygun\blockloader\models\Settings;
use madebyraygun\blockloader\base\PluginLogTrait;

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

        Craft::$app->onInit(function() {
            $settings = $this->getSettings();
            // Automatically include all block classes
            $blocksPath = $settings['blocksPath'];
            $blockFiles = glob($blocksPath . '/*.php');
            $blockClasses = [];

            foreach ($blockFiles as $file) {
                $className = $this->getClassNameFromFile($file);
                if ($className) {
                    $blockClasses[] = '\\' . $className;
                }
            }
            BlocksProvider::init($blockClasses);
        });
    }

    /**
     * Extract a fully qualfied (namespaced) classname from a php file.
     * This function assumes PSR-0 compliance.
     *
     * @param   string  $filePath   Path to the php file.
     * @return  string|false        The resulting classname
     */
    private function getClassNameFromFile($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $namespace  = null;
        $classname  = null;
        $cnMatches  = null;
        $nsMatches  = null;
        $file       = file_get_contents($filePath);

        if (preg_match_all('/\n\s*(abstract\s|final\s)*class\s+(?<name>[^\s;]+)\s*/i', $file, $cnMatches, PREG_PATTERN_ORDER)) {
            $classname  = array_pop($cnMatches['name']);

            if (preg_match_all('/namespace\s+(?<name>[^\s;]+)\s*;/i', $file, $nsMatches, PREG_PATTERN_ORDER)) {
                $namespace  = array_pop($nsMatches['name']);
            }
        }

        if (empty($classname)) {
            return false;
        }

        return "$namespace\\$classname";
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
