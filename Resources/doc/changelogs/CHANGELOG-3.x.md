# CJW-Network ConfigProcessor Bundle 3.x changelog

## 3.0.1 (xx.12.2020)

* Fixed an issue with difference highlighting: When the state was saved in the url, 
  the highlighting would trigger immediately before any other JS had loaded which caused 
  some false markings as certain classes and attributes had not been set at that point (now
  it will trigger after the other JS has loaded properly)
  
* Fixed an issue with synchronous scrolling, where when the first node of a list was unique 
  to said list, the synchronous scrolling would throw an error and never complete

## 3.0 (11.12.2020)

* This changelog has been created to ship with the first full version of the bundle
  
* Bug fixes and overall improvements heading up to the release
  
* Addition of important documentation leading up to the release

* Initial release

