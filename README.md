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
 - Symfony components specified in `composer.json`
 - Doctrine ORM entities (Doctrine ODM is not supported)
 - Meilisearch v1.5 - Necessary for full address search (performance too low otherwise)
   ```yaml
   version: '3.7'
   
   services:
      meilisearch:
          hostname: meilisearch
          image: getmeili/meilisearch:v1.5
          environment:
            - MEILI_ENV=development
            # set the max payload to 200Mb instead of 100 default one
            # addresses file is around 170Mb
            - MEILI_HTTP_PAYLOAD_SIZE_LIMIT=209715200
            - MEILI_NO_ANALYTICS=true
          ports:
            - '7701:7700'
          networks:
            - network
          volumes:
            - meilisearch-data:/data.ms
          restart: unless-stopped
   ```

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

declare(strict_types=1);

namespace App\Entity;

use Crovitche\SwissGeoBundle\Entity\BuildingAddress;
use Doctrine\ORM\Mapping as ORM;

//...
#[ORM\Entity(CustomerRepository::class), ORM\Table("Customer")]
#[ORM\Index(columns: ["egaid"], name: "IX___Customer___building_address")]
class Customer
{
    //...
    #[ORM\ManyToOne(BuildingAddress::class)]
    #[ORM\JoinColumn("egaid", "egaid", false, true, "SET NULL")]
    private ?BuildingAddress $postalAddress = null;
}
```

## 2. Create a database migration.
To do this, you have to use [DoctrineMigrationBundle](https://symfony.com/bundles/DoctrineMigrationsBundle/current/index.html).

Generate a migration using [Maker bundle](https://symfony.com/bundles/SymfonyMakerBundle/current/index.html).
```console
php bin/console make:migration
```

This command will automatically generate the SQL you in a migration php file.

Check the created migration and add this piece of code if it does not exist :
```php
    public function up(Schema $schema): void
    {
        // ...
        $this->addSql(/** @lang MySQL */'
            ALTER TABLE Building_address
                ADD CONSTRAINT CK___Building_address___building_name__xor__address_number
                    CHECK ((building_name IS NOT NULL XOR address_number IS NOT NULL) OR (building_name IS NULL AND address_number IS NULL));
        ');
        // ...
    }

    public function down(Schema $schema): void
    {
        // ...
        $this->addSql(/** @lang MySQL */'
            ALTER TABLE Building_address DROP CONSTRAINT CK___Building_address___building_name__xor__address_number;
        ');
        // ...
    }

    public function isTransactional(): bool
    {
        return false;
    }
```


## 3. Execute the migration

```console
php bin/console doctrine:migrations:migrate -n
```

## 4. Import data
Note: you can also run the following commands in a cron job so that your data is
regularly updated.

### 4.1 Localities
```console
php bin/console swiss-geo-bundle:import:localities --no-debug
```

### 4.2 Streets
```console
php bin/console swiss-geo-bundle:import:streets --no-debug
```

### 4.3 Building addresses
```console
php bin/console swiss-geo-bundle:import:building-addresses --no-debug
```

### 4.4 Generate/update documents in Meilisearch
```console
php bin/console swiss-geo-bundle:import:meilisearch:documents:generate --no-debug
```
