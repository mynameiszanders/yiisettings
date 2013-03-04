# Settings, a [Yii](https://github.com/yiisoft/yii) Extension

A simple extension for setting and retrieving cache-able key/value pairs to use as application settings.
Each class implements the `get()`, `set()`, and `delete()` methods, and at a minimum caches these settings for subsequent requests.

## Installation

- Extract these files to the path given by the alias `application.extensions.Settings`.
- Add your chosen class as an application component
- Set your chosen options (all options available are given in the example).

## Example

```php
<?php
    // Your application configuration.
    return array(
        // ...
        'components' => array(
            // ...
            'settings' => array(
                'class' => 'application.extensions.Settings.ConfigSettings',
                'cacheComponent' => 'cache',
                'cacheId'        => 'settingsCache',
                'cacheTimeout'   => 3600,
            ),
        ),
    );
```

## Class-specific Options

### `DbSettings`

- `tableName`: The name of the table to keep the settings in.
- `dbComponent`: The application component identifier pointing to the database connection.
- `createTable`: Boolean, whether the create the settings table on-the-fly is it does not already exist.