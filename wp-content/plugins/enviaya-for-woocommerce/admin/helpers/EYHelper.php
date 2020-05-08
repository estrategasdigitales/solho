<?php

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

class EYHelper {
    public static function makeDestiny($shipping){
        $settings = self::settings();

        return [
            'full_name' => $shipping['shipping']['first_name'].' '.$shipping['shipping']['last_name'],
            'company' => $shipping['shipping']['company'],
            'country_code' => $shipping['shipping']['country'],
            'postal_code' => $shipping['shipping']['postcode'],
            'direction_1' => $shipping['shipping']['address_1'],
            'district' => $shipping['shipping']['address_2'],
            'phone' => $shipping['billing']['phone'],
            'state_code' => $shipping['shipping']['state'],
            'city' => $shipping['shipping']['city'],
            'email' => $settings['send_shipment_notice'] == '0' ? null : $shipping['billing']['email'],
        ];
    }

    /* Used by callback, do not remove! */
    private static function upperize($el) {
        return str_replace('..', '.', mb_convert_case($el, MB_CASE_TITLE, "UTF-8"));
    }

    public static function is_woo() {
        $url = $_SERVER['REQUEST_URI'];
        $page_id = url_to_postid($url);

        if((is_cart() || get_option('woocommerce_cart_page_id') == $page_id) || (is_checkout() || get_option('woocommerce_checkout_page_id') == $page_id) || $url == '/?wc-ajax=update_order_review' || is_admin()){
            return true;
        }

        return false;
    }

    public static function formatDate(\Datetime $date, $format, $locale = 'en') {
        if (!class_exists('IntlDateFormatter')) {
            return ucwords(date_i18n($format, $date->getTimestamp()));
        }

        $intlFormats = [
            'l, M. j' => 'EEEE, LLL. dd'
        ];

        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::LONG);
        $formatter->setPattern($intlFormats[$format]);

        return implode(' ', array_map('EYHelper::upperize', explode(' ', $formatter->format($date))));
    }

    public static function buildPackage($order_id) {
        $order = wc_get_order( $order_id );
        $items = $order->get_items();
        $package = array();

        foreach ($items as $item) {
            $weight_unit = get_option('woocommerce_weight_unit');
            $dimension_unit = get_option('woocommerce_dimension_unit');
            $data = $item->get_data();
            $product = wc_get_product( $data['variation_id'] > 0 ? $data['variation_id'] : $data['product_id'] );
            $weight = (float)$product->get_weight();

            if ($weight && $weight_unit === 'g') {
                $weight /= 1000;
                $weight_unit = 'kg';
            }

            if ($weight && $weight_unit === 'oz') {
                $weight /= 35.274;
                $weight_unit = 'kg';
            }

            $end_weight = round($weight, 2);

            $package[] = array(
                'quantity' => $item->get_quantity(),
                'weight' => $end_weight,
                'weight_unit' => $weight_unit,
                'length' => $product->get_length(),
                'height' => $product->get_height(),
                'width' => $product->get_width(),
                'dimension_unit' => $dimension_unit,
            );
        }

        return $package;
    }

    public static function libAPI() {
        global $EY_lang;

        $settings = self::settings();

        return new EnviayaAPI([
            'api_key'                   => $settings['api_key'],
            'enviaya_account'           => $settings['enviaya_account'],
            'carrier_account'           => null,
            'app_id'                    => 1,
            'domain'                    => $EY_lang->api_domain,
        ]);
    }

    public static function create($data) {
        $settings = self::settings();
        $currency = $settings['enable_currency_support'] === 'yes' ? get_woocommerce_currency() : $settings['default_currency'];

        $props = [
            'rate_currency'             => $currency,
            'shipment_type'             => 'Package',
            'parcels'                   => $data['parcels'],
            'origin_country_code'       => $data['origin_country_code'],
            'origin_postal_code'        => $data['origin_postal_code'],
            'origin_state_code'         => $data['origin_state_code'],
            'destination_country_code'  => $data['destination_country_code'],
            'destination_postal_code'   => $data['destination_postal_code'],
            'destination_state_code'    => $data['destination_state_code'],
            'insured_value_currency'    => $currency,
            'currency'                  => $currency,
            'order_total_amount'        => (float)$data['order_total_amount'],
            'locale'                    => get_user_locale()
        ];

        return $props;
    }

    public static function create_shipment($data) {
        global $wpdb;

        $settings = self::settings();

        $states = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_order_items
                    WHERE order_id = {$data['get_post']} AND order_item_type = 'shipping'");

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


        $order = $data['wc_get_order'];
        $order_id = $order->get_id();

        if (isset($settings['origin_address']->country_code) && isset($settings['origin_address']->postal_code)) {
            $country_code = $settings['origin_address']->country_code;
            $postal_code = $settings['origin_address']->postal_code;
            $full_name = '';
            $company = '';
            $direction_1 = '';
            $city = '';
            $phone = '';
            $state_code = '';
            $neighborhood = '';
            $district = '';
            $email = '';

            $response = EYHelper::libAPI()->directions(null);

            if (isset($response->directions) && !isset($response->errors)) {
                foreach ($response->directions as $address) {
                    if ($country_code === $address->country_code && $postal_code === $address->postal_code) {
                        $full_name = $address->full_name;
                        $company = $address->company;
                        $direction_1 = $address->direction_1;
                        $city = $address->city;
                        $phone = $address->phone;
                        $state_code = $address->state_code;
                        $neighborhood = $address->neighborhood;
                        $district = $address->district;
                        $email = $address->email;
                        break;
                    }
                }
            }

            $shipping = $order->get_data();
            $_destiny = EYHelper::makeDestiny($shipping);
            $parcels = EYHelper::buildPackage($order_id);

            if(isset($data['rate_id'])){
                $states3 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}".PREFIX."_rates WHERE rate_id = {$data['rate_id']}");
                $carrier_service_code = $states3[0]->carrier_service_code;
                $carrier_name = $states3[0]->carrier;
            }
        }

        $props = [
            'rate_id'                   => isset($data['rate_id']) ? $data['rate_id'] : null,
            'shipment_id'               => $data['shipment_id'],
            'rate_currency'             => $settings['enable_currency_support'] === 'yes' ? get_woocommerce_currency() : $settings['default_currency'],
            'carrier'                   => $carrier_name,
            'carrier_service_code'      => $carrier_service_code,
            'origin_full_name'          => $full_name,
            'origin_company'            => $company,
            'origin_country_code'       => $country_code,
            'origin_postal_code'        => $postal_code,
            'origin_direction_1'        => $direction_1,
            'origin_city'               => $city,
            'origin_phone'              => $phone,
            'origin_state_code'         => $state_code,
            'origin_neighborhood'       => $neighborhood,
            'origin_district'           => $district,
            'origin_email'              => $email,
            'destination_full_name'     => $_destiny['full_name'],
            'destination_company'       => $_destiny['company'],
            'destination_country_code'  => $_destiny['country_code'],
            'destination_postal_code'   => $_destiny['postal_code'],
            'destination_direction_1'   => $_destiny['direction_1'],
            'destination_district'      => $_destiny['district'],
            'destination_phone'         => $_destiny['phone'],
            'destination_state_code'    => $_destiny['state_code'],
            'destination_city'          => $_destiny['city'],
            'destination_email'         => $_destiny['email'],
            'parcels'                   => $parcels,
            'content'                   => 'Varios',
            'label_format'              => 'Letter',
            'shipment_type'             => 'Package',
            'locale'                    => get_user_locale()
        ];

        return $props;
    }

    public static function settings() {
        $settings = get_option('woocommerce_enviaya_settings');

        $api_key_production = isset($settings['api_key_production']) ? $settings['api_key_production'] : null;
        $api_key_test = isset($settings['api_key_test']) ? $settings['api_key_test'] : null;
        $enabled_test_mode = isset($settings['enabled_test_mode']) ? $settings['enabled_test_mode'] : 'no';
        $api_key = $enabled_test_mode == 'yes' ? $api_key_test : $api_key_production;

        return [
            'api_key'                           => $api_key,
            'enviaya_account'                   => isset($settings['enviaya_account']) ? $settings['enviaya_account'] : '',
            'enable_rating'                     => isset($settings['enable_rating']) ? $settings['enable_rating'] : '1',
            'shipping_service_design_advanced'  => isset($settings['shipping_service_design_advanced']) ? $settings['shipping_service_design_advanced'] : '0',
            'shipping_carrier_name'             => isset($settings['shipping_carrier_name']) ? $settings['shipping_carrier_name'] : '0',
            'shipping_delivery_time'            => isset($settings['shipping_delivery_time']) ? $settings['shipping_delivery_time'] : '0',
            'default_or_advanced_design'        => isset($settings['default_or_advanced_design']) ? $settings['default_or_advanced_design'] : '0',
            'display_carrier_logo'              => isset($settings['display_carrier_logo']) ? $settings['display_carrier_logo'] : '0',
            'shipping_service_design'           => isset($settings['shipping_service_design']) ? $settings['shipping_service_design'] : '1',
            'group_by_carrier'                  => isset($settings['group_by_carrier']) ? $settings['group_by_carrier'] : '0',
            'as_defined_price'                  => isset($settings['as_defined_price']) ? $settings['as_defined_price'] : '0',
            'enable_contingency_shipping'       => isset($settings['enable_contingency_shipping']) ? $settings['enable_contingency_shipping'] : '1',
            'enable_standard_flat_rate'         => isset($settings['enable_standard_flat_rate']) ? $settings['enable_standard_flat_rate'] : 'yes',
            'standard_flat_rate'                => isset($settings['standard_flat_rate']) ? $settings['standard_flat_rate'] : '100',
            'enable_express_flat_rate'          => isset($settings['enable_express_flat_rate']) ? $settings['enable_express_flat_rate'] : 'yes',
            'express_flat_rate'                 => isset($settings['express_flat_rate']) ? $settings['express_flat_rate'] : '150',
            'default_currency'                  => isset($settings['default_currency']) ? $settings['default_currency'] : get_woocommerce_currency(),
            'enable_currency_support'           => isset($settings['enable_currency_support']) ? $settings['enable_currency_support'] : 'no',
            'rate_on_add_to_cart'               => isset($settings['rate_on_add_to_cart']) ? $settings['rate_on_add_to_cart'] : '0',
            'send_shipment_notice'              => isset($settings['send_shipment_notice']) ? $settings['send_shipment_notice'] : '0',
            'availability'                      => isset($settings['availability']) ? $settings['availability'] : 'all',
            'countries'                         => isset($settings['countries']) ? $settings['countries'] : '',
            'doken_integration'                 => isset($settings['doken_integration']) ? $settings['doken_integration'] : 'yes',
            'timeout'                           => isset($settings['timeout']) ? $settings['timeout'] : '15',
            'enable_api_logging'                => isset($settings['enable_api_logging']) ? $settings['enable_api_logging'] : 'no',
            'total_remove'                      => isset($settings['total_remove']) ? $settings['total_remove'] : 'no',
            'excluded_zones_data'               => isset($settings['excluded_zones_data']) ? $settings['excluded_zones_data'] : [],
            'origin_address'                    => isset($settings['origin_address']) ? json_decode(str_replace('||', '"',  $settings['origin_address'])) : null,
            'enable_estimated_shipping'         => isset($settings['enable_estimated_shipping']) ? $settings['enable_estimated_shipping'] : '0'
        ];
    }
}
