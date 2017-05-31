# Avvo Proxy

This is an example proxy script for working with the 
[Avvo API](http://avvo.github.io/api-doc/) from a browser. _It is meant 
for instructional purposes only, and should not be used in a production environment with additional security measures._


## Usage

First, copy config-sample.php to config.php, and update the parameters with your own app keys. Note that due to how Avvo's OAuth2 implementation works, you'll have to acquire the bearer token separately and hard-code into your config.php.

Then to call an API, pass a parameter **_ep** that contains the desired endpoint within the API, 
beginning with a forward slash:

```$javascript
jQuery.getJSON("./avvo.php?_ep=/lawyers.json");
```

Note that the endpoint will be prepended with *https://api.avvo.com/api/4* (no trailing slash).

Any other parameters will be sent to the requested endpoint, so this should allow full access to the API. It is 
currently only meant for making GET requests; any POST/PUT/DELETE requests will fail in unexpected (but spectacular?!) 
ways.

Cheers!
