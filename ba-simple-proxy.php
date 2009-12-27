<?PHP

// Script: Simple PHP Proxy: Get external HTML, JSON and more!
//
// *Version: 1.5, Last updated: 12/27/2009*
// 
// Project Home - http://benalman.com/projects/php-simple-proxy/
// GitHub       - http://github.com/cowboy/php-simple-proxy/
// Source       - http://github.com/cowboy/php-simple-proxy/raw/master/ba-simple-proxy.php
// 
// About: License
// 
// Copyright (c) 2009 "Cowboy" Ben Alman,
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
// 1.5 - (12/27/2009) Initial release
// 
// Topic: GET Parameters
// 
// Certain GET (query string) parameters may be passed into ba-simple-proxy.php
// to control its behavior, this is a list of these parameters. 
// 
//   url - The remote URL resource to fetch. Any GET parameters to be passed
//     through to the remote URL resource must be urlencoded in this parameter.
//   mode - If mode=json, the response will be JSON or JSONP. If mode is omitted,
//     the response will be sent using the same content type that the remote URL
//     resource returned.
//   callback - If mode=json, the response JSON will be wrapped in this JSONP
//     named function call.
//   user_agent - This value will be sent to the remote URL request as the
//     `User-Agent:` HTTP request header. If omitted, the browser user agent
//     will be passed through.
//   send_cookies - If send_cookies=1, all cookies will be forwarded through to
//     the remote URL request.
//   send_session - If send_session=1 and send_cookies=1, the SID cookie will be
//     forwarded through to the remote URL request.
//   full_headers - If mode=json and full_headers=1, the JSON response will
//     contain detailed header information.
//   full_status - If mode=json and full_status=1, the JSON response will
//     contain detailed cURL status information, otherwise it will just contain
//     the `http_code` property.
// 
// Topic: POST Parameters
// 
// All POST parameters are automatically passed through to the remote URL
// request.
// 
// Topic: JSON data structure
// 
// This request:
// ba-simple-proxy.php?mode=json&url=http://www.google.com/
// 
// Will return the contents of http://www.google.com/ in JSON format:
// 
// > { "contents": "<html>...</html>", "headers": {...}, "status": {...} }
// 
//   contents - (String) The contents of the remote URL resource.
//   headers - (Object) A hash of HTTP headers returned by the remote URL
//     resource.
//   status - (Object) A hash of status codes returned by cURL.
// 
// Topic: JSONP data structure
// 
// This request:
// ba-simple-proxy.php?mode=json&url=http://www.google.com/&callback=foo
// 
// Will return the contents of http://www.google.com/ in JSONP format:
// 
// > foo({ "contents": "<html>...</html>", "headers": {...}, "status": {...} })
// 
//   contents - (String) The contents of the remote URL resource.
//   headers - (Object) A hash of HTTP headers returned by the remote URL
//     resource.
//   status - (Object) A hash of status codes returned by cURL.
// 
// Topic: Notes
// 
// * Assumes magic_quotes_gpc = Off in php.ini

if ( $_GET['url'] ) {
  $ch = curl_init( $_GET['url'] );
  
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
  
  list( $header, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', curl_exec( $ch ), 2 );
  
  $status = curl_getinfo( $ch );
  
  curl_close( $ch );
  
} else {
  $contents = 'ERROR: url not specified';
  $status = array( 'http_code' => 'ERROR' );
}

// Split header text into an array.
$header_text = preg_split( '/[\r\n]+/', $header );

if ( $_GET['mode'] == 'json' ) {
  
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
  $jsonp_callback = isset($_GET['callback']) ? $_GET['callback'] : null;
  
  // Generate JSON/JSONP string
  $json = json_encode( $data );
  
  print $jsonp_callback ? "$jsonp_callback($json)" : $json;
  
} else {
  
  // Propagate headers to response.
  foreach ( $header_text as $header ) {
    if ( preg_match( '/^(?:Content-Type|Content-Language|Set-Cookie):/i', $header ) ) {
      header( $header );
    }
  }
  
  print $contents;
}

?>
