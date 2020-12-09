# CJW's Config-Processor Bundle

# Goal

This Bundle has been created to serve the function of parsing / processing the existing
parameter-/options array that exists within a standard symfony and especially
eZ - / Ibexa - Platform app. **Similar to the eZPublish Ini settings viewer** of old, it
is supposed to take hte existing configuration and provide a visual representation that
is easy to read, understand and work with for developers. Therefore, it provides various 
functions, options and views to display site access context specific parameters, values
and much more.

# Provided Functionality

Installing the bundle (refer to `Installation` further down the page), will add a `Config Processing View` tab under the 
`Admin` tab of the  standard eZ / Ibexa Backoffice. Clicking that tab will bring you to the frontend this bundle provides 
with the following functionality (excerpt):

- **Display** of the entire configuration of your Symfony project
- **Filter** for and display parameters in a specific site access context
- **View** and compare parameters in up to two specific site access contexts at the same time
- **Highlighting** of differences within the two site access contexts
- **Synchronous** scrolling in the comparison view
- **Limit** the comparison to common or uncommon parameters of the lists
- **Search** for specific keys or values in the parameter list
- **Mark** parameters as favourites and view them in a dedicated view
- **Get** location info about the parameters (which files do they appear in and with what value)
- **Download** a file representation of the parameter lists
- **Many** more


# Authors

- [**CJW-Network**](https://www.cjw-network.com/)
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

**Since right now there is no 1.0 or above version:**

```console
$ composer require cjw-network/cjw-config-processor:dev-main
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

### Important Note:

The Symfony Kernel of your installation must not be final, since a substantial part of the
location retrieval process builds upon that kernel and extends it. That means, that the `src/Kernel.php`
class must be publicly available.

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

# COPYRIGHT

Copyright (C) 2020 CJW-Network. All rights reserved.

# LICENSE

http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
