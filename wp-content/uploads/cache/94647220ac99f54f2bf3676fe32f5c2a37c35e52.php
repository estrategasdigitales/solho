<?php

//$page = get_page_by_path( 'home' );
        // ICL_LANGUAGE_CODE;
$id = apply_filters( 'wpml_object_id', 5, 'post', TRUE  );
$page = get_post( $id );

$video_url = get_post_meta($page->ID, 'wpcf-video-home', TRUE );



if (function_exists('is_handheld') and !is_handheld()) {

    $content = apply_filters('the_content', $page->post_content);
    //$content = str_replace(']]>', ']]&gt;', $content);
}

?>


<section class="home section" id="home">

<?php
if ( function_exists('is_handheld') AND is_handheld() ) {
$image_url = get_post_meta( $page->ID, 'wpcf-fondo-home', TRUE);

if($image_url) { ?>
    <div class="video-image" style="background-image: url('<?php echo $image_url ?>');"></div>
<?php
}
?>


<?php } else { ?>
<!--video autoplay loop-->
<video autoplay muted loop preload="auto" ng-src="<?php echo $video_url ?>">
<source src="<?php echo $video_url ?>" type="video/mp4">
Tu navegador no soporta video HTML5.
</video>
<?php } ?>



<div class="logo-menu">

<img alt="Logo footer" src="<?= App\asset_path('images/logo_doporto_capital.svg'); ?>">

<?php if (function_exists('is_handheld') and !is_handheld()) { ?>
    <div class="content col-md-5 offset-md-1">
        <?php echo $content ?>
    </div>
<?php } ?>

<a href="#home2" class="scrollTo"><i class="fas fa-arrow-down"></i></a>
</div>


</section>