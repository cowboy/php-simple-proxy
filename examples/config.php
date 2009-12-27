<?PHP

$shell['title1'] = "Simple PHP Proxy";
$shell['link1']  = "http://benalman.com/projects/php-simple-proxy/";

ob_start();
?>
  <a href="http://benalman.com/projects/php-simple-proxy/">Project Home</a>,
  <a href="http://benalman.com/code/projects/php-simple-proxy/docs/">Documentation</a>,
  <a href="http://github.com/cowboy/php-simple-proxy/">Source</a>
<?
$shell['h3'] = ob_get_contents();
ob_end_clean();

$shell['jquery'] = 'jquery-1.3.2.js';

$shell['shBrush'] = array( 'JScript', 'Xml' );

?>
