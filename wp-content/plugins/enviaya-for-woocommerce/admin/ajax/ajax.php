<?php

function get_enviaya_accounts()
{
    global $EY_lang;
    $dir = WP_PLUGIN_DIR.'/enviaya-for-woocommerce/lang/';
    $request = wp_remote_get( content_url().'/plugins/enviaya-for-woocommerce/lang/en_US.json');
    if (is_dir($dir)){
        if ($dh = opendir($dir)){
            while (($file = readdir($dh)) !== false) {
                if ($file === get_locale().'.json') {
                    $request = wp_remote_get( content_url().'/plugins/enviaya-for-woocommerce/lang/'.get_locale().'.json');
                }
            }
            closedir($dh);
        }
    }
    $EY_lang = json_decode($request['body']);

    global $woocommerce;

    if (isset($_POST['api_key'])) {
        $response = EYHelper::libAPI()->get_accounts($_POST['api_key']);

        if (!isset($response->errors) && isset($response->enviaya_accounts)) {
            $acc_array = $response->enviaya_accounts;
        }

        if (empty($acc_array)) {
            echo ("<option value='none'>{$EY_lang->to_retrieve_your_billing_accounts}</option>");
        } else {
            foreach ($acc_array as $acc) {
                if ($acc->status == 'active') {
                    echo('<option value="' . $acc->account . '">' . $acc->alias . ' (' . $acc->account .  ')</option>');
                }
            }
        }
    }
    wp_die();
}

add_action('wp_ajax_enviaya_ajax_get_accounts', 'get_enviaya_accounts');

function get_enviaya_origin_address()
{
    $dir = WP_PLUGIN_DIR.'/enviaya-for-woocommerce/lang/';
    $request = wp_remote_get( content_url().'/plugins/enviaya-for-woocommerce/lang/en_US.json');
    if (is_dir($dir)){
        if ($dh = opendir($dir)){
            while (($file = readdir($dh)) !== false) {
                if ($file === get_locale().'.json') {
                    $request = wp_remote_get( content_url().'/plugins/enviaya-for-woocommerce/lang/'.get_locale().'.json');
                }
            }

            closedir($dh);
        }
    }
    $EY_lang = json_decode($request['body']);

    global $woocommerce;

    if (isset($_POST['api_key'])) {

        $props = [
            'api_key' => $_POST['api_key'],
            'param' => '&only_own=true',
        ];

        $response = EYHelper::libAPI()->directions($props);

        if (empty($response->directions)) {
            echo ("<option value='none'>{$EY_lang->to_retrieve_your_billing_accounts}</option>");
        } else {
            if (isset($response->directions) && !isset($response->errors)) {
                foreach ($response->directions as $address) {
                    if (!empty($address->full_name) || !empty($address->company))
                        echo('<option value="' . str_replace('"', '||', json_encode($address)) . '">' .
                            (!empty($address->full_name) ? $address->full_name : $address->company) . '</option>');
                }
            } else {
                $origin_addresses['none'] = $EY_lang->get_origin_addresses;
            }
        }
    }
    wp_die();
}

add_action('wp_ajax_enviaya_ajax_get_origin_address', 'get_enviaya_origin_address');

function downloadApiLogs(){
    global $EY_lang;
    $dir = WP_PLUGIN_DIR.'/enviaya-for-woocommerce/lang/';
    $request = wp_remote_get( content_url().'/plugins/enviaya-for-woocommerce/lang/en_US.json');
    if (is_dir($dir)){
        if ($dh = opendir($dir)){
            while (($file = readdir($dh)) !== false) {
                if ($file === get_locale().'.json') {
                    $request = wp_remote_get( content_url().'/plugins/enviaya-for-woocommerce/lang/'.get_locale().'.json');
                }
            }
            closedir($dh);
        }
    }
    $EY_lang = json_decode($request['body']);

    if (!class_exists('ZipArchive')) {
        die($EY_lang->no_zip_extenstion);
    }

    $date = new \DateTime();
    $fileName = 'API_logs_' . $date->format('H_i_s d-m-Y') . '.zip';
    $filePath = ABSPATH . "wp-content/" . $fileName;
    $zip = new ZipArchive();
    $zip->open($fileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zipOptions = [
        'remove_all_path' => TRUE,
    ];
    $zip->addGlob(ENVIAYA_API_LOGS_FOLDER. "/*", 0, $zipOptions);

    $zip->close();
    header("Expires: 0");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header('Cache-Control: pre-check=0, post-check=0, max-age=0', false);
    header("Pragma: no-cache");
    header("Content-type: zip");
    header("Content-Disposition:attachment; filename={$fileName}");
    header("Content-Type: application/force-download");
    readfile($fileName);
    wp_die();
}
add_action('wp_ajax_ey_download_api_logs', 'downloadApiLogs');

function deleteApiLogs(){
    $files = glob(ENVIAYA_API_LOGS_FOLDER . '/*');
    foreach($files as $file){
        if(is_file($file))
            try{
                unlink($file);
            } catch(\Exeption $e){
                echo $e;
            }
    }
    wp_die();
}
add_action('wp_ajax_ey_delete_api_logs', 'deleteApiLogs');
// function downloadLabel(){
//     global $EY_lang;
//     $id = $_GET['id'];
//     $url = "https://".$EY_lang->api_domain.'/shipping/shipments/download_label?id='.$id;
//     logAPI("Request label by id {$id} url: {$url}", "label_request");
//     echo $url;
//     wp_die();
// }
// add_action('wp_ajax_enviaya_download_label', 'downloadLabel');
