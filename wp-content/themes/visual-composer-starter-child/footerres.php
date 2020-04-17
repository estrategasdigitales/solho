<?php
/**
 * Footer
 *
 * @package WordPress
 * @subpackage Visual Composer Starter
 * @since Visual Composer Starter 1.0
 */

if ( visualcomposerstarter_is_the_footer_displayed() ) : ?>
	<?php visualcomposerstarter_hook_before_footer(); ?>
	<footer id="footer">	
		<div class="footer-bottom">
			<div class="<?php echo esc_attr( visualcomposerstarter_get_content_container_class() ); ?>">
				
				<div class="text-center">
					
						<span>2018 Copyright ópticas alef Todos los derechos reservados | sitio web realizado por <a href="http://bobo.mx/" target="_blank">bobo.mx</a> | <a href="#">Aviso de privacidad</a></span>
				

				</div>
			</div>
		</div>
	</footer>


	<?php visualcomposerstarter_hook_after_footer(); ?>
<?php endif; ?>
<?php wp_footer(); ?>

</body>
</html>




