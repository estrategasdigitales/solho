<?php
/**
 * Ajax Search Widget
 *
 * @author YITH
 * @package YITH WooCommerce Ajax Search Premium
 * @version 1.2
 */

if ( !defined( 'YITH_WCAS' ) ) { exit; } // Exit if accessed directly

if( !class_exists( 'YITH_WCAS_Ajax_Search_Widget' ) ) {
    /**
     * YITH WooCommerce Ajax Navigation Widget
     *
     * @since 1.0.0
     */
    class YITH_WCAS_Ajax_Search_Widget extends WP_Widget {
        /**
         * constructor
         *
         * @access public
         */
        function __construct() {

            /* Widget variable settings. */
            $this->woo_widget_cssclass    = 'woocommerce widget_product_search yith_woocommerce_ajax_search';
            $this->woo_widget_description = __( 'An Ajax Search box for products only.', 'yith-woocommerce-ajax-search' );
            $this->woo_widget_idbase      = 'yith_woocommerce_ajax_search';
            $this->woo_widget_name        = __( 'YITH WooCommerce Ajax Product Search', 'yith-woocommerce-ajax-search' );

            /* Widget settings. */
            $widget_ops = array( 'classname'   => $this->woo_widget_cssclass,
                                 'description' => $this->woo_widget_description
            );

            /* Create the widget. */
            parent::__construct( 'yith_woocommerce_ajax_search', $this->woo_widget_name, $widget_ops );
        }


        /**
         * widget function.
         *
         * @see WP_Widget
         * @access public
         * @param array $args
         * @param array $instance
         * @return void
         */
        function widget( $args, $instance ) {

            extract($args);

            $title = isset( $instance['title'] ) ? $instance['title'] : '';
            $title = apply_filters('widget_title', $title, $instance, $this->id_base);


            $template = (isset( $instance['template'] ) &&   $instance['template']) ? 'template=wide' : '';
            $filters_above = (isset( $instance['filters_above'] ) && $instance['filters_above']) ? 'class=filters-above' : '';

            echo $before_widget;

            if ($title) echo $before_title . $title . $after_title;

            echo do_shortcode('[yith_woocommerce_ajax_search '.$template.' '.$filters_above.']');

            echo $after_widget;
        }

        /**
         * update function.
         *
         * @see WP_Widget->update
         * @access public
         * @param array $new_instance
         * @param array $old_instance
         * @return array
         */
        function update( $new_instance, $old_instance ) {
            $instance['title'] = strip_tags(stripslashes($new_instance['title']));
            $instance['template'] = isset( $new_instance['template'] ) ? 1 : 0;
            $instance['filters_above'] = isset( $new_instance['filters_above'] ) ? 1 : 0;
            return $instance;
        }

        /**
         * form function.
         *
         * @see WP_Widget->form
         * @access public
         * @param array $instance
         * @return void
         */
        function form( $instance ) {

            $defaults = array(
                'title'            => '',
                'template'         => 0,
                'filters_above'    => 0
            );

            $instance = wp_parse_args( (array) $instance, $defaults );

            ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woocommerce' ) ?></label>
                <input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('template'); ?>"> <?php _e( 'Template wide', 'yith-woocommerce-ajax-search' ); ?></label>
                    <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'template' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'template' ) ); ?>" value="1" <?php checked( $instance['template'], 1) ?> />

            </p>
            <p>
                <label for="<?php echo $this->get_field_id('filters_above'); ?>"> <?php _e( 'Filters above', 'yith-woocommerce-ajax-search' ); ?></label>
                <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'filters_above' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'filters_above' ) ); ?>" value="1" <?php checked( $instance['filters_above'], 1) ?> />

            </p>
        <?php
        }
    }
}