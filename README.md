# CJW's Config-Processor Bundle

# Goal

This Bundle has been created to serve the function of parsing / processing the existing
parameter-/options array that exists within a standard symfony and especially
eZ - / Ibexa - Platform app. **Similar to the eZPublish Ini settings viewer** of old, it
is supposed to take the existing configuration and provide a visual representation that
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
- **Automatic Highlighting** of differences within the two site access contexts
- **Synchronous** scrolling mode in the comparison view for improved readability
- **Limit** the comparison to common or uncommon parameters of the lists
- **Search** for specific keys or values in the parameter list
- **Mark** parameters as favourites and view them in a dedicated view
- **Get** location info about the parameters (which files do they appear in and with what value)
- **Download** a file representation of the parameter lists
- **And** more

# Help And More Info

[Documentation Index](Resources/doc/index.md)

# Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

* **Installing the newest (stable) build of the bundle for the highest supported eZ / Ibexa Platform version:**
  ```shell
  $ composer require cjw-network/cjw-config-processor
  ```

* **Installing the bundle for eZ / Ibexa platform version 3.***:
  ```shell
  $ composer require cjw-network/cjw-config-processor:3.*
  ```
  * [**Next Steps and Details**](Resources/doc/installation/3.x-Installation.en.md)

* **Installing the bundle for eZ / Ibexa platform version 2.***:
  ```shell
  $ composer require cjw-network/cjw-config-processor:2.*
  ```
  * [**Next Steps and Details**](Resources/doc/installation/2.x-Installation.en.md)


# Authors

- [**CJW-Network**](https://www.cjw-network.com/)
- **Frederic Bauer**
  <br/>
  <br/>

# COPYRIGHT

Copyright (C) 2020 CJW-Network. All rights reserved.

# LICENSE

http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
