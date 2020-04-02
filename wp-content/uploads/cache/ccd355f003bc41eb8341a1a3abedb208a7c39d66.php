
<?php

$id_alianzas = apply_filters( 'wpml_object_id', 30, 'post', TRUE  );
$page_alianzas = get_post( $id_alianzas );


$title = apply_filters('the_title', $page_alianzas->post_title);
$content = apply_filters('the_content', $page_alianzas->post_content);


$content = str_replace(']]>', ']]&gt;', $content);

?>

<section class="alianzas section" id="alianzas">

    <div class="row align-wrapper">

        <div class="col-md-6">	
            <h1> <?php echo $title ?> </h1>
        </div>

        <div class="col-md-6 mid-blue-div">

        </div>

    </div>
    
    <div class="row align-wrapper">
        <div class="offset-md-2 col-md-4 text-md-left">
            <?php echo $content ?>


        </div>

        
    </div>

 


</section>