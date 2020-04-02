<?php


$labels_es = [
    $correo = "Correo electrónico",
    $nombre = "Nombre completo",
    $asunto = "Asunto",
];
$labels_en = [
    $correo = "Email",
    $nombre = "Name",
    $asunto = "Subject",
];
$label_var = 'labels_'.ICL_LANGUAGE_CODE;


$contacto_default_id = 65;

$id_contacto = apply_filters( 'wpml_object_id', $contacto_default_id, 'post', TRUE  );
$page_contacto = get_post( $id_contacto );

$title = apply_filters('the_title', $page_contacto->post_title);


?>

<footer class="content-info section fp-auto-height">


  <div class="row first-row">

    <?php if( function_exists('is_handheld') AND !is_handheld() ) { ?>
    <div class="offset-md-1 col-md-2">
    <?php
          wp_nav_menu(
          array(
          'menu'              => 'menu-principal',
          'theme_location'    => 'primary',
          'depth'             => 0,
          'container'         => 'div',
          'container_class'   => 'menu-izquierdo',
          'container_id'      => 'mainMenu',
          'menu_class'        => 'nav navbar-nav',
          'fallback_cb'       => '\lib\wp_bootstrap_navwalker::fallback',
          'walker'            => new wp_bootstrap_navwalker())
          ); ?>
    </div>
    <?php } ?>

    <div class="col-md-1">
      <?php (dynamic_sidebar('sidebar-footer3')); ?>
    </div>
    
    <div class="col-md-3">

      <div class="contacto">

        <p><?php echo $title ?></p>

        <form id="theForm" class="simform" autocomplete="off" method="post" accept-charset="utf-8" enctype="multipart/form-data">
          <div class="simform-inner">
            <ol class="questions">

              <?php
              $output = "";
              for ($i=0; $i < sizeof($$label_var) ; $i++) {

                $output = "<li>
                
                <input id='q" .$i. "' class='ql' name='q" .$i. "' type='text' placeholder='".$$label_var[$i]."'>
                

                </li>";

                echo $output;

              }
              ?>
            </ol><!-- /questions -->

            <button class="submit" type="submit">Send answers</button>
            <div class="controls">
              <button class="next <?php echo is_handheld() ? 'show' : '' ?>">
                <i class="fas fa-arrow-right"></i>
              </button>
              <div class="progress"></div>
              <span class="number">
                <span class="number-current"></span>
                <span class="number-total"></span>
              </span>
              <span class="error-message"></span>
            </div><!-- / controls -->

          </div><!-- /simform-inner -->
          <span class="final-message"></span>

        </form><!-- /simform -->

      </div>

    </div>

    <div class="offset-2 offset-md-1 col-md-3">
      <?php (dynamic_sidebar('sidebar-footer2')); ?>
    </div>

  </div>

  <div class="row second-row">


        <div class="col-5 offset-md-3 col-md-2">
              <img class="logo-menu img-fluid" alt="Logo menu" src="<?= App\asset_path('images/logo_doporto_consortium.svg'); ?>">
        </div>
                
        <div class="col-7 padding">
              &reg; Pr1me Capital, Derechos Reservados <?php echo date("Y"); ?>
        </div>

    
  </div>

</footer>
