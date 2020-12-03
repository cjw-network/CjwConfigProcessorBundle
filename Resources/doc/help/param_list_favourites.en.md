# Help Pages: Favourites

This display is supposed to provide an overview for the `Parameterlist: Favourites` - view.

## Purpose of the view

This view is mainly responsible for providing a customizable display of parameters. This is so
that a user is able to limit the amount and type of parameters being displayed to them.

Via this view, it is possible to provide a list of parameters most important to the user and
to employ that same view across multiple installations.

## Ways to set favourites

There are two main ways to mark parameters as favourites and have them displayed in the dedicated view
as such.

### 1. Provide a list of keys in the backend

In order to provide parameters to be marked as favourites via yaml configuration, it is important to follow
a certain scheme within the yaml file.

**The overall configuration might look like this:**

```yaml
cjw_config_processor:
  favourite_parameters:
    allow: true
    scan_parameters: true
    parameters:
      - "examples"
```

#### Examining the configuration

Let's take a look at the given options and determine the meaning behind those lines:

**Configuration Key:**

```yaml
cjw_config_processor:
```

This line is simply the key used to provide the configuration to the bundle itself.

**Specific Config-Section:**

```yaml
  favourite_parameters:
```

This line is the key which signals to the bundle that the following configuration will be aimed towards the favourites-
functionality.

**Options:**

```
    allow: true
```

This is arguably the most important option. This option determines whether the favourites-functionality will be active
in the bundle in the first place. Providing the value `false` to that option will turn the entire feature off and will leave
the favourites view entirely empty. Conversely, providing `true` will enable the feature.

```yaml
    scan_parameters: true
```

When `scan_parameters` is turned on, the entirety of the parameters marked as favourites will be searched for any possible
site access dependency. If one is found, the parameter will then be marked as favourite for all site accesses and should
a site access context be set in the favourites view, all the parameters will be displayed with their site access specific value.

> **If a parameter is not site access dependent, then its value will be displayed unchanged even in a site access context!**

```yaml
    parameters:
```

The `parameters` key
