<?php

//$page_trayectoria = get_page_by_path( 'servicios' );
$id_servicios = apply_filters( 'wpml_object_id', 14, 'post', TRUE  );
$page_servicios = get_post( $id_servicios );


$title = apply_filters('the_title', $page_servicios->post_title);

$content = apply_filters('the_content', $page_servicios->post_content);
//$content = str_replace(']]>', ']]&gt;', $content);


$args = array(
	'sort_order' => 'menu_order',
	'hierarchical' => 1,
	'child_of' => $id_servicios,
	'parent' => -1,
	'exclude_tree' => '',
	'number' => '',
	'offset' => 0,
	'post_type' => 'page',
	'post_status' => 'publish'
); 
$pages = get_pages($args);


?>

<section class="servicios section" id="servicios">


	<div class="row align-wrapper">

		<div class="col-md-6">	
			<h1> <?php echo $title ?> </h1>
		</div>

		<div class="col-md-6 mid-blue-div">
			<?php echo $content ?>
		</div>

	</div>


	<div class="owl-carousel owl-theme">

		<?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
			
			<div class="item">
				<div class="featured-image">
					<?php $image_url = get_the_post_thumbnail( $page->ID, 'medium', ['class'=>'img-fluid'] ) ?>
					<?php echo $image_url ?>
				</div>
				<div class="content sz-owl-content">
					<?php
					$link = get_permalink($page->ID);
					?>
					<h3><?php echo apply_filters('the_title', $page->post_title) ?></h3>
					<?php //echo apply_filters('the_content', wp_trim_words($page->post_content, 11)) ?>


					<!--a class="readmore" href="<?php echo $link ?>">Leer más ></a-->
				</div>
				
			</div>
		<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
	</div>

</section>