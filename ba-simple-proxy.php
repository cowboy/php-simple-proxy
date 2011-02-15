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
// 
// ############################################################################

// Change these configuration options if needed, see above descriptions for info.

// ############################################################################

main();

function main(){
  $proxy = new SimpleProxy();	
  $url = $proxy->getURL();
  $headersContent = $proxy->fetchURL($url);
 if( sizeof($headersContent) !=2){
   throw new Exception("Error trying to get content: "+$url);
 }
 // print_r(  $headersContent[0]);
  
  //print_r($headersContent);
 $proxy->outputHeaders($headersContent[0]);

  $content = $proxy->filterContent($headersContent[1], $url);

  $proxy->outputContent($content);
  //	print_r($headersContent[0]);
  //print $headersContent[1];

}

class SimpleProxy {
  protected $enable_jsonp    = false;
  protected $enable_native   = false;
  protected $valid_url_regex = '/.*/';
  
  protected $url;
  protected $contentType;

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
      throw new Exception("Invalid  url,");
    }
    $this->url = $url;
    return $url;
  }

  function fetchURL($url){

    $ch = curl_init( $url );

    if ( strtolower($_SERVER['REQUEST_METHOD']) == 'post' ) {
      curl_setopt( $ch, CURLOPT_POST, true );
      curl_setopt( $ch, CURLOPT_POSTFIELDS, $_POST );
    }

    if ( $_GET['send_cookies'] ) {
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

    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $ch, CURLOPT_HEADER, true );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

    curl_setopt( $ch, CURLOPT_USERAGENT, $_GET['user_agent'] ? $_GET['user_agent'] : $_SERVER['HTTP_USER_AGENT'] );

    $response =  curl_exec( $ch );
   $this->url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   $_GET['url']=$this->url;
   $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
   
   $semiColon = strpos($contentType, ";");
   
   if($semiColon === false){
	   $this->mimeType = $contentType;
   } else {
	  $this->mimeType = substr($contentType, 0, $semiColon);
   }
     
    curl_close( $ch );
    //print $response;
    //print "<br/><br/>";
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
     
    $headerLines = explode("\n", $header);
    $content = substr($response, $headerEndPosition+3);
    

    
    
  
    
    return array($headerLines, $content);
    
    
  }

  function outputHeaders(array $headers){

    //filterHeaders($headers);

    //print "<br/><br/>";
    foreach ( $headers as $h ) {
      //if ( preg_match( '/^(Content-Type|Content-Language|Set-Cookie):/i', $h ) ) {
	    header( $h );
	    //print $h;
	    //print "</br>";
      //}
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
		     return "href=\"http://localhost:8080/gewthen/tools/proxy/ba-simple-proxy.php?url=$link\"";
		  }
		  return "href=\"http://localhost:8080/gewthen/tools/proxy/ba-simple-proxy.php?url=$baseURL/$link\"";
		
		');
		
    	 $imageMap = create_function ('$match',' 
		  $link = $match[2];
		   $parts = parse_url($_GET[\'url\']);
    $baseURL = $parts[\'scheme\'].\'://\'.$parts[\'host\'].$parts[\'path\'];
		  if(substr($link, 0, 4)==="http"){
		     return "href=\"http://localhost:8080/gewthen/tools/proxy/ba-simple-proxy.php?url=$link\"";
		  } if($link[0]=="/"){
		      return $match[1]."http://localhost:8080/gewthen/tools/proxy/ba-simple-proxy.php?url=".$baseURL."/".$match[2].$match[3].$match[4];
		  } else {
			 $parts = explode("/", $baseURL);
			 array_pop($parts);
			 $baseURL = implode($parts, "/");
			return $match[1]."http://localhost:8080/gewthen/tools/proxy/ba-simple-proxy.php?url=".$baseURL."/".$match[2].$match[3].$match[4]; 

		  }
		
		');
    $parts = parse_url($url);
    $baseURL = $parts['scheme'].'://'.$parts['host'];
    // change image links
    $content =  preg_replace_callback('/([< ]+src[\s]*=[\s]*"?)([http:\/\/])?([^ ">]+)/', $imageMap, $content);
    // change background links
    $content =  preg_replace('/(background[\s]*=[\s]*"?)([http:\/\/])?([^ ]+)("?)/', "$1http://localhost:8080/gewthen/tools/proxy/ba-simple-proxy.php?url=$baseURL/$2$3$4", $content); 


    // change anchor links
    $content = preg_replace_callback('/(href[\s]*="?)([^ >"]+)/i',  $linkMap , $content);
    //<body background=images/bg01.gif
    return $content;
  }




  function outputContent($content){
    echo $content;
  }

}











/*
   if ( $_GET['mode'] == 'native' ) {
   if ( !$enable_native ) {
   $contents = 'ERROR: invalid mode';
   $status = array( 'http_code' => 'ERROR' );
   }

// Propagate headers to response.


print $contents;

} else {

// $data will be serialized into JSON data.
$data = array();

// Propagate all HTTP headers into the JSON data object.
if ( $_GET['full_headers'] ) {
$data['headers'] = array();

foreach ( $header_text as $header ) {
preg_match( '/^(.+?):\s+(.*)$/', $header, $matches );
if ( $matches ) {
$data['headers'][ $matches[1] ] = $matches[2];
}
}
}

// Propagate all cURL request / response info to the JSON data object.
if ( $_GET['full_status'] ) {
$data['status'] = $status;
} else {
$data['status'] = array();
$data['status']['http_code'] = $status['http_code'];
}

// Set the JSON data object contents, decoding it from JSON if possible.
$decoded_json = json_decode( $contents );
$data['contents'] = $decoded_json ? $decoded_json : $contents;

// Generate appropriate content-type header.
$is_xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
header( 'Content-type: application/' . ( $is_xhr ? 'json' : 'x-javascript' ) );

// Get JSONP callback.
$jsonp_callback = $enable_jsonp && isset($_GET['callback']) ? $_GET['callback'] : null;

// Generate JSON/JSONP string
$json = json_encode( $data );

print $jsonp_callback ? "$jsonp_callback($json)" : $json;

}*/
	
