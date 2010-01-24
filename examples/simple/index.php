<?PHP

include "../index.php";

$shell['title3'] = "It's just that simple";

$shell['h2'] = 'Get external HTML, JSON and more!';

// ========================================================================== //
// SCRIPT
// ========================================================================== //

ob_start();
?>
$(function(){
  
  // Handle form submit.
  $('#params').submit(function(){
    var proxy = '../../ba-simple-proxy.php',
      url = proxy + '?' + $('#params').serialize();
    
    // Update some stuff.
    $('#request').html( $('<a/>').attr( 'href', url ).text( url ) );
    $('#response').html( 'Loading...' );
    
    // Test to see if HTML mode.
    if ( /mode=native/.test( url ) ) {
      
      // Make GET request.
      $.get( url, function(data){
        
        $('#response')
          .html( '<pre class="brush:xml"/>' )
          .find( 'pre' )
            .text( data );
        
        SyntaxHighlighter.highlight();
      });
      
    } else {
      
      // Make JSON request.
      $.getJSON( url, function(data){
        
        $('#response')
          .html( '<pre class="brush:js"/>' )
          .find( 'pre' )
            .text( JSON.stringify( data, null, 2 ) );
        
        SyntaxHighlighter.highlight();
      });
    }
    
    // Prevent default form submit action.
    return false;
  });
  
  // Submit the form on page load if ?url= is passed into the example page.
  if ( $('#url').val() !== '' ) {
    $('#params').submit();
  }
  
  // Disable AJAX caching.
  $.ajaxSetup({ cache: false });
  
  // Disable dependent checkboxes as necessary.
  $('input:radio').click(function(){
    var that = $(this),
      c1 = 'dependent-' + that.attr('name'),
      c2 = c1 + '-' + that.val();
    
    that.closest('form')
      .find( '.' + c1 + ' input' )
        .attr( 'disabled', 'disabled' )
        .end()
      .find( '.' + c2 + ' input' )
        .removeAttr( 'disabled' );
  });
  
  // Clicking sample remote urls should populate the "Remote URL" box.
  $('#sample a').click(function(){
    $('#url').val( $(this).attr( 'href' ) );
    return false;
  });
});
<?
$shell['script'] = ob_get_contents();
ob_end_clean();

// ========================================================================== //
// HTML HEAD ADDITIONAL
// ========================================================================== //

ob_start();
?>
<script type="text/javascript" language="javascript">

// I want to use json2.js because it allows me to format stringified JSON with
// pretty indents, so let's nuke any existing browser-specific JSON parser.
window.JSON = null;

</script>
<script type="text/javascript" src="../../shared/json2.js"></script>
<script type="text/javascript" language="javascript">

<?= $shell['script']; ?>

$(function(){
  
  // Syntax highlighter.
  SyntaxHighlighter.defaults['auto-links'] = false;
  SyntaxHighlighter.highlight();
  
});

</script>
<style type="text/css" title="text/css">

/*
bg: #FDEBDC
bg1: #FFD6AF
bg2: #FFAB59
orange: #FF7F00
brown: #913D00
lt. brown: #C4884F
*/

#page {
  width: 700px;
}

#params input.text {
  display: block;
  border: 1px solid #000;
  width: 540px;
  padding: 2px;
  margin-bottom: 0.2em;
}

#params input.submit {
  display: block;
  margin-top: 0.6em;
}

.indent {
  margin-left: 1em;
}

#sample {
  font-size: 90%;
}

</style>
<?
$shell['html_head'] = ob_get_contents();
ob_end_clean();

// ========================================================================== //
// HTML BODY
// ========================================================================== //

ob_start();
?>
<?= $shell['donate'] ?>

<p>
  With <a href="http://benalman.com/projects/php-simple-proxy/">Simple PHP Proxy</a>, your JavaScript can
  access content in remote webpages, without cross-domain security limitations, even if it's not available
  in JSONP format. Of course, you'll need to install this PHP script on your server.. but that's a small
  price to have to pay for this much awesomeness.
</p>
<p>
  Please note that while jQuery is used here, you can use any library you'd like.. or just code your
  XMLHttpRequest objects by hand, it doesn't matter. This proxy just acts a bridge between the client
  and server to facilitate cross-domain communication, so the client-side JavaScript is entirely left
  up to you (but I recommend jQuery's <a href="http://docs.jquery.com/Ajax/jQuery.getJSON">getJSON</a>
  method because of its simplicity).
</p>
<p>
  Please see the <a href="http://benalman.com/projects/php-simple-proxy/">project page</a> and 
  <a href="http://benalman.com/code/projects/php-simple-proxy/docs/">documentation</a> for more usage
  information.
</p>

<form id="params" method="get" action="">
  <div>
    <label>
      <b>Remote URL</b>
      <input id="url" class="text" type="text" name="url" value="<?= $_GET['url'] ?>">
    </label>
  </div>
  <p id="sample">
    ..or try these sample Remote URLs:
    <a href="http://github.com/">GitHub</a>,
    <a href="http://github.com/cowboy/php-simple-proxy/raw/master/examples/simple/json_sample.js">a sample JSON (not JSONP) request</a>,
    <a href="http://github.com/omg404errorpage">a 404 error page</a>
  </p>
  <div>
    <label>
      <input type="radio" name="mode" value="native" disabled="disabled">
      Native <i>(disabled by default)</i>
    </label>
  </div>
  <div>
    <label>
      <input type="radio" name="mode" value="json" checked="checked" disabled="disabled">
      JSON
    </label>
  </div>
  <div class="dependent-mode dependent-mode-json indent">
    <div>
      <label>
        <input type="checkbox" name="full_headers" value="1" checked="checked">
        Full Headers
      </label>
    </div>
    <div>
      <label>
        <input type="checkbox" name="full_status" value="1" checked="checked">
        Full Status
      </label>
    </div>
  </div>
  <input class="submit" type="submit" name="submit" value="Submit">
</form>

<h3>Request URL</h3>
<p id="request">N/A, click Submit!</p>

<h3>Simple PHP Proxy response</h3>
<div id="response">N/A, click Submit!</div>

<h3>The code</h3>

<pre class="brush:js">
<?= htmlspecialchars( $shell['script'] ); ?>
</pre>

<?
$shell['html_body'] = ob_get_contents();
ob_end_clean();

// ========================================================================== //
// DRAW SHELL
// ========================================================================== //

draw_shell();

?>
