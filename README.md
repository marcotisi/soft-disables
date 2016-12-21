SoftEnable
======
SoftEnable is a Laravel package which lets you easily manage enabled and disabled model.
This package is heavily inspired by Laravel's SoftDeletes Trait,

## Installation

Add `marcotisi/soft-enable` as a requirement to `composer.json`:

```javascript
{
    "require": {
        "marcotisi/soft-enable": "1.*"
    }
}
```

Update your packages with `composer update` or install with `composer install`.

You can also add the package using `composer require marcotisi/soft-enable`.

------------------------------------------------------------------------------------------------------------

## Introduction

How often did you find yourself querying models excluding the disabled one?
```php
<?php

Post::where('enabled', true)->get();
```
This trait will "hide" every model which is not enabled, and gives you a bunch of useful methods.

## Getting Started

Simply use the `MarcoTisi\SoftEnable\SoftEnable` trait on the model:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use MarcoTisi\SoftEnable\SoftEnable;

class Post extends Model
{
    use SoftEnable;
}
``` 

You should also add the `enabled` column to the model's table. You can do it in your migration using the Laravel schema builder:

```php
<?php

Schema::table('posts', function ($table) {
    $table->bool('enabled')->default(1);
});
```

Don't forget to add a default value, as trait won't do it for you!

If you want to use another name for the enabled column, just define the constant `ENABLED` in your model:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use MarcoTisi\SoftEnable\SoftEnable;

class Post extends Model
{
    use SoftEnable;
    
    const ENABLED = 'is_enabled';
}
```

## Usage

You can enable or disable a model by calling `enable` or `disable`.

```php
<?php
// Enable the model
Post::find(1)->enable();

// Disable the model
Post::find(1)->disable();
```

When a model is disabled, it will be excluded from any query result.
To retrieve disabled models too, you can use the `withDisabled` method:

```php
<?php
$posts = Post::withDisabled()->get();
```

You can also use the `withDisabled()` method on a relation:

```php
<?php
$posts->comments()->withDisabled()->get();
```

To check if a retrieved model is enabled or disabled you can use the `isEnable` or `isDisable` methods.

```php
<?php
if ($posts->first()->isEnabled()) {
    // ...
}
```

```php
<?php
if ($posts->first()->isDisabled()) {
    // ...
}
```
