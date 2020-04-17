<?php /* Template Name: Home */ ?>



<?php $__env->startSection('content'); ?>

  <?php while(have_posts()): ?> <?php (the_post()); ?>
    <?php echo $__env->make('home_partials.home', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php echo $__env->make('home_partials.home2', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php echo $__env->make('home_partials.servicios-parents', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    
    <?php echo $__env->make('home_partials.inversiones', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php echo $__env->make('home_partials.casos', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php echo $__env->make('home_partials.alianzas', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php echo $__env->renderWhen(FALSE, 'home_partials.noticias', array_except(get_defined_vars(), array('__data', '__path'))); ?>
  <?php endwhile; ?>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>