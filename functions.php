<?php

  require_once("auth.php");

  if ( !isset( $content_width ) ) $content_width = 768;
  if ( function_exists( 'add_theme_support' ) ) add_theme_support( 'automatic-feed-links' );
  if ( function_exists( 'register_nav_menu' ) ) register_nav_menu( 'menu', 'Menu' );
  if ( function_exists('register_sidebar') ) register_sidebar( array(
    'name' => __( 'Widgets', 'simplest' ),
    'id' => 'widgets',
    'before_widget' => '<div class="widget">',
    'after_widget' => '</div><!-- widget -->',
    'before_title' => '<h4>',
    'after_title' => '</h4>') );

function autolink_emails($content) {
  $content = preg_replace("%(?<!mailto:)\b([a-z0-9A-Z.]+)@usc.edu(?!</a>)%", 
                          "<a href='mailto:$1@usc.edu'>$1@</a>", $content);
  return $content;
}

add_filter( 'the_content', 'autolink_emails' );

function nonbreaking_hyphens($content) {
  $content = str_replace("&ndash;", "&#8209;", $content);
  $content = str_replace(json_decode('"\u2013"'), "&#8209;", $content);
  return $content;
}

add_filter( 'the_content', 'nonbreaking_hyphens');


function noside_func() {
     return "<style type='text/css'>#sidebar {display:none;} #main {width:auto; float:none;}</style>";
}
add_shortcode('noside', 'noside_func');

function spoiler_func($atts, $content) {
     return "<span class='spoiler'>$content</span>";
}
add_shortcode('spoiler', 'spoiler_func');

function websheet_func($atts, $content) {
     return "webshoot";
}
add_shortcode('websheet', 'websheet_func');

?>
