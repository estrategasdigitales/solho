<?php
function add_order_carrier_status_column( $columns ) {
    $new_columns = [];

    foreach ($columns as $column_name => $column_info) {
        $new_columns[ $column_name ] = $column_info;

        if ($column_name === 'order_status') {
            $new_columns['order_carrier_status'] = __( 'Tracking', ENVIAYA_PLUGIN );
        }
    }

    return $new_columns;
}

add_filter( 'manage_edit-shop_order_columns', 'add_order_carrier_status_column', 20 );

function add_order_carrier_status_column_data( $column ) {
    global $post, $wpdb;

    if ( $column === 'order_carrier_status' ) {
        $states = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'enviaya_shipment WHERE order_id = '.$post->ID.' ORDER BY id DESC;');
        echo isset($states[0]) && !empty($states[0]->shipment_status) ? $states[0]->shipment_status : 'None';
    }
}
add_action( 'manage_shop_order_posts_custom_column', 'add_order_carrier_status_column_data' );

function enviaya_delivery_status($data = null){
    if(!(is_checkout())){
        global $wpdb;

        $settings = EYHelper::settings();
        $request = wp_remote_get( content_url().'/plugins/enviaya-for-woocommerce/lang/en_US.json');
        $lang = json_decode($request['body']);
        $order_id = $data->get_order_number();
        $qry = "SELECT * FROM {$wpdb->prefix}".PREFIX."_shipment WHERE order_id = {$order_id} ORDER BY id DESC;";
        $states = $wpdb->get_results( $qry );

        if(isset($states[0]) && isset($states[0]->enviaya_shipment_number)){
            $number_old = $states[0]->enviaya_shipment_number;
        }

        $number = !empty($number_old) ? $number_old : null;

        if(!empty($number)){
            $url = 'https://'.$lang->api_domain.'/api/v1/trackings';
            $options = array(
                'http' => array(
                    'header'  => "Content-Type: application/json",
                    'method'  => 'POST',
                    'content' => '{
                        "api_key":"'.$settings['api_key'].'",
                        "shipment_number": "'.$number.'"
                    }',
                )
            );

            $context  = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);
            $result_array = json_decode($result);
            $checkpoints = isset($result_array->checkpoints) ? $result_array->checkpoints : null;
        }

        if(!empty($checkpoints)) {
            echo '<div id="timeline">';

            foreach ($checkpoints as $key => $checkpoint) {
                $shipment_date = $checkpoint->date;
                $shipment_date_new = date("d/m/Y G:i", strtotime($shipment_date));

                $checkpoint->date !== null ? $checkpoint_date = '<span>'.$shipment_date_new.' hrs</span>' : $checkpoint_date = '';
                $checkpoint->description !== null ? $checkpoint_description = $checkpoint->description : $checkpoint_description = '';
                $checkpoint->country_code !== null ? $checkpoint_country_code = $checkpoint->country_code : $checkpoint_country_code = '';
                $checkpoint->postal_code !== null ? $checkpoint_postal_code = $checkpoint->postal_code : $checkpoint_postal_code = '';
                $checkpoint->city !== null ? $checkpoint_city = $checkpoint->city : $checkpoint_city = '';
                $checkpoint->comments !== null ? $checkpoint_comments = $checkpoint->comments : $checkpoint_comments = '';

                $icon = '<div class="timeline-icon"><i class="fas fa-arrow-up"></i></div>';

                if(!next($checkpoints)) {
                    $icon = '<div class="timeline-icon start"><i class="fas fa-file"></i></div>';
                }

                $timeline_top = '<div class="timeline-top">';

                if($checkpoint->description == 'Delivered - Signed for by') {
                    $icon = '<div class="timeline-icon end"><i class="fas fa-check"></i></div>';
                    $timeline_top = '<div class="timeline-top green">';
                }

                $checkpoint_array = array(
                    $checkpoint_country_code,
                    $checkpoint_postal_code,
                    $checkpoint_city,
                    $checkpoint_comments
                );

                $checkpoint_str = implode(', ', array_filter($checkpoint_array));
                $checkpoint_info = '<div class="timeline-item">'.$icon.'
                                                <div class="timeline-content timeline-right">
                                                '.$timeline_top .$checkpoint_description.'
                                                </div></div>
                                                <div class="timeline-content">
                                                <div class="timeline-top">
                                                '.$checkpoint_date.'
                                                </div>
                                                <div class="timeline-bottom">' .$checkpoint_str.'</div>
                                                </div></div>';

                echo $checkpoint_info;
            };

            echo '</div>';
        }
    }
}

add_action( 'woocommerce_order_details_after_customer_details' , 'enviaya_delivery_status');
