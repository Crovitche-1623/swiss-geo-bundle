# SwissGeoBundle

SwissGeoBundle provide you a clean way to use swiss building addresses.
If you have the following use cases, this bundle can be useful for you :
 - An autocomplete address input without incorrect address entries
 - Store addresses locally (**offline !**)
   - "*I want to locate my clients on a map offline.*"
 - Create statistics based on addresses:
   - "*In which locality do I have the majority of my customers?*"
 - Avoid Google Map services and therefore save money
 - Avoid having postal addresses that are no longer valid.
   - "*How can this customer live here? This building has been destroyed!*"
 - Get some information about addresses:
   - "*Is this building partly residential ?*"
   - "*Is this address on a street or a place?*"
   - "*Is this address already built or planned ?*"
 - ...

# Technical requirements
SwissGeoBundle requires the following:
 - MySQL 8.0 or higher (other RDBMS coming soon...) with this options activated:
   - LOAD DATA INFILE https://dev.mysql.com/doc/refman/8.0/en/load-data-local-security.html
 - PHP 8.1 or higher
 - Symfony 6.1 or higher
 - Doctrine ORM entities (Doctrine ODM is not supported)
 - Meilisearch v0.28 (Necessary for full address search (performance too low otherwise)

# Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

## Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
composer require crovitche/swiss-geo-bundle
```

Then make sure the bundle is enabled in registered bundles in 
`config/bundles.php` if your application doesn't use Symfony Flex.


# Getting started

Run the following commands and put them in a cron if you want your data to be 
updated regularly.

## 1. Configure your entity
```php
<?php
// src/Entity/Customer.php

namespace App\Entity;

use Crovitche\SwissGeoBundle\Entity\BuildingAddress;
use Doctrine\ORM\Mapping as ORM;

//...
class Customer
{
    //...
    #[ORM\ManyToOne(BuildingAddress::class)]
    #[ORM\JoinColumn("egaid", "egaid", false, true, "SET NULL")]
    private ?BuildingAddress $postalAddress = null;
}
```

## 2. Import data

Note: you can also run the following commands in a cron job so that your data is
regularly updated.

### 2.1 Localities
```console
php bin/console swiss-geo-bundle:import:localities --no-debug
```

### 2.2 Streets
```console
php bin/console swiss-geo-bundle:import:streets --no-debug
```

### 2.3 Building addresses
```console
php bin/console swiss-geo-bundle:import:building-addresses --no-debug
```

### 2.4 Generate/update documents in Meilisearch
```console
php bin/console swiss-geo-bundle:import:meilisearch:documents:generate --no-debug
```
