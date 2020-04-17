
<?php

$casos_id = 25;
//$id_inversiones = apply_filters( 'wpml_object_id', $investment_parent_ID, 'post', TRUE  );
$page_casos = get_post( $casos_id );


$title = apply_filters('the_title', $page_casos->post_title);
$content = apply_filters('the_content', $page_casos->post_content);


$content = str_replace(']]>', ']]&gt;', $content);

$args = array(
	'sort_order' => 'menu_order',
	'hierarchical' => 1,
	'child_of' => $casos_id,
	'parent' => -1,
	'exclude_tree' => '',
	'number' => '',
	'offset' => 0,
	'post_type' => 'page',
	'post_status' => 'publish'
); 
$integrantes = get_pages($args); 

?>

<section class="casos section" id="casos">

    <div class="row align-wrapper">
        <div class="col-sm-8 col-xl-4">
            
            <div class="casos-text">
                
                <h1> <?php echo $title ?> </h1>
                
                <div class="content">
                    <?php echo apply_filters('the_content', $content) ?>
                </div>
            </div>
        </div>

        <div class="col-sm-4 offset-xl-1 col-xl-6">

            <div class="intengrantes-wrap no-gutters row">
                <?php
                $higher = 0;
                foreach($integrantes as $integrante) {

                    if( $integrante->menu_order > $higher ) {
                        $higher = $integrante->menu_order;
                    }
                }

                for ($i = 1; $i <= $higher; $i++) {

                    $is_empty = TRUE;

                    for ($i2 = 0; $i2 <= count($integrantes); $i2++) {

                        if( $integrantes[$i2]->menu_order === $i ) {

                            $data_image = get_the_post_thumbnail_url($integrantes[$i2]->ID);
                            $image_url = get_the_post_thumbnail( $integrantes[$i2]->ID, 'medium', ['class'
                                                                                                            =>'img-fluid integrante col-4 ' . $integrantes[$i2]->menu_order,
                                                                                                            'data-title' => $integrantes[$i2]->post_title,
                                                                                                            'data-content' =>  str_replace ( '"', "'",  apply_filters('the_content', $integrantes[$i2]->post_content) ),
                                                                                                            'alt' => 'Casos de éxito',
                                                                                                            'data-toggle' => "modal",
                                                                                                            'data-target' => (function_exists('is_mobile') AND is_mobile() ) ? '#modalCasos'  : '' ,
                                                                                                            'data-image' => (function_exists('is_mobile') AND is_mobile() ) ? $data_image : '' ,

                                                                                                            ] );
                            echo $image_url;
                            $is_empty = FALSE;
                            break;
                            
                        } 
                    }//end inner for

                    if( $is_empty ) {
                        ?>
                        <div class="integrante-vacia col-4">

                        </div>
                        <?php
                    }
                }//end outer for

                ?>
            </div>

        </div>
        
    </div>

</section>