<?php
/**
 * Plugin Name: EnvíaYa
 * Description: An powerful plugin to rate multi-carrier shipment services and est. delivery dates during checkout, create shipment labels with one click and track.
 * Version: 1.11.7
 * Text Domain: enviaya-for-woocommerce.php
 * Domain Path: /lang/mo
 * License: GPL v3
 */

global $EY_lang;
if(!isset($EY_lang)){
    $EY_lang = getLang();
}

if (!defined('ENVIAYA_PLUGIN'))
{
    define('ENVIAYA_PLUGIN', 'enviaya-for-woocommerce.php');
}

if (!defined('ENVIAYA_PLUGIN'))
{
    define('ENVIAYA_PLUGIN', 'enviaya-for-woocommerce.php');
}

add_filter( 'wc_get_template', function ( $file, $name ) {
    if ( $name === 'order/order-details.php' ) {
        $file = __DIR__ . '/public/templates/order-details.php';
    }
    return $file;
}, 10, 2 );

add_filter( 'wc_get_template', function ( $file, $name ) {
    if ( $name === 'cart/cart-shipping.php' ) {
        $file = __DIR__ . '/public/templates/cart-shipping.php';
    }
    return $file;
}, 10, 2 );

add_filter( 'plugin_row_meta', 'wk_plugin_row_meta', 10, 2 );
function wk_plugin_row_meta( $links, $file ) {
    global $EY_lang;

    if(!isset($EY_lang)){
        $EY_lang = getLang();
    }

    if ( plugin_basename( __FILE__ ) == $file ) {
        $row_meta = array(
            'author_uri'        => '<a href="'.esc_url( 'https://enviaya.com.mx/' ) .' "target="_blank" >' . esc_html__( __($EY_lang->author_uri, ENVIAYA_PLUGIN), 'domain' ) . '</a>',
            'plugin_uri'        => '<a href="'.esc_url( 'https://enviaya.com.mx/' ) .' "target="_blank" >' . esc_html__( __($EY_lang->plugin_uri, ENVIAYA_PLUGIN), 'domain' ) . '</a>',
            'documentation'     => '<a href="'.esc_url( 'https://soporte.enviaya.com.mx/portal/kb/articles/instalacion-plugin-woocommerce' ) .' "target="_blank" >' . esc_html__( __($EY_lang->documentation, ENVIAYA_PLUGIN), 'domain' ) . '</a>',
            'premium_support'   => '<a href="'.esc_url( 'https://soporte.enviaya.com.mx' ) .' "target="_blank" >' . esc_html__( __($EY_lang->premium_support, ENVIAYA_PLUGIN), 'domain' ) . '</a>'
        );

        return array_merge( $links, $row_meta );
    }
    return (array) $links;
}

add_action('admin_menu', 'register_my_custom_submenu_page', 60);
function register_my_custom_submenu_page() {
    add_submenu_page( 'woocommerce', 'EnvíaYa', 'EnvíaYa', 'manage_options', 'admin.php?page=wc-settings&amp;tab=shipping&amp;section=enviaya');
}

if( !function_exists('is_plugin_active') ) {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
// check if accessed directly
    if (!defined('ABSPATH'))
    {
        exit;
    }

    // check if WooCommerce is active
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
    {
        exit;
    }

    // constant this file
    if (!defined('ENVIAYA_FILE'))
    {
        define('ENVIAYA_FILE', __FILE__);
    }

    if(!defined('ENVIAYA_API_LOGS_FOLDER')){
        define('ENVIAYA_API_LOGS_FOLDER', WP_PLUGIN_DIR. '/enviaya-for-woocommerce/api-logs');
        if(!is_dir(ENVIAYA_API_LOGS_FOLDER)){
            mkdir(ENVIAYA_API_LOGS_FOLDER);
        }
    }
    global $EY_lang;
    if(!isset($EY_lang)){
        $EY_lang = getLang();
    }

    require_once __DIR__."/includes/enviaya.php";
    require_once __DIR__."/admin/helpers/hooks.php";
    require_once __DIR__."/admin/enviaya-for-woocommerce-admin.php";
    require_once __DIR__."/admin/ajax/ajax.php";
    require_once __DIR__."/includes/ajax.php";

    function my_enviaya_admin_style ()
    {
        wp_register_style('ey-css', plugins_url('admin/css/enviaya.css', ENVIAYA_FILE));
        wp_enqueue_style('ey-css', plugins_url('admin/css/enviaya.css', ENVIAYA_FILE));
    }
    add_action('admin_head', 'my_enviaya_admin_style');

    // front-side ajax
    function enviaya_front_ajax ()
    {
        wp_register_script('ey-ajax', plugins_url('public/js/ajax.js', ENVIAYA_FILE), array('jquery'), '1.14');
        wp_localize_script( 'ey-ajax', 'EnviayaAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        wp_enqueue_script('ey-ajax');
    }
    add_action( 'wp_enqueue_scripts', 'enviaya_front_ajax' );

    //front-side css
    function enviaya_front_css ()
    {
        wp_register_style('ey-css-public', plugins_url('public/css/enviaya.css', ENVIAYA_FILE));
        wp_enqueue_style('ey-css-public', plugins_url('public/css/enviaya.css', ENVIAYA_FILE));
    }
    add_action( 'wp_enqueue_scripts', 'enviaya_front_css' );

    // hooks
    add_filter('plugin_action_links_' . plugin_basename(ENVIAYA_FILE), ['Enviaya', 'plugin_settings_link']);

    add_filter('woocommerce_shipping_methods',  ['Enviaya', 'shipping_methods']);

    add_filter('allowed_http_origins', 'add_allowed_enviaya_origins');

    function add_allowed_enviaya_origins($origins) {
        $origins[] = get_site_url();
        return $origins;
    }

    // function pluginInit() {
        register_activation_hook( __FILE__, array( 'Enviaya', 'activate' ) );
    // }
    // add_action( 'plugins_loaded', 'pluginInit' );

    function my_enviaya_endpoints() {
        add_rewrite_endpoint( 'webhook', EP_ROOT | EP_PAGES );
    }

    add_action( 'init', 'my_enviaya_endpoints' );

    function my_enviaya_func($args) {
        global $wpdb, $EY_lang;
        if (isset($args['status'])) {
            if (strtoupper($args['status']) == "SUCCESS") {
                switch ($args['action']) {
                    case 'status_update':
                        try {
                            $wpdb->get_results("UPDATE {$wpdb->prefix}".PREFIX."_shipment SET
                                status = '{$args['shipment']['status']}',
                                carrier_shipment_number = '{$args['shipment']['carrier_shipment_number']}',
                                shipment_id = '{$args['shipment']['shipment_id']}',
                                event_code = '{$args['shipment']['event_code']}',
                                event_description = '{$args['shipment']['event_description']}',
                                event = '{$args['shipment']['event']}',
                                status_code = '{$args['shipment']['status_code']}',
                                sub_event_code = '{$args['shipment']['sub_event_code']}',
                                sub_event = '{$args['shipment']['sub_event']}',
                                sub_event_description = '{$args['shipment']['sub_event_description']}'
                                WHERE enviaya_shipment_number = '{$args['shipment']['shipment_number']}'");

                            print_r('{"status":200, "messages":"'.$EY_lang->the_status_was_updated_successfully.'"}');

                        } catch (Exception $e) {
                            echo $EY_lang->exception,  $e->getMessage(), "\n";
                        }
                        break;

                    case 'carrier_tracking_number_update':
                        try {

                            $wpdb->get_results("UPDATE {$wpdb->prefix}".PREFIX."_shipment SET
                                carrier_shipment_number = '{$args['shipment']['carrier_shipment_number']}'
                                WHERE enviaya_shipment_number = '{$args['shipment']['shipment_number']}'");

                            print_r('{"status":200, "messages":"'.$EY_lang->tracking_number_successfuly_updated.'"}');

                        } catch (Exception $e) {
                            echo $EY_lang->exception,  $e->getMessage(), "\n";
                        }
                        break;

                    case 'label_update':
                        try {
                            $wpdb->get_results("UPDATE {$wpdb->prefix}".PREFIX."_shipment SET
                                carrier_shipment_number = '{$args['shipment']['carrier_shipment_number']}',
                                label_url = '{$args['shipment']['label']}'
                                WHERE enviaya_shipment_number = '{$args['shipment']['shipment_number']}'");

                            print_r('{"status":200, "messages":"'.$EY_lang->the_label_url_was_updated_successfully.'"}');

                        } catch (Exception $e) {
                            echo $EY_lang->exception,  $e->getMessage(), "\n";
                        }
                        break;

                    case 'amounts_update':
                        try {
                            $wpdb->get_results("UPDATE {$wpdb->prefix}".PREFIX."_shipment SET
                                carrier_shipment_number = '{$args['shipment']['carrier_shipment_number']}',
                                total_amount = '{$args['shipment']['total_amount']}',
                                net_total_amount = '{$args['shipment']['net_total_amount']}',
                                vat_amount = '{$args['shipment']['vat_amount']}'
                                WHERE enviaya_shipment_number = '{$args['shipment']['shipment_number']}'");

                            print_r('{"status":200, "messages":"'.$EY_lang->the_shipment_amount_was_updated_successfully.'"}');

                        } catch (Exception $e) {
                            echo $EY_lang->exception,  $e->getMessage(), "\n";
                        }
                        break;

                    case 'destination_update':
                        try {
                            $states1 = $wpdb->get_results("SELECT order_id FROM {$wpdb->prefix}".PREFIX."_shipment
                                WHERE enviaya_shipment_number = '{$args['shipment']['shipment_number']}'");

                            $order_id = $states1[0]->order_id;

                            $wpdb->get_results("UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$args['shipment']['destination_company']}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_company'");


                            $wpdb->get_results("UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$args['shipment']['destination_phone']}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_phone'");

                            $qry2 = "UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$args['shipment']['destination_direction_1']}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_address_1'";
                            $states2 = $wpdb->get_results( $qry2 );

                            $wpdb->get_results("UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$args['shipment']['destination_direction_2']}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_address_2'");

                            $wpdb->get_results("UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$args['shipment']['destination_country_code']}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_country'");

                            $wpdb->get_results("UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$args['shipment']['destination_postal_code']}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_postcode'");

                            $wpdb->get_results("UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$args['shipment']['destination_city']}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_city'");

                            $wpdb->get_results("UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$args['shipment']['destination_state_code']}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_state'");

                            $full_name = $args['shipment']['destination_full_name'];
                            $arr = explode(" ", $full_name);

                            $wpdb->get_results("UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$arr[0]}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_first_name'");


                            $arr[0] = '';
                            $full_name = join(' ', $arr);

                            $wpdb->get_results("UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$full_name}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_last_name'");

                            $wpdb->get_results("UPDATE {$wpdb->prefix}postmeta SET
                                meta_value = '{$args['shipment']['destination_email']}'
                                WHERE post_id = '{$order_id}' AND meta_key = '_billing_email'");

                            print_r('{"status":200, "messages":"'.$EY_lang->destination_address_successfuly_updated.'"}');

                        } catch (Exception $e) {
                            echo $EY_lang->exception,  $e->getMessage(), "\n";
                        }
                        break;

                    case 'origin_update':
                        try {
                            $config1 = get_option('woocommerce_enviaya_settings');
                            $config = json_decode(str_replace('||', '"', $config1['origin_address']));

                            $config->country_code = $args['shipment']['origin_country_code'];
                            $config->state_code = $args['shipment']['origin_state_code'];
                            $config->postal_code = $args['shipment']['origin_postal_code'];
                            $config->city = $args['shipment']['origin_city'];
                            $config->neighborhood = $args['shipment']['origin_neighborhood'];
                            $config->direction_1 = $args['shipment']['origin_direction_1'];
                            $config->direction_2 = $args['shipment']['origin_direction_2'];
                            $config->full_name = $args['shipment']['origin_full_name'];
                            $config->company = $args['shipment']['origin_company'];
                            $config->phone = $args['shipment']['origin_phone'];
                            $config->email = $args['shipment']['origin_email'];

                            $config2 = str_replace('"', '||', json_encode($config));
                            $config1['origin_address'] = $config2;

                            update_option('woocommerce_enviaya_settings', $config1);

                            print_r('{"status":200, "messages":"'.$EY_lang->the_origin_was_updated_successfully.'"}');

                        } catch (Exception $e) {
                            echo $EY_lang->exception, $e->getMessage(), "\n";
                        }
                        break;

                    default:
                        print_r('{"status":400, "messages":"'.$EY_lang->an_error_ocurred.'"}');

                        break;
                }
            } else {
                echo $EY_lang->unsuccessful;
            }
        }
    }

    //dokan
    function prefix_add_enviaya_tab($menu_items)
    {
        $menu_items['enviaya'] = [
            'title' => __('Enviaya'),
            'icon' => '<i class="fa fa-user-circle"></i>',
            'url' => dokan_get_navigation_url('settings/enviaya'),
            'pos' => 90,
            'permission' => 'dokan_view_store_settings_menu',
        ];
        return $menu_items;
    }

    add_filter('dokan_get_dashboard_settings_nav', 'prefix_add_enviaya_tab');


    function prefix_set_enviaya_tab_title($title, $tab)
    {
        if ('enviaya' === $tab) {
            $title = __('Enviaya');
        }
        return $title;
    }

    add_filter('dokan_dashboard_settings_heading_title', 'prefix_set_enviaya_tab_title', 10, 2);
    /**
     * Sets the help text for the 'About' settings tab.
     *
     * @param string $help_text
     * @param string $tab
     *
     * @return string Help text for tab with slug $tab
     */
    function prefix_set_enviaya_tab_help_text($help_text, $tab)
    {
        global $EY_lang;
        if ('enviaya' === $tab) {
            $help_text = $EY_lang->personalize;
        }
        return $help_text;
    }

    add_filter('dokan_dashboard_settings_helper_text', 'prefix_set_enviaya_tab_help_text', 10, 2);


    function setup_form()
    {
        global $EY_lang;
        $setup_form = [];

        $setup_form['use_enviaya'] = array(
            'title'       => __($EY_lang->use_enviaya, ENVIAYA_PLUGIN),
            'label'       => ' ',
            'type'        => 'checkbox',
            'name'        => 'dokan_enviaya_use',
            'description' => __($EY_lang->use_enviaya_help, ENVIAYA_PLUGIN),
            'desc_tip'    => true,

        );

        $setup_form['api_key_production'] = array(
            'title'       => __($EY_lang->api_key, ENVIAYA_PLUGIN),
            'type'        => 'text',
            'name'        => 'dokan_enviaya_api_key_production',
            'description' => __($EY_lang->api_key_help, ENVIAYA_PLUGIN),
            'default'     => '',
            'desc_tip'    => true,
        );

        $setup_form['api_key_test'] = array(
            'title'       => __($EY_lang->test_api_key, ENVIAYA_PLUGIN),
            'type'        => 'text',
            'name'        => 'dokan_enviaya_api_key_test',
            'description' => __($EY_lang->api_key_help, ENVIAYA_PLUGIN),
            'default'     => '',
            'desc_tip'    => true,
        );

        $setup_form['timeout'] = array(
            'title'        => __($EY_lang->timeout, ENVIAYA_PLUGIN),
            'type'         => 'text',
            'name'         => 'dokan_enviaya_timeout',
            'description'  => __($EY_lang->timeout_help, ENVIAYA_PLUGIN),
            'default'      => '30',
            'desc_tip'     => true,
        );

        $setup_form['enabled_test_mode'] = array(
            'title'        => __($EY_lang->enable_test_mode, ENVIAYA_PLUGIN),
            'label'        => ' ',
            'default'      => $EY_lang->no,
            'type'         => 'checkbox',
            'name'         => 'dokan_enviaya_enabled_test_mode',
            'description'  => __($EY_lang->test_mode_help, ENVIAYA_PLUGIN),
            'desc_tip'     => true,
        );

        $setup_form['enviaya_account'] = array(
            'title'       => __( $EY_lang->account, ENVIAYA_PLUGIN ),
            'type'        => 'select',
            'name'         => 'dokan_enviaya_account',
            'class'    => 'wc-enhanced-select',
            'css'      => 'min-width:300px;',
            'description' => '',
            'default'     => '',
            'desc_tip'    => true,
        );

        return $setup_form;
    }


    function prefix_output_help_tab_content($query_vars)
    {
        global $EY_lang;

        if (isset($query_vars['settings']) && 'enviaya' === $query_vars['settings']) {
            if (!current_user_can('dokan_view_store_settings_menu')) {
                dokan_get_template_part('global/dokan-error', '', [
                    'deleted' => false,
                    'message' => $EY_lang->no_access_permissions
                ]);
            } else {
                $user_id = get_current_user_id();
                $url = 'https://'.$EY_lang->api_domain.'/api/v1/get_accounts?api_key=';
                $value = [];
                foreach (setup_form() as $item) {
                    $value["{$item['name']}"] = get_user_meta($user_id, $item['name'], true);
                }
                ?>

                <form method="post" id="settings-form" action="" class="dokan-form-horizontal">
                    <input type="hidden" name="enviaya_url" value="<?php echo $url; ?>">
                    <?php
                    foreach (setup_form() as $item) {
                        if ($item['type'] == 'checkbox') {
                            $checked = $value[$item['name']] == 1 ? 'checked' : '';
                            echo "<div class='dokan-form-group'>
                                    <label class='dokan-w3 dokan-control-label' for='{$item['name']}'>{$item['title']}</label>
                                    <div class='dokan-w5 dokan-text-left'>
                                        <div class='checkbox'>
                                            <label>
                                                <input type='hidden' name='{$item['name']}' value='0'>
                                                <input name='{$item['name']}' value='1' id='{$item['name']}' type='{$item['type']}' {$checked}>
                                            </label>
                                        </div>
                                    </div>
                                  </div>";
                        } elseif($item['type'] == 'select') {
                            echo "<div class='dokan-form-group'>
                                    <label class='dokan-w3 dokan-control-label' for='{$item['name']}'>
                                        {$item['title']}
                                        <span class='dokan-tooltips-help tips' data-placement='bottom' data-original-title='{$item['description']}'>
                                            <i class='fa fa-question-circle'></i>
                                        </span>
                                    </label>
                                    <div class='dokan-w5'>
                                        <select name='{$item['name']}' id='{$item['name']}' class='dokan-form-control'></select>
                                    </div>
                                </div>";
                        } else {
                            echo "<div class='dokan-form-group'>
                                    <label class='dokan-w3 dokan-control-label' for='{$item['name']}'>
                                        {$item['title']}
                                        <span class='dokan-tooltips-help tips' data-placement='bottom' data-original-title='{$item['description']}'>
                                            <i class='fa fa-question-circle'></i>
                                        </span>
                                    </label>
                                    <div class='dokan-w5'>
                                        <input class='dokan-form-control' name='{$item['name']}' value='{$value[$item['name']]}' id='{$item['name']}' type='{$item['type']}'>
                                    </div>
                                </div>";
                        }
                    }
                    ?>

                    <div class="dokan-form-group">
                        <div class="dokan-w4 ajax_prev dokan-text-left" style="margin-left: 25%">
                            <input type="submit" name="dokan_update_enviaya_settings" class="dokan-btn dokan-btn-danger dokan-btn-theme" value="<?php esc_attr_e($EY_lang->update_settings); ?>">
                        </div>
                    </div>
                </form>

                <script src="https://code.jquery.com/jquery-3.4.1.js"
                        integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU="
                        crossorigin="anonymous"></script>

                <script>
                  $(document).ready(function() {
                    function enviayaAccounts() {
                        var enviaya_url = $("input[name='enviaya_url']").val();
                        var dokan_enviaya_api_key_production = $("input[name='dokan_enviaya_api_key_production']").val();
                        var dokan_enviaya_api_key_test = $("input[name='dokan_enviaya_api_key_test']").val();
                        var api_key = dokan_enviaya_api_key_test ? dokan_enviaya_api_key_test : dokan_enviaya_api_key_production;

                        $.ajax({
                          url: enviaya_url + api_key,
                          type: 'GET',
                          success: function(res) {
                            var mySelect = $("#dokan_enviaya_account");
                            var selectValues = $.makeArray(JSON.stringify(res['enviaya_accounts']));

                            $.each(selectValues,function(index,value){
                              var account = $.parseJSON(value)[0].account
                              var alias = $.parseJSON(value)[0].alias

                              mySelect.empty();
                              mySelect.append($('<option>',
                                {
                                  value: account,
                                  text : alias
                                }));
                            });
                          },
                          error: function() {
                            var mySelect = $("#dokan_enviaya_account");
                            mySelect.empty();
                          }
                        });
                    };

                    enviayaAccounts();
                    $("input").keyup(function () {
                      enviayaAccounts();
                    });

                  });
                </script>

                <style>
                    #settings-form p.help-block {
                        margin-bottom: 0;
                    }
                </style>
                <?php
            }
        }
    }

    add_action('dokan_render_settings_content', 'prefix_output_help_tab_content');

    function prefix_save_enviaya_settings($user_id)
    {
        // Bail if another settings tab is being saved
        foreach (setup_form() as $item) {
            update_user_meta($user_id, $item['name'], sanitize_text_field($_POST["{$item['name']}"]));
        }
    }

    add_action('dokan_store_profile_saved', 'prefix_save_enviaya_settings');


    add_filter('woocommerce_billing_fields', 'remove_enviaya_billing_phone_field', 20);
    function remove_enviaya_billing_phone_field($fields)
    {
        $fields ['billing_phone']['required'] = true; // To be sure "NOT required"

        return $fields;
    }


    add_action('rest_api_init', function () {
        register_rest_route('enviaya/v1', '/webhook', array(
                'methods' => 'POST',
                'callback' => 'my_enviaya_func',
            )
        );
    });

} else {
    printf( '<div class="error"><p>'.__($EY_lang->woocommerce_disabled_message, ENVIAYA_PLUGIN).'</p></div>');
    deactivate_plugins('enviaya-for-woocommerce/enviaya-for-woocommerce.php');
}

function getLang ($returnArray = false){
    $dir = __DIR__.'/lang/';
    if (is_dir($dir)){
        $request = file_get_contents( __DIR__.'/lang/en_US.json');
        if ($dh = opendir($dir)){
            while (($file = readdir($dh)) !== false) {
                if ($file === get_locale().'.json') {
                    $request = file_get_contents( __DIR__.'/lang/'.get_locale().'.json');
                }
            }
            closedir($dh);
        } else {
            error_log("LANG_ERROR: can't open the dir \"{$dir}\"");
        }
    } else {
        error_log("LANG_ERROR: can't find the dir \"{$dir}\"");
        return false;
    }

    if(!$request){
        error_log("LANG_ERROR: error in lang file");
        return false;
    }

    return json_decode($request, $returnArray);
}

/**
 * Write log into file if logging is enabled
 * @param string Message which will be written into the file
 * @param string type of log, different types writes into different files
 *
 * @return bool
 */
function logAPI ($message, $type){
    if(get_option('woocommerce_enviaya_settings')['enable_api_logging'] != 'yes'){
        return null;
    }
    $dateNow = new \DateTime();
    $filePath = ENVIAYA_API_LOGS_FOLDER .'/'. $type .'_'. $dateNow->format('d:m:Y H:i:s') . ".log";
    $gDate = new \DateTimeZone('UTC');
    $formattedLine = '['. $dateNow->format('d-M-Y H:i:s ') . $gDate->getName() .']: '. $message . "\n";
    if(file_put_contents($filePath, $formattedLine) === false){
        error_log("Can't create or open the file: " . $filePath);
        return false;
    }
    return true;
}

add_filter( 'woocommerce_cart_shipping_method_full_label', 'enviaya_shipping_service_design_advanced_label', 10, 2 );
function enviaya_shipping_service_design_advanced_label( $label, $method ) {

    $meta_data = $method->get_meta_data();
    $shipping_carrier_name = get_option('woocommerce_enviaya_settings')['shipping_carrier_name'];

    if(isset($meta_data['carrier_name'])){
      if($shipping_carrier_name === "1"){
        $label = $meta_data['carrier_name']." - ".$label;
      }
    }

    return $label;
}

?>
