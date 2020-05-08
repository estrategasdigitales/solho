<?php
class Enviaya
{
    public static function add_column_if_not_exist($table, $column, $type) {
        global $wpdb;

        $states2 = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}".$table." LIKE '".$column."'" );
        if (strlen(json_encode($states2)) < 3) {
            $wpdb->get_results( "ALTER TABLE {$wpdb->prefix}".$table." ADD COLUMN ".$column." ".$type." NOT NULL;" );
            error_log("ALTER TABLE {$wpdb->prefix}".$table." ADD COLUMN ".$column." ".$type." NOT NULL;");
        }
    }
    // activate plugin if PHP and WP versions are OK.
    public static function activate( $wp = '3', $php = '2' )
    {
        global $wpdb;
        error_log("activate plugin");

        $qry = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}".PREFIX."_rates (
            id INT NOT NULL AUTO_INCREMENT,
            order_id INT NOT NULL,
            rate_id INT NOT NULL,
            shipment_id INT NOT NULL,
            carrier VARCHAR(40) NOT NULL,
            carrier_service_name VARCHAR(40) NOT NULL,
            carrier_service_code VARCHAR(40) NOT NULL,
            estimated_delivery VARCHAR(40) NOT NULL,
            currency VARCHAR(10) NOT NULL,
            carrier_logo_url VARCHAR(80) NOT NULL,
            total_amount VARCHAR(10) NOT NULL,
            net_total_amount VARCHAR(10) NOT NULL,
            dynamic_service_name VARCHAR(60) NOT NULL,
            label_rates TEXT,
                PRIMARY KEY (id))";
        $states = $wpdb->get_results( $qry );

        $shipment_id = $wpdb->get_results( "SELECT 'shipment_id' FROM {$wpdb->prefix}".PREFIX."_rates  WHERE 0;");
        if ($shipment_id) {
            $wpdb->get_results( "ALTER TABLE {$wpdb->prefix}".PREFIX."_rates ADD shipment_id INT;" );
        }

        $qry2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}".PREFIX."_shipment (
            id INT NOT NULL AUTO_INCREMENT,
            rate_id INT NOT NULL,
            order_id INT NOT NULL,
            carrier VARCHAR(40) NOT NULL,
            carrier_logo_url VARCHAR(80) NOT NULL,
            estimated_delivery VARCHAR(40) NOT NULL,
            carrier_service_name VARCHAR(40) NOT NULL,
            carrier_service_code VARCHAR(40) NOT NULL,
            total_amount VARCHAR(10),
            net_total_amount VARCHAR(10),
            currency VARCHAR(10),
            vat_amount VARCHAR(10),
            enviaya_shipment_number VARCHAR(80) NOT NULL,
            carrier_shipment_number VARCHAR(80) NOT NULL,
            shipment_status VARCHAR(80) NOT NULL,
            webhook_status VARCHAR(80) NOT NULL,
            label_url VARCHAR(240) NOT NULL,
            status VARCHAR(80) NOT NULL,
            shipment_id VARCHAR(80),
            event_code VARCHAR(80),
            event_description VARCHAR(80),
            event VARCHAR(80),
            status_code VARCHAR(80),
            sub_event_code VARCHAR(80),
            sub_event VARCHAR(80),
            sub_event_description VARCHAR(80) NOT NULL,
                PRIMARY KEY (id))";
        $states2 = $wpdb->get_results( $qry2 );

        $wpdb->get_results( "ALTER TABLE {$wpdb->prefix}".PREFIX."_shipment MODIFY label_url VARCHAR(240) ;" );

        self::add_column_if_not_exist('enviaya_shipment', 'rate_id', 'INT');
        self::add_column_if_not_exist('enviaya_shipment', 'order_id', 'INT');
        self::add_column_if_not_exist('enviaya_shipment', 'carrier', 'VARCHAR(40)');
        self::add_column_if_not_exist('enviaya_shipment', 'carrier_logo_url', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'estimated_delivery', 'VARCHAR(40)');
        self::add_column_if_not_exist('enviaya_shipment', 'carrier_service_name', 'VARCHAR(40)');
        self::add_column_if_not_exist('enviaya_shipment', 'carrier_service_code', 'VARCHAR(40)');
        self::add_column_if_not_exist('enviaya_shipment', 'total_amount', 'VARCHAR(10)');
        self::add_column_if_not_exist('enviaya_shipment', 'net_total_amount', 'VARCHAR(10)');
        self::add_column_if_not_exist('enviaya_shipment', 'currency', 'VARCHAR(10)');
        self::add_column_if_not_exist('enviaya_shipment', 'vat_amount', 'VARCHAR(10)');
        self::add_column_if_not_exist('enviaya_shipment', 'enviaya_shipment_number', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'carrier_shipment_number', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'shipment_status', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'webhook_status', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'label_url', 'VARCHAR(240)');
        self::add_column_if_not_exist('enviaya_shipment', 'status', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'shipment_id', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'event_code', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'event_description', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'event', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'status_code', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'sub_event_code', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'sub_event', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_shipment', 'sub_event_description', 'VARCHAR(80)');


        self::add_column_if_not_exist('enviaya_rates', 'order_id', 'INT');
        self::add_column_if_not_exist('enviaya_rates', 'rate_id', 'INT');
        self::add_column_if_not_exist('enviaya_rates', 'shipment_id', 'INT');
        self::add_column_if_not_exist('enviaya_rates', 'carrier', 'VARCHAR(40)');
        self::add_column_if_not_exist('enviaya_rates', 'carrier_service_name', 'VARCHAR(40)');
        self::add_column_if_not_exist('enviaya_rates', 'carrier_service_code', 'VARCHAR(40)');
        self::add_column_if_not_exist('enviaya_rates', 'estimated_delivery', 'VARCHAR(40)');
        self::add_column_if_not_exist('enviaya_rates', 'currency', 'VARCHAR(10)');
        self::add_column_if_not_exist('enviaya_rates', 'carrier_logo_url', 'VARCHAR(80)');
        self::add_column_if_not_exist('enviaya_rates', 'total_amount', 'VARCHAR(10)');
        self::add_column_if_not_exist('enviaya_rates', 'net_total_amount', 'VARCHAR(10)');
        self::add_column_if_not_exist('enviaya_rates', 'dynamic_service_name', 'VARCHAR(60)');
        self::add_column_if_not_exist('enviaya_rates', 'label_rates', 'TEXT');


        error_log("ACTIVATE PLUGIN ENVIAYA FOR WOOCOMMERCE");
        error_log(get_template_directory());

//        if (!file_exists(get_template_directory() . '/' . dirname('woocommerce/cart/cart-shipping.php'))) {
//            mkdir(get_template_directory() . '/' . dirname('woocommerce/cart/cart-shipping.php'), 0777, true);
//        }

//        if (!file_exists(get_template_directory().'/'.dirname('woocommerce/order/order-details.php'))){
//            mkdir(get_template_directory().'/'.dirname('woocommerce/order/order-details.php'), 0777, true);
//        }

//        $file = WP_CONTENT_DIR.'/plugins/enviaya-for-woocommerce/public/templates/cart-shipping.php';
//        $newfile = get_template_directory().'/woocommerce/cart/cart-shipping.php';
//        if (!copy($file, $newfile)) {
//            error_log("ERROR OVERRIDE WOOCOMMERCE TEMPLATE: " . json_encode(error_get_last()));
//        }
//
//        $file = WP_CONTENT_DIR.'/plugins/enviaya-for-woocommerce/public/templates/order-details.php';
//        $newfile = get_template_directory().'/woocommerce/order/order-details.php';
//        if (!copy($file, $newfile)) {
//            error_log("ERROR OVERRIDE WOOCOMMERCE TEMPLATE: " . json_encode(error_get_last()));
//        }

        global $wp_version;

        if ( version_compare(PHP_VERSION, $php, '<' ) )
        {
            $flag = 'PHP';
        }

        if ( version_compare( $wp_version, $wp, '<' ) )
        {
            $flag = 'WordPress';
        }

        if (isset($flag)) {
            $version = 'PHP' == $flag ? $php : $wp;
            deactivate_plugins(basename(__FILE__));
            wp_die('<p>Plugin <strong>Enviaya!</strong> requires ' . $flag . '  version ' . $version . ' or higher.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => TRUE));
        } else {
            return;
        }

    }

    // appear Configuration link at the plugin list
    public static function plugin_settings_link($links)
    {
        $href = 'admin.php?page=wc-settings&tab=shipping&section=enviaya';

        $settings_link = "<a href='{$href}'>". __( 'Configuration', ENVIAYA_PLUGIN ) ."</a>";

        array_unshift($links, $settings_link);

        return $links;
    }

    // add new method in shipping config
    static public function shipping_methods($methods)
    {
        $methods[] = 'EnviaYa_Shipping_Method';

        return $methods;
    }

    public function enviaya_request($api_url , $method = 'GET', $json = [])
    {
        global $EY_lang, $settings;

        if ($method == 'POST') {

            $wp_request_headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            );

            // error_log(json_encode($settings['timeout']));

            // $timeout = (Enviaya_Shipping_Method::get_timeout() != '') ? Enviaya_Shipping_Method::get_timeout() : 60;
            $timeout = $settings['timeout'];

            $request_data = [
                'method'    => $method,
                'timeout'   => $timeout,
                'headers'   => $wp_request_headers,
                'body'      => json_encode($json)
            ];

            $response = wp_remote_request($api_url, $request_data);

            return json_decode(wp_remote_retrieve_body($response));
        } else if ($method == 'GET') {
            $response = wp_remote_get($api_url, $json);
            return wp_remote_retrieve_body($response);
        } else {
            $response = $EY_lang->error_this_request_method_is_not_supported;
        }
        return $response;

    }
}
