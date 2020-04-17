<?php


$investment_parent_ID = 92;

$id_inversiones = apply_filters( 'wpml_object_id', $investment_parent_ID, 'post', TRUE  );
$page_inversiones = get_post( $id_inversiones );


$title = apply_filters('the_title', $page_inversiones->post_title);
$content = $page_inversiones->post_content;
$content = apply_filters('the_content', $content);
$content = str_replace(']]>', ']]&gt;', $content);



$args = array(
	'child_of' => $id_inversiones
); 
$pages = get_pages($args);
//var_dump($args);

?>


<section class="inversiones section" id="inversiones">

    <div class="row align-wrapper">

        <div class="col-md-6">
            <h1><?php echo $title ?></h1>
        </div>
    </div>
    

    <div class="carousel-wrap">

        <div class="owl-carousel2 owl-theme">

            <?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                
                <div class="item">

                    <?php $url_inversiones = get_post_meta( $page->ID, 'wpcf-url-inversiones', TRUE ) ?>

                    <div class="featured-image">
                        <?php $image_url = get_the_post_thumbnail( $page->ID, 'w1000', ['class'=>'img-fluid', 'alt' => 'imagen de la inversión'] ) ?>
                        
                        <?php echo ( $url_inversiones ) ? '<a target="_blank" href="' . $url_inversiones .'">' : '' ?>
                        <?php echo $image_url ?>
                        <?php echo ( $url_inversiones ) ? '</a>' : '' ?>

                    </div>
                    <div class="content">
                        <h3> <?php echo apply_filters('the_title', $page->post_title) ?> </h3>
                        <?php echo apply_filters('the_content', $page->post_content) ?>
                        <p class="texto">
                            <?php $url_texto = get_post_meta( $page->ID, 'wpcf-texto-inversiones', TRUE ) ?>
                            <?php echo $url_texto ?>
                        </p>


                    </div>
                    
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

    </div>

</section>