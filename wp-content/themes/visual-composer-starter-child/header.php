<?php
/**
 * Header
 *
 * @package WordPress
 * @subpackage Visual Composer Starter
 * @since Visual Composer Starter 1.0
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<?php visualcomposerstarter_hook_after_head(); ?>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">

  
  <script defer src="https://use.fontawesome.com/releases/v5.0.9/js/all.js" integrity="sha384-8iPTk2s/jMVj81dnzb/iFR2sdA7u06vHJyyLlAd4snFpCl/SnyUjRrbdJsw1pGIl" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD-lxgRcdbXIZhWAJgoHigRpB_8Jezv0lg"></script>

 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script> -->
  <script
  src="https://code.jquery.com/jquery-3.3.1.js"
  integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60="
  crossorigin="anonymous"></script>
  <script
  src="<?php echo get_site_url(); ?>/wp-content/themes/visual-composer-starter-child/js/scripts.js"></script>
<script src="https://kit.fontawesome.com/d718b59fa5.js" crossorigin="anonymous"></script>

	<?php wp_head() ?>
</head>
<body <?php body_class(); ?>>
<header id="header">
  <nav class="navbar navbar-default" role="navigation">

   

    <div class="superioruno">
      <div class="container">
        
        <div class="col-md-6 col-xs-12 logogen">
          <a class="navbar-brand" href="<?php echo site_url(); ?>"><img src="<?php echo site_url(); ?>/wp-content/themes/visual-composer-starter-child/img/logo.png"></a>
        </div>
        <div class="col-md-6 col-xs-12 text-right iconossuperi"> 
          <div class="col-md-9 col-sm-6 text-right">
            <?php echo do_shortcode('[yith_woocommerce_ajax_search]');?> 
          </div>
        <div class="col-md-3 col-sm-6" style="padding: 0px">
          <?php global $woocommerce; ?>
           <a class="carrosuperior" href="<?php echo $woocommerce->cart->get_cart_url(); ?>"
          title="<?php _e('Cart View', 'woothemes'); ?>">
          <img src="<?php echo get_site_url(); ?>/wp-content/themes/visual-composer-starter-child/img/carrito.png">
          </a>
          
          <a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php _e( 'View your shopping cart' ); ?>">( <?php echo sprintf ( _n( '%d', '%d', WC()->cart->get_cart_contents_count() ), WC()->cart->get_cart_contents_count() ); ?> )</a>


         <?php if ( is_user_logged_in() ) { ?>
  <a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>" title="<?php _e('My Account','woothemes'); ?>"><?php _e('Mi cuenta','woothemes'); ?></a>
 <?php } 
 else { ?>
  <a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>" title="<?php _e('Login / Register','woothemes'); ?>"><?php _e('Login','woothemes'); ?></a>
 <?php } ?>


          </div>
        </div>   
      </div>  
    </div>
    <!-- <div class="contenedorsuperior2">
       <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>                        
          </button>
        </div>
        
      <div class="collapse navbar-collapse" id="myNavbar">
        <?php wp_nav_menu( array( 'theme_location' => 'primary','container'=>'ul', 'menu_class'=>'nav navbar-nav navbar-center' ) ); ?>
       
      </div>
      </div>
    </div> -->
</nav>
    
  </header>
