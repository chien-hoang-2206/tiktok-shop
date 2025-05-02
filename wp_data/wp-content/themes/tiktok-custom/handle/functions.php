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

function create_notification($user_id, $title, $message, $type = 'general', $related_post = 0) {
    $notification_id = wp_insert_post([
        'post_type' => 'notification',
        'post_title' => $title,
        'post_content' => $message,
        'post_status' => 'publish',
        'meta_input' => [
            'user_id' => $user_id,
            'status' => 'unread',
            'type' => $type,
            'related_post' => $related_post,
        ],
    ]);
    return $notification_id;
}

?>