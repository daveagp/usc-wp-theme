<?php
add_action( 'wp_enqueue_scripts', 'websheet_enqueue_resources' );
function websheet_enqueue_resources() {
  // enqueue scripts and styles
  $enq = array(
               "/../websheets/jquery.min.js",

               "/../websheets/CodeMirror/lib/codemirror.css",
               "/../websheets/CodeMirror/lib/codemirror.js",
               "/../websheets/CodeMirror/theme/neat.css",
               "/../websheets/CodeMirror/mode/clike/clike.js",
               "/../websheets/CodeMirror/addon/selection/mark-selection.js",
               "/../websheets/CodeMirror/addon/edit/matchbrackets.js",
               
               "http://fonts.googleapis.com/css".
               "?family=Source+Code+Pro:400,700",
               "http://cdn.mathjax.org/mathjax/latest/MathJax.js".
               "?config=TeX-AMS-MML_HTMLorMML",

               "http://bits.usc.edu/websheets/websheets.css",
               "http://bits.usc.edu/websheets/websheets.js",

               );
  foreach ($enq as $i=>$url) {
    if (strpos($url, "css") !== false)
      wp_enqueue_style("uscwebsheets-$i", $url);
    else
      wp_enqueue_script("uscwebsheets-$i", $url);
  };
}

add_action('wp_head', 'websheets_head');
function websheets_head() {
  echo "<script type='text/x-mathjax-config'> 
MathJax.Hub.Config({tex2jax: {displayMath: [ ['$$','$$'] ], inlineMath: [['$','$'] ]} });
</script>\n";
  global $WS_AUTHINFO;
  echo "<script type='text/javascript'> 
   websheets.authinfo = ".json_encode($GLOBALS['WS_AUTHINFO']).";
   websheets.urlbase = 'http://bits.usc.edu/websheets/';
   websheets.require_login = true;
   websheets.header_toggling = true;
</script>\n";
}
function websheet_func($atts, $content) {
  if (!array_key_exists("slug", $atts)) 
    return "<div>Websheet shortcode needs a slug.</div>";

  $descriptorspec = array(
                          0 => array("pipe", "r"),  // stdin
                          1 => array("pipe", "w"),  // stdout
                          2 => array("pipe", "w"),  // stderr
                          );

  global $WS_AUTHINFO;
  $process = proc_open("/home/parallel05/www/docs/websheets/load.py " . $atts["slug"] . " " . $WS_AUTHINFO['username'] . " False", $descriptorspec, $pipes, "/home/parallel05/www/docs/websheets/");

  if (!is_resource($process)) {
    echo "Internal error, could not run Websheet program";
    die;
  }

  fwrite($pipes[0], json_encode($WS_AUTHINFO));//$stdin);
  fclose($pipes[0]);
  $stdout = stream_get_contents($pipes[1]);
  fclose($pipes[1]);
  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[2]);
  $return_value = proc_close($process);
  
  if ($stderr != "" || $return_value != 0) {
    echo "Internal error: <pre>";
    echo $stdout . $stderr . "\nReturned $return_value</pre>";
    return;
  }

  if ($stdout[0] != '{') 
     return "<div><tt><b>Websheet error: $stdout. Please notify the instructor.</b></tt></div>";

  return 
  '<div><script type=text/javascript>websheets.createHere("'.$atts["slug"].'"'.",$stdout);</script></div>";
}

add_shortcode('websheet', 'websheet_func');

