<header class="banner">
  <div class="<?php echo ( function_exists('is_handheld') and !is_handheld()  ) ? 'container' : '' ?>">

    <?php if( function_exists('is_handheld') AND is_handheld() ) { ?>

      <div class="pos-f-t">

        <nav class="navbar navbar-dark-blue navbar-dark bg-dark">

          <img class="logo-menu" alt="Logo menu" src="<?= App\asset_path('images/logo-menu.png'); ?>">

          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navBarToggleMenu" aria-controls="navBarToggleMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
        </nav>

        <div class="collapse" id="navBarToggleMenu">
          <div class="bg-dark p-4 navbar-dark-blue">
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

              <?php (dynamic_sidebar('sidebar-footer3')); ?>

          </div>
        </div>

      </div>

    <?php } else {
        ?>

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

      <?php (dynamic_sidebar('sidebar-footer3')); ?>


      


      <?php } ?>


  </div>
</header>
