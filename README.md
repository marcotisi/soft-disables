SoftEnable
======
SoftEnable is a Laravel package which lets you easily manage enabled and disabled model.

## Disclaimer

This package is a clone of the well known SoftDeletes trait. Tests and architecture have been heavily inspired by this great trait.

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

You should also add the `enabled` column to your table. You can do it in your migration using the Laravel schema builder:

```php
<?php

Schema::table('posts', function ($table) {
    $table->bool('enabled')->default(1);
});
```

Don't forget to add a default value, as trait won't do it for you!
You can also change the column name, defining a constant in your model:

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
