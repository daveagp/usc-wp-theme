<?php
if ($WS_AUTHINFO["error_plaintext"] != "") {
   $usc_auth_info = "<span><i>".$WS_AUTHINFO["error_plaintext"]."</i></span>";
}
else {
   $authp = $WS_AUTHINFO["logged_in"] ? 'logout' : 'Google';
   $text = $WS_AUTHINFO["logged_in"] ?
      ("Logout from <b>".$WS_AUTHINFO['username']."</b>")
      : 'Login to @usc account via Google';
   $usc_auth_info =
      "<a class='usc-auth-status' " .
      "href=\"javascript:websheets.auth_reload('$authp')\">$text</a>";
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
 <head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <link rel="profile" href="http://gmpg.org/xfn/11" />
    <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
    <title><?php bloginfo( 'name' ); ?><?php wp_title( '&mdash;' ); ?></title>

    <?php if ( is_singular() && get_option( 'thread_comments') ) wp_enqueue_script( 'comment-reply' ); ?>
    <?php wp_head(); ?>

   <meta name="viewport" content="width=device-width, initial-scale=0.9, user-scalable=no" />

<?php $themedir =  get_template_directory_uri(); ?>

   <script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>

   <link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>?6" />
   <script src="<?php echo $themedir; ?>/js/jquery-scrolltofixed-min.js"></script>
   <script src="<?php echo $themedir; ?>/js/codedrop.js?24"></script>
   <script src="<?php echo $themedir; ?>/js/expand.js"></script>
   <script src="<?php echo $themedir; ?>/js/highlight.js?1"></script>
   <script src="<?php echo $themedir; ?>/js/beginlec.js"></script>

   <script type="text/javascript">
     jQuery(document).ready(function() {
         jQuery("#sidebar").scrollToFixed( { 
            marginTop: <?php echo ( is_admin_bar_showing() ? 70+32 : 70); ?>,
            fixed: function() { jQuery(this).css("box-sizing", "inherit"); },
     unfixed: function() { jQuery(this).css("box-sizing", "border-box"); }
         });
     });
     </script>
   
<style type="text/css">
#logo_wrapper #logo {
    background: #990000 url("<?php echo $themedir; ?>/img/logo.gif") no-repeat;
}
header {
    background: url("<?php echo $themedir; ?>/img/bg.jpg");
}
</style>

  </head>
  <body <?php body_class(); ?>>
    <div id="top_bar" class="noprint">
      <div class="center">
	<div id="logo_wrapper">
	  <a href="http://usc.edu"><div id="logo"></div></a>
	</div>
      </div>
    </div>
    <header class="noprint">
      <div class="center">
<h1>
        <a style="border: none; color: white;" href="<?php echo esc_url( home_url( '/' ) ); ?>">
	  <span><?php bloginfo( 'name' ); ?></span>
	  <?php bloginfo( 'description' ); ?>
</a>
	</h1>
      </div>
    </header>
    
    <div id="content" class="center">
      <div class="noscreen">
        <h1>
	  <?php bloginfo( 'name' ); ?>:
	  <?php bloginfo( 'description' ); ?>
	</h1>
      </div>
      <div id="main">
   <div style="font-size:12px" class="noprint">
	<span class="mobileonly-inline">
	  <a href="#navigation" style="float:left">Navigation menu</a>
	</span>
            <span style="float:right"> <?php echo $usc_auth_info; ?> </span>
</div><div style='clear:both' class="noprint"></div>
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
          <!--<div <?php post_class(); ?>>-->
            <!--<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>-->
	    <h2><?php the_title(); ?></h2> 
            <?php the_content(); ?>
            <?php if ( !is_singular() && get_the_title() == '' ) : ?>
              <a href="<?php the_permalink(); ?>">(more...)</a>
            <?php endif; ?>
            <?php if ( is_singular() ) : ?>
              <div class="pagination"><?php wp_link_pages(); ?></div>
            <?php endif; ?>
<!--            <div class="clear"> </div> -->
      <!--</div><!-- post_class() -->
          <?php if ( is_singular() ) : ?>
            <div class="meta">
<!--              <p>Posted by <?php the_author_posts_link(); ?>
              on <a href="<?php the_permalink(); ?>"><?php the_date(); ?></a>
              in <?php the_category( ', ' ); ?><?php the_tags( ', ' ); ?></p> -->
            </div><!-- meta -->
            <?php comments_template(); ?>
          <?php endif; ?>
        <?php endwhile; else: ?>
          <div class="hentry"><h2>Sorry, the page you requested cannot be found</h2></div>
        <?php endif; ?>
        <?php if ( is_active_sidebar( 'widgets' ) ) : ?>
          <div class="widgets"><?php dynamic_sidebar( 'widgets' ); ?></div>
        <?php endif; ?>
        <?php if ( is_singular() || is_404() ) : ?>
<!--          <div class="left"><a href="<?php bloginfo( 'url' ); ?>">&laquo; Home page</a></div> -->
        <?php else : ?>
<!--          <div class="left"><?php next_posts_link( '&laquo; Older posts' ); ?></div>
          <div class="right"><?php previous_posts_link( 'Newer posts &raquo;' ); ?></div>-->
        <?php endif; ?>
      </div><!-- main -->

    <div id="sidebar" style="z-index: 1000; box-sizing: border-box;" class="noprint">
      <nav id="navigation">
        <?php 
wp_nav_menu( array( 'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s<li>'.$usc_auth_info.'</li></ul>' ) );
//	wp_nav_menu(); 
        ?>
      </nav>
    </div>

       <div class="clear"> </div
<footer>
  <div class="left">&nbsp;</div>
  <div class="right">&nbsp;</div>
  <div class="clear"></div>
  </footer>

    </div><!-- content -->
    <?php wp_footer(); ?> 
  </body>
</html>
