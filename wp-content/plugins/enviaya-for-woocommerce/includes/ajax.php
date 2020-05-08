<?php

function getEnviayaProductPrice($product_id)
{
    global $wpdb;

    $qry = "SELECT meta_value FROM {$wpdb->prefix}postmeta
        WHERE post_id = {$product_id} AND meta_key = '_price'";
    $states = $wpdb->get_results( $qry );

    return $states[0]->meta_value;
}

function enviaya_carriers_logo()
{
    $settings = EYHelper::settings();

    if (isset($settings['display_carrier_logo']) && isset($settings['shipping_service_design']) &&
        isset($settings['group_by_carrier'])) {
        $rates = WC()->shipping->get_packages()[0]['rates'];
        $result['shipping_service_design'] = $settings['shipping_service_design'];
        $result['show_carrier_logo'] = $settings['show_carrier_logo'];
        $result['group_by_carrier'] = $settings['group_by_carrier'];
        if (isset($rates)) {
            foreach ($rates as $rate) {
                $result['rates'][$rate->get_meta_data()['carrier_name']][$rate->get_id()] = array (
                    'carrier_name' => $rate->get_meta_data()['carrier_name'],
                    'carrier_service_name' => $rate->get_meta_data()['carrier_service_name'],
                    'carrier_logo' => $rate->get_meta_data()['carrier_logo'],
                    'estimated_delivery' => $rate->get_meta_data()['estimated_delivery'],
                    'delivery_cost' => $rate->get_meta_data()['delivery_cost'],
                    'currency_symbol' => get_woocommerce_currency_symbol(),
                );
                $result['carrier_logos'][$rate->get_meta_data()['carrier_name']] = $rate->get_meta_data()['carrier_logo'];
            }
        };

        echo json_encode($result);
    } else {
        echo 'error';
    }
    wp_die();
}

add_action( 'wp_ajax_enviaya_carriers_logo', 'enviaya_carriers_logo' );
add_action( 'wp_ajax_nopriv_enviaya_carriers_logo', 'enviaya_carriers_logo' );

function enviaya_front_title()
{
    $settings = EYHelper::settings();

    echo ($settings['rate_on_add_to_cart'] == '1') ? $settings['title_rating_configuration_'.get_locale()] : '';
    // echo Enviaya_Shipping_Method::get_front_title();
    wp_die();
}

add_action( 'wp_ajax_enviaya_front_title', 'enviaya_front_title' );
add_action( 'wp_ajax_nopriv_enviaya_front_title', 'enviaya_front_title' );

function download_enviaya_shipment($data) {
    global $EY_lang, $wpdb;

    $order = wc_get_order($_POST['order_id']);
    $order_id = $order->get_id();

        $props = [
            'get_post'     => get_post($_POST['order_id']),
            'wc_get_order' => $order_id
        ];

        $result = EYHelper::libAPI()->create(EYHelper::create_shipment($props));
        $response2 = $result['response'];

        error_log("NEW ORDER REQUEST 1: " . json_encode($result['request']));
        logAPI(json_encode($result['request']), 'shipment_request');

        error_log("NEW ORDER RESPONSE: " . json_encode($result['response']));
        logAPI(json_encode($result['response']), 'shipment_response');

        $states_rate = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}".PREFIX."_rates WHERE 
            order_id = {$order->ID} AND carrier_service_code = '{$result['request']['carrier_service_code']}' AND 
            carrier = '{$result['request']['carrier']}';");
        $rate_id = $states_rate[0]->rate_id;
        $states0 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}".PREFIX."_rates WHERE 
            order_id = {$order->ID} AND rate_id = {$rate_id};");

        if (isset($states0[0]) && isset($response2->enviaya_shipment_number)
            && isset($response2->carrier_shipment_number)) {
            $wpdb->query("INSERT INTO {$wpdb->prefix}".PREFIX."_shipment (
                rate_id, order_id, carrier, carrier_logo_url, estimated_delivery, carrier_service_name, 
                carrier_service_code, total_amount, net_total_amount, currency, enviaya_shipment_number, 
                carrier_shipment_number, label_url, shipment_status, webhook_status
                ) VALUES (
                    '{$rate_id}', '{$order->ID}', '{$states0[0]->carrier}', '{$states0[0]->carrier_logo_url}', 
                    '{$states0[0]->estimated_delivery}', '{$states0[0]->carrier_service_name}', 
                    '{$states0[0]->carrier_service_code}', '{$states0[0]->total_amount}', 
                    '{$states0[0]->net_total_amount}', '{$states0[0]->currency}', 
                    '{$response2->enviaya_shipment_number}', '{$response2->carrier_shipment_number}', 
                    '{$response2->label_share_link}', 'Waiting', 'empty' )");
        }

        echo "<div class='view shipment' ship_num={.$response2->enviaya_shipment_number} carrier={$states0[0]->carrier} >
            <div class='wc-order-data-row wc-order-totals-items wc-order-items-editable'>
                <img src={$states0[0]->carrier_logo_url} title=".strtoupper($states0[0]->carrier)." class=shipmen_logo />
                <ul>
                    <small>{$EY_lang->service}</small>
                    <li>
                    <img class=status_image src=/wp-content/plugins/enviaya-for-woocommerce/public/img/statuses/status-1.png id=status-1 style='display: inline;'>
                    {$states0[0]->carrier_service_name} ( {$states0[0]->total_amount} {$states0[0]->currency} ) </li>
                    <small>{$EY_lang->estimated_delivery}:</small>
                    <li>{$states0[0]->estimated_delivery}</li>
                    <small>{$EY_lang->brand_name} {$EY_lang->shipment_number}</small>
                    <li>
                        <a target=_blank href=https://{$EY_lang->api_domain}/track?track_ref={$response2->enviaya_shipment_number}&show_events=true >{$response2->enviaya_shipment_number}</a>
                    </li>
                    <small>{$EY_lang->tracking_no}</small>
                    <li>
                        <a target=_blank href=https://{$EY_lang->api_domain}/track?track_ref={$response2->carrier_shipment_number}&show_events=true >{$response2->carrier_shipment_number}</a>
                    </li>
                    <a target=_blank href={$response2->label_share_link} id=download_label>
                        <button type=button>{$EY_lang->download_label}</button>
                        </a>
                    <a target=_blank href=https://{$EY_lang->api_domain}/track?track_ref={$response2->enviaya_shipment_number}&show_events=true >
                        <button type=button >{$EY_lang->track_shipment}</button>
                    </a>
                </ul>
            </div>
        </div><hr>|||";
}

add_action( 'wp_ajax_download_shipment', 'download_enviaya_shipment' );
add_action( 'wp_ajax_nopriv_download_shipment', 'download_enviaya_shipment' );

function create_enviaya_shipment($data) {
    global $EY_lang, $wpdb;

    $order_id = (string)$_POST['order_id'];
    error_log("ENVIAYA_INIT");

    $order = wc_get_order($order_id);
    $items = $order->get_items();

    $qry = "SELECT * FROM {$wpdb->prefix}woocommerce_order_items
            WHERE order_id = {$order->get_id()} AND order_item_type = 'shipping'";
    $states = $wpdb->get_results($qry);

    if (is_admin()) {
        $wcItemDataStore = new WC_Order_Item_Product_Data_Store();

        if (!$states) {
            foreach ($items as $item) {
                $wpdb->insert(
                    $wpdb->prefix . 'woocommerce_order_items', array(
                        'order_item_name' => $item->get_name(),
                        'order_item_type' => 'shipping',
                        'order_id' => $order->get_id(),
                    )
                );

                $insertID = $wpdb->insert_id;

                $itemProduct = new WC_Order_Item_Product($item->get_id());
                $itemProduct->set_id($insertID);
                $wcItemDataStore->save_item_data($itemProduct);
                $itemProduct->apply_changes();
                $wcItemDataStore->clear_cache($itemProduct);
            }

            $states = $wpdb->get_results($qry);
        }

        $rate_id = $_COOKIE['rate_id'];
        //wp_woocommerce_order_itemmeta
        $order_item_id = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_order_items
            WHERE order_id = {$order->get_id()} AND order_item_type = 'shipping'");

        $enviaya_rates = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}".PREFIX."_rates
            WHERE order_id = {$order->get_id()} AND rate_id = {$rate_id}");

        $data_item_meta = [];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'delivery_cost',
            'meta_value' => $enviaya_rates['0']->total_amount
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'estimated_delivery',
            'meta_value' => $enviaya_rates['0']->estimated_delivery
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'carrier_logo',
            'meta_value' => $enviaya_rates['0']->carrier_logo_url
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'carrier_service_code',
            'meta_value' => $enviaya_rates['0']->carrier_service_code
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'carrier_service_name',
            'meta_value' => $enviaya_rates['0']->carrier_service_name
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'carrier_name',
            'meta_value' => $enviaya_rates['0']->carrier
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'rate_id',
            'meta_value' => $enviaya_rates['0']->rate_id
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'label_advanced',
            'meta_value' => 1
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'taxes',
            'meta_value' => 'a:1:{s:5:"total";a:0:{}}'
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'total_tax',
            'meta_value' => 0
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'cost',
            'meta_value' => $enviaya_rates['0']->net_total_amount
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'instance_id',
            'meta_value' => 0
        ];

        $data_item_meta[] = [
            'order_item_id' => $order_item_id['0']->order_item_id,
            'meta_key' => 'method_id',
            'meta_value' => 'enviaya'
        ];


        foreach ($data_item_meta as $item) {
            $wpdb->query("INSERT INTO {$wpdb->prefix}woocommerce_order_itemmeta (order_item_id, meta_key, meta_value)
                        VALUES ('{$item['order_item_id']}', '{$item['meta_key']}', '{$item['meta_value']}')");
        }
    }

    $props = [
        'get_post'          => $order_id,
        'wc_get_order'      => wc_get_order($order_id),
        'rate_id'           => $_POST['rate_id'],
        'shipment_id'       => $enviaya_rates['0']->shipment_id
    ];

    $result = EYHelper::libAPI()->create(EYHelper::create_shipment($props));
    $response2 = $result['response'];

    error_log("NEW ORDER REQUEST: " . json_encode($result['request']));
    logAPI(json_encode($result['request']), 'shipment_request');

    logAPI(json_encode($response2), 'shipment_response');
    error_log("NEW ORDER RESPONSE: " . json_encode($response2));

    $rate_id = $_POST['rate_id'];

    $states0 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}".PREFIX."_rates WHERE order_id = {$order->ID} 
        AND rate_id = {$rate_id};");

    if(isset($response2->label_share_link)){
        $wpdb->query("INSERT INTO {$wpdb->prefix}".PREFIX."_shipment (
            rate_id, order_id, carrier, carrier_logo_url, estimated_delivery, carrier_service_name, carrier_service_code, 
            total_amount, net_total_amount, currency, enviaya_shipment_number, carrier_shipment_number, label_url, 
            shipment_status, webhook_status
            ) VALUES (
                '{$rate_id}', '{$order->ID}', '{$states0[0]->carrier}', '{$states0[0]->carrier_logo_url}', 
                '{$states0[0]->estimated_delivery}', '{$states0[0]->carrier_service_name}', 
                '{$states0[0]->carrier_service_code}', '{$states0[0]->total_amount}', '{$states0[0]->net_total_amount}', 
                '{$states0[0]->currency}', '{$response2->enviaya_shipment_number}', '{$response2->carrier_shipment_number}', 
                '{$response2->label_share_link}', 'Wailting', 'empty' )");

        echo "<div class='view shipment' ship_num={$response2->enviaya_shipment_number} carrier={$states0[0]->carrier} >
                <div class='wc-order-data-row wc-order-totals-items wc-order-items-editable'>
                    ".(isset($states0[0]->carrier_logo_url) ? "<img src={$states0[0]->carrier_logo_url} title=".strtoupper($states0[0]->carrier)." class=shipmen_logo >" : "")."
                    <ul>
                        ".(isset($states0[0]->carrier_service_name) ?
                            "<small>{$EY_lang->service}</small>
                            <li>
                            <img class=status_image src=/wp-content/plugins/enviaya-for-woocommerce/public/img/statuses/status-1.png id=status-1 style='display: inline'>
                            {$states0[0]->carrier_service_name} ( {$states0[0]->total_amount} {$states0[0]->currency} ) 
                            </li>" : "")."
                        ".(isset($states0[0]->estimated_delivery) ?
                            "<small>{$EY_lang->estimated_delivery}:</small>
                            <li>{$states0[0]->estimated_delivery}</li>" : "")."
                        ".(isset($response2->enviaya_shipment_number) ?
                            "<small>{$EY_lang->shipment_number}</small>
                            <li><a target=_blank href=https://{$EY_lang->api_domain}/track?track_ref={$response2->enviaya_shipment_number}&show_events=true >{$response2->enviaya_shipment_number}</a></li>" : "")."
                        ".(isset($response2->carrier_shipment_number) ? "
                        <small>{$EY_lang->tracking_no}</small>
                        <li><a target=_blank href=https://{$EY_lang->api_domain}/track?track_ref={$response2->carrier_shipment_number}&show_events=true >{$response2->carrier_shipment_number}</a></li>" : "")."
                        ".(isset($response2->label_share_link) ? "
                        <a target=_blank href={$response2->label_share_link} id=download_label><button type=button>{$EY_lang->download_label}</button></a>" : "")."
                        ".(isset($response2->enviaya_shipment_number) ? "
                        <a target=_blank href=https://{$EY_lang->api_domain}/track?track_ref={$response2->enviaya_shipment_number}&show_events=true><button type=button >{$EY_lang->track_shipment}</button></a>" : "")."
                    </ul>
                </div>
            </div>|||";
    } else {
        if(isset($response2) && isset($response2->errors) && !empty($response2->errors)) {
            if(is_array($response2->errors)){
                foreach ($response2->errors as $item) {
                    if(!empty($item)){
                        echo '<div class="updraftmessage error">'. $item .'</div>';
                    }
                }
            } else {
                echo '<div class="updraftmessage error">'. $response2->errors .'</div>';
            }

        } elseif (!isset($response2->errors)) {
            echo '<div class="updraftmessage error">'. $EY_lang->technical_error .'</div>';
        }
    }

    die();
    //}

}

add_action( 'wp_ajax_create_shipment', 'create_enviaya_shipment' );
add_action( 'wp_ajax_nopriv_create_shipment', 'create_enviaya_shipment' );

function refresh_shipment_status() {
    global $EY_lang, $wpdb;

    $settings = EYHelper::settings();
    $shipment_id = (string)$_POST['shipment_id'];
    $data = file_get_contents('https://'.$EY_lang->api_domain.'/api/v1/shipments/'.$shipment_id.'?api_key='.$settings['api_key']);
    $status = !empty(json_decode($data)->shipment->shipment_status) ? json_decode($data)->shipment->shipment_status : 'None';
    $wpdb->get_results('UPDATE '.$wpdb->prefix.'enviaya_shipment SET shipment_status="'.$status.'" WHERE enviaya_shipment_number = "'.$shipment_id.'"');

    echo json_decode($data)->shipment->shipment_status;
    wp_die();
}

add_action( 'wp_ajax_refresh_shipment_status', 'refresh_shipment_status' );
add_action( 'wp_ajax_nopriv_refresh_shipment_status', 'refresh_shipment_status' );

function tracking_enviaya_order($data) {
    global $EY_lang;

    $settings = EYHelper::settings();

    if(!isset($_POST['api_key']))
        $_POST['api_key'] = $EY_lang->api_domain;
    if(!isset($_POST['enviaya_account'])) {
        $_POST['enviaya_account'] = $settings['enviaya_account'];
    }

    $props = [
        "shipment_number"   => $_POST['shipment_number'],
        "carrier"           => $_POST['carrier']
    ];

    logAPI(json_encode($props), 'tracking_request');
    $response = EYHelper::libAPI()->track($props);
    logAPI(json_encode($response), 'tracking_response');
    // var_dump($response);

    if (property_exists($response, 'carrier_tracking_number') && $response->enviaya_shipment_number) {
        global $wpdb;

        $wpdb->get_results("UPDATE {$wpdb->prefix}".PREFIX."_shipment SET shipment_status='{$response->status_message}', carrier_shipment_number='{$response->carrier_tracking_number}', enviaya_shipment_number='{$response->enviaya_shipment_number}', estimated_delivery='{$response->delivery_date}' WHERE enviaya_shipment_number = '{$_POST['shipment_number']}'");
    }
    if(property_exists($response, 'status_code')){
        echo $response->status_code . '|||';
    } else {
        echo '500|||';
    }
}

add_action( 'wp_ajax_tracking_order', 'tracking_enviaya_order' );
add_action( 'wp_ajax_nopriv_tracking_order', 'tracking_enviaya_order' );

function optain_enviaya_service($package = array()) {
    global $wpdb;

    $settings = EYHelper::settings();

    $order = $_POST['order_id'];
    $parcels = EYHelper::buildPackage($order);
    $origin = json_decode($settings['origin_address']);

    $country = get_option('woocommerce_default_country');
    $arr = explode(":", $country);
    $country = $arr[0];
    $total = WC()->cart->subtotal;
    $order_total_amount = $settings['enable_currency_support'] === 'yes' ?  apply_filters( 'raw_woocommerce_price', floatval($total < 0 ? $total * -1 : $total)) : $total;

    // TODO: Look here
    $props = [
        'rate_currency'             => $settings['enable_currency_support'] === 'yes' ? get_woocommerce_currency() : $settings['default_currency'],
        'shipment_type'             => 'Package',
        'parcels'                   => $parcels,
        'origin_country_code'       => $origin->country_code ? $origin->country_code : $country,
        'origin_postal_code'        => $origin->postal_code,
        'origin_state_code'         => null,
        'destination_country_code'  => $_POST['country_code'],
        'destination_postal_code'   => $_POST['postal_code'],
        'destination_state_code'    => null,
        'insured_value_currency'    => $settings['enable_currency_support'] === 'yes' ? get_woocommerce_currency() : $settings['default_currency'],
        'currency'                  => $settings['enable_currency_support'] === 'yes' ? get_woocommerce_currency() : $settings['default_currency'],
        'order_total_amount'        => (float)$order_total_amount,
        'locale'                    => get_user_locale()
    ];

    $result = EYHelper::libAPI()->calculate($props);
    $response = $result['response'];

    error_log("NEW ORDER REQUEST 3: ".json_encode($result['request']));

    if (!isset($response->errors)) {
        error_log("NEW ORDER REQUEST 31: ".json_encode($result['request']));
        if (!empty($response) && empty($response->errors)) {
            error_log("NEW ORDER REQUEST 32: ".json_encode($result['request']));
            $option = '';
            foreach ($response as $key => $carrier) {
                if (!empty($carrier) && $key !== "warning") {
                    foreach ($carrier as $carrier_rate) {
                        $tax = get_option('woocommerce_prices_include_tax');
                        $_price = $tax == 'yes' ? $carrier_rate->net_total_amount : $carrier_rate->total_amount;
                        $currency = get_woocommerce_currency();
                        $estimated_delivery = date_create($carrier_rate->estimated_delivery);

                        if (!$settings['default_or_advanced_design']) {
                            $switch = $settings['shipping_service_design'];

                            $logo = ($settings['display_carrier_logo'] === '1') ? "<img class=enviaya_carrier_logo src='{$carrier_rate->carrier_logo_url}'>" : "";
                            switch($switch) {
                                case '0':
                                    $delivery_date = date_format($estimated_delivery,"d/m/Y");
                                    $description = $logo . "<span class=enviaya_service>{$carrier_rate->carrier_service_name}</span> - <span class=enviaya_delivery_date>{$delivery_date}</span> <span class=enviaya_amount>({$_price}{$currency})</span>";
                                    break;
                                case '1':
                                    $delivery_date = date_format($estimated_delivery,"d/m/Y");
                                    $description = $logo . "<span class=enviaya_service>{$carrier_rate->carrier}</span> - <span class=enviaya_delivery_date>{$delivery_date}</span> <span class=enviaya_amount>({$_price}{$currency})</span>";
                                    break;
                                case '2':
                                    $delivery_date = date_format($estimated_delivery,"l, M. j");
                                    $description = $logo . "<span class=enviaya_delivery_date>{$delivery_date}</span> <span class=enviaya_amount>({$_price}{$currency})</span>";
                                    break;
                                case '3':
                                    $delivery_date = date_format($estimated_delivery,"l, M. j");
                                    $description = $logo . "<span class=enviaya_delivery_date>{$delivery_date}</span> <br> <span class=enviaya_amount>{$_price}{$currency}</span> - <span class=enviaya_service>{$carrier_rate->carrier_service_name}</span>";
                                    break;
                                case '4':
                                    $delivery_date = date_format($estimated_delivery,"l, M. j");
                                    $description = $logo . "<span class=enviaya_delivery_date>{$delivery_date}</span> <br><span class=enviaya_amount>{$_price}{$currency}</span> - <span class=enviaya_service>{$carrier_rate->carrier}</span>";
                                    break;
                            }

                            $description = base64_encode($description);
                        } else {
                            $switch = $settings['shipping_service_design_advanced'];

                            switch($switch) {
                                case '0':
                                    $description = strtoupper($carrier_rate->dynamic_service_name);
                                    break;
                                case '1':
                                    $description = strtoupper($carrier_rate->enviaya_service_name);
                                    break;
                                case '2':
                                    $description = strtoupper($carrier_rate->carrier_service_name);
                                    break;
                            }

                            $description = base64_encode($description);
                        }

                        $rate = array(
                            'id' => $carrier_rate->rate_id,
                            'label' => $description,
                            'cost' => $tax == 'yes' ? $carrier_rate->net_total_amount : $carrier_rate->total_amount,
                            'meta_data' => array(
                                'rate_id' => $carrier_rate->rate_id,
                                'carrier_name' => $carrier_rate->carrier,
                                'carrier_service_name' => $carrier_rate->carrier_service_name,
                                'carrier_service_code' => $carrier_rate->carrier_service_code,
                                'carrier_logo' => $carrier_rate->carrier_logo_url,
                                'estimated_delivery' => $carrier_rate->estimated_delivery,
                                'delivery_cost' => $tax == 'yes' ? $carrier_rate->net_total_amount : $carrier_rate->total_amount,
                            )
                        );

                        // $temp = $tax == 'yes' ? $carrier_rate->total_amount : $carrier_rate->net_total_amount;

                        $option .= "<option rate_id='".$carrier_rate->rate_id."' carrier='".$carrier_rate->carrier."' carrier_logo='".$carrier_rate->carrier_logo_url."' estimated_delivery='".$carrier_rate->estimated_delivery."' carrier_service_name='".$carrier_rate->carrier_service_name."' carrier_service_code='".$carrier_rate->carrier_service_code."'>".
                            $carrier_rate->carrier." ".$carrier_rate->carrier_service_name ." (".$carrier_rate->total_amount.get_woocommerce_currency()." )</option>";

                        $wpdb->get_results("INSERT INTO {$wpdb->prefix}".PREFIX."_rates (order_id, rate_id, shipment_id, carrier, carrier_service_name, carrier_service_code, estimated_delivery, currency, carrier_logo_url, total_amount, net_total_amount, dynamic_service_name) VALUES ({$order}, {$carrier_rate->rate_id}, {$carrier_rate->shipment_id}, '{$carrier_rate->carrier}', '{$carrier_rate->carrier_service_name}', '{$carrier_rate->carrier_service_code}', '{$carrier_rate->estimated_delivery}', '".get_woocommerce_currency()."', '{$carrier_rate->carrier_logo_url}', '{$carrier_rate->total_amount}', '{$carrier_rate->net_total_amount}', '{$carrier_rate->dynamic_service_name}')");
                    }
                }
            }
            echo $option;
        }

        error_log("NEW ORDER REQUEST 33: ".json_encode($result['request']));

    } else {
        error_log("NEW ORDER REQUEST 34: ".json_encode($result['request']));

        echo 'false';
    }

    error_log("NEW ORDER REQUEST 35: ".json_encode($result['request']));


    echo "|||";
}

add_action( 'wp_ajax_optain_service', 'optain_enviaya_service' );
add_action( 'wp_ajax_nopriv_optain_service', 'optain_enviaya_service' );
