# Magento 2 - Attribute Migration

`AttributeMigration` is an open source Magento 2 Package made for migrating Attributes to different types.

## Features

* Migrate Boolean to Select
* Migrate Text to Select

More Migrations are coming - there is also a plan to integrate dynamic migration by detecting source and target Attribute types.

## Installation

You can safely install this module by Composer:

```
composer require danielmaier/attributemigration
php bin/magento setup:upgrade
```

## Usage

You are able to execute each Migration by CLI. Feel free to run the migrations by Cronjobs and therefore pass the Attribute Codes as Arguments, or run in interactive mode and ignore the Arguments. Migrations can be called in both ways:

*Interactive Mode*

```
php bin/magento attribute-migration:text-to-select
```

You will be asked to enter an old Attribute Code as source and a new one as target.

*Automated Mode*

```
php bin/magento attribute-migration:text-to-select --old_attribute=<CODE> --new_attribute=<CODE>
````

When you are not sure how the Arguments are called, you can check them (for every Magento 2 Command) by running:

```
php bin/magento help attribute-migration:text-to-select
````

## Contributing

Contributions are welcome from everyone if you want to add another feature or discover a bug.