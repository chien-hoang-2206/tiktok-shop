<?php

function get_tiktok_config()
{
    $page = get_page_by_path('setting-page');
    if (!$page)
        return [];

    $id = $page->ID;

    return [
        'access_token' => get_field('access_token', $id),
        'app_secret' => get_field('app_secret', $id),
        'app_key' => get_field('app_key', $id),
        'shop_cipher' => get_field('shop_cipher', $id),
        'shop_id' => get_field('shop_id', $id),
        'seller_id' => get_field('seller_id', $id),
        'version' => get_field('version', $id),
    ];
}

function generate_tiktok_sign($path, $params, $body = null, $app_secret)
{
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

    // HMAC SHA256
    return hash_hmac('sha256', $sign_string, $app_secret);
}

function sync_tiktok_orders()
{
    $config = get_tiktok_config();
    $access_token = $config['access_token'];
    $app_key = $config['app_key'];
    $app_secret = $config['app_secret'];
    $shop_cipher = $config['shop_cipher'];
    $shop_id = $config['shop_id'];
    $seller_id = $config['seller_id'];
    $version = $config['version'];
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

    $body = new stdClass();

    // Tạo sign đúng quy trình
    $sign = generate_tiktok_sign($path, $params, $body, $app_secret);

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

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (is_wp_error($response)) {
        wp_die('API TikTok Error Access Token: ' . esc_html($response->get_error_message()), 'Toktok API Error', ['response' => 500]);
    }

    if (isset($body['code']) && $body['code'] != 0) {
        error_log('[TikTok API Error ' . $body['code'] . '] ' . $body['message']);
        add_action('admin_notices', function () use ($body) {
            echo '<div class="notice notice-error"><p>TikTok API: ' . esc_html($body['message']) . '</p></div>';
        });
        return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $orders = $data['data']['orders'] ?? [];

    $existing_orders = get_posts([
        'post_type' => 'tiktok_order',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'titles',
    ]);

    $existing_titles = array_map(fn($post) => $post->post_title, $existing_orders);

    foreach ($orders as $order) {
        if (!in_array($order['id'], $existing_titles)) {
            create_tiktok_order_post($order, $shop_id, $seller_id);
        }
    }
}

function create_tiktok_order_post($order, $shop_id, $seller_id)
{
    $order_id = $order['id'] ?? '';
    $shop_code = $shop_id;
    $order_notice = $order['buyer_message'] ?? '';
    $customer_name = $order['buyer_email'] ?? '';
    $total_price = $order['payment']['total_amount'] ?? '';
    $net_revenue = $order['payment']['original_total_product_price'] ?? '';

    if (!$order_id)
        return;

    // Kiểm tra nếu đơn hàng đã tồn tại
    $existing_posts = get_posts([
        'post_type' => 'tiktok_order',
        'meta_key' => 'order_number',
        'meta_value' => $order_id,
        'numberposts' => 1,
        'fields' => 'ids',
    ]);

    if (!empty($existing_posts))
        return;

    // Tìm designer có ít đơn nhất
    $designer_id = find_designer_with_fewest_orders();

    // Tính deadline (sau 2 ngày kể từ hôm nay)
    $deadline = date('d/m/Y', strtotime('+2 days', current_time('timestamp')));

    // Tạo bài viết
    $post_id = wp_insert_post([
        'post_type' => 'tiktok_order',
        'post_status' => 'publish',
        'post_title' => $order_id,
    ]);

    try {
        if ($post_id && !is_wp_error($post_id)) {
            update_field('order_number', $order_id, $post_id);
            update_field('shop_code', $shop_code, $post_id);
            update_field('order_notice', $order_notice, $post_id);
            update_field('customer_name', $customer_name, $post_id);
            update_field('total', $total_price, $post_id);
            update_field('net_revenue', $net_revenue, $post_id);

            update_field('designer', $designer_id, $post_id);
            update_field('deadline', $deadline, $post_id);
            update_field('status', '1', $post_id);
            update_field('seller_id', $seller_id, $post_id);

            update_order_items_from_api($post_id, $order['line_items']);

            $designer_name = 'Unknown';
            if ($designer_id) {
                $designer_user = get_user_by('ID', $designer_id);
                if ($designer_user) {
                    $designer_name = $designer_user->display_name;
                }
            }
            create_notification(
                $seller_id,
                'New Order #' . $order_id . ' (Assigned to ' . $designer_name . ')',
                'Order #' . $order_id . ' has been created and assigned to ' . $designer_name . '.',
                'new_order',
                $post_id
            );
        }
    } catch (\Throwable $th) {
        error_log('[TikTok API Error] ' . $th->getMessage());
        add_action('admin_notices', function () use ($th) {
            echo '<div class="notice notice-error"><p>TikTok API: ' . esc_html($th->getMessage()) . '</p></div>';
        });
    }
}

function update_order_items_from_api($post_id, $line_items)
{
    $order_items = [];

    foreach ($line_items as $item) {
        $order_items[] = [
            'image' => esc_url($item['sku_image'] ?? ''),
            'product_name' => sanitize_text_field($item['product_name'] ?? ''),
            'sku' => sanitize_text_field($item['sku_name'] ?? ''),
            'quantity' => intval($item['quantity'] ?? 1),
            'price' => intval($item['sale_price'] ?? 0)
        ];
    }

    update_post_meta($post_id, 'order_items', $order_items);
}

function find_designer_with_fewest_orders()
{
    $designers = get_users([
        'role' => 'designer',
        'fields' => ['ID'],
    ]);

    $min_orders = null;
    $chosen_id = null;

    foreach ($designers as $designer) {
        $count = new WP_Query([
            'post_type' => 'tiktok_order',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'designer',
                    'value' => $designer->ID,
                    'compare' => '='
                ]
            ],
            'fields' => 'ids',
            'posts_per_page' => -1
        ]);

        $num_orders = $count->found_posts;

        // Ưu tiên chọn người chưa có đơn → return ngay
        if ($num_orders === 0) {
            return $designer->ID;
        }

        // Nếu không ai rảnh, chọn người có ít đơn nhất
        if ($min_orders === null || $num_orders < $min_orders) {
            $min_orders = $num_orders;
            $chosen_id = $designer->ID;
        }
    }

    return $chosen_id;
}
