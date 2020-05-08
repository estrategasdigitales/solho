<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.2.0
 */

// var_dump($available_methods);


include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
$settings = EYHelper::settings();

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function is_excluded_zone($package) {
    $settings = EYHelper::settings();
    $curZone = $package["destination"];
    $zones_data = json_decode($settings['excluded_zones_data']);
    if(!is_array($zones_data) && !is_object($zones_data)){
        return false;
    }
    foreach ($zones_data as $zone) {
        $subZone = explode(',', $zone->regions);
        $country = isset($subZone[0]) && explode(':', $subZone[0])[0] === 'country' ? explode(':', $subZone[0])[1] : false;
        $state = isset($subZone[1]) && explode(':', $subZone[1])[0] === 'state' ? explode(':', $subZone[1])[2] : false;
        $zip = explode(',', $zone->zips);

        if (
            (!$state && $country && $curZone['country'] === $country) ||
            ($state && $country && $curZone['country'] === $country && $curZone['state'] === $state) ||
            (!empty($zip) && in_array($curZone['postcode'], $zip))
        ) {
            return true;
        }
    }

    return false;
}
?>
<tr class="shipping">
    <th><?php echo wp_kses_post( $package_name ); ?></th>
    <td data-title="<?php echo esc_attr( $package_name ); ?>">
        <?php if ( 1 <= count( $available_methods ) ) : ?>
            <?php
            $header_t = '';
            $group = $settings['group_by_carrier'];
            ?>

            <ul id="shipping_method">

                <?php if ($settings['default_or_advanced_design'] == '0') {
                    foreach ( $available_methods as $method ) : ?>
                        <li>
                            <?php
                            printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />
							<label for="shipping_method_%1$d_%2$s">%5$s</label>',
                                $index, sanitize_title( $method->id ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ), wc_cart_totals_shipping_method_label( $method ) );

                            do_action( 'woocommerce_after_shipping_rate', $method, $index );
                            ?>
                        </li>
                    <?php endforeach;
                } else { ?>
                    <?php if ($group == 1) { ?>
                        <?php
                        if ( is_plugin_active( 'enviaya-for-woocommerce/enviaya-for-woocommerce.php' ) && !is_excluded_zone($package) ) {
                            $list = array();
                            $tp = 0;
                            foreach ( $available_methods as $key => $method ) {
                                $tp = $key;
                                $list[] = $method;
                            }

                            $count = count($list);
                            for ($i = 0; $i < $count; $i++) {
                                for ($j = $i; $j < $count; $j++) {
                                    $label_advanced1 = $list[$i]->get_meta_data();
                                    $label_advanced2 = $list[$j]->get_meta_data();
                                    if (isset($label_advanced1['delivery_cost']) && isset($label_advanced2['delivery_cost']) && $label_advanced1['delivery_cost'] > $label_advanced2['delivery_cost']) {
                                        $tp = $list[$i];
                                        $list[$i] = $list[$j];
                                        $list[$j] = $tp;
                                    }
                                }
                            }

                            $arrier_list = array();
                            foreach ( $list as $i => $method ) {
                                $header = $method->get_meta_data();

                                if (isset($header['carrier_name']) && !in_array($header['carrier_name'] ,$arrier_list)) {
                                    $arrier_list[] = $header['carrier_name'];
                                }
                            }


                            foreach ( $available_methods as $method ) {
                                $tp = $method->id;
                                $label_advanced = $available_methods[$tp]->get_meta_data();

                                if(!isset($label_advanced['label_advanced'])){
                                    printf('<li><input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr(sanitize_title($method->id)), esc_attr($method->id), checked($method->id, $chosen_method, false));
                                    printf('<label for="shipping_method_%1$s_%2$s">%3$s</label></li>', $index, esc_attr(sanitize_title($method->id)), wc_cart_totals_shipping_method_label($method));
                                }

                            }


                            foreach ( $arrier_list as $carrier ) {
                                echo '<h3 class=enviaya_carrier>'.strtoupper($carrier).'</h3>';
                                foreach ( $list as $method ) {
                                    $header = $method->get_meta_data();

                                    if (isset($header['carrier_name']) && $header['carrier_name'] == $carrier) {
                                        if ($method->id == 'standard_flat_rate' || $method->id == 'express_flat_rate') {
                                            $temp = $header['label_advanced'];
                                        } elseif (!empty($label_advanced['label_advanced'])) {
                                            $temp = base64_decode($header['label_advanced']);
                                        } else {
                                            $temp = $available_methods[$tp]->get_label();
                                        }

                                        var_dump($header['label_advanced']);

                                        echo '<li><input type="radio" style="" name="shipping_method['.$index.']" data-index="'.$index.'" id="shipping_method_'.$index.'_'.sanitize_title( $method->id ).'" value="'.esc_attr( $method->id ).'" class="shipping_method" '.checked( $method->id, $chosen_method, false ).' />
										<label for="shipping_method_'.$index.'_'.sanitize_title( $method->id ).'">'.$temp.'</label></li>';
                                    }
                                }
                            }
                        } else {
                            foreach ( $available_methods as $method ) {
                                printf('<input type="radio" style="" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />
							        <label for="shipping_method_%1$d_%2$s">%5$s</label>',
                                    $index, sanitize_title($method->id), esc_attr($method->id), checked($method->id, $chosen_method, false), wc_cart_totals_shipping_method_label($method));
                            }
                        }
                        do_action( 'woocommerce_after_shipping_rate', $method, $index );
                        ?>
                    <?php } else {
                        foreach ( $available_methods as $method ) {
                            $tp = $method->id;
                            $max = $method->cost;
                            $label_advanced = $available_methods[$tp]->get_meta_data();

                            if(isset($label_advanced['label_advanced'])){
                                $temp = base64_decode($label_advanced['label_advanced']);
                                printf('<li><input type="radio" name="shipping_method['.$index.']" data-index="'.$index.'" id="shipping_method_'.$index.'_'.sanitize_title( $available_methods[$tp]->id ).'" value="'.esc_attr( $available_methods[$tp]->id ).'" class="shipping_method" '.checked( $available_methods[$tp]->id, $chosen_method, false ).' />
						               <label for="shipping_method_'.$index.'_'.sanitize_title( $available_methods[$tp]->id ).'">'.$temp.'</label></li>');
                            } else {
                                printf('<li><input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr(sanitize_title($method->id)), esc_attr($method->id), checked($method->id, $chosen_method, false));
                                printf('<label for="shipping_method_%1$s_%2$s">%3$s</label></li>', $index, esc_attr(sanitize_title($method->id)), wc_cart_totals_shipping_method_label($method));
                            }
                        }
                    }

                }?>
            </ul>
        <?php elseif ( 1 === count( $available_methods ) ) :  ?>
            <?php
            $method = current( $available_methods );
            printf( '%3$s <input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d" value="%2$s" class="shipping_method" />', $index, esc_attr( $method->id ), base64_encode(base64_decode(wc_cart_totals_shipping_method_label($method), true)) === wc_cart_totals_shipping_method_label($method) ? base64_decode(wc_cart_totals_shipping_method_label( $method )) : wc_cart_totals_shipping_method_label( $method ) );
            do_action( 'woocommerce_after_shipping_rate', $method, $index );
            ?>
        <?php elseif ( WC()->customer->has_calculated_shipping() ) : ?>
            <?php echo apply_filters( is_cart() ? 'woocommerce_cart_no_shipping_available_html' : 'woocommerce_no_shipping_available_html', wpautop( __( 'There are no shipping methods available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' ) ) ); ?>
        <?php elseif ( ! is_cart() ) : ?>
            <?php echo wpautop( __( 'Enter your full address to see shipping costs.', 'woocommerce' ) ); ?>
        <?php endif; ?>

        <?php if ( $show_package_details ) : ?>
            <?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
        <?php endif; ?>

        <?php if ( ! empty( $show_shipping_calculator ) ) : ?>
            <?php woocommerce_shipping_calculator(); ?>
        <?php endif; ?>
    </td>
</tr>
