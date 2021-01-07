# Help Pages: Custom Bundle Configuration

This file is supposed to deliver a detailed look at how to configure the bundle via
yaml settings.

-----
## Purpose of the config

As with many bundles for the Symfony framework, it is possible to configure a few
behaviours of the bundle to your liking via specific configuration made in a yaml file
in the config directory of your installation.

-----
## Configuration Overview

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

  env_variables:
    allow: true
```

The above settings are the complete set of possible settings for the bundle. It is
possible to set each of them to your liking and in the following paragraphs, a more
detailed examination is provided.

```yaml
cjw_config_processor:
```

This first line however does not require more in depth discussion as it simply serves as
the identifier for Symfony to signal that the following lines should be given to the bundle.

-----
### The "Custom Site Access Parameters"

The purpose of the custom site access parameters is to allow the potential user of the bundle
to give a list of parameters that are not naturally listed in the site access overview and display
them in addition to the specific site access parameters.

**Using the feature might break some convenience functions of the site access views
(such as location retrieval).**

* custom site access parameters:
    ```yaml
      custom_site_access_parameters:
    ```

  This line simply signals that the configuration following it, will concern the custom
  site access parameters feature. The rest of the following configuration must be written
  beneath this upper key.

* allow:
    ```yaml
        allow: false
    ```

  This line signals whether the custom parameters feature is supposed to be active
  or not. The `allow` option can either be set to `true` or `false`. Setting it to
  `true` will enable the feature, and the succeeding configuration will then be applied.

* scan parameters:
    ```yaml
        scan_parameters: false
    ```

  This option describes whether the parameters that have been given to be added to the
  site access specific view will be checked and edited for any site access dependencies.

  This means, that the parameters will be checked against the site access list of your application
  and if a key in the hierarchy is found that matches a site access, it will be presented and
  built like the other site access parameters (the site access key will be removed, and the
  subsequent keys up to the value will be fused into one key).

  The option can either be set to `true` or `false`.

    * `true` will enable the scanning. **It will likely lead to false positives and may disrupt
      the normal display of the parameters.**

    * `false` will disable the scanning, which leads to a normal and unfiltered display
      of the parameters.

* parameters:
    ```yaml
     parameters:
      - "parameter1"
      - "parameter2.with.more.parts"
      - "parameter3.parts"
    ```

  This option marks the actual heart of the feature: Everything listed under this key
  will be treated like a custom parameter which will be added to the site access view.

  There are different levels of depth when writing down a key:

    * Adding an entire parameter tree:

  When wanting to add an entire tree of keys to the site access view (such as `ezdesign`
  for example), the user can write down the path to the highest level of the key tree
  and if that path exists, everything under the given path / key segments (including the
  given segments itself) will be added to the view.

    ```yaml
      parameters:
       - "ezdesign"
      # This is the top most level of the parameter tree, as a result the entire "ezdesign" tree will be added
    ```

    * Adding a subtree:

  Similarly to how an entire hierarchy of keys can be added by writing down the top most key
  of the desired tree, one can also limit the hierarchy of keys to a subtree by giving the
  path / segments of keys down to that specific level of the tree.

    ```yaml
      parameters:
        - "example.lowerLevel"
      # This selection will add everything under the "lowerLevel" key within the "example" key
    ```

    * Adding a specific parameter:

  If the user knows the path of a parameter down to its value, this path can be given to
  add only the specific parameter to the site access view.

    ```yaml
      parameters:
        - "example.lowerLevel.lastKey"
      # This selection (if it goes down to the last level before the value) will only add that specific parameter.
    ```

  **If the user adds paths or keys that don't exist in the configuration of your Symfony
  application, then they will be ignored. So make sure to provide valid and correct paths.**

-----
### "Favourite Parameters"

The purpose of this option is to allow the user of the bundle to **filter the list of parameters**
for important or often used parameters and display them in a dedicated view with available site access
context.

**Using this feature allows to provide a structured, pre-made or dynamically created list of parameters.**

* favourite parameters:
    ```yaml
        favourite_parameters:
    ```

  This line simply signals that the configuration following it, will concern the favourite
  parameters feature. The rest of the following configuration must be written
  beneath this upper key.

* allow:
    ```yaml
        allow: true
    ```

  This option is used to turn the feature on or off. As a result, it can be set to either
  `true` or `false`.

    * `True` - Setting the option to true will enable the feature. **The dedicated view, and the option
      to add or remove favourites are only available, when the feature is turned on!**

    * `False` - Setting the option to that will disable the feature entirely, and the
      dedicated favourite view will remain empty.

* scan parameters:
    ```yaml
      scan_parameters: true
    ```

  This option, similar to the option of the same name for the custom parameters, will
  determine, whether the favourites will be scanned for any site access dependencies and
  filtered as such.

    * `True` - Setting the option to true will enable the scanning.

    * `False` - Setting the option to false will disable the scanning.

  **Important note on the feature:**

  In contrast to its equivalent option in the custom parameters, for the favourites,
  turning it on also enables site access specific viewings of the parameters and will
  make sure that adding a site-access-specific parameter will also add all of its equivalents
  from other site accesses!

* parameters:
    ```yaml
      parameters:
        - "parameter1"
        - "parameter2.with.more.parts"
        - "parameter3.parts"
    ```

  This option marks one of the hearts of the entire feature: Similar to how the
  option works with the custom site access parameters, giving parameter keys here
  will add the parameters to your favourites and then to the dedicated favourites view.

  **If the `scan_parameters` option is enabled then any site access dependent parameters
  given under the `parameters` option will be added in all of its site access variants!**

  There are different levels of depth when writing down a key:

    * Adding an entire parameter tree:

  When wanting to add an entire tree of keys to the favourites view (such as `ezdesign`
  for example), the user can write down the path to the highest level of the key tree
  and if that path exists, everything under the given path / key segments (including the
  given segments itself) will be added to the view.

    ```yaml
      parameters:
       - "ezdesign"
      # This is the top most level of the parameter tree, as a result the entire "ezdesign" tree will be added
    ```

    * Adding a subtree:

  Similarly to how an entire hierarchy of keys can be added by writing down the top most key
  of the desired tree, one can also limit the hierarchy of keys to a subtree by giving the
  path / segments of keys down to that specific level of the tree.

    ```yaml
      parameters:
        - "example.lowerLevel"
      # This selection will add everything under the "lowerLevel" key within the "example" key
    ```

    * Adding a specific parameter:

  If the user knows the path of a parameter down to its value, this path can be given to
  add only the specific parameter to the site access view.

    ```yaml
      parameters:
        - "example.lowerLevel.lastKey"
      # This selection (if it goes down to the last level before the value) will only add that specific parameter.
    ```

  **If the user adds paths or keys that don't exist in the configuration of your Symfony
  application, then they will be ignored. So make sure to provide valid and correct paths.**

-----
### "Environmental Variables"

The purpose of this view is to display the environmental parameters, which are typically "hidden" in
a standard Symfony in a more convenient way, since they often feature configuration for the application.

Since this configuration might contain sensitive information, or the user simply does not require it
to be visible, the bundle offers a way to disable that information.

* env variables:

    ```yaml
      env_variables:
    ```

  This line simply signals that the configuration following it, will concern the environment
  variables display feature of the bundle. The rest of the following configuration must follow
  that upper key.

* allow:

    ```yaml
        allow: true
    ```

  This option is used to turn the feature on or off. As a result, it can be set to either `true`
  or `false`.

    * `True` - Setting the option to true will enable the feature, and the variables will be visible #
      in their dedicated view. This is the standard value of the option.
    * `False` - Setting the option to false will turn the feature off and although the dedicated view
      will still be accessible, it will remain empty.
