# FeDex Bagisto
This extension allows your customers to collect their orders from your physical store.

## Requirements
- [Bagisto](https://github.com/bagisto/bagisto)

## Installation

### Install with composer
1. Run the following command
```php
composer require hunghbm/fedex-bagisto
```
2. Open config/app.php and add **Hunghbm\FedEx\Providers\FedExServiceProvider::class**.
3. Run the following command
```php
composer dump-autoload
```
4. Go to `https://<your-site>/admin/configuration/sales/carriers`.
5. Make sure that **Marketplace FedEx** is active and press save.

### Install with package folder
1. Unzip all the files to **packages/Hunghbm/FedEx**.
2. Open config/app.php and add **Hunghbm\FedEx\Providers\FedExServiceProvider::class**.
3. Go to `https://<your-site>/admin/configuration/sales/carriers`.
4. Make sure that **Marketplace FedEx** is active and press save.

Your customers are now able to select the new shipping method.
