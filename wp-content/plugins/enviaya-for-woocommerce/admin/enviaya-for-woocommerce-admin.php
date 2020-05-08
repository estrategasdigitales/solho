<?php

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
include_once( __DIR__ . '/helpers/EYHelper.php' );
include_once( __DIR__ . '/lib/index.php' );
// init enviaya

const PREFIX = 'enviaya';

function enviaya_shipping_init()
{

    $settings = EYHelper::settings();

    if (
            ($settings['rate_on_add_to_cart'] == 1 && isset($_REQUEST['wc-ajax']) &&
                $_REQUEST['wc-ajax'] === 'add_to_cart') || (EYHelper::is_woo() && WC() && isset(WC()->cart)
                && !empty(WC()->cart) && (!isset($_REQUEST['shipping_method']) || empty($_REQUEST['shipping_method']))))
    {

      add_action('wp', function(){

        $packages = WC()->cart->get_shipping_packages();

        if ($packages) {
            foreach ($packages as $key => $package) {
                WC()->session->set('shipping_for_package_' . $key, null);
            }
        }

      });

    }

    $excludedZone = false;

    if (!class_exists( 'Enviaya_Shipping_Method')) {
        if (isset($_POST['down_label'])) {
            global $wpdb;

            $order = get_post();

            if (isset($settings['origin_address']->country_code) && isset($settings['origin_address']->postal_code)) {
                $props = [
                    'get_post'     => $order,
                    'wc_get_order' => wc_get_order(),
                ];

                $result = EYHelper::libAPI()->create(EYHelper::create_shipment($props));
                $response2 = $result['response'];

                error_log("NEW ORDER 1 REQUEST: " . json_encode($result['request']));
                error_log("NEW ORDER 1 RESPONSE: " . json_encode($response2));

                $states_rate = $wpdb->get_results(
                        "SELECT * FROM {$wpdb->prefix}".PREFIX."_rates WHERE order_id = {$order->ID} AND
                        carrier_service_code = '{$result['request']['carrier_service_code']}' AND
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
                            '{$response2->label_share_link}', 'Wailting', 'empty' )");
                }
            }

            header("refresh:0;");
        }

        if (isset($_POST['ship'])) {
            global $wpdb;

            $props = [
                'get_post'     => get_post(),
                'wc_get_order' => wc_get_order(),
                'rate_id'      => $_COOKIE['rate_id']
            ];

            $result = EYHelper::libAPI()->create(EYHelper::create_shipment($props));
            $response2 = $result['response'];

            error_log("NEW ORDER 2 REQUEST: " . json_encode($result['request']));
            error_log("NEW ORDER 2 RESPONSE: " . json_encode($response2));

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
                        '{$response2->label_share_link}', 'Wailting', 'empty' )");
            }

            header("refresh:0;");
        }
        class Enviaya_Shipping_Method extends WC_Shipping_Method {
            protected $tech_conf_form_fields;
            protected $rating_conf_form_fields;
            protected $access_conf_form_fields;
            protected $adv_conf_form_fields;
            protected $excluded_zones_form_fields;
            protected $sender_addr_form_fields;
            protected $status_form_fields;

            public $parcels;

            public static $instance;

            public function __construct()
            {
                global $EY_lang;
                parent::__construct();

                $settings = EYHelper::settings();

                $this->EYTools = new Enviaya;
                $this->id                 = 'enviaya';
                $this->method_title       = $EY_lang->brand_name;
                $this->title              = $EY_lang->brand_name;
                $this->enabled            = 'yes';
                $this->api_key            = $settings['api_key'];
                $this->init();
                self::$instance = $this;

                return $this;
            }

            public static function get() {
                if (self::$instance === null) {
                    // error_log("NEW INSTANCE");
                    self::$instance = new self();
                }

                return self::$instance;
            }

            function init()
            {
                error_log("INIT: START");

                // Load the settings API
                $this->init_settings();

                // settings form
                $this->access_conf_form_fields = $this->access_conf_form($this->settings);
                $this->rating_conf_form_fields = $this->rating_conf_form($this->settings);
                $this->adv_conf_form_fields = $this->adv_conf_form($this->settings);
                $this->excluded_zones_form_fields = $this->excluded_zones_form($this->settings);
                $this->sender_addr_form_fields = $this->sender_addr_form($this->settings);
                $this->tech_conf_form_fields = $this->tech_conf_form($this->settings);
                $this->status_form_fields = $this->status_form($this->settings);

                $this->form_fields = array_merge(
                    $this->access_conf_form_fields,
                    $this->rating_conf_form_fields,
                    $this->adv_conf_form_fields,
                    $this->excluded_zones_form_fields,
                    $this->sender_addr_form_fields,
                    $this->tech_conf_form_fields,
                    $this->status_form_fields
                );

                // Save settings in admin if you have any defined
                add_action( 'woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);

                // JS and CSS
                add_action( 'admin_enqueue_scripts', [$this, 'js']);
                add_action( 'admin_enqueue_scripts', [$this, 'css']);

                error_log("INIT: FINISH");

            }

            public static function getProductPrice($product_id)
            {
                global $wpdb;

                $states = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}postmeta
                    WHERE post_id = {$product_id} AND meta_key = '_price'");

                return $states[0]->meta_value;
            }

            public static function if_cart_shipping_enabled ()
            {
                $settings = EYHelper::settings();

                error_log("IF_CART_SHIPPING_ENABLED: START");

                return $settings['enable_estimated_shipping'];
            }

            public static function get_timeout ()
            {
                $settings = EYHelper::settings();

                error_log("GET_TIMEOUT: START222");

                return $settings['timeout'];
            }

            public static function shipping_service_design()
            {
                $settings = EYHelper::settings();

                error_log("SHIPPING_SERVICE_DESIGN: START");

                $result['show_carrier_logo'] = $settings['display_carrier_logo'];
                $result['group_by_carrier'] = $settings['group_by_carrier'];
                $result['shipping_service_design'] = $settings['shipping_service_design'];

                return $result;
            }

            private function is_excluded_zone($package) {
                $settings = EYHelper::settings();

                $curZone = $package["destination"];
                if(!array_key_exists('excluded_zones_data', $settings)){
                    return false;
                } else if (empty(json_decode($settings['excluded_zones_data']))){
                    return false;
                }
                foreach (json_decode($settings['excluded_zones_data']) as $zone) {
                    $subZone = explode(',', $zone->regions);
                    $country = isset($subZone[0]) && explode(':', $subZone[0])[0] === 'country' ?
                        explode(':', $subZone[0])[1] : false;
                    $state = isset($subZone[1]) && explode(':', $subZone[1])[0] === 'state' ?
                        explode(':', $subZone[1])[2] : false;
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

            public function calculate_shipping( $package = array() ) {
                global $EY_lang;

                $settings = EYHelper::settings();

                if (!$this->is_excluded_zone($package)) {
                    if (isset($package['rates'])) {
                        foreach ($package['rates'] as $key => $value)
                        {
                            unset($package['rates'][$key]);
                        }
                    }

                    // error_log("CALCULATE_SHIPPING: START" . json_encode($package));

                    $dest_country = $package["destination"]["country"];
                    $dest_postcode = $package["destination"]['postcode'];
                    $dest_statecode = $package["destination"]['state'];

                    if (($this->settings['availability'] == 'specific') &&
                        !in_array($dest_country,$this->settings['countries'])) {
                        if ($this->settings['enable_contingency_shipping'] == 1)  {
                            if ($settings['enable_rating'] == '1') {
                                if ( is_plugin_active('enviaya-for-woocommerce/enviaya-for-woocommerce.php')) {
                                    if($this->settings['enable_standard_flat_rate'] == 'yes'){
                                        $this->add_rate(array(
                                            'id' => 'standard_flat_rate',
                                            'label' => 'Standard Flat Rate',
                                            'cost' => $this->settings['standard_flat_rate'],
                                            'meta_data' => array(
                                                'label_advanced' => 'Standard Flat Rate',
                                                'rate_id' => '0',
                                                'carrier_name' => 'Standard Flat Rate',
                                                'carrier_service_name' => 'Standard Flat Rate',
                                                'carrier_logo' => '',
                                                'estimated_delivery' => '',
                                                'delivery_cost' => $this->settings['standard_flat_rate'],
                                            ),
                                        ));
                                    }
                                    if($this->settings['enable_express_flat_rate'] == 'yes') {
                                        $this->add_rate(array(
                                            'id' => 'express_flat_rate',
                                            'label' => 'Express Flat Rate',
                                            'cost' => $this->settings['express_flat_rate'],
                                            'meta_data' => array(
                                                'label_advanced' => 'Express Flat Rate',
                                                'rate_id' => '1',
                                                'carrier_name' => 'Express Flat Rate',
                                                'carrier_service_name' => 'Express Flat Rate',
                                                'carrier_logo' => '',
                                                'estimated_delivery' => '',
                                                'delivery_cost' => $this->settings['express_flat_rate'],
                                            ),
                                        ));
                                    }
                                } else {
                                    $this->add_rate(array(
                                        'id' => 'standard_flat_rate',
                                        'label' => 'Standard Flat Rate',
                                        'cost' => $this->settings['standard_flat_rate'],
                                        'meta_data' => array(
                                            'label_advanced' => 'Standard Flat Rate',
                                            'rate_id' => '0',
                                            'carrier_name' => 'Standard Flat Rate',
                                            'carrier_service_name' => 'Standard Flat Rate',
                                            'carrier_logo' => '',
                                            'estimated_delivery' => '',
                                            'delivery_cost' => $this->settings['standard_flat_rate'],
                                        ),
                                    ));
                                    $this->add_rate(array(
                                        'id' => 'express_flat_rate',
                                        'label' => 'Express Flat Rate',
                                        'cost' => $this->settings['express_flat_rate'],
                                        'meta_data' => array(
                                            'label_advanced' => 'Express Flat Rate',
                                            'rate_id' => '1',
                                            'carrier_name' => 'Express Flat Rate',
                                            'carrier_service_name' => 'Express Flat Rate',
                                            'carrier_logo' => '',
                                            'estimated_delivery' => '',
                                            'delivery_cost' => $this->settings['express_flat_rate'],
                                        ),
                                    ));
                                }
                            }
                        }

                        return false;
                    }

                    if (isset($this->settings['origin_address']) && ($this->settings['origin_address'] != 'none')) {
                        $origins = str_replace('||', '"', $this->settings['origin_address']);
                        $origin = json_decode($origins);

                        $country = get_option('woocommerce_default_country');
                        $arr = explode(":", $country);
                        $country = $arr[0];

                        $origin_country = isset($origin->country_code) ? $origin->country_code : $country;
                        $origin_postcode = isset($origin->postal_code) ? $origin->postal_code : null;
                        $origin_statecode = isset($origin->state_code) ? $origin->state_code : null;
                    } else {
                        return false;
                    }

                    // error_log($this->settings['enabled_test_mode']);
                    $total = WC()->cart->subtotal;
                    $order_total_amount = $settings['enable_currency_support'] === 'yes' ?
                        apply_filters('raw_woocommerce_price', floatval($total < 0 ? $total * -1 : $total)) : $total;

                    $parcels = $this->calculate_parcels($package);

                    $props = [
                        'parcels'                   => $parcels,
                        'origin_country_code'       => $origin_country,
                        'origin_postal_code'        => $origin_postcode,
                        'origin_state_code'         => $origin_statecode,
                        'destination_country_code'  => $dest_country,
                        'destination_postal_code'   => $dest_postcode,
                        'destination_state_code'    => $dest_statecode,
                        'order_total_amount'        => $order_total_amount,
                    ];

                    //dokan use
                    $user_id = isset($package['seller_id']) ? $package['seller_id'] : null;
                    $dokan_enviaya_use = false;

                    if ($user_id && (int)get_user_meta($user_id, 'dokan_enviaya_use', true) === 1) {
                        $dokan_enviaya_use = get_user_meta($user_id, 'dokan_enviaya_use', true);
                        if(!is_super_admin($user_id)){
                            $dokan_enviaya_account = get_user_meta($user_id, 'dokan_enviaya_account', true);
                            $dokan_enviaya_enabled_test_mode = get_user_meta($user_id, 'dokan_enviaya_enabled_test_mode', true);
                            $dokan_enviaya_api_key_production = get_user_meta($user_id, 'dokan_enviaya_api_key_production', true);
                            $dokan_enviaya_api_key_test = get_user_meta($user_id, 'dokan_enviaya_api_key_test', true);
                            $request['api_key'] = (int)$dokan_enviaya_enabled_test_mode === 1 ? $dokan_enviaya_api_key_test : $dokan_enviaya_api_key_production;
                            $request['enviaya_account'] = $dokan_enviaya_account;
                        }
                    }

                    $product = array();

                    foreach ( $package['contents'] as $item_id => $values )
                    {
                        for ($i = 1; $i <= $values['quantity']; $i++) {
                            $product[] = $values;
                        }
                    }

                    $result = EYHelper::libAPI()->calculate(EYHelper::create($props));


                    if ($settings['enable_rating'] == '1') {
                        $response = $result['response'];
                    } else {
                        $response = null;
                    }

                    error_log("CALCULATE_SHIPPING REQUEST:" . json_encode($result['request']));
                    logAPI(json_encode($result['request']), 'rating_request');

                    error_log("CALCULATE_SHIPPING RESPONSE:" . json_encode($response));
                    logAPI(json_encode($response), 'rating_response');

                    $rate_list = array();

                    if (!empty($response) && empty($response->errors)) {
                        $tax = get_option('woocommerce_calc_taxes');

                        foreach ($response as $key => $carrier) {
                            if ($key === 'store_pickup') {
                                $rate = array(
                                    'label' => $carrier->dynamic_service_name,
                                    'cost' => 0,
                                    'user' => isset($package['user']) && isset($package['user']->id) ?
                                        $package['user']->id : null,
                                    'meta_data' => array(
                                        'label_advanced' => $carrier->dynamic_service_name,
                                        'rate_id' => 0,
                                        'carrier_name' => $carrier->carrier,
                                        'carrier_service_name' => $carrier->carrier_service_name,
                                        'carrier_service_code' => $carrier->carrier_service_code,
                                        'carrier_logo' => $carrier->carrier_logo_url,
                                        'estimated_delivery' => $carrier->estimated_delivery,
                                        'delivery_cost' => 0,
                                    )
                                );

                                array_unshift($rate_list, $rate);
                            }

                            if (!empty($carrier) && $key !== "warning" && $key !== "store_pickup") {
                                foreach ($carrier as $carrier_rate) {
                                    if (isset($carrier_rate->rate_id)) {
                                        if(isset($carrier_rate->messages) && $carrier_rate->messages === null){
                                            continue;
                                        }

                                        $_price = $tax == 'yes' ? $carrier_rate->net_total_amount
                                            : $carrier_rate->total_amount;
                                        $estimated_delivery = isset($carrier_rate->estimated_delivery) ?
                                            date_create($carrier_rate->estimated_delivery) : null;

                                        $logo = $settings['display_carrier_logo'] === '1' &&
                                            $carrier_rate->carrier_logo_url ?
                                            "<img class=enviaya_carrier_logo src='{$carrier_rate->carrier_logo_url}'>" :
                                            "";

                                        $line = '<span> - </span>';
                                        $br = '</br>';

                                        if($settings['shipping_delivery_time'] === '1'){
                                            $delivery_date = isset($carrier_rate->est_transit_time_hours) ?
                                                self::calc_delivery_time((float)str_replace('-', '',
                                                    $carrier_rate->est_transit_time_hours)) : null;
                                            $line = isset($carrier_rate->est_transit_time_hours) ? $line : null;
                                        } elseif($settings['shipping_delivery_time'] === '2'){
                                            $delivery_date = null;
                                            $line = null;
                                            $br = null;
                                        }

                                        $shipping_carrier_name = $settings['shipping_carrier_name'] === '1' ? $carrier_rate->carrier." - " : "";

                                        switch($settings['shipping_service_design']) {
                                            case '0':
                                                if($settings['shipping_delivery_time'] === '0') {
                                                    $delivery_date = isset($estimated_delivery) ?
                                                        date_format($estimated_delivery, "d/m/Y") : null;
                                                } else {
                                                    $delivery_date = isset($delivery_date) ? $delivery_date : null;
                                                }
                                                $description1 = $logo . "<span class=enviaya_carrier_name>{$shipping_carrier_name}</span>
                                                    <span class=enviaya_service>[!carrier_service_name!]</span>{$line}
                                                    <span class=enviaya_delivery_date>{$delivery_date}</span>
                                                    <span class=enviaya_amount>([!price!][!currency!])</span>";
                                                break;
                                            case '1':
                                                if($settings['shipping_delivery_time'] === '0') {
                                                    $delivery_date = isset($estimated_delivery) ?
                                                        EYHelper::formatDate($estimated_delivery, 'l, M. j',
                                                            get_locale()) : null;
                                                } else {
                                                    $delivery_date = isset($delivery_date) ? $delivery_date : null;
                                                }
                                                $description1 = $logo . "<span class=enviaya_delivery_date>
                                                    {$delivery_date}</span> {$br}<span class=enviaya_amount>
                                                    ([!price!][!currency!])</span>";
                                                break;
                                        }

                                        switch($settings['shipping_service_design_advanced']) {
                                            case '0':
                                                $description2 = $carrier_rate->dynamic_service_name . $line .
                                                    $delivery_date;
                                                break;
                                            case '1':
                                                if($carrier_rate->enviaya_service_name){
                                                    $description2 = $carrier_rate->enviaya_service_name . $line .
                                                        $delivery_date;
                                                } else {
                                                    $description2 = $carrier_rate->carrier_service_name . $line .
                                                        $delivery_date;
                                                }
                                                break;
                                            case '2':
                                                $description2 = $carrier_rate->carrier_service_name . $line .
                                                    $delivery_date;
                                                break;
                                            case '3':
                                                if($settings['shipping_delivery_time'] === '0') {
                                                    $delivery_date = isset($estimated_delivery) ?
                                                        EYHelper::formatDate($estimated_delivery, 'l, M. j',
                                                            get_locale()) : null;
                                                } else {
                                                    $delivery_date = isset($delivery_date) ? $delivery_date : null;
                                                }
                                                $description2 = $logo . "<span class=enviaya_delivery_date>
                                                    {$delivery_date}</span> <br> <span class=enviaya_amount>
                                                    [!price!][!currency!]</span> - <span class=enviaya_service>
                                                    [!carrier_service_name!]</span>";
                                                break;
                                        }


                                        if(isset($carrier_rate->additional_configuration)) {
                                            if ($settings['as_defined_price'] == '0')  {
                                                $description1 = str_replace("([!price!][!currency!])",
                                                    '', $description1);
                                            }

                                            $_price = '0.00';
                                            $description1 = str_replace("[!carrier_service_name!]",
                                                $carrier_rate->additional_configuration->free_shipping->free_shipping_name, $description1);
                                            $description2 = str_replace("[!carrier_service_name!]",
                                                $carrier_rate->additional_configuration->free_shipping->free_shipping_name, $description1);
                                        } else {
                                            $description1 = str_replace("[!carrier_service_name!]",
                                                $carrier_rate->carrier_service_name, $description1);
                                        }

                                        $rate = array(
                                            'id' => $carrier_rate->rate_id,
                                            'label' => $description2,
                                            'cost' => $_price,
                                            'user' => isset($package['user']) && isset($package['user']->id) ?
                                                $package['user']->id : null,
                                            'meta_data' => array(
                                                'label_advanced' => $description1,
                                                'rate_id' => $carrier_rate->rate_id,
                                                'carrier_name' => $carrier_rate->carrier,
                                                'carrier_service_name' => $carrier_rate->carrier_service_name,
                                                'carrier_service_code' => $carrier_rate->carrier_service_code,
                                                'carrier_logo' => $carrier_rate->carrier_logo_url,
                                                'estimated_delivery' => isset($carrier_rate->estimated_delivery) ?
                                                    $carrier_rate->estimated_delivery : null,
                                                'delivery_cost' => $_price,
                                            )
                                        );

                                        $rate_list[] = $rate;
                                    }
                                }
                            }
                        }
                    }

                    $hasPermissions = false;

                    if (current_user_can( 'manage_woocommerce' )) {
                        $hasPermissions = true;
                    }

                    if ($hasPermissions && !empty($response) && !empty($response->errors) && !empty($response->errors)
                        && !empty(array_column((array)$response->errors, 'parcels'))) {
                        add_filter( 'woocommerce_cart_totals_before_shipping', function() use ($EY_lang) {
                            echo '<div class="enviaya-info err">'.$EY_lang->no_dimensions_error.'</div>';
                        });

                        add_filter( 'woocommerce_review_order_before_shipping', function() use ($EY_lang) {
                            echo '<div class="enviaya-info err">'.$EY_lang->no_dimensions_error.'</div>';
                        });
                    } elseif ($hasPermissions && !empty($response) && !empty($response->errors) &&
                        !empty($response->errors) && !empty($response->errors->parcels)) {
                        add_filter( 'woocommerce_cart_totals_before_shipping', function() use ($EY_lang) {
                            echo '<div class="enviaya-info err">'.$EY_lang->no_weight_error.'</div>';
                        });

                        add_filter( 'woocommerce_review_order_before_shipping', function() use ($EY_lang) {
                            echo '<div class="enviaya-info err">'.$EY_lang->no_weight_error.'</div>';
                        });
                    }

                    usort($rate_list, function($a, $b) { return $a['cost'] > $b['cost']; });
                    $currency = $settings['enable_currency_support'] !== 'yes' && $settings['default_currency'] ?
                        $settings['default_currency'] : get_woocommerce_currency();

                    foreach ($rate_list as $rate) {
                        $cost = $settings['enable_currency_support'] === 'yes' ? $rate['cost'] :
                            apply_filters( 'raw_woocommerce_price', floatval($rate['cost'] < 0 ?
                                $rate['cost'] * -1 : $rate['cost']));

                        $rate['label'] = str_replace(['[!currency!]', '[!price!]', '[!carrier_service_name!]'],
                            [$currency, $cost, $rate['meta_data']['carrier_service_name']], $rate['label']);
                        $rate['meta_data']['label_advanced'] = str_replace('[!currency!]', $currency,
                            $rate['meta_data']['label_advanced']);
                        $rate['meta_data']['label_advanced'] = base64_encode(str_replace(['[!price!]', '[!currency!]'],
                            [$cost, $currency], $rate['meta_data']['label_advanced']));

                        $this->add_rate($rate);
                    };
                }
            }

            public function calc_delivery_time($item){
                global $EY_lang;

                if($item < 24){
                    $result = round($item, 1);

                    if($result == 1){
                        $result = $result .' '. __($EY_lang->hour, ENVIAYA_PLUGIN);
                    } else {
                        $result = $result .' '. __($EY_lang->hours, ENVIAYA_PLUGIN);
                    }
                } else {
                    $result = round($item / 24, 1);

                    if($result == 1){
                        $result = $result .' '. __($EY_lang->day, ENVIAYA_PLUGIN);
                    } else {
                        $result = $result .' '. __($EY_lang->days, ENVIAYA_PLUGIN);
                    }
                }

                return $result;
            }

            public function calculate_parcels($package = array())
            {
                $_parcels = array();

                foreach ( $package['contents'] as $item_id => $values ) {
                    $_Q = $values['quantity'];
                    $_qty = (float)$_Q;
                    $endWeight = 0;
                    $weightUnit = 'kg';
                    $tmpLength = null;
                    $tmpHeight = null;
                    $tmpWidth = null;
                    $dimensionUnit = null;

                    if(!empty($values['data'])) {
                        $product = $values['data'];
                        error_log('Product type: ' . json_encode(gettype($product)));

                        $thisParcel = null;
                        $_weight = (float)$product->get_weight();

                        $tmpLength = $product->get_length() ? (float)$product->get_length() : null;
                        $tmpHeight = $product->get_height() ? (float)$product->get_height() : null;
                        $tmpWidth = $product->get_width() ? (float)$product->get_width() : null;

                        $weightUnit = get_option('woocommerce_weight_unit');
                        $dimensionUnit = get_option('woocommerce_dimension_unit');

                        if ($_weight && $weightUnit === 'g') {
                            $_weight /= 1000;
                            $weightUnit = 'kg';
                        }

                        if ($_weight && $weightUnit === 'oz') {
                            $_weight /= 35.274;
                            $weightUnit = 'kg';
                        }

                        $endWeight = round($_weight, 2);
                    }

                    $thisParcel = array(
                        'quantity'      => $_qty,
                        'weight'        => $endWeight ? round($endWeight, 2) : null,
                        'weight_unit'   => $weightUnit,
                        'length'        => $tmpLength ? round($tmpLength, 2) : null,
                        'height'        => $tmpHeight ? round($tmpHeight, 2) : null,
                        'width'         => $tmpWidth ? round($tmpWidth, 2) : null,
                        'dimension_unit'=> $dimensionUnit,
                    );

                    $_parcels[] = $thisParcel;
                }

                return $_parcels;
            }

            public function js()
            {
                global $EY_lang;

                echo "<script>function lang() {return ". json_encode($EY_lang) .";}</script>";

                wp_register_script('ey-admin-ajax', plugins_url('admin/js/ajax.js', ENVIAYA_FILE),
                    array('jquery'), '1.13', $in_footer = true);
                wp_enqueue_script( 'ey-admin-ajax');
            }

            public function css()
            {
                // error_log("CSS: START");

                wp_enqueue_style('ey-admin-css', plugins_url('admin/css/enviaya.css', ENVIAYA_FILE));
            }

            public function admin_options()
            {
                global $woocommerce, $EY_lang;

                $settings = EYHelper::settings();

                $php_version    = PHP_VERSION;
                $wp_version     = get_bloginfo('version');
                $wc_version     = $woocommerce->version;
                $server_name    = $_SERVER['SERVER_NAME'];
                $curl_exists    = class_exists('WP_Http_Curl') ? 'Yes': 'No';
                $stringSupport  = base64_encode(json_encode([
                    'php_version'           => $php_version,
                    'wordpress_version'     => $wp_version,
                    'woocommerce_version'   => $wc_version,
                    'server_name'           => $server_name,
                    'curl_exists'           => $curl_exists
                ]));
                ?>

                <?php $this->js(); ?>

                <h2 class="nav-tab-wrapper" id="enviaya_admin">
                    <a href="#" class="nav-tab tablinks" onclick="openTab(event, 'enviaya-access-conf'); return false;"
                       id="defaultOpen"><?=__($EY_lang->access_configuration, ENVIAYA_PLUGIN)?></a>
                    <a href="#" class="nav-tab tablinks" onclick="openTab(event, 'enviaya-rating-conf'); return false;">
                        <?=__($EY_lang->rating_configuration, ENVIAYA_PLUGIN)?></a>
                    <a href="#" class="nav-tab tablinks" onclick="openTab(event, 'enviaya-adv-conf'); return false;">
                        <?=__($EY_lang->advanced_configuration, ENVIAYA_PLUGIN)?></a>
                    <a href="#" class="nav-tab tablinks" onclick="openTab(event, 'enviaya-excluded-zones'); return false;">
                        <?=__($EY_lang->excluded_zones, ENVIAYA_PLUGIN)?></a>
                    <a href="#" class="nav-tab tablinks" onclick="openTab(event, 'enviaya-sender-addr'); return false;">
                        <?=__($EY_lang->sender_address, ENVIAYA_PLUGIN)?></a>
                    <a href="#" class="nav-tab tablinks" onclick="openTab(event, 'enviaya-technical-conf'); return false;">
                        <?=__($EY_lang->technical_configuration, ENVIAYA_PLUGIN)?></a>
                    <a href="#" class="nav-tab tablinks" onclick="openTab(event, 'enviaya-status'); return false;">
                        <?=__($EY_lang->status, ENVIAYA_PLUGIN)?></a>
                </h2>

                <?php add_thickbox(); ?>

                <a href="#TB_inline?width=600&height=550&inlineId=modal-window-id" style="display: none;"
                   id="thickbox" class="thickbox">click here</a>

                <div id="modal-window-id" style="display: none;">
                    <div id="slide1">
                        <h2><?=__($EY_lang->welcome_screen_1_title, ENVIAYA_PLUGIN)?></h2>
                        <p><?=__($EY_lang->welcome_screen_1_text, ENVIAYA_PLUGIN)?></p>
                        <table class="form-table">
                            <tbody>
                            <tr valign="top">
                                <th scope="row" class="titledesc">
                                    <label for="woocommerce_enviaya_api_key_production">
                                        <?=__($EY_lang->api_key, ENVIAYA_PLUGIN)?>
                                        <span class="woocommerce-help-tip"></span>
                                    </label>
                                </th>
                                <td class="forminp">
                                    <fieldset>
                                        <legend class="screen-reader-text">
                                            <span><?=__($EY_lang->api_key, ENVIAYA_PLUGIN)?></span>
                                        </legend>
                                        <input class="input-text regular-input" id="instruction_api" type="text"
                                               style="width: 400px;" value="">
                                    </fieldset>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row" class="titledesc">
                                    <label for="woocommerce_enviaya_api_key_production">
                                        <?=__($EY_lang->test_api_key, ENVIAYA_PLUGIN)?>
                                        <span class="woocommerce-help-tip"></span>
                                    </label>
                                </th>
                                <td class="forminp">
                                    <fieldset>
                                        <legend class="screen-reader-text">
                                            <span><?=__($EY_lang->test_api_key, ENVIAYA_PLUGIN)?></span>
                                        </legend>
                                        <input class="input-text regular-input" id="instruction_test_api" type="text"
                                               style="width: 400px;" value="">
                                    </fieldset>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <button type="button" var1="" var2="" id="countinue1" onchange="console.log('sdfadsf')"
                                class="button-primary"><?=__($EY_lang->countinue, ENVIAYA_PLUGIN)?></button>
                    </div>
                    <div id="slide2" style="display: none;">
                        <h2><?=__($EY_lang->welcome_screen_2_title, ENVIAYA_PLUGIN)?></h2>
                        <p><?=__($EY_lang->welcome_screen_2_text, ENVIAYA_PLUGIN)?></p>
                        <table class="form-table">
                            <tbody>
                            <tr valign="top">
                                <th scope="row" class="titledesc">
                                    <label for="instruction_billing_account">
                                        <?=__($EY_lang->account, ENVIAYA_PLUGIN)?>
                                    </label>
                                </th>
                                <td class="forminp">
                                    <fieldset>
                                        <legend class="screen-reader-text">
                                            <span><?=__($EY_lang->account, ENVIAYA_PLUGIN)?></span>
                                        </legend>
                                        <select class="select wc-enhanced-select select2-hidden-accessible"
                                                id="instruction_billing_account" style="min-width:300px;" tabindex="-1"
                                                aria-hidden="true">
                                        </select>
                                        <p class="description" id="select1_error" style="display: none">
                                            <?=__($EY_lang->api_key_error, ENVIAYA_PLUGIN)?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <button type="button" id="countinue2" class="button-primary">
                            <?=__($EY_lang->countinue, ENVIAYA_PLUGIN)?>
                        </button>
                    </div>
                    <div id="slide3" style="display: none;">
                        <h2><?=__($EY_lang->welcome_screen_3_title, ENVIAYA_PLUGIN)?></h2>
                        <p><?=__($EY_lang->welcome_screen_3_text, ENVIAYA_PLUGIN)?></p>
                        <table class="form-table">
                            <tbody>
                            <tr valign="top">
                                <th scope="row" class="titledesc">
                                    <label for="woocommerce_enviaya_origin_address">
                                        <?=__($EY_lang->sender_address, ENVIAYA_PLUGIN)?>
                                    </label>
                                </th>
                                <td class="forminp">
                                    <fieldset>
                                        <legend class="screen-reader-text">
                                            <span><?=__($EY_lang->sender_address, ENVIAYA_PLUGIN)?></span>
                                        </legend>
                                        <select class="select wc-enhanced-select select2-hidden-accessible enhanced"
                                                id="instruction_origin_address" style="min-width:300px;" tabindex="-1"
                                                aria-hidden="true">
                                        </select>
                                        <p class="description" id="select2_error" style="display: none">
                                            <?=__($EY_lang->api_key_error, ENVIAYA_PLUGIN)?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <button type="button" id="countinue3" class="button-primary">
                            <?=__($EY_lang->countinue, ENVIAYA_PLUGIN)?>
                        </button>
                    </div>
                    <div id="slide4" style="display: none;">
                        <h2><?=__($EY_lang->welcome_screen_4_title, ENVIAYA_PLUGIN)?></h2>
                        <p><?=__($EY_lang->welcome_screen_4_text, ENVIAYA_PLUGIN)?></p>
                        <button type="button" id="countinue4" class="button-primary">
                            <?=__($EY_lang->countinue, ENVIAYA_PLUGIN)?>
                        </button>
                    </div>
                    <div id="slide5" style="display: none;">
                        <h2><?=__($EY_lang->welcome_screen_5_title, ENVIAYA_PLUGIN)?></h2>
                        <p><?=__($EY_lang->welcome_screen_5_text, ENVIAYA_PLUGIN)?></p>
                        <button type="button" id="close_setup" class="button-primary">
                            <?=__($EY_lang->close, ENVIAYA_PLUGIN)?>
                        </button>
                    </div>
                </div>

                <div class="tabcontent  enviaya-settings" id="enviaya-status">
                    <h3><?=__($EY_lang->status, ENVIAYA_PLUGIN)?></h3>
                    <p></p>

                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label><?=__( $EY_lang->domain, ENVIAYA_PLUGIN)?>:</label>
                            </th>
                            <td class="forminp">
                                <?php echo $server_name . '<br>'; ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label><?=__( $EY_lang->php_version, ENVIAYA_PLUGIN)?></label>
                            </th>
                            <td class="forminp">
                                <?php echo $php_version . '<br>'; ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label><?=__( $EY_lang->wordpress_version, ENVIAYA_PLUGIN)?></label>
                            </th>
                            <td class="forminp">
                                <?php echo $wp_version . '<br>'; ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label><?=__( $EY_lang->woocommerce_version, ENVIAYA_PLUGIN)?></label>
                            </th>
                            <td class="forminp">
                                <?php echo  $wc_version . '<br>'; ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label><?=__( $EY_lang->curl_existence, ENVIAYA_PLUGIN)?></label>
                            </th>
                            <td class="forminp">
                                <?php echo  $curl_exists . '<br>'; ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label><?=__($EY_lang->support_code, ENVIAYA_PLUGIN)?></label>
                                <span class="woocommerce-help-tip" data-tip="Send this code when requesting support"></span>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <input id="stringSupport" type="text" value="<?php echo $stringSupport; ?>"
                                           class="input-text regular-input">
                                    <button onclick="copyToStringSupport(); return false;">
                                        <?=__( $EY_lang->copy, ENVIAYA_PLUGIN)?>
                                    </button>
                                </fieldset>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label><?=__( $EY_lang->download_logs, ENVIAYA_PLUGIN)?>:</label>
                                <span class="woocommerce-help-tip"></span>
                            </th>
                            <td class="forminp">
                                <a href="/wp-content/debug.log" download="debug.log"><input type="button" style="-webkit-appearance: button;" value="<?=__( $EY_lang->download_logs, ENVIAYA_PLUGIN)?>"></a>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="tabcontent enviaya-settings" id="enviaya-access-conf">
                    <?php
                    $this->form_fields = $this->access_conf_form($this->settings);
                    parent::admin_options();
                    ?>
                </div>

                <div class="tabcontent enviaya-settings" id="enviaya-technical-conf">
                    <?php
                    $this->form_fields = $this->tech_conf_form($this->settings);
                    parent::admin_options();
                    ?>

                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label><?=__( $EY_lang->download_api_logs, ENVIAYA_PLUGIN)?>:</label>
                                <span class="woocommerce-help-tip"></span>
                            </th>
                            <td class="forminp">
                                <input id="download-api-logs-button" type="button" style="-webkit-appearance: button;"
                                       value="<?=__( $EY_lang->download_api_logs, ENVIAYA_PLUGIN)?>">
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label><?=__( $EY_lang->delete_api_logs, ENVIAYA_PLUGIN)?>:</label>
                                <span class="woocommerce-help-tip"></span>
                            </th>
                            <td class="forminp">
                                <input id="delete-api-logs-button" type="button" style="-webkit-appearance: button;"
                                       value="<?=__( $EY_lang->delete_api_logs, ENVIAYA_PLUGIN)?>">
                            </td>
                        </tr>
                    </table>

                    <?php
                    $this->form_fields = $this->status_form($this->settings);
                    parent::admin_options();
                    ?>
                </div>


                <div class="tabcontent  enviaya-settings" id="enviaya-rating-conf">
                    <?php
                    $this->form_fields = $this->rating_conf_form($this->settings);
                    parent::admin_options();
                    ?>
                </div>
                <div class="tabcontent  enviaya-settings" id="enviaya-adv-conf">
                    <?php
                    $this->form_fields = $this->adv_conf_form($this->settings);
                    parent::admin_options();
                    ?>
                </div>
                <div class="tabcontent  enviaya-settings" id="enviaya-excluded-zones">
                    <?php
                    global $wpdb;

                    $this->form_fields = $this->excluded_zones_form_title($this->settings);
                    parent::admin_options();

                    $locations = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}woocommerce_tax_rate_locations`");
                    $continents = WC()->countries->get_continents();
                    $allowed_countries = WC()->countries->get_allowed_countries();
                    ?>
                    <table class="form-table">
                        <tbody>
                        <tr valign="top" class="">
                            <th scope="row" class="titledesc">
                                <label for="zone_name">
                                    <?php esc_html_e( __( $EY_lang->zone_name, ENVIAYA_PLUGIN), 'woocommerce' ); ?>
                                    <?php echo wc_help_tip( __( __( $EY_lang->zone_name_help, ENVIAYA_PLUGIN), 'woocommerce' ) ); // @codingStandardsIgnoreLine ?>
                                </label>
                            </th>
                            <td class="forminp">
                                <input type="text" data-attribute="zone_name" name="zone_name" id="excluded_zone_name"
                                       value="" placeholder="<?php esc_attr_e( __( $EY_lang->zone_name, ENVIAYA_PLUGIN), 'woocommerce' ); ?>">
                            </td>
                        </tr>
                        <tr valign="top" class="">
                            <th scope="row" class="titledesc">
                                <label for="zone_locations">
                                    <?php esc_html_e( __( $EY_lang->zone_regions, ENVIAYA_PLUGIN), 'woocommerce' ); ?>
                                    <?php echo wc_help_tip( __( __( $EY_lang->zone_regions_help, ENVIAYA_PLUGIN), 'woocommerce' ) ); // @codingStandardsIgnoreLine ?>
                                </label>
                            </th>
                            <td class="forminp">
                                <select multiple="multiple" data-attribute="zone_locations" id="excluded_zone_locations"
                                        name="zone_locations"
                                        data-placeholder="<?php esc_html_e( __( $EY_lang->zone_regions_placeholder, ENVIAYA_PLUGIN), 'woocommerce' ); ?>"
                                        class="wc-shipping-zone-region-select chosen_select">
                                    <?php
                                    foreach ( $continents as $continent_code => $continent ) {
                                        echo '<option value="continent:' . esc_attr( $continent_code ) . '"' . (in_array( "continent:$continent_code", $locations ) ? ' selected="true"' : '') . ' alt="">' . esc_html( $continent['name'] ) . '</option>';

                                        $countries = array_intersect( array_keys( $allowed_countries ), $continent['countries'] );

                                        foreach ( $countries as $country_code ) {
                                            echo '<option value="country:' . esc_attr( $country_code ) . '"' . (in_array( "country:$country_code", $locations ) ? ' selected="true"' : '') . ' alt="' . esc_attr( $continent['name'] ) . '">' . esc_html( '&nbsp;&nbsp; ' . $allowed_countries[ $country_code ] ) . '</option>';

                                            if ( $states = WC()->countries->get_states( $country_code ) ) {
                                                foreach ( $states as $state_code => $state_name ) {
                                                    echo '<option value="state:' . esc_attr( $country_code . ':' . $state_code ) . '"' . (in_array( "state:$country_code:$state_code", $locations ) ? ' selected="true"' : '') . ' alt="' . esc_attr( $continent['name'] . ' ' . $allowed_countries[ $country_code ] ) . '">' . esc_html( '&nbsp;&nbsp;&nbsp;&nbsp; ' . $state_name ) . '</option>';
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                                <div>
                                    <textarea name="zone_postcodes" data-attribute="zone_postcodes"
                                              id="excluded_zone_postcodes"
                                              placeholder="<?php esc_attr_e( __( $EY_lang->zone_regions_list_placeholder, ENVIAYA_PLUGIN), 'woocommerce' ); ?>"
                                              class="input-text large-text" cols="25" rows="5"></textarea>
                                    <span class="description">
                                        <?php printf( __( __( $EY_lang->zone_regions_list_help, ENVIAYA_PLUGIN), 'woocommerce' ), 'https://docs.woocommerce.com/document/setting-up-shipping-zones/#section-3' ); ?>
                                    </span><?php // @codingStandardsIgnoreLine. ?>
                                </div><br />

                                <button class="button-primary add-zone-btn"><?php echo __( $EY_lang->add_zone, ENVIAYA_PLUGIN); ?></button>
                            </td>
                        </tr>
                        <tr valign="top" class="">
                            <th scope="row" class="titledesc">
                                <label for="all_zones">
                                    <?php esc_html_e( __( $EY_lang->excluded_zones, ENVIAYA_PLUGIN), 'woocommerce' ); ?>
                                </label>
                            </th>
                            <td class="forminp">
                                <table class="wc-shipping-zone-methods widefat">
                                    <thead>
                                    <tr>
                                        <th><?php echo __( $EY_lang->excluded_zones_table_name, ENVIAYA_PLUGIN); ?></th>
                                        <th><?php echo __( $EY_lang->excluded_zones_table_regions, ENVIAYA_PLUGIN); ?></th>
                                        <th><?php echo __( $EY_lang->excluded_zones_table_delete, ENVIAYA_PLUGIN); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody class="wc-shipping-zone-method-rows ui-sortable ex-zones-table"></tbody>
                                </table>
                            </td>
                        </tr>

                        </tbody>
                    </table>

                    <?php
                    $this->form_fields = $this->excluded_zones_form($this->settings);
                    parent::admin_options();
                    ?>
                </div>
                <div class="tabcontent  enviaya-settings" id="enviaya-sender-addr">
                    <?php
                    $this->form_fields = $this->sender_addr_form($this->settings);
                    parent::admin_options();

                    $postal_code = isset($settings['origin_address']->postal_code) ?
                        $settings['origin_address']->postal_code : null;
                    $full_name = isset($settings['origin_address']->full_name) ?
                        $settings['origin_address']->full_name : null;
                    $phone = isset($settings['origin_address']->phone) ?
                        $settings['origin_address']->phone : null;

                    ?>
                    <?php if (isset($adddr2)) {?>

                        <input type="hidden" name="postcode" id="adrhid_postcode" value="<?php echo $postal_code; ?>">
                        <input type="hidden" name="fullname" id="adrhid_fullname" value="<?php echo $full_name; ?>">
                        <input type="hidden" name="email" id="adrhid_phone" value="<?php echo $phone; ?>">

                    <?php } ?>

                    <table class="form-table">
                        <tbody>
                        <tr valign="top">
                            <th scope="row" class="titledesc"></th>
                            <td class="forminp">
                                <div id="full_address">
                                    <span class="adr_full_name"></span><br>
                                    <span class="adr_phone"></span><br>
                                    <span class="adr_email"></span><br>
                                    <br>
                                    <span class="adr_full_name"></span><br>
                                    <span class="adr_full_name"></span><br>
                                    <span class="adr_full_name"></span><br>
                                    <span class="adr_full_name"></span><br>
                                    <span class="adr_full_name"></span><br>
                                    <span class="adr_full_name"></span><br>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <?php if(isset($_POST['save'])):?>
                <?php if($_POST['save'] == 'excluded_zones'):?>
                    <script>
                        jQuery(document).ready(function() {
                            openTab(event, 'enviaya-excluded-zones');
                        });
                    </script>
                <?php endif;?>
            <?php endif;?>

                <?php
            }


            /**
             * @param array $settings
             * @return mixed
             */
            static function access_conf_form($settings = [])
            {
                global $EY_lang;

                $enviaya_accounts = [];
                $response = EYHelper::libAPI()->get_accounts(null);

                if (!isset($response->errors) && isset($response->enviaya_accounts)) {
                    $acc_array = $response->enviaya_accounts;
                }

                if (isset($acc_array)) {
                    foreach ($acc_array as $acc) {
                        if ($acc->status == 'active') {
                            $enviaya_accounts[$acc->account] = $acc->alias . ' ('. $acc->account .')';
                        }
                    }
                } else {
                    $enviaya_accounts['none'] = $EY_lang->to_retrieve_your_billing_accounts;
                }


                $setup_form['api_key_configuration'] = array(
                    'title'       => __( $EY_lang->api_key_configuration, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                );

                $setup_form['api_key_production'] = array(
                    'title'       => __( $EY_lang->api_key, ENVIAYA_PLUGIN),
                    'type'        => 'text',
                    'description' => __( $EY_lang->api_key_help, ENVIAYA_PLUGIN ),
                    'default'     => '',
                    'desc_tip'    => true,
                );

                $setup_form['api_key_test'] = array(
                    'title'       => __( $EY_lang->test_api_key, ENVIAYA_PLUGIN),
                    'type'        => 'text',
                    'description' => __( $EY_lang->api_key_help, ENVIAYA_PLUGIN ),
                    'default'     => '',
                    'desc_tip'    => true,
                );

                $setup_form['enabled_test_mode'] = array(
                    'title'         => __( $EY_lang->enable_test_mode, ENVIAYA_PLUGIN ),
                    'label' => ' ',
                    'default' => $EY_lang->no,
                    'type'          => 'checkbox',
                    'description'   => __( $EY_lang->test_mode_help, ENVIAYA_PLUGIN ),
                    'desc_tip'      => true,
                );

                $setup_form['hr_1'] = array(
                    'type'        => 'title',
                    'class'        => 'woocommerce_enviaya_hr'
                );

                $setup_form['billing_account'] = array(
                    'title'       => __( $EY_lang->billing_account, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                );

                // country list
                $countries_obj = new WC_Countries();
                $countries     = $countries_obj->get_countries();

                $setup_form['enviaya_account'] = array(
                    'title'       => __( $EY_lang->account, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'class'    => 'wc-enhanced-select',
                    'css'      => 'min-width:300px;',
                    'description' => __( str_replace('{#api_link#}',
                        $EY_lang->api_domain,$EY_lang->billing_account_help), ENVIAYA_PLUGIN ),
                    'default'     => '',
                    'options'   => $enviaya_accounts,
                    'desc_tip'    => true,

                );

                return $setup_form;
            }

            /**
             * @param array $settings
             * @return mixed
             */
            static function rating_conf_form($settings = [])
            {
                global $EY_lang;

                $settings = EYHelper::settings();

                $setup_form['rating_configuration'] = array(
                    'title'       => __( $EY_lang->rating_configuration, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                );

                $setup_form['enable_rating']     = array(
                    'title'       => __( $EY_lang->enable_rating, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'desc_tip'    => false,
                    'default'     => '1',
                    'description' => __( $EY_lang->remove_rating_zones_hint, ENVIAYA_PLUGIN ),
                    'options'       => array(
                        '0' => __($EY_lang->no, ENVIAYA_PLUGIN),
                        '1' => __($EY_lang->yes, ENVIAYA_PLUGIN)
                    ),
                );

                $setup_form['hr_2'] = array(
                    'type'        => 'title',
                    'class'        => 'woocommerce_enviaya_hr'
                );

                // Shipping Services Design
                $setup_form['shipping_services_design'] = array(
                    'title'       => __( $EY_lang->shipping_services_display, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                );

                $setup_form['title_rating_configuration_'.get_locale()] = array(
                    'title'              => __( $EY_lang->title, ENVIAYA_PLUGIN ),
                    'type'               => 'text',
                    'description'        => __( $EY_lang->rates_title, ENVIAYA_PLUGIN ),
                    'desc_tip'    => true,
                );

                $setup_form['shipping_carrier_name']     = array(
                    'title'       => __( $EY_lang->carrier_name, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'description' => __( $EY_lang->carrier_name_help, ENVIAYA_PLUGIN ),
                    'desc_tip'    => true,
                    'options'       => array(
                        '0' => __($EY_lang->carrier_name_option_1, ENVIAYA_PLUGIN),
                        '1' => __($EY_lang->carrier_name_option_2, ENVIAYA_PLUGIN)
                    ),
                );

                $setup_form['shipping_service_design_advanced']     = array(
                    'title'       => __( $EY_lang->service_name, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'description' => __( $EY_lang->service_name_help, ENVIAYA_PLUGIN ),
                    'default'     => '0',
                    'desc_tip'    => true,
                    'options'       => array(
                        '0' => __( $EY_lang->service_name_1, ENVIAYA_PLUGIN ),
                        '1' => __( $EY_lang->service_name_2, ENVIAYA_PLUGIN ),
                        '2' => __( $EY_lang->service_name_3, ENVIAYA_PLUGIN ),
                        '3' => __( $EY_lang->service_name_4, ENVIAYA_PLUGIN ),
                    ),

                );

                $setup_form['shipping_delivery_time']     = array(
                    'title'       => __( $EY_lang->shipping_delivery_time, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'description' => __( $EY_lang->shipping_delivery_time_help, ENVIAYA_PLUGIN ),
                    'default'     => '0',
                    'desc_tip'    => true,
                    'options'       => array(
                        '0' => __( $EY_lang->show_estimated_delivery_date, ENVIAYA_PLUGIN ),
                        '1' => __( $EY_lang->show_transit_time, ENVIAYA_PLUGIN ),
                        '2' => __( $EY_lang->do_not_show_delivery_time, ENVIAYA_PLUGIN ),
                    ),
                );

                $setup_form['default_or_advanced_design'] = array(
                    'title'       => __($EY_lang->shipping_services_design, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'description' => '<div id=default_or_advanced_design_example></div>',
                    'default'     => '0',
                    'desc_tip'    => __( $EY_lang->shipping_services_design_help, ENVIAYA_PLUGIN ),
                    'options'       => array(
                        '0' => __($EY_lang->default_shipping_services_design, ENVIAYA_PLUGIN),
                        '1' => __($EY_lang->advanced_shipping_services_design, ENVIAYA_PLUGIN)
                    ),
                );

                $setup_form['display_carrier_logo']     = array(
                    'title'       => __( $EY_lang->display_carrier_logo, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'description' => __( '', ENVIAYA_PLUGIN ),
                    'desc_tip'    => true,
                    'options'       => array(
                        '0' => __($EY_lang->no, ENVIAYA_PLUGIN),
                        '1' => __($EY_lang->yes, ENVIAYA_PLUGIN)
                    ),
                );

                $pict = "<img id=pic0 src=https://enviaya-public.s3-us-west-1.amazonaws.com/images/99minutos.png
                            title=MINUTOS class=shipmen_logo style='padding: 1px;'>
                         <img id=pic1 src=https://enviaya-public.s3-us-west-1.amazonaws.com/images/ups.png
                            title=UPS class=shipmen_logo style='padding: 1px;'>
                         <img id=pic2 src=https://enviaya-public.s3-us-west-1.amazonaws.com/images/redpack.png
                            title=REDPACK class=shipmen_logo style=padding: 1px;>
                         <img id=pic3 src=https://enviaya-public.s3-us-west-1.amazonaws.com/images/fedex.png
                            title=FEDEX class=shipmen_logo style='padding: 1px;'>
                         <img id=pic4 src=https://enviaya-public.s3-us-west-1.amazonaws.com/images/dhl.jpg
                            title=DHL class=shipmen_logo style='padding: 1px;'>";

                $var0 = __($EY_lang->next_day, ENVIAYA_PLUGIN)." - 14/11/2017 ($ 156.43)";
                $var1 = "<label style='display: inline-block' class=srvc-name></label><br>06/11/2017 ($ 156.43)";
                $var2 = __($EY_lang->ex_date, ENVIAYA_PLUGIN)." ($ 156.43)";
                $var3 = __($EY_lang->ex_date, ENVIAYA_PLUGIN)." <br> $ 156.43 - <label style='display: inline-block'
                    class=srvc-name></label>";

                $setup_form['shipping_service_design']     = array(
                    'title'       => __( $EY_lang->shipping_services_display, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'default'     => '1',
                    'description' => "<span id=span-example >{$EY_lang->shipping_services_design_example}</span>
                        <div class=block-span> <span id=span-image>{$pict}</span><span id=block_design >
                        <span id=ds0>{$var0}</span><span id=ds1>{$var1}</span><span id=ds2>{$var2}</span>
                        <span id=ds3>{$var3}</span></span></div>",
                    'options'       => array(
                        '0' => __($EY_lang->advanced_shipping_services_design_1, ENVIAYA_PLUGIN),
                        '1' => __($EY_lang->advanced_shipping_services_design_2, ENVIAYA_PLUGIN),
                    ),

                );

                $setup_form['group_by_carrier']     = array(
                    'title'       => __( $EY_lang->group_by_carrier, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'description' => __( '', ENVIAYA_PLUGIN ),
                    'desc_tip'    => true,
                    'options'       => array(
                        '0' => __($EY_lang->no, ENVIAYA_PLUGIN),
                        '1' => __($EY_lang->yes, ENVIAYA_PLUGIN)
                    ),
                );

                $setup_form['recomendation_shipping_services'] = array(
                    'type'        => 'title',
                    'description' => __( $EY_lang->custom_design_text, ENVIAYA_PLUGIN ),
                );

                $setup_form['hr_3'] = array(
                    'type'        => 'title',
                    'class'       => 'woocommerce_enviaya_hr'
                );

                // Free Shipping
                $setup_form['free_shipping'] = array(
                    'title'       => __( $EY_lang->free_shipping, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                );

                //Recommendation: Enable Shipment Filters
                $setup_form['subsidies_hint'] = array(
                    'title'       => __( ' ', ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                    'description' => __( str_replace(['{#api_link#}','{#subsidies_link#}'],
                        [isset($EY_lang->api_domain) ? $EY_lang->api_domain : null, $settings['enviaya_account']],
                        $EY_lang->free_shipping_text), ENVIAYA_PLUGIN ),
                );

                $setup_form['as_defined_price']     = array(
                    'title'       => __( $EY_lang->as_defined_price, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'description' => __( '', ENVIAYA_PLUGIN ),
                    'desc_tip'    => true,
                    'default'     => '0',
                    'options'       => array(
                        '0' => __($EY_lang->do_not_show_price, ENVIAYA_PLUGIN),
                        '1' => __($EY_lang->show_price_as, ENVIAYA_PLUGIN),
                    ),
                );

                $setup_form['hr_4'] = array(
                    'type'        => 'title',
                    'class'       => 'woocommerce_enviaya_hr'
                );

                // Contingency Shipping
                $setup_form['contingency_shipping'] = array(
                    'title'       => __( $EY_lang->contingency_shipping_services, ENVIAYA_PLUGIN ),
                    'type'        => 'title',

                );

                $setup_form['enable_contingency_shipping']     = array(
                    'title'       => __( $EY_lang->enable_contingency_shipping_services, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'default'     => '1',
                    'desc_tip'    => __( $EY_lang->contingency_services_help, ENVIAYA_PLUGIN ),
                    'options'       => array(
                        '0' => __($EY_lang->no, ENVIAYA_PLUGIN),
                        '1' => __($EY_lang->yes, ENVIAYA_PLUGIN)
                    ),
                );

                $setup_form['enable_standard_flat_rate'] = array(
                    'title'       => __( $EY_lang->contingency_standard_shipping, ENVIAYA_PLUGIN ),
                    'label' => ' ',
                    'default'       => $EY_lang->yes,
                    'type'          => 'checkbox',
                    'desc_tip'      => false,
                );

                $setup_form['standard_flat_rate'] = array(
                    'title'       => __( $EY_lang->contingency_standard_rate, ENVIAYA_PLUGIN ),
                    'type'        => 'text',
                    'description' => get_woocommerce_currency(),
                    'default'     => '100',
                    'desc_tip'    => __( $EY_lang->contingency_standard_rate_help, ENVIAYA_PLUGIN ),
                );

                $setup_form['enable_express_flat_rate'] = array(
                    'title'       => __( $EY_lang->contingency_express_shipping, ENVIAYA_PLUGIN ),
                    'label' => ' ',
                    'default'       => $EY_lang->yes,
                    'type'          => 'checkbox',
                    'desc_tip'      => false,
                );

                $setup_form['express_flat_rate'] = array(
                    'title'       => __( $EY_lang->contingency_express_rate, ENVIAYA_PLUGIN ).
                        ' (' .get_woocommerce_currency(). ')',
                    'type'        => 'text',
                    'description' => get_woocommerce_currency(),
                    'default'     => '150',
                    'desc_tip'    => __( $EY_lang->contingency_express_rate_help, ENVIAYA_PLUGIN ),
                );

                $setup_form['hr_5'] = array(
                    'type'        => 'title',
                    'class'       => 'woocommerce_enviaya_hr'
                );

                //Recommendation: Additional account configurations
                $setup_form['recomendation_account_configurations'] = array(
                    'title'       => __( $EY_lang->additional_account_configurations_title, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                    'description' => __( str_replace(['{#api_link#}','{#subsidies_link#}'], [isset($EY_lang->api_domain) ?
                        $EY_lang->api_domain : null, $settings['enviaya_account']],
                        $EY_lang->additional_account_configurations_text), ENVIAYA_PLUGIN ),
                );

                $setup_form['hr_6'] = array(
                    'type'        => 'title',
                    'class'       => 'woocommerce_enviaya_hr'
                );

                //Recommendation: Enable Shipment Filters
                $setup_form['recomendation_shipment_filters'] = array(
                    'title'       => __( $EY_lang->carrier_and_services_configuration, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                    'description' => __( str_replace('{#api_link#}',
                        $EY_lang->api_domain,$EY_lang->carrier_and_services_configuration_help), ENVIAYA_PLUGIN ),
                );

                $setup_form['hr_7'] = array(
                    'type'        => 'title',
                    'class'       => 'woocommerce_enviaya_hr'
                );

                $setup_form['recomendation_subsidies'] = array(
                    'title'       => __( $EY_lang->subsidies_title, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                    'description' => __( $EY_lang->subsidies_text, ENVIAYA_PLUGIN ) . "<br><a target=_blank
                        href=https://{$EY_lang->api_domain}/shipping/subsidy_amounts >{$EY_lang->subsidies_link_text}</a>",
                );

                $setup_form['hr_8'] = array(
                    'type'        => 'title',
                    'class'       => 'woocommerce_enviaya_hr'
                );

                $setup_form['additional_options'] = array(
                    'title'       => __( $EY_lang->additional_options, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                );

                $currency_code_options = get_woocommerce_currencies();

                foreach ( $currency_code_options as $code => $name ) {
                    $currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
                }

                $setup_form['default_currency']     = array(
                    'title'       => __( $EY_lang->default_currency, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'default'     => get_woocommerce_currency(),
                    'desc_tip' => false,
                    'options'     => $currency_code_options,
                );

                $setup_form['enable_currency_support'] = array(
                    'title'       => __( $EY_lang->enable_currency_support, ENVIAYA_PLUGIN ),
                    'label' => ' ',
                    'default'     => $EY_lang->no,
                    'type'          => 'checkbox',
                    'description'   => "<span id=enable_currency_support_description>
                        {$EY_lang->enable_currency_support_help}</span>",
                    'desc_tip'    => false,
                );

                $setup_form['rate_on_add_to_cart']     = array(
                    'title'       => __( $EY_lang->rate_on_add_to_cart, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'description' => __( $EY_lang->rate_on_add_to_cart_info, ENVIAYA_PLUGIN ),
                    'default'     => '0',
                    'desc_tip'    => __( $EY_lang->rate_on_add_to_cart_help, ENVIAYA_PLUGIN ),
                    'options'     => array(
                        '0' => __($EY_lang->no, ENVIAYA_PLUGIN),
                        '1' => __($EY_lang->yes, ENVIAYA_PLUGIN)
                    ),
                );

                return $setup_form;
            }

            /**
             * @param array $settings
             * @return mixed
             */
            static function adv_conf_form($settings = [])
            {
                global $EY_lang;

                $setup_form['advanced_configuration'] = array(
                    'title'       => __( $EY_lang->advanced_configuration, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                );

                $setup_form['send_shipment_notice']     = array(
                    'title'       => __( $EY_lang->send_shipment_notifications, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'description' => __( $EY_lang->send_shipment_notifications_help, ENVIAYA_PLUGIN ),
                    'desc_tip'    => true,
                    'default'     => '0',
                    'options'       => array(
                        '1' => __($EY_lang->yes, ENVIAYA_PLUGIN),
                        '0' => __($EY_lang->no, ENVIAYA_PLUGIN)
                    ),
                );

                //                Migrated to server level.
                //                $setup_form['ignore_destination_state']     = array(
                //                    'title'       => __( $EY_lang->ignore_destination_state, ENVIAYA_PLUGIN ),
                //                    'type'        => 'select',
                //                    'description' => __( $EY_lang->ignore_recipient_help, ENVIAYA_PLUGIN ),
                //                    'desc_tip'    => true,
                //                    'default'     => '1',
                //                    'options'       => array(
                //                        '1' => __($EY_lang->yes, ENVIAYA_PLUGIN),
                //                        '0' => __($EY_lang->no, ENVIAYA_PLUGIN)
                //                    ),
                //                );

                $countries_obj = new WC_Countries();
                $countries     = $countries_obj->get_countries();

                $setup_form['availability']  = array(
                    'title'              => __( $EY_lang->ship_to_countries, ENVIAYA_PLUGIN ),
                    'type'               => 'select',
                    'default'            => 'all',
                    'class'    => 'availability wc-enhanced-select',
                    'css'      => 'min-width:300px;',
                    'options'            => array(
                        'all'            => __( $EY_lang->all_countries, ENVIAYA_PLUGIN ),
                        'specific'       => __( $EY_lang->specific_countries, ENVIAYA_PLUGIN ),
                    ),
                );

                $setup_form['countries'] = array(
                    'title'         => __( $EY_lang->ship_to_countries, ENVIAYA_PLUGIN ),
                    'type'          => 'multiselect',
                    'class'         => 'chosen_select wc-enhanced-select',
                    'css'           => 'width: 300px;',
                    'description'   => __( '', ENVIAYA_PLUGIN ),
                    'default'       => '',
                    'desc_tip'      => true,
                    'options'       => $countries,
                );

                $setup_form['doken_integration'] = array(
                    'title'       => __( $EY_lang->enable_dokan, ENVIAYA_PLUGIN ),
                    'label' => ' ',
                    'default'     => $EY_lang->yes,
                    'type'          => 'checkbox',
                    'description'   => __( $EY_lang->enable_dokan_help, ENVIAYA_PLUGIN ),
                    'desc_tip'      => true,
                    // 'css'         => 'display: none;',
                );

                return $setup_form;
            }

            static function tech_conf_form($settings = [])
            {
                global $EY_lang;

                $setup_form['tech_configuration'] = array(
                    'title'       => __( $EY_lang->technical_configuration, ENVIAYA_PLUGIN ),
                    'type'        => 'title',

                );

                $setup_form['timeout'] = array(
                    'title'       => __( $EY_lang->timeout, ENVIAYA_PLUGIN ),
                    'type'        => 'text',
                    'description' => __( $EY_lang->timeout_help, ENVIAYA_PLUGIN ),
                    'default'     => '15',
                    'desc_tip'    => true,
                );

                $setup_form['enable_api_logging'] = [
                    'title'         => __( $EY_lang->enable_api_logs, ENVIAYA_PLUGIN ),
                    'label' => ' ',
                    'default' => $EY_lang->no,
                    'type'          => 'checkbox',
                    'description'   => __( $EY_lang->enable_api_logs_help, ENVIAYA_PLUGIN ),
                    'desc_tip'      => true,
                ];

                return $setup_form;
            }

            /**
             * @param array $settings
             * @return mixed
             */
            static function status_form($settings = [])
            {
                global $EY_lang;

                $setup_form['total_remove'] = array(
                    'title'         => __( $EY_lang->remove_all_settings_and_data, ENVIAYA_PLUGIN ),
                    'label' => ' ',
                    'default' => $EY_lang->no,
                    'type'          => 'checkbox',
                    'description'   => __( $EY_lang->remove_all_settings_and_data_help, ENVIAYA_PLUGIN ),
                    'desc_tip'      => true,
                );

                return $setup_form;
            }

            /**
             * @param array $settings
             * @return mixed
             */
            static function excluded_zones_form_title($settings = []){
                global $EY_lang;

                $setup_form['tech_excluded_zones'] = array(
                    'title'       => __( $EY_lang->excluded_zones_title, ENVIAYA_PLUGIN ),
                    'type'        => 'title',
                    'description'   => __( $EY_lang->excluded_zones_title_help, ENVIAYA_PLUGIN ),
                );

                return $setup_form;
            }

            /**
             * @param array $settings
             * @return mixed
             */
            static function excluded_zones_form($settings = []){
                global $EY_lang;

                $setup_form['excluded_zones_data'] = array(
                    'type'  => 'hidden',
                    'default' => [],
                );

                return $setup_form;
            }

            /**
             * @param array $settings
             * @return mixed
             */
            static function sender_addr_form($settings = [])
            {
                global $EY_lang;

                $setup_form['sender_addr'] = array(
                    'title'       => __( $EY_lang->sender_address, ENVIAYA_PLUGIN ),
                    'type'        => 'title',

                );

                //origin addresses list
                $origin_addresses = [];
                $props = [
                    'param' => '&get_origins=t',
                ];
                $response = EYHelper::libAPI()->directions($props);

                if (isset($response->directions) && !isset($response->errors)) {
                    foreach ($response->directions as $address) {
                        $ord_adr = json_encode($address);
                        $ord_adr = str_replace('"', '||', $ord_adr);
                        $origin_addresses[$ord_adr] = !empty($address->full_name) ? $address->full_name :
                            $address->company;
                    }
                } else {
                    $origin_addresses['{}'] = $EY_lang->get_origin_addresses;
                }


                $setup_form['origin_address'] = array(
                    'title'       => __( $EY_lang->sender_address, ENVIAYA_PLUGIN ),
                    'type'        => 'select',
                    'class'    => 'wc-enhanced-select',
                    'css'      => 'min-width:300px;',
                    'description' => '<a href="https://app.'.$EY_lang->api_domain.'/directions/new?origin=true"
                        target="_blank">'.__( $EY_lang->add_new_sender, ENVIAYA_PLUGIN ).'</a> <div></div>',
                    'default'     => '',
                    'options'   => $origin_addresses,
                );

                return $setup_form;
            }
        }

        do_action('enviaya_shipping_rates_updated');
        add_action( 'woocommerce_new_order_item', 'enviaya_new_order',  1, 3  );

        function enviaya_new_order($item_id, $item, $order_id) {
            $settings = EYHelper::settings();

            if (!empty($item->get_meta('carrier_service_name')) && !empty($item->get_meta('carrier_name'))) {
                $order = wc_get_order( $order_id );
                $shipping = $order->get_data();
                $origin_address = $settings['origin_address'];
                $parcels = EYHelper::buildPackage($order_id);

                $total = WC()->cart->subtotal;
                $order_total_amount = isset($settings['enable_currency_support']) &&
                    $settings['enable_currency_support'] === 'yes' ?
                    apply_filters( 'raw_woocommerce_price', floatval($total < 0 ? $total * -1 : $total)) : $total;

                $props = [
                    'rate_currency'             => isset($settings['enable_currency_support']) &&
                    $settings['enable_currency_support'] === 'yes' ? get_woocommerce_currency() :
                        (isset($settings['default_currency']) && $settings['default_currency'] ?
                            $settings['default_currency'] : get_woocommerce_currency()),
                    'shipment_type'             => 'Package',
                    'parcels'                   => $parcels,
                    'origin_country_code'       => isset($origin_address) ? $origin_address->country_code : null,
                    'origin_postal_code'        => isset($origin_address) ? $origin_address->postal_code : null,
                    'origin_state_code'         => isset($origin_address) ? $origin_address->state_code : null,
                    'destination_country_code'  => $shipping['shipping']['country'],
                    'destination_postal_code'   => $shipping['shipping']['postcode'],
                    'destination_state_code'    => $shipping['shipping']['state'],
                    'insured_value_currency'    => $settings['enable_currency_support'] === 'yes' ?
                        get_woocommerce_currency() : $settings['default_currency'],
                    'currency'                  => $settings['enable_currency_support'] === 'yes' ?
                        get_woocommerce_currency() : $settings['default_currency'],
                    'order_total_amount'        => (float)$order_total_amount,
                    'locale'                    => get_user_locale()
                ];


                $result = EYHelper::libAPI()->calculate($props);

                if ($settings['enable_rating'] == '1') {
                    $response = $result['response'];
                } else {
                    $response = null;
                }

                logAPI(json_encode($result['request']), 'rating_request');
                error_log("RATES REQUEST: " . json_encode($result['request']));

                logAPI(json_encode($response), 'rating_response');
                error_log("RATES RESPONSE: " . json_encode($response));

                foreach ($response as $key => $value) {
                    if ($key !== 'warning') {
                        $rate_list[] = $value;
                    }
                }
            }
        }
}
}

function rm_register_enviaya_meta_box() {
    global $wpdb, $EY_lang;

    wp_register_script('ey-ajax', plugins_url('admin/js/ajax.js', ENVIAYA_FILE), array('jquery'),
        '1.13');
    wp_localize_script( 'ey-ajax', 'EnviayaAjax',
        array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    wp_enqueue_script('ey-ajax');

    add_meta_box( 'meta-box-ship', $EY_lang->brand_name .' '. $EY_lang->shipments,
        'rm_meta_box_callback_enviaya_1', 'shop_order', 'side', 'high' );

    $order = get_post();
    $enviaya_shipment = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}".PREFIX."_shipment WHERE
        order_id = {$order->ID}");

    if(empty($enviaya_shipment)){
        add_meta_box( 'meta_box_new', 'New '.$EY_lang->brand_name .' '. $EY_lang->shipment,
            'rm_meta_box_callback_enviaya_2', 'shop_order', 'side', 'high' );
    }
}
add_action( 'add_meta_boxes', 'rm_register_enviaya_meta_box');

function rm_meta_box_callback_enviaya_2() {
    global $wpdb, $EY_lang;

    $settings = EYHelper::settings();
    $order = get_post();

    $orderw = wc_get_order();
    $address = $orderw->get_address();

    $shipping_method = wc_get_order()->get_shipping_methods();
    $first_method = $shipping_method ? reset($shipping_method) : false;
    $is_ey_method = $first_method && $first_method->get_method_id() === 'enviaya';

    if (!$is_ey_method) {
      echo '<style>#order_shipping_line_items { display: table-row-group !important; }</style>';
    }

    $qry = "SELECT * FROM {$wpdb->prefix}".PREFIX."_rates
            WHERE order_id = {$order->ID}";
    $states3 = $wpdb->get_results( $qry );

    if (!count($states3)) {

        $origin_address = $settings['origin_address'];
        $parcels = EYHelper::buildPackage($order->ID);

        $props = [
            'rate_currency'             => $settings['enable_currency_support'] === 'yes' ? get_woocommerce_currency() :
                $settings['default_currency'],
            'shipment_type'             => 'Package',
            'parcels'                   => $parcels,
            'origin_country_code'       => isset($origin_address->country_code) ? $origin_address->country_code : null,
            'origin_postal_code'        => isset($origin_address->postal_code) ? $origin_address->postal_code : null,
            'origin_state_code'         => isset($origin_address->state_code) ? $origin_address->state_code : null,
            'destination_country_code'  => $orderw->get_shipping_country(),
            'destination_postal_code'   => $orderw->get_shipping_postcode(),
            'destination_state_code'    => $orderw->get_shipping_state(),
            'insured_value_currency'    => $settings['enable_currency_support'] === 'yes' ? get_woocommerce_currency() :
                $settings['default_currency'],
            'currency'                  => $settings['enable_currency_support'] === 'yes' ? get_woocommerce_currency() :
                $settings['default_currency'],
            'locale'                    => get_user_locale(),
        ];

        if ($settings['enable_rating'] == '1') {
            $response = EYHelper::libAPI()->calculate($props)['response'];
        } else {
            $response = null;
        }

        error_log("RATES RESPONSE:" . json_encode($response));

        if ($response) {
            $rate_list = array();
            foreach ($response as $key => $value) {
                if ($key === 'store_pickup') {
                    $rate_list[] = $value;
                }

                if ($key !== 'warning' && $key !== 'errors' && $key = 'store_pickup') {
                    foreach ($value as $resp) {
                        $rate_list[] = $resp;
                    }
                }
            }

            foreach ($rate_list as $key => $resp) {
                if (isset($resp->rate_id)) {
                    $wpdb->get_results("INSERT INTO {$wpdb->prefix}".PREFIX."_rates (order_id, rate_id,
                        shipment_id, carrier, carrier_service_name, carrier_service_code, estimated_delivery, currency,
                        carrier_logo_url, total_amount, net_total_amount, dynamic_service_name, label_rates) VALUES
                        (".$order->ID.", ".$resp->rate_id.", ".$resp->shipment_id.", '".$resp->carrier."',
                        '".$resp->carrier_service_name."', '".$resp->carrier_service_code."',
                        '".$resp->estimated_delivery."', '".$resp->currency."', '".$resp->carrier_logo_url."',
                        '".$resp->total_amount."', '".$resp->net_total_amount."', '".$resp->dynamic_service_name."',
                        '');");
                    // error_log("INSERT INTO RATES :" . json_encode($qry));
                }
            }
        }
    }

    $states3 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}".PREFIX."_rates
            WHERE order_id = {$order->ID}");

    $states = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_order_items
            WHERE order_id = {$order->ID} AND order_item_type = 'shipping'");

    $customer_selected = $states[0]->order_item_name;

    $states = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_order_itemmeta
            WHERE order_item_id = {$states[0]->order_item_id}");

    $carrier_service_code = '';
    $carrier_name = '';

    foreach($states as $value) {
        if ($value->meta_key == 'carrier_name') {
            $carrier_name = $value->meta_value;
        }

        if ($value->meta_key == 'carrier_service_code') {
            $carrier_service_code = $value->meta_value;
        }
    }

    $option = '';

    if (count($states3)) {
        $states_rate = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}".PREFIX."_rates WHERE
            order_id = {$order->ID} AND carrier_service_code = '{$carrier_service_code}' AND
            carrier = '{$carrier_name}';");

        if (!isset($states_rate[0]->rate_id)) {
            if (isset($_COOKIE['rate_id']))
                $rate_id = $_COOKIE['rate_id'];
            else
                $rate_id = 0;
        } else {
            $rate_id = (int)$states_rate[0]->rate_id;
        }

        foreach($states as $val) {
            $list[$val->meta_key] = $val->meta_value;
        }

        $states = $wpdb->get_results("SELECT rate_id FROM {$wpdb->prefix}".PREFIX."_shipment
                WHERE order_id = {$order->ID} ORDER BY id DESC LIMIT 1;");

        if ( count($states) == 0 ) {
            foreach ($states3 as $state) {
                setcookie('rate_id', $rate_id, time() + (86400 * 999), "/" );

                if ($rate_id == $state->rate_id ) {
                    $order = wc_get_order(get_post());
                    $get_order_notes = wc_get_order_notes([ 'order_id' => $order->get_id(), 'type' => 'customer']);
                    if(!$get_order_notes){
                        $note = __("carrier={$state->carrier}; carrier_service_name={$state->carrier_service_name};
                            carrier_service_code={$state->carrier_service_code}; rate_id={$rate_id};
                            shipment_id={$state->shipment_id};");
                        $order->add_order_note( $note, $added_by_user = true );
                    }
                    $option .= "<option selected=selected ";
                } else {
                    $option .= "<option ";
                }

                $_price = $state->total_amount;

                if ($state->carrier_service_name == "Free Shipping" || $state->carrier_service_name == "Recolección en tienda" || $state->carrier_service_name == "Store Pickup" ||  $state->carrier_service_name ==  "Abholung im Shop")
                    $option .= "rate_id='{$state->rate_id}' carrier='{$state->carrier}'
                        carrier_logo='{$state->carrier_logo_url}' estimated_delivery='{$state->estimated_delivery}'
                        carrier_service_name='{$state->carrier_service_name}'
                        carrier_service_code='{$state->carrier_service_code}'>{$state->carrier_service_name}</option>";
                else
                    $option .= "rate_id='{$state->rate_id}' carrier='{$state->carrier}'
                        carrier_logo='{$state->carrier_logo_url}' estimated_delivery='{$state->estimated_delivery}'
                        carrier_service_name='{$state->carrier_service_name}'
                        carrier_service_code='{$state->carrier_service_code}'>{$state->carrier}
                        {$state->carrier_service_name} ({$_price} {$state->currency})</option>";
            }
        } else {
            foreach ($states3 as $state) {

                if (isset($states[0])) {
                    setcookie('rate_id', $states[0]->rate_id, time() + (86400 * 999), "/" );
                    // error_log("FREE SHIPPING : ". $states[0]->rate_id);
                    if ($states[0]->rate_id === $state->rate_id ) {
                        $option .= "<option selected=selected ";
                    } else {
                        $option .= "<option ";
                    }
                } else {
                    $option .= "<option ";
                    // error_log("FREE SHIPPING : ". $states3[0]->rate_id);
                    setcookie('rate_id', $states3[0]->rate_id, time() + (86400 * 999), "/" );
                }

                $tax = get_option('woocommerce_prices_include_tax');
                $_price = $tax == 'yes' ? $state->net_total_amount : $state->total_amount;

                // error_log("FREE SHIPPING : ". json_encode($state));
                if ($state->carrier_service_name == "Free Shipping")
                    $option .= "rate_id='{$state->rate_id}' carrier='{$state->carrier}'
                        carrier_logo='{$state->carrier_logo_url}' estimated_delivery='{$state->estimated_delivery}'
                        carrier_service_name='{$state->carrier_service_name}'
                        carrier_service_code='{$state->carrier_service_code}'>{$state->carrier_service_name}</option>";
                else
                    $option .= "rate_id='{$state->rate_id}' carrier='{$state->carrier}'
                        carrier_logo='{$state->carrier_logo_url}' estimated_delivery='{$state->estimated_delivery}'
                        carrier_service_name='{$state->carrier_service_name}'
                        carrier_service_code='{$state->carrier_service_code}'>{$state->carrier}
                        {$state->carrier_service_name } ({$_price} {$state->currency})</option>";
            }
        }
    }

    echo "<div id=loader style='width: 100%; height: 100%; position: absolute; background-image: url(/wp-content/plugins/enviaya-for-woocommerce/public/img/loader.gif); background-size: contain; background-repeat: no-repeat; background-color: #fff; opacity: 0.85; background-position: center; z-index: -1;'></div>";

    if ($option && !($carrier_name && $carrier_service_code)) {
        $title = '';
    } else {
        if (base64_decode($customer_selected) == 'Free Shipping' || base64_decode($customer_selected) == 'Express Flat Rate') {
            $carrier_header = base64_decode($customer_selected);
        } else {
            $carrier_header = $customer_selected;
        }

        if($carrier_name && $carrier_service_code) {

            $title = "<div id=download_label>
                        <h3>{$EY_lang->purchased_service}</h3><span id=carrier_header >{$carrier_header}</span>
                        <form name=down_label >
                        <br><button type=button name=down_label id=down_label
                            class='button button-primary calculate-action'>{$EY_lang->download_label}</button>
                        </form></div>";
        } else {
            $title = "<div id=download_label><h3>{$EY_lang->purchased_service}</h3><span id=carrier_header>{$carrier_header}</span>
            <form name=optain_button>
            <input type=hidden id=country_code name=country_code value={$address['country']} >
            <input type=hidden id=postal_code name=postal_code value={$address['postcode']} >
            <input type=hidden id=state_code name=state_code value>
            <br><button type=button name=optain_button id=optain_button
                class='button button-primary calculate-action'>{$EY_lang->get_shipment_services}
            </button>
            </form></div>";
        }
    }

    if ($customer_selected == 'Free shipping') {
        $title = "<div id=download_label><h3>{$EY_lang->purchased_service}</h3>
                    <span id=carrier_header >{$customer_selected}</span>
                    <form name=optain_button>
                    <input type=hidden id=country_code name=country_code value={$address['country']} >
                    <input type=hidden id=postal_code name=postal_code value={$address['postcode']} >
                    <input type=hidden id=state_code name=state_code value ><br>
                    <button type=button style='display: none;' name=optain_button id=optain_button
                    class='button button-primary calculate-action'>{$EY_lang->get_shipment_services}
                    </button>
                    </form>
                  </div>";
    }

    $text_is_excluded = is_excluded_zone_admin() ?
        "<p style='background: #ff0000; width: 100%; display: block; color: #fff; padding: 10px 10px;
            box-sizing: border-box; text-align: center;'><b>{$EY_lang->excluded_zones}</b>
        </p>" : "";
    echo $text_is_excluded;

    if($option == null) {
        echo "<script>
        function change_carrier(obj) {
            var index = obj.selectedIndex;
            var item = obj.children[index];
            var rate = item.getAttribute('rate_id');

            document.cookie =  'rate_id=' + rate;
        }
    </script>
    {$title}
    ";
    }

    if($option != null) {
        echo '<div id="shipment_button">';

        echo "
        <h3>{$EY_lang->create_shipment}</h3>
        <div class=shipment_list>
            <span>{$EY_lang->shipping_service}</span>
            <select onchange=change_carrier(this) id=carrier_list name=carrier_list>{$option}</select>
        </div>
        <br/ >
            <div>
                <form name=ship method=post>
                    <button type=button name=ship id=ship class=button button-primary calculate-action>
                    {$EY_lang->create_shipment}
                    </button>
                </form>
            </div>";

        echo "</div>";
    }

    echo "<div id=optain_ship><h4>{$EY_lang->rating_error_no_services_found}</h4></div>";
}

function is_excluded_zone_admin() {
    $settings = EYHelper::settings();

    $order = get_post();
    $order_data = new WC_Order($order->ID);
    $curZone = $order_data;

    if(!array_key_exists('excluded_zones_data', $settings)){
        return false;
    } else if (empty(json_decode($settings['excluded_zones_data']))){
        return false;
    }

    foreach (json_decode($settings['excluded_zones_data']) as $zone) {
        $subZone = explode(',', $zone->regions);
        $country = isset($subZone[0]) && explode(':', $subZone[0])[0] === 'country' ?
            explode(':', $subZone[0])[1] : false;
        $state = isset($subZone[1]) && explode(':', $subZone[1])[0] === 'state' ?
            explode(':', $subZone[1])[2] : false;
        $zip = explode(',', $zone->zips);

        if ((!$state && $country && $curZone->get_billing_country() === $country) || (!empty($zip) &&
                in_array($curZone->get_billing_postcode(), $zip))) {
            return true;
        }
    }

    return false;
}


function rm_meta_box_callback_enviaya_1() {
    global $EY_lang, $wpdb;

    $settings = EYHelper::settings();
    $order = get_post();
    $id =$order->ID;
    $qry = "SELECT * FROM {$wpdb->prefix}".PREFIX."_shipment
            WHERE order_id = {$id} ORDER BY id DESC;";
    $states = $wpdb->get_results( $qry );

    if (!count($states)) {
        echo "<h7>{$EY_lang->no_shipments}</h7>";
    }

    if (count($states)) {
        echo "<script>document.getElementById('meta-box-ship').style.display = 'block'; </script>";
    } else {
        echo "<script>document.getElementById('meta-box-ship').style.display = 'none'; </script>";
    }

    foreach($states as $stat) {
        $_price = $stat->total_amount;
        $currency = isset($stat->currency) ? $stat->currency : $settings['enable_currency_support'] === 'yes' ?
            get_woocommerce_currency() : $settings['default_currency'];
        echo "<td class=name>
            <div class=view shipment ship_num={$stat->enviaya_shipment_number} carrier={$stat->carrier}>
                <div class='wc-order-data-row wc-order-totals-items wc-order-items-editable'>
                    <img src={$stat->carrier_logo_url} title=".strtoupper($stat->carrier)." class=shipmen_logo />
                    <ul data-shipment-id={$stat->enviaya_shipment_number} >
                        <small>{$EY_lang->status}</small>
                        <li>
                            <span id=text-entry-status>{$stat->shipment_status}</span>
                            <span class='dashicons dashicons-image-rotate refresh-shipment-status' style='width: 20px; height: 20px; font-size: 15px; margin-left: 5px; margin-top: 2px;'></span>
                        </li>

                        <small>{$EY_lang->service}</small>
                        <li>
                            <img class=status_image src=/wp-content/plugins/enviaya-for-woocommerce/public/img/statuses/status-1.png id=status-1 >
                            <img class=status_image src=/wp-content/plugins/enviaya-for-woocommerce/public/img/statuses/status-2.png id=status-2 >
                            <img class=status_image src=/wp-content/plugins/enviaya-for-woocommerce/public/img/statuses/status-13.png id=status-13 >
                            <img class=status_image src=/wp-content/plugins/enviaya-for-woocommerce/public/img/statuses/status-14.png id=status-14 >
                            <img class=status_image src=/wp-content/plugins/enviaya-for-woocommerce/public/img/statuses/status-40.png id=status-40 >
                            <img class=status_image src=/wp-content/plugins/enviaya-for-woocommerce/public/img/statuses/status-50.png id=status-50 >
                            <img class=status_image src=/wp-content/plugins/enviaya-for-woocommerce/public/img/statuses/status-90.png id=status-90 >
                            <span class=\"woocommerce-help-tip no-quesion\" data-tip=\"".$EY_lang->amount.":
                                ".$stat->net_total_amount.$currency." <br>".$EY_lang->vat.":
                                ".($stat->total_amount - $stat->net_total_amount).$currency." <br>".$EY_lang->total.":
                                ".$stat->total_amount.$currency."\">{$stat->carrier_service_name}
                                ( {$_price} ".$currency." )
                            </span>
                        </li>
                        <small>{$EY_lang->estimated_delivery}</small>
                        <li>{$stat->estimated_delivery}</li>
                        <small>{$EY_lang->brand_name} {$EY_lang->shipment_number}</small>
                        <li>
                        <a target=_blank href=https://{$EY_lang->api_domain}/track?track_ref={$stat->enviaya_shipment_number}&show_events=true >{$stat->enviaya_shipment_number}</a>
                        </li>
                        <small>{$EY_lang->tracking_no}</small>
                        <li>
                        <a target=_blank href=https://{$EY_lang->api_domain}/track?track_ref={$stat->carrier_shipment_number}&show_events=true >{$stat->carrier_shipment_number}</a>
                        </li>
                        <a target=_blank href={$stat->label_url} >
                            <button type=button>{$EY_lang->download_label}</button>
                        </a>
                        <a target=_blank href=https://{$EY_lang->api_domain}/track?track_ref={$stat->enviaya_shipment_number}&show_events=true >
                            <button type=button >{$EY_lang->track_shipment}</button>
                        </a>
                    </ul>
                </div>
            </div>
        </td>";
    }
}



add_action('woocommerce_shipping_init', function () {
    $settings = EYHelper::settings();

    if (($settings['rate_on_add_to_cart'] == 1 && isset($_REQUEST['wc-ajax']) && $_REQUEST['wc-ajax'] === 'add_to_cart') || EYHelper::is_woo()) {
        enviaya_shipping_init();
    }
});
