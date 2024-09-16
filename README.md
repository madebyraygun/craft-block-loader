# Craft Block Loader

This is a context loader for Craft CMS matrix blocks and nested entries. Use this plugin in conjunction with the [Craft Component Library](https://github.com/madebyraygun/craft-component-library) to format your block context to work with your component library.

See a sample implementation in the [Craft Component Library Demo](https://github.com/madebyraygun/craft-component-library-demo) repository.

## Requirements

This plugin requires Craft CMS 5.0 or later, and PHP 8.2 or later.

## Installation

This plugin is not available in the Craft Plugin Store, but is published on Packagist and can be installed with Composer. 

From the terminal:

```
composer require madebyraygun/craft-block-loader
php craft plugin/install block-loader
```

## Configuration

By default, the plugin will initialize all block class definitions from the `craft/modules/blocks` directory. Each block class is paired with a matrix field handle (the default is 'blocks'), and will make the context for any matching blocks on the entry available under the `blocks` global context handle. Hook the `blocks` template hook in your entry template to populate the context.

To change these defaults, create a `block-loader.php` file in your Craft config directory to change this directory.

Sample config:
```php
return [
  'blocksPath' => dirname(__DIR__) . '/modules/blocks',
  'matrixHandle' => 'blocks',
  'globalContextHandle' => 'blocks',
  'hookName' => 'blocks'
];
```

## Usage

Each block in your matrix field will get a corresponding block class definition in your `blocks` directory. A simple example would look like this:

`AnchorBlock.php`
```php
namespace modules\blocks;

use craft\elements\Entry;
use madebyraygun\blockloader\base\ContextBlock;

class AnchorBlock extends ContextBlock
{
    public function getContext(Entry $block): array
    {
        return [
            'anchorId' => $block->anchorId,
        ];
    }
}
```

Not very exciting, but hopefully you see that this gives you the opportunity to return an _any_ custom data you want for your matrix block content.

More complex examples can extend the `ContextBlockSettings` class to change the settings on a per-block basis. Settings options include:
* `matrixHandle`: The handle of the matrix field this block will be paired with.
* `blockHandle`: The handle of the matrix block this class should be paired with. This is typically auto-generated based on the classname.
* `eagerFields`: Whether to [load fields eagerly](https://craftcms.com/docs/5.x/development/eager-loading.html) to improve performance on related or nested elements. Passed as an array of field handles like `['cards.image','cards.author']`.
* `cacheable`: Whether the results of the query should be cached (improves performance on large pages)
* `contextHandle`: What context global should the output of this class be appended to (defaults is `blocks`)

```php
namespace modules\blocks;

use craft\elements\Entry;
use madebyraygun\blockloader\base\ContextBlock;
use madebyraygun\blockloader\base\ContextBlockSettings;

class RichTextBlock extends ContextBlock
{
    protected function onInit(ContextBlockSettings $settings): void
    {
        $settings
            ->blockHandle('richTextColumns')
            ->eagerFields([
                'richTextColumns',
            ]);
    }

    public function getContext(Entry $block): array
    {
        $columnsTable = $block->richTextColumns->all();
        $columns = [];
        foreach ($columnsTable as $column) {
            $columns[] = [
                'body' => $body = ($field ? (string) $field : '');
            ];
        }
        return [
            'align' => $block->align->value ?? 'left',
            'width' => $block->width->value ?? 'default',
            'columns' => $columns,
        ];
    }
}
```

In your entry template:

```html
{% hook 'blocks' %}
{% for item in blocks|default %}
  {% include '@content-blocks/' ~ item.handle with item.context %}
{% endfor %}
```
