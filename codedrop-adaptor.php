<?php 
add_shortcode('assignment', 'assignment_shortcode');
function assignment_shortcode($atts, $content) {
   $slug = $atts["slug"];
   $course = $atts["course"];
   if (!$slug || !$course)
      return "<div>Internal error: missing slug or course.</div>";
   
   $_REQUEST['assignment'] = $slug;
   $_REQUEST['course'] = $course;
   require_once("/home/parallel05/www/docs/codedrop/codedrop.php");

   return frontend_div();
}

add_action('wp_head', 'codedrop_head');
function codedrop_head() {
  echo "<script type='text/javascript'> submit_ajax_url = 'http://bits.usc.edu/codedrop/codedrop.php'; </script>
<link rel='stylesheet' href='http://bits.usc.edu/codedrop/codedrop.css?ver=4.2.2' type='text/css' />
<script type='text/javascript' src='http://bits.usc.edu/codedrop/codedrop.js?ver=4.2.2'></script>
";
}
