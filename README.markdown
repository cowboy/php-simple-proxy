# Simple PHP Proxy: Get external HTML, JSON and more! #
[http://benalman.com/projects/php-simple-proxy/](http://benalman.com/projects/php-simple-proxy/)

Version: 1.6, Last updated: 1/24/2009

With Simple PHP Proxy, your JavaScript can access content in remote webpages, without cross-domain security limitations, even if it's not available in JSONP format. Of course, you'll need to install this PHP script on your server.. but that's a small price to have to pay for this much awesomeness.

Visit the [project page](http://benalman.com/projects/php-simple-proxy/) for more information and usage examples!


## Documentation ##
[http://benalman.com/code/projects/php-simple-proxy/docs/](http://benalman.com/code/projects/php-simple-proxy/docs/)


## Examples ##
This working example, complete with fully commented code, illustrates one way
in which this PHP script can be used.

[http://benalman.com/code/projects/php-simple-proxy/examples/simple/](http://benalman.com/code/projects/php-simple-proxy/examples/simple/)  


## Release History ##

1.6 - (1/24/2009) Now defaults to JSON mode, which can now be changed to native mode by specifying ?mode=native. Native and JSONP modes are disabled by default because of possible XSS vulnerability issues, but are configurable in the PHP script along with a url validation regex.  
1.5 - (12/27/2009) Initial release


## License ##
Copyright (c) 2010 "Cowboy" Ben Alman  
Dual licensed under the MIT and GPL licenses.  
[http://benalman.com/about/license/](http://benalman.com/about/license/)
