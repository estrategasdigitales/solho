<?php

$id = apply_filters( 'wpml_object_id', 5, 'post', TRUE  );
$page_home = get_post( $id );

$content = apply_filters('the_content', $page_home->post_content);
//$content = str_replace(']]>', ']]&gt;', $content);

$text = get_post_meta($page_home->ID, 'wpcf-texto-home2', TRUE );
$text = apply_filters('the_content', $text);

$image_url = get_post_meta($page_home->ID, 'wpcf-imagen-home2', TRUE );



if (function_exists('is_handheld') and is_handheld()) {

    $content = apply_filters('the_content', $content);
    //$content = str_replace(']]>', ']]&gt;', $content);
}

?>

<section class="home2 section" id="home2">

    <div class="home2-wrapper row">

        <div class="sz_text col-md-5 offset-md-1">
            <?php 
                if (function_exists('is_handheld') and is_handheld()) {
                    echo $content;
                }
            ?>

            <?php echo $text ?>
        </div>

        <div class="featured col-md-6">

            <?php if($image_url) { ?>
                <img src="<?php echo $image_url ?>" alt="Imagen segunda sección" class="img-fluid">
            <?php } ?>
        </div>

    </div>

</section>