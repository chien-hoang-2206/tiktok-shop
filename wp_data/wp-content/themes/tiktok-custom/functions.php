<?php
require_once get_template_directory() . '/includes/tiktok-api.php';


function tiktok_custom_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus([
        'main_menu' => 'Main Menu',
        'footer_menu' => 'Footer Menu',
    ]);
}
add_action('after_setup_theme', 'tiktok_custom_setup');

function tiktok_custom_enqueue() {
    wp_enqueue_style('tiktok-custom-style', get_stylesheet_uri());
    wp_enqueue_script('tiktok-custom-js', get_template_directory_uri() . '/assets/js/main.js', [], false, true);
}
add_action('wp_enqueue_scripts', 'tiktok_custom_enqueue');


// add roles
function add_custom_roles() {
    add_role('manager', 'Manager', ['read' => true]);
    add_role('seller', 'Seller', ['read' => true]);
    add_role('designer', 'Designer', ['read' => true]);
}
add_action('init', 'add_custom_roles');

// call api
add_action('load-edit.php', function () {
    if ($_GET['post_type'] === 'tiktok_order') {
        $last_call = get_transient('tiktok_order_last_call');

        sync_tiktok_orders();
        if (!$last_call || time() - $last_call > 300) {
            set_transient('tiktok_order_last_call', time(), 300);
        }
    }
});function generate_tiktok_sign($path, $params, $body = null, $app_secret) {
    unset($params['sign'], $params['access_token']);
    ksort($params);

    // Ghép key+value không có dấu =
    $param_string = '';
    foreach ($params as $key => $val) {
        $param_string .= $key . $val;
    }

    // Path + param string
    $sign_string = $path . $param_string;

    // Nếu có body (và KHÔNG multipart), thêm JSON vào
    if (!empty($body) && is_object($body)) {
        $sign_string .= json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // Bọc secret
    $sign_string = $app_secret . $sign_string . $app_secret;

    // Debug để so sánh với tool
    echo "Sign String:\n" . $sign_string . "\n";

    // HMAC SHA256
    return hash_hmac('sha256', $sign_string, $app_secret);
}

function sync_tiktok_orders() {
    $access_token = 'ROW_98lSEwAAAAA1RW7pUGCuxeLtWOMsFNHBkrgXb9nVM6OOGtkzCOXn7G3DNTtJN4DhGO_YCsDXlkUoXvDi6ucj66gAGd3w2npEwFQRtf0nANKgs_YTjdZ6DA';
    $app_key = '6g227phmobr8k';
    $app_secret = 'bec98c8b3dc27bd3dae42ae4fa2357198d105cef';
    $shop_cipher = 'ROW_D5k-pAAAAAC1xzOL8eNCz97fnDk2_pri';
    $shop_id = '7496169812939344750';
    $version = '202309';
    $timestamp = time();
    $page_size = 10;

    $path = '/order/202309/orders/search';
    $params = [
        'app_key' => $app_key,
        'page_size' => $page_size,
        'shop_cipher' => $shop_cipher,
        'shop_id' => $shop_id,
        'timestamp' => $timestamp,
        'version' => $version
    ];

    $body = new stdClass(); // đúng là {}

    // Tạo sign đúng quy trình
    $sign = generate_tiktok_sign($path, $params, $body, $app_secret);
    echo "SIGN: $sign\n";

    // Tạo URL thủ công (để giữ đúng thứ tự param giống lúc ký)
    $query = http_build_query(array_merge($params, [
        'sign' => $sign,
        'access_token' => $access_token,
    ]));

    $url = 'https://open-api.tiktokglobalshop.com' . $path . '?' . $query;

    // Gọi API
    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $access_token,
        ],
        'body' => json_encode($body),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        error_log('TikTok Order Sync Failed: ' . $response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        echo '<pre>';
        print_r(json_decode($body, true));
        echo '</pre>';
        exit;
    }
}
