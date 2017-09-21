# container-interop/service-provider bridge for Laravel

Import `service-provider` as defined in `container-interop` into a Laravel application.

## Usage

### Installation

Add the package in composer:

```sh
composer require thecodingmachine/laravel-universal-service-provider ^1.0
```

Add `\TheCodingMachine\Laravel\ContainerInteropBridgeServiceProvider` in your `config/app.php` file.

**config/app.php**
```php
<?php

return [
    //...
    'providers' => [
        //...
        TheCodingMachine\Laravel\ContainerInteropBridgeServiceProvider::class
    ],
    //...
];      
```

### Usage using thecodingmachine/discovery

The bridge will use thecodingmachine/discovery to automatically discover the universal service providers of your project. If the service provider you are loading publishes itself
on thecodingmachine/discovery, then you are done. The services declared in the service provider are available in the Laravel container!

### Usage using manual declaration

If the service provider you are using does not publishes itself using thecodingmachine/discovery, you will have to declare it manually in the `container-interop-service-providers` key of your `config/app.php' file.

Set the service provider fully qualified class name in the parameter `container-interop-service-providers`:

**config/app.php**
```php
<?php
use \GlideModule\GlideServiceProvider;

return [
  ...
  'container-interop-service-providers' => [
    GlideServiceProvider::class
  ]
];
```

Now, you can do : `$app->make('glide')`

## Disabling discovery

You can disable discovery using the `container-interop-service-provider-enable-discovery` setting:

**config/app.php**
```php
<?php
use \GlideModule\GlideServiceProvider;

return [
  ...
  'container-interop-service-provider-enable-discovery' => false
];
```

##Purging the cache

The Laravel service provider in this package is a **deferred provider**.

Laravel compiles and stores a list of all of the services supplied by this provider. Then, only when you attempt to resolve one of these services does Laravel load the service provider.

If you add a new service to one of the universal service providers, you will need to purge the "compiled" services. You can do this with this command line:

```php
php artisan clear-compiled
```
