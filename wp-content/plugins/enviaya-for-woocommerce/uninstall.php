<?php
/**
 *  EnviaYa Uninstall
 *
 *  Uninstalling WooCommerce deletes user roles, pages, tables, and options.
 *
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb, $EY_lang;

$deleteAllData = get_option('woocommerce_enviaya_settings')['total_remove'];
error_log('Delete ALL: ' .  json_encode($deleteAllData));

$dir = WP_PLUGIN_DIR.'/enviaya-for-woocommerce/lang/';
$request = wp_remote_get( content_url().'/plugins/enviaya-for-woocommerce/lang/en_US.json');
if (is_dir($dir)) {
   if ($dh = opendir($dir)) {
      while (($file = readdir($dh)) !== false) {
            if ($file === get_locale().'.json') {
               $request = wp_remote_get( content_url().'/plugins/enviaya-for-woocommerce/lang/'.get_locale().'.json');
            }
      }
      closedir($dh);
   }
}
$EY_lang = json_decode($request['body']);

if(strtoupper(trim($deleteAllData)) === strtoupper("yes")) {
   // Delete options.
   $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce_enviaya_setting%';");

   // Drop tables
   $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}".PREFIX."_rates");
   $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}".PREFIX."_shipment");
}
