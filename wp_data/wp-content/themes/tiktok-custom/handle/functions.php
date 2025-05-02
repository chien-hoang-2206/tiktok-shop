<?php
function get_web_config()
{
    $page = get_page_by_path('setting-page');
    if (!$page)
        return [];

    $id = $page->ID;

    $config = [
        'access_token' => get_field('access_token', $id),
        'app_secret' => get_field('app_secret', $id),
        'app_key' => get_field('app_key', $id),
        'shop_cipher' => get_field('shop_cipher', $id),
        'shop_id' => get_field('shop_id', $id),
        'version' => get_field('version', $id),
    ];

    foreach ($config as $key => $value) {
        if (empty($value)) {
            error_log("⚠️ Thiếu cấu hình: $key trong setting-page");
        }
    }

    return $config;
}

?>