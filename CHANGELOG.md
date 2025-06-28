# Changelog

## 3.1.3 - 2025-6-28

### Fixed

- Duplicate block cache entries on multi-site config [https://github.com/madebyraygun/craft-block-loader/issues/18](#18)

## 3.1.2 - 2025-2-28

### Fixed

- Clear cache for related entry owner element on entry save

## 3.1.1 - 2024-10-28

### Fixed

- Error in logic for clearing block cache data for related entries

## 3.1.0 - 2024-10-24

### Added

- Console command to clear block cache for entries related to expired entries

### Documentation

- Added examploe for CkEditor nested entries inside matrix blocks

## 3.0.0 - 2024-09-23

### Added

- **Breaking change:** Replaces context hooks with `entry.blocksFromField` method to load block content
- **Breaking change:** Replaces `onInit` method in block class definitions with optional `setSettings` method
- Adds support for CKEditor nested entries fields
- Adds utility and console command to clear block cache 
- Adds block cache config option
- Adds autoloading config option

### Improvements

- Better block class autoloading

## 2.0.0 - 2024-09-11

- Craft CMS 5 compatibility.

## 1.0.0 - 2024-08-31

- Initial release.
