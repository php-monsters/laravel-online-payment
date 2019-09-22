Larapay Version 6+ required PHP 7+

1. Installing via composer

```bash
composer require tartan/laravel-online-payment
```
2. Add package service provider to your app service providers:

```php
Tartan\Larapay\LarapayServiceProvider::class,
```
3. Add package alias to your app aliases:

```php
'Larapay' => Tartan\Larapay\Facades\Larapay::class,
```
4. Publish package assets and configs

```bash
php artisan vendor:publish --provider="Tartan\Larapay\LarapayServiceProvider"
```

5. Run migration
```bash
php artisan migrate
```

6. Run install command
```bash
php artisan larapay:install 
```