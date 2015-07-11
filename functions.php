<?php

remove_filter('the_content', 'wptexturize');

if ( !isset( $content_width ) ) 
  $content_width = 768;
if ( function_exists( 'add_theme_support' ) ) 
  add_theme_support( 'automatic-feed-links' );
if ( function_exists( 'register_nav_menu' ) ) 
  register_nav_menu( 'menu', 'Menu' );
if ( function_exists('register_sidebar') ) 
  register_sidebar( array(
                          'name' => __( 'Widgets', 'simplest' ),
                          'id' => 'widgets',
                          'before_widget' => '<div class="widget">',
                          'after_widget' => '</div><!-- widget -->',
                          'before_title' => '<h4>',
                          'after_title' => '</h4>') );

add_filter( 'the_content', 'autolink_emails' );
function autolink_emails($content) {
  $content = preg_replace("%(?<!mailto:)\b([a-z0-9A-Z.]+)@usc.edu(?!</a>)%", 
                          "<a href='mailto:$1@usc.edu'>$1@</a>", $content);
  return $content;
}

add_filter( 'the_content', 'nonbreaking_hyphens');
function nonbreaking_hyphens($content) {
  $content = str_replace("&ndash;", "&#8209;", $content);
  $content = str_replace(json_decode('"\u2013"'), "&#8209;", $content);
  return $content;
}

add_shortcode('noside', 'noside_func');
function noside_func() {
  return "<style type='text/css'>#sidebar {display:none;} #main {width:auto; float:none;}</style>";
}

add_shortcode('spoiler', 'spoiler_func');
function spoiler_func($atts, $content) {
  return "<span class='spoiler'>$content</span>";
}

add_shortcode('comment', 'comment_func');
function comment_func($atts, $content) {
  return "";
}

add_shortcode('highlight', 'highlight_func');
function highlight_func($atts, $content) {
  return "<div class='highlight'>$content
<script type='text/javascript'>parentPreToCM();</script>
</div>";
}

function my_theme_add_editor_styles() {
   add_editor_style( 'custom-editor-style.css' );
}
add_action( 'init', 'my_theme_add_editor_styles' );

require_once("/home/parallel05/www/docs/websheets/auth.php");
require_once("websheets.php");
require_once("codedrop-adaptor.php"); // note, this depends on websheets.js
require_once("visualize.php");

