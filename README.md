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
By default, the plugin will initialize all block class definitions using the namespace `modules\blocks` in the `craft/modules/blocks` directory.
To change these defaults, create a `block-loader.php` file in your Craft config directory to change this directory.

Sample config:
```php
return [
  'blocksNamespace' => 'modules\blocks',
];
```

## Usage
Each class defined using the provided namespace `modules\blocks` is paired with each entry block inside a [Matrix Field](https://docs.craftcms.com/api/v5/craft-fields-matrix.html#matrix) or a [Ckeditor Field](https://github.com/craftcms/ckeditor) using their block handle. You need to extend from the `ContextBlock` class and implement the `getContext` method to return the context data for your block.

Example:
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

Not very exciting, but hopefully you see that this gives you the opportunity to return an _any_ custom data you want for your block content.

More complex examples can extend the `ContextBlockSettings` class to change the settings on a per-block basis. Settings options include:
* `fieldHandle`: The `entry` type handle inside a `matrix` or `ckeditor` fields. By default this is auto-generated based on the classname. Example: a class `RichTextBlock` will look for a `richText` handle inside your fields.
* `templateHandle`: The handle to id each block. This can be any id you want to use to identify the block in your templates.
* `eagerFields`: Whether to [load fields eagerly](https://craftcms.com/docs/5.x/development/eager-loading.html) to improve performance on related or nested elements. Passed as an array of field handles like `['cards.image','cards.author']`.
* `cacheable`: Whether the results of the query should be cached (improves performance on large pages)

```php
namespace modules\blocks;

use craft\elements\EntryBlock;
use madebyraygun\blockloader\base\ContextBlock;

class RichTextBlock extends ContextBlock
{
    public function setSettings(): void
    {
        $this->settings
            ->templateHandle('richTextColumns')
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
{% set blocks = entry.blocksFromField('contentBlocks') %}
{% for item in blocks %}
  {% include '@content-blocks/' ~ item.templateHandle with item.context %}
{% endfor %}
```

## Cache
The plugin will cache all blocks defined as `cacheable` in the context block settings. This will improve performance for blocks that don't change its context on a per-request basis. The blocks cache is cleared every time an Entry is saved, affecting only the blocks that are related to the saved entry.

If you need to clear the cache globally for all entries, you can do so from the Craft Control Panel under the `Utilities > Caches > Blocks context data` item or by running the `php craft clear-caches/block-loader` command from the terminal.


## Ckeditor Fields
You can call `blocksFromField` function on an entry indistinctly of the field type (`Matrix` or `Ckeditor`). The plugin will automatically detect the field type and return the blocks in the same format. However, for `Ckeditor` fields the `markup` is by default wrapped as a block, you can pass the context into a specific component or just render the `markup` directly.

```twig
{% set blocks = entry.blocksFromField('ckEditorFieldHandle') %}
{% for item in blocks %}
  {% if item.templateHandle == 'markup' %}
    {{ item.context.content|raw }}
  {% else %}
    {% include '@content-blocks/' ~ item.templateHandle with item.context %}
  {% endif %}
{% endfor %}
```

For more advance preprocessing on the `markup` context blocks you can create your own class extending from `ContextBlock` class and implementing the `getMarkupContext` method. For this class to be automatically called the `fieldHandle` setting must be set to `markup`. Or just name the class `MarkupBlock` and the plugin will automatically derive the template handle from the class name as `markup`.

```php
namespace modules\blocks;
use madebyraygun\blockloader\base\ContextBlock;

class MarkupBlock extends ContextBlock
{
    public function getMarkupContext(string $markup): array
    {
        // do some preprocessing on the markup before rendering
        $markup = preg_replace('/<p>/', '<p class="prose">', $markup);
        return [
            'content' => $markup,
        ];
    }
}
```
