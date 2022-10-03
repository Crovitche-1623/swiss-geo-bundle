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
 - MySQL 8.0 or higher (other RDBMS coming soon...)
 - PHP 8.1 or higher
 - Symfony 6.1 or higher
 - Doctrine ORM entities (Doctrine ODM is not supported)

# Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

## Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require crovitche/swiss-geo-bundle
```

Then make sure the bundle is enabled in registered bundles in 
`config/bundles.php` if your application doesn't use Symfony Flex.
