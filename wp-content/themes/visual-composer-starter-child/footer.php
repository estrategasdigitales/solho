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
		<div class="row">
			<div class="container">
				<div class="col-md-4 text-center redesf2">
					<a href="<?php echo site_url(); ?>/aviso-de-privacidad">Aviso de privacidad</a>
				</div>
				<div class="col-md-4 text-center redesf">
					
					
					<a href=""><i class="fab fa-facebook-square" target="_blank"></i></a>
					<a href=""  target="_blank"><i class="fab fa-instagram"></i></a>
					
				</div>
				
				<div class="col-md-4 text-center redesf">
					<span>2020. Solho. Todos los derechos reservados</span>
						
				</div>
				
			</div>
		</div>
		
	</footer>








	<?php visualcomposerstarter_hook_after_footer(); ?>
<?php endif; ?>
<?php wp_footer(); ?>

</body>
</html>




