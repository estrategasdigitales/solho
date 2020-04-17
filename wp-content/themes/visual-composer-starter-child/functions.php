<?php
add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );

function enqueue_parent_styles() {
wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
 }

add_filter( 'woocommerce_product_tabs', 'wcs_woo_remove_reviews_tab', 98 );
    function wcs_woo_remove_reviews_tab($tabs) {
    unset($tabs['reviews']);
    return $tabs;
}
 /* Add Show All Products to Woocommerce Shortcode */
function woocommerce_shortcode_display_all_products($args)
{
 if(strtolower(@$args['post__in'][0])=='all')
 {
  global $wpdb;
  $args['post__in'] = array();
  $products = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE `post_type`='product'",ARRAY_A);
  foreach($products as $k => $v) { $args['post__in'][] = $products[$k]['ID']; }
 }
 return $args;
}
add_filter('woocommerce_shortcode_products_query', 'woocommerce_shortcode_display_all_products');
// Change 'add to cart' text on archive product page
add_filter( 'woocommerce_product_add_to_cart_text', 'bryce_archive_add_to_cart_text' );
function bryce_archive_add_to_cart_text() {
        return __( 'AGREGAR', 'your-slug' );
}
add_filter( 'woocommerce_get_image_size_gallery_thumbnail', function( $size ) {
return array(
'width' => 500,
'height' => 500,
'crop' => 0,
);
} );

add_filter( 'yith_wcas_submit_as_input', '__return_false' );
add_filter( 'yith_wcas_submit_label', 'my_yith_wcas_submit_label' );
function my_yith_wcas_submit_label( $label ) { 
    return '<i class="fa fa-search"></i>' . $label; 
}
add_filter( 'woocommerce_product_tabs', 'yikes_remove_description_tab', 20, 1 );

function yikes_remove_description_tab( $tabs ) {

	// Remove the description tab
    if ( isset( $tabs['description'] ) ) unset( $tabs['description'] );      	
    if ( isset( $tabs['additional_information'] ) ) unset( $tabs['additional_information'] );     
    return $tabs;
}



?>