<?PHP

// Script: Simple PHP Proxy: Get external HTML, JSON and more!
//
// *Version: 1.6, Last updated: 1/24/2009*
// 
// Project Home - http://benalman.com/projects/php-simple-proxy/
// GitHub       - http://github.com/cowboy/php-simple-proxy/
// Source       - http://github.com/cowboy/php-simple-proxy/raw/master/ba-simple-proxy.php
// 
// About: License
// 
// Copyright (c) 2010 "Cowboy" Ben Alman,
// Dual licensed under the MIT and GPL licenses.
// http://benalman.com/about/license/
// 
// About: Examples
// 
// This working example, complete with fully commented code, illustrates one way
// in which this PHP script can be used.
// 
// Simple - http://benalman.com/code/projects/php-simple-proxy/examples/simple/
// 
// About: Release History
// 
// 1.8 - (2/03/2012) Add optional caching of proxied results by Stefan Hoth <sh@jnamic.com>
// 1.7 - (2/03/2012) Add optional whitelist-check by Stefan Hoth <sh@jnamic.com>
// 1.6 - (1/24/2009) Now defaults to JSON mode, which can now be changed to
//       native mode by specifying ?mode=native. Native and JSONP modes are
//       disabled by default because of possible XSS vulnerability issues, but
//       are configurable in the PHP script along with a url validation regex.
// 1.5 - (12/27/2009) Initial release
// 
// Topic: GET Parameters
// 
// Certain GET (query string) parameters may be passed into ba-simple-proxy.php
// to control its behavior, this is a list of these parameters. 
// 
//   url - The remote URL resource to fetch. Any GET parameters to be passed
//     through to the remote URL resource must be urlencoded in this parameter.
//   mode - If mode=native, the response will be sent using the same content
//     type and headers that the remote URL resource returned. If omitted, the
//     response will be JSON (or JSONP). <Native requests> and <JSONP requests>
//     are disabled by default, see <Configuration Options> for more information.
//   callback - If specified, the response JSON will be wrapped in this named
//     function call. This parameter and <JSONP requests> are disabled by
//     default, see <Configuration Options> for more information.
//   user_agent - This value will be sent to the remote URL request as the
//     `User-Agent:` HTTP request header. If omitted, the browser user agent
//     will be passed through.
//   send_cookies - If send_cookies=1, all cookies will be forwarded through to
//     the remote URL request.
//   send_session - If send_session=1 and send_cookies=1, the SID cookie will be
//     forwarded through to the remote URL request.
//   full_headers - If a JSON request and full_headers=1, the JSON response will
//     contain detailed header information.
//   full_status - If a JSON request and full_status=1, the JSON response will
//     contain detailed cURL status information, otherwise it will just contain
//     the `http_code` property.
// 
// Topic: POST Parameters
// 
// All POST parameters are automatically passed through to the remote URL
// request.
// 
// Topic: JSON requests
// 
// This request will return the contents of the specified url in JSON format.
// 
// Request:
// 
// > ba-simple-proxy.php?url=http://example.com/
// 
// Response:
// 
// > { "contents": "<html>...</html>", "headers": {...}, "status": {...} }
// 
// JSON object properties:
// 
//   contents - (String) The contents of the remote URL resource.
//   headers - (Object) A hash of HTTP headers returned by the remote URL
//     resource.
//   status - (Object) A hash of status codes returned by cURL.
// 
// Topic: JSONP requests
// 
// This request will return the contents of the specified url in JSONP format
// (but only if $enable_jsonp is enabled in the PHP script).
// 
// Request:
// 
// > ba-simple-proxy.php?url=http://example.com/&callback=foo
// 
// Response:
// 
// > foo({ "contents": "<html>...</html>", "headers": {...}, "status": {...} })
// 
// JSON object properties:
// 
//   contents - (String) The contents of the remote URL resource.
//   headers - (Object) A hash of HTTP headers returned by the remote URL
//     resource.
//   status - (Object) A hash of status codes returned by cURL.
// 
// Topic: Native requests
// 
// This request will return the contents of the specified url in the format it
// was received in, including the same content-type and other headers (but only
// if $enable_native is enabled in the PHP script).
// 
// Request:
// 
// > ba-simple-proxy.php?url=http://example.com/&mode=native
// 
// Response:
// 
// > <html>...</html>
// 
// Topic: Notes
// 
// * Assumes magic_quotes_gpc = Off in php.ini
// 
// Topic: Configuration Options
// 
// These variables can be manually edited in the PHP file if necessary.
// 
//   $enable_jsonp - Only enable <JSONP requests> if you really need to. If you
//     install this script on the same server as the page you're calling it
//     from, plain JSON will work. Defaults to false.
//   $enable_native - You can enable <Native requests>, but you should only do
//     this if you also whitelist specific URLs using $valid_url_regex, to avoid
//     possible XSS vulnerabilities. Defaults to false.
//   $valid_url_regex - This regex is matched against the url parameter to
//     ensure that it is valid. This setting only needs to be used if either
//     $enable_jsonp or $enable_native are enabled. Defaults to '/.*/' which
//     validates all URLs.
//   $authz_header - an index into the $_SERVER array locating authorization
//     data which is to be proxied in the HTTP Authorization header. This is
//     necessary since, in a default deployment, Apache will not pass an
//     incoming Authorization header to a script. As a convention, we pass
//     authorization data to the proxy in the X-Authorization header, so the
//     default value is 'HTTP_X_AUTHORIZATION'
//   $cors_allow_origin - a space-separated list of origins, each of the form
//     https://example.com:8443, from which scripts will be allowed to access
//     the proxy. See http://www.w3.org/TR/cors/ for details.
//   $cors_allow_methods - HTTP methods allowed from the origins specified in
//     $cors_allow_origin. Defaults to 'GET, POST, PUT, PATCH, DELETE, HEAD'
//   $cors_allow_headers - HTTP headers allowed from the origins specified in
//     $cors_allow_origin. Defaults to 'X-Authorization, Content-Type'
// 
// ############################################################################

main();

function main(){
	// Change these configuration options if needed, see above descriptions for info.
	$enable_jsonp    = false;
	$enable_native   = true;

try {
  $proxy = new SimpleProxy();	
  $url = $proxy->getURL();
  $headersContent = $proxy->fetchURL($url);
 if( sizeof($headersContent) !=2){
   throw new Exception("Error trying to get content: "+$url);
 }

	if (isset($_GET['mode']) && $_GET['mode'] == 'json' ) {
		if ( !$enable_jsonp ) {
			$contents = 'ERROR: invalid mode (enable_jsonp)';
			$status = array( 'http_code' => 'ERROR' );
			echo $contents;
		} else {
			$jsoncontent = $proxy->outputContentJson($headersContent[0], $headersContent[1]);
			echo ($jsoncontent);
		}
	} else {
		if ( !$enable_native ) {
			$contents = 'ERROR: invalid mode (enable_native)';
			$status = array( 'http_code' => 'ERROR' );
			echo $contents;
		} else {
			$proxy->outputHeaders($headersContent[0]);
			$content = $proxy->filterContent($headersContent[1], $url);
			$proxy->outputContent($content);
		}
	}

} catch (Exception $e) {
	header('HTTP/1.1 403 Forbidden');
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}
}

class proxyLocalCache {
  //how long after a cache will be renewed
  static protected $CACHE_TTL = 600;//10 mins
  static protected $CACHE_DIR = '/tmp/.cache';

// ############################################################################
//  FUNCTIONS
// ############################################################################

/**
 * checks or creates the cache dir 
 */
static function prepare_cache(){
  return is_writable(self::$CACHE_DIR) || mkdir(self::$CACHE_DIR,0777,true);
}

/**
 * generates a cachefile name for a given url
 */
static function get_cachefile_name($url){
  return self::$CACHE_DIR.'/'.sha1($url);
}

/**
 * checks if a cache file exists and is not expired for a given url
 */
static function cachefile_exits($url){

  if(! self::prepare_cache()){
    return false;
  }

  return is_readable(  self::get_cachefile_name($url) ) && ! self::cachefile_is_too_old($url);
}

/**
 * returns if the modification time is older than the cache-time
 */
static function cachefile_is_too_old($url){
    return ( time() - filemtime( self::get_cachefile_name($url) )) >= self::$CACHE_TTL;
}

/**
 * checks if a cache file exists for a given url
 */
static function cachefile_read($url){

  if(! self::prepare_cache()){
    return false;
  }

  return file_get_contents( self::get_cachefile_name($url) );
}

static function cachefile_write($url, $content){

  if(! self::prepare_cache()){
    return false;
  }

  return file_put_contents( self::get_cachefile_name($url), $content);
}
}
// ############################################################################

class SimpleProxy {
  protected $valid_url_regex = '/.*/';

  // CACHING
  protected $enable_caching = false;

  // only domains listed in this array will be allowed to be proxied
  protected $WHITELIST_DOMAINS = array('google.com','google.de', 'nu.nl', 'www.nu.nl');

  protected $authz_header = 'HTTP_X_AUTHORIZATION';
  protected $cors_allow_origin  = null;
  protected $cors_allow_methods = 'GET, POST, PUT, PATCH, DELETE, HEAD';
  protected $cors_allow_headers = 'X-Authorization, Content-Type';

  protected $proxy_url = "http://lol2/ba-simple-proxy.php";

  function getURL(){
    global $valid_url_regex;
    $url = $_GET['url'];
    $needle = "url=";
    $pos = strpos($_SERVER['REQUEST_URI'], $needle);
    $url = substr($_SERVER['REQUEST_URI'], $pos+strlen($needle));
    
    if ( !$url ) {
      // Passed url not specified.
      $contents = 'ERROR: url not specified';
      $status = array( 'http_code' => 'ERROR' );
      throw new Exception("URL not specified.");
    } else if ( !preg_match( $this->valid_url_regex, $url ) ) {
      // Passed url doesn't match $valid_url_regex.
      $contents = 'ERROR: invalid url';
      $status = array( 'http_code' => 'ERROR' );
      throw new Exception("Invalid  url.");
    } elseif (   is_array($this->WHITELIST_DOMAINS) && ! empty($this->WHITELIST_DOMAINS) && 
               ! in_array( parse_url($url,PHP_URL_HOST), $this->WHITELIST_DOMAINS) ) {
      $contents = 'ERROR: invalid url (not in whitelist)';
      $status = array( 'http_code' => 'ERROR' );
      throw new Exception("ERROR: invalid url (not in whitelist).");
    }
    $this->url = $url;
    return $url;
  }
  
	function fetchURL($url){
		if($this->enable_caching && proxyLocalCache::cachefile_exits($url)){
		  $headerLines = array();
		  $content = proxyLocalCache::cachefile_read($url);  
		  return array($headerLines, $content);
		}else{
		  if ( isset( $cors_allow_origin ) ) {
			header( 'Access-Control-Allow-Origin: '.$cors_allow_origin );
			if ( isset( $cors_allow_methods ) ) {
			  header( 'Access-Control-Allow-Methods: '.$cors_allow_methods );
			}
			if ( isset( $cors_allow_headers ) ) {
			  header( 'Access-Control-Allow-Headers: '.strtolower($cors_allow_headers) );
			}
			if ( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ) {
			  // We're done - don't proxy CORS OPTIONS request
			  exit();
			}
		  }

		  $ch = curl_init( $url );

		  // Pass on request method, regardless of what it is
		  curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD'] );

		  // Pass on content, regardless of request method
		  if ( isset($_SERVER['CONTENT_LENGTH'] ) && $_SERVER['CONTENT_LENGTH'] > 0 ) {
			// PiBa-NL (possibly an issue with  enctype="multipart/form-data" ??)
			die("CONTENT_LENGTH");
			curl_setopt( $ch, CURLOPT_POSTFIELDS, file_get_contents("php://input") );
		  }
		
		  if (isset($_GET['send_cookies']) && $_GET['send_cookies'] ) {
			$cookie = array();
			foreach ( $_COOKIE as $key => $value ) {
			  $cookie[] = $key . '=' . $value;
			}
			if ( $_GET['send_session'] ) {
			  $cookie[] = SID;
			}
			$cookie = implode( '; ', $cookie );
		  
			curl_setopt( $ch, CURLOPT_COOKIE, $cookie );
		  }
		
			$headers = array();
			if ( isset($authz_header) && isset($_SERVER[$authz_header]) ) {
				// Set the Authorization header
				array_push($headers, "Authorization: ".$_SERVER[$authz_header] );
			}
			if ( isset($_SERVER['CONTENT_TYPE']) ) {
				// Pass through the Content-Type header
				array_push($headers, "Content-Type: ".$_SERVER['CONTENT_TYPE'] );
			}	
			if ( count($headers) > 0 ) {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
			}
			
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_HEADER, true );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			
			curl_setopt( $ch, CURLOPT_USERAGENT, isset($_GET['user_agent']) && $_GET['user_agent'] ? $_GET['user_agent'] : $_SERVER['HTTP_USER_AGENT'] );
			// in case you wish Not to confirm the CA for your server (e.g. it's inside your org)
			// curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER , false);
		
			$response = curl_exec( $ch );
			if ($response === FALSE) {
				die(curl_error($ch)); // in case we have errors - let's report them!
			}
			$this->url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
			$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
			$this->status = curl_getinfo( $ch );
			curl_close( $ch ); 
			
			/*list( $header, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', $response, 2 );
			if($header == 'HTTP/1.1 100 Continue') {
				list($header, $contents) = preg_split( '/([\r\n][\r\n])\\1/', $contents, 2 );
			}*/
			
			$_GET['url']=$this->url;
			$semiColon = strpos($contentType, ";");

			if($semiColon === false){
				$this->mimeType = $contentType;
			} else {
				$this->mimeType = substr($contentType, 0, $semiColon);
			}
			$headerStartPosition = 0;
			$headerEndPosition = 0;
			do{
				$headerStartPosition = $headerEndPosition;
				$headerStartPosition = strpos($response, "HTTP/", $headerStartPosition);
				$headerEndPosition = strpos($response, "\n\r", $headerStartPosition);

				$header = substr($response, $headerStartPosition, $headerEndPosition-$headerStartPosition);
				// print $header;
			}while(strpos($response, "HTTP/", $headerEndPosition)!==false);
			//    print ".".$header.".";
			// Split header text into an array.
			$headerLines = preg_split( '/[\r\n]+/', $header );
			$content = substr($response, $headerEndPosition+3);

			 //die("hoi<pre>$header<br><br><br>\r\n\r\n\r\n$content");

			if($this->enable_caching){
			  proxyLocalCache::cachefile_write($url, $content);  
			}
			return array($headerLines, $content);		
		}
	}
	
	function outputHeaders(array $headers){
		//filterHeaders($headers);
		//print "<br/><br/>";
		foreach ( $headers as $h ) {
			if ( preg_match( '/^(Content-Type|Content-Language|Set-Cookie):/i', $h ) ) {
				header( $h );
			//print $h;
			//print "</br>";
			}
		}
	}
	function filterHeaders($headers){
		$matches = array();
		foreach($headers as $h){
			// grab http code
			if(preg_match("/^HTTP\/[0-9]+[\.][0-9][\s]*([0-9]+)[\s]*(.*)$/i", $h, $matches)){
				switch($matches[1]){
					case 301: // Moved Permanently
				}
			}
		}
	}

  function filterContent($content, $url){
	 if($this->contentType == "text/html") {
		 return;
	 }
	 
	 $linkMap = create_function ('$match',' 
		  $link = $match[2];
		   $parts = parse_url($_GET[\'url\']);
    $baseURL = $parts[\'scheme\'].\'://\'.$parts[\'host\'].$parts[\'path\'];
		  if(substr($link, 0, 4)==="http"){
		     return "href=\"$proxy_url?url=$link\"";
		  }
		  return "href=\"$proxy_url?url=$baseURL/$link\"";
		
		');
		
    	 $imageMap = create_function ('$match',' 
		  $link = $match[2];
		   $parts = parse_url($_GET[\'url\']);
    $baseURL = $parts[\'scheme\'].\'://\'.$parts[\'host\'].$parts[\'path\'];
		  if(substr($link, 0, 4)==="http"){
		     return "href=\"$proxy_url?url=$link\"";
		  } if($link[0]=="/"){
		      return $match[1]."$proxy_url?url=".$baseURL."/".$match[2].$match[3].$match[4];
		  } else {
			 $parts = explode("/", $baseURL);
			 array_pop($parts);
			 $baseURL = implode($parts, "/");
			return $match[1]."$proxy_url?url=".$baseURL."/".$match[2].$match[3].$match[4]; 

		  }
		
		');
    $parts = parse_url($url);
    $baseURL = $parts['scheme'].'://'.$parts['host'];
    // change image links
    $content =  preg_replace_callback('/([< ]+src[\s]*=[\s]*"?)([http:\/\/])?([^ ">]+)/', $imageMap, $content);
    // change background links
    $content =  preg_replace('/(background[\s]*=[\s]*"?)([http:\/\/])?([^ ]+)("?)/', "$1$proxy_url?url=$baseURL/$2$3$4", $content); 


    // change anchor links
    $content = preg_replace_callback('/(href[\s]*="?)([^ >"]+)/i',  $linkMap , $content);
    //<body background=images/bg01.gif
    return $content;
  }

  function outputContent($content){
	echo $content;
  }
	function outputContentJson($headers, $contents) {
		// $data will be serialized into JSON data.
		$data = array();

		// Propagate all HTTP headers into the JSON data object.
		if (isset($_GET['full_headers']) && $_GET['full_headers'] ) {
			$data['headers'] = array();

			foreach ( $headers as $header ) {
				preg_match( '/^(.+?):\s+(.*)$/', $header, $matches );
				if ( $matches ) {
					$data['headers'][ $matches[1] ] = $matches[2];
				}
			}
		}

		// Propagate all cURL request / response info to the JSON data object.
		if (isset($_GET['full_status']) && $_GET['full_status'] ) {
			$data['status'] = $this->status;
		} else {
			$data['status'] = array();
			$data['status']['http_code'] = $this->status['http_code'];
		}

		// Set the JSON data object contents, decoding it from JSON if possible.
		$decoded_json = json_decode( $contents );
		$data['contents'] = $decoded_json ? $decoded_json : $contents;

		// Generate appropriate content-type header.
		$is_xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
		header( 'Content-type: application/' . ( $is_xhr ? 'json' : 'javascript' ) );

		// Get JSONP callback.
		$jsonp_callback = $enable_jsonp && isset($_GET['callback']) ? $_GET['callback'] : null;

		// Generate JSON/JSONP string
		$json = json_encode( $data );

		return $jsonp_callback ? "$jsonp_callback($json)" : $json;
	}  
}//SimpleProxy

?>
