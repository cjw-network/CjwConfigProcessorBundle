# Help Pages: Favourites

This display is supposed to provide a detailed look at the `Parameterlist: Favourites` - view.

## Purpose of the view

This view is mainly responsible for providing a customizable display of parameters. This is so
that a user is able to limit the amount and type of parameters being displayed to them.

Via this view, it is possible to provide a list of parameters most important to the user and
to employ that same view across multiple installations.

## Ways to set favourites

There are two main ways to mark parameters as favourites and have them displayed in the dedicated view
as such, if the feature itself is turned on.

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

For information on how the config works and what it does, check [Configuration of the bundle](bundle_configuration.en.md)

If one employs this method of adding parameters to their favourites, then **this configuration
can be copied and pasted to any project that employs this bundle**, and the same list of favourites
will be available to you in the bundle frontend as well.

**This way the parameters only need to be set once and can be ported to any project.**

### 2. Mark parameters in the views of the bundle

The second option to set and remove parameters as favourites is to simply click on the
star symbol next to the name of every parameter key directly before the value of the parameter
starts.

The visual indicators:

* **A full star** signals that the parameter is already set as a favourite, clicking it
  will aim to remove the parameter as a favourite

* **An empty star** signals that the parameter is not set as a favourite, clicking it
  will aim to add the parameter as a favourite

* **An orange star** will appear after the star has been clicked and signals that the
  process of setting or removing the parameter as a favourite is ongoing

* **A green star** will appear after the process of marking or removing a parameter as favourite
  has been completed successfully.

* **Click, and the star resets its visual indication**. This will take place, when
  the process of marking or removing a parameter as favourite did not complete successfully,
  and the change has not been committed.

### Using both methods:

The parameters given via the first method will always remain in the same state. Adding
or removing parameters via the second method does not change the given parameters via method one.

The parameters of the first method are read and cached internally. From that point onward, only
the internal favourite list is employed and edited, when adding or removing favourites via method two.

**Retrieving the parameters of the first method will only take place, when no other internal
state of the favourite list is available (when the cache is empty)**.
