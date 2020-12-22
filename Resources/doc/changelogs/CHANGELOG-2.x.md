# CJW-Network ConfigProcessor Bundle 2.x changelog

## 2.0.1 (xx.12.2020)

* Adapted the custom kernel boot process to make the location retrieval functionality
  available in Symfony 3.4 and Ibexa Platform 2.5

* Fixed that changes to the favourite parameter list via the frontend would be ignored

* Fixed error in synchronous scrolling, where unique nodes would not be added to the other list (when they were
  the first node of the list)

* Changed differences highlighting via the url, to only start highlighting, when the entire page is already loaded and done
  with the other javascripts

* Improved some internal documentation

* Improved config path retrieval: Now the process is able to find configuration files more effectively
  and easily and should be aware of every used file for configuration except for the custom bundle config
  which is conducted by the bundles themselves.

* Fixed an issue where for resources outside the project structure, the paths would be
  cut badly (it was tried to cut the project directory out of the path which didn't feature
  the directory), leading to false paths in the frontend.

## 2.0 (11.12.2020)

* This changelog has been created to ship with the first full version of the bundle

* Bug fixes and overall improvements heading up to the release

* Addition of important documentation leading up to the release

* Initial release

