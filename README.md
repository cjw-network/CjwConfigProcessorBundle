# CJW's Config-Processor Bundle

# Goal

This Bundle has been created to serve the function of parsing / processing the existing
parameter-/options array that exists within a standard symfony and especially
eZPlatform app. Its purpose is to take the unsorted array and split it up into pieces based
on the target of the options and specifically the site-access it is targeting in order to
allow a smoother and more comfortable developer experience.

# Authors

- **CJW-Network**
- **Frederic Bauer**
  <br/>
  <br/>

# Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

## Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require cjw-network/cjw-config-processor
```

## Applications that don't use Symfony Flex

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require cjw-network/cjw-config-processor
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    // <vendor>\<bundle-name>\<bundle-long-name>::class => ['all' => true],
    CJW\CJWConfigProcessorBundle\CJWConfigProcessorBundle::class => ['all' => true],
];
```

## Routing

Afterwards, you need to add a yaml file to your `config/routes` directory.
This file can be named (for example) `cjw_config_processing.yaml` and must contain
the following content:

```yaml
cjw_config_processor_bundle:
  resource: "@CJWConfigProcessorBundle/Resources/config/routing.yaml"
```

## Additional Bundle Config

You can also customize a few bundle settings and adapt the bundle to your requirements.
To do that, you need to create a yaml file in the `config/packages` directory of your
installation. Name the file however you like, for example `cjw_config_processor.yaml`
and then set the following (partially) optional options:

```yaml
# example settings
cjw_config_processor:
  custom_site_access_parameters:
    allow: false
    scan_parameters: false
    parameters:
      - "parameter1"
      - "parameter2.with.more.parts"
      - "parameter3.parts"

  favourite_parameters:
    allow: true
    scan_parameters: true
    parameters:
      - "parameter1.very.specific"
      - "parameter2.broader"
      - "parameter3"
      - "parameter2.others"
```

