# Help Pages: Environmental Variable Display

This file is supposed to provide an overview over the
`Environmental Parameters` - view.

## Use of the ENV parameters

In Symfony there is the option to actively employ environmental parameters in yaml
files by wrapping them in `%` and writing the name of the parameter in lower case.

Example:

```yaml
# This retrieves the environmental "CACHE_POOL" for the yaml file
test:
  cache_pool: %cache_pool%
```

The parameters cannot always be called one to one by their `ENV`-name. Sometimes their
name is shortened by removing a part of the original name. Symfony does provide proper help
in error messages, should the name not be entirely correct.

## Page Overview

Since there is not a lot of functionality on this page, and it serves a very limited purpose,
there will only be a verbal description of the page.

The page contains:
* (1) The typical header of the page, with its info texts, breadcrumbs and
  help teaser lines.

* (2) The left side menu with its buttons leading to other views.

* (3) The param list header with a heading, the searchbar and the "global open subtree"-button

* (4) The actual list of parameters

## Elements:

The parameters in this view differ from the ones in other views of the bundle frontend
in that they do not typically feature nesting values, which eliminates a lot of the typical
functionality in these views. On top of that, they do not posses a path origin in the bundle's
list, since they are not typical container parameters of Symfony, and they cannot be favoured,
since they are not like the other parameters.

1. **The View-Header**:
   The View-Header is the uppermost, changing element on the page. It contains 3
   important items (from top to bottom):
   <br/>
    1. **The path**: The topmost element contains a rough overview of where you are
       in the frontend of the bundle (here: `Admin > CJW Config Processing > Environmental Parameterlist`)

    2. **The headline**: It is the biggest element in the header, and it states what view
       or tab you are looking at in the bundle

    3. **Last update**: This element shows you when the displayed parameters have last been updated,
       meaning, if you updated the config, but the additions do not show up, it could be
       due to you looking at an outdated version of the page (refresh the page in that case)

    4. **Help Teaser**:
       This is a short introductory sentence, meant to provide a quick overview over what
       the current view is for. At the right end of the teaser, the `show help` - button
       resides, which, when clicked, opens this help file in an overlay.

2. **The Left Sidebar Menu**:
    1. **Site Access Parameters**:
       This is a button on the left sidebar menu. If clicked, the user will be taken to the
       parameter view for the site access specific parameters.

    2. **All Parameters**:
       Another button on the left sidebar menu, which, when clicked, brings the user to a view
       of all parameters of the symfony application (without site access limitations).

    3. **Favourite Parameters**:
       Is a button on the left sidebar menu, which, when clicked, takes the user to the
       view dedicated to the parameters marked as favourites by the user and is the view discussed in
       this help. (This view only contains parameters, when the feature is enabled (check the bundle config)).

    4. **Environmental Parameters**:
       Is a button, which, when clicked, takes the user to an overview of all environmental variables
       and their value, as they are known to the server. This is also the current view.

3. **List-Header**:
   The list-header may vary depending on the view, but it typically contains a headline,
   which describes the beginning of the actual parameter-list and also global utility buttons.

    1. **Searchbar**:
       The searchbar allows a user to search for specific keys (or values, depending on the current
       `searchmode`). Simply enter text into the search-field and wait a short moment, as
       there is a small delay between your input, and the search starting, and then you
       should start seeing the results of your search.

        1. Next to the search-field itself, there is a button on the far right within the bar,
           clicking the button, will switch the search mode from key search to value search and vice
           versa.
        2. Another button will appear, when input is entered into the searchbar: An `X`. Clicking
           this button will clear the search input and reset the search.
        3. `search-mode`: The search mode dictates how the input search text is being handled:
           If the key search is active (default mode, indicated by a blue outline around the searchbar when in focus),
           only keys are searched, which fit the given search text. If the value search is active
           (indicated by a green outline around the bar), only values will be searched which match the
           given search input.

    2. **Global Open Subtree Button**:
       This button does not really serve any purpose in this view at the moment.

4. **List of Parameters**:
   The Parameterlist is simply a list of parameters (in this case only environmental variables and their values).

    1. Example of a **Parameter (inline value)**:
       Similar to the parameter keys generally, this example includes the visual representation of a parameter key, but
       contrary to that, there is also a value (green colour) on the same line. Due to the key
       featuring a value right afterwards, it provides no `utility buttons` in this list.
