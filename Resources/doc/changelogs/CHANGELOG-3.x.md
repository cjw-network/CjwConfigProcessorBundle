# CJW-Network ConfigProcessor Bundle 3.x changelog

## 3.1.0 (08.01.2020)

* Added Symfony console command to display the processed configuration in the console. This command
  also allows the user to specify site access context and / or filter the parameters for specific
  subtrees to customize the command execution and output.

* Fixed error, where when turning off the favourite feature, an error would be thrown in the bundle.

* Updated documentation.

* Added display of environmental parameters, and their values in a dedicated view.

* Added additional configuration for the new feature (allowing turning the feature on or off).

* Updated CustomParamProcessor to allow more dynamic setting of the site access to filter for with the
  custom parameters.

* Added Symfony console command to display the locations determined for the processed configuration
  by the bundle. It also allows specifying a parameter to filter for, to only display the locations
  belonging to that specific parameter.

## 3.0.1 (23.12.2020)

* Fixed an issue with difference highlighting: When the state was saved in the url,
  the highlighting would trigger immediately before any other JS had loaded which caused
  some false markings as certain classes and attributes had not been set at that point (now
  it will trigger after the other JS has loaded properly)

* Fixed an issue with synchronous scrolling, where when the first node of a list was unique
  to said list, the synchronous scrolling would throw an error and never complete

* Improved config path retrieval: Now the process is able to find configuration files more effectively
  and easily and should be aware of every used file for configuration except for the custom bundle config
  which is conducted by the bundles themselves.

* Fixed issues with the file representations of parameter lists, where special characters being
  used in keys or values would cause issues and produce invalid yaml. These are now automatically
  escaped and should result in valid yaml files.

* More (and more detailed) documentation.

* Fixed an issue where for resources outside the project structure, the paths would be
  cut badly (it was tried to cut the project directory out of the path which didn't feature
  the directory), leading to false paths in the frontend.

* Fixed an issue with config path retrieval for bundles outside the vendor directory,
  where the general config directory would be found and added to the list of custom paths,
  leading to immense loading times and endless loops of configuration, which would often amount
  to an error


## 3.0 (11.12.2020)

* This changelog has been created to ship with the first full version of the bundle

* Bug fixes and overall improvements heading up to the release

* Addition of important documentation leading up to the release

* Initial release

