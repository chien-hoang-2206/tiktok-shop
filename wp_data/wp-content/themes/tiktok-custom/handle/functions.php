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

function create_notification($user_id, $title, $message, $type = 'general', $related_post = 0, $comment_id = null)
{
    $notification_id = wp_insert_post([
        'post_type' => 'notification',
        'post_title' => $title,
        'post_content' => $message,
        'post_status' => 'publish',
        'meta_input' => [
            'user_id' => $user_id,
            'status' => 'unread',
            'related_post' => $related_post,
            'type' => $type,
            'comment_id' => $comment_id,
        ],
    ]);

    return $notification_id;
}

// designer dashboard

function render_tiktok_order_summary_widget()
{
    $user_id = get_current_user_id();
    $month = date('Y-m');

    // Lấy tất cả đơn được gán cho designer hiện tại
    $orders = get_posts([
        'post_type' => 'tiktok_order',
        'meta_query' => [
            [
                'key' => 'designer',
                'value' => $user_id,
                'compare' => '=',
            ]
        ],
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids'
    ]);

    $completed = 0;
    $revising = 0;
    $revenue_total = 0;
    $revenue_month = 0;

    foreach ($orders as $order_id) {
        $status = get_post_meta($order_id, 'status', true);
        $created_at = get_the_date('Y-m', $order_id);
        $revenue = (int) get_post_meta($order_id, 'net_revenue', true);

        $revenue_total += $revenue;
        if ($created_at === $month) {
            $revenue_month += $revenue;
        }

        if ($status == 2)
            $revising++;
        if ($status == 3)
            $completed++;
    }

    echo "<ul style='line-height:1.9em;font-size:14px'>";
    echo "<li>🎨 <strong>Assigned Orders:</strong> " . count($orders) . "</li>";
    echo "<li>✅ <strong>Completed:</strong> $completed</li>";
    echo "<li>✏️ <strong>Revising:</strong> $revising</li>";
    echo "<li>💰 <strong>Total Revenue:</strong> " . number_format($revenue_total) . "VNĐ</li>";
    echo "<li>📅 <strong>This Month Revenue:</strong> " . number_format($revenue_month) . "VNĐ</li>";
    echo "</ul>";
}

// seller dashboard

function render_tiktok_order_today_summary_widget()
{
    $month = date('Y-m');
    $today = date('Y-m-d');
    $current_user_id = get_current_user_id();
    // Đơn hàng hôm nay
    $today_orders = get_posts([
        'post_type' => 'tiktok_order',
        'post_status' => 'publish',
        'date_query' => [
            [
                'after' => $today . ' 00:00:00',
                'before' => $today . ' 23:59:59',
                'inclusive' => true
            ]
        ],
        'meta_query' => [
            [
                'key' => 'seller_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ],
        'numberposts' => -1,
        'fields' => 'ids'
    ]);

    $completed = 0;
    $revising = 0;
    $revenue_today = 0;
    $revenue_month = 0;

    foreach ($today_orders as $order_id) {
        $status = get_post_meta($order_id, 'status', true);
        $revenue = (int) get_post_meta($order_id, 'net_revenue', true);

        $revenue_today += $revenue;

        if ($status == 2)
            $revising++;
        if ($status == 3)
            $completed++;
    }

    // Doanh thu tháng
    $month_orders = get_posts([
        'post_type' => 'tiktok_order',
        'post_status' => 'publish',
        'date_query' => [
            [
                'after' => $month . '-01',
                'before' => $month . '-31',
                'inclusive' => true
            ]
        ],
        'meta_query' => [
            [
                'key' => 'seller_id',
                'value' => $current_user_id,
                'compare' => '='
            ]
        ],
        'numberposts' => -1,
        'fields' => 'ids'
    ]);

    foreach ($month_orders as $oid) {
        $revenue_month += (int) get_post_meta($oid, 'net_revenue', true);
    }

    echo "<ul style='line-height:1.9em;font-size:14px'>";
    echo "<li>📦 <strong>Today Orders:</strong> " . count($today_orders) . "</li>";
    echo "<li>✏️ <strong>Revising:</strong> $revising</li>";
    echo "<li>✅ <strong>Completed:</strong> $completed</li>";
    echo "<li>💰 <strong>Today Revenue:</strong> " . number_format($revenue_today) . " VNĐ</li>";
    echo "<li>📅 <strong>This Month Revenue:</strong> " . number_format($revenue_month) . " VNĐ</li>";
    echo "</ul>";
}

function render_widget_total_orders_month()
{
    $data = get_order_stats_current_month(null, false);
    render_chart_widget('chart_total_orders_month', 'Total Orders This Month', $data['data'], '#0073aa', $data['labels']);
}

function render_widget_completed_orders()
{
    $data = get_order_stats_current_month('3', false); // status = 3
    render_chart_widget('chart_completed_orders', 'Completed Orders This Month', $data['data'], '#28a745', $data['labels']);
}

function render_widget_revising_orders()
{
    $data = get_order_stats_current_month('2', false); // status = 2
    render_chart_widget('chart_revising_orders', 'Revising Orders This Month', $data['data'], '#fd7e14', $data['labels']);
}

function render_widget_revenue()
{
    $data = get_order_stats_current_month(null, true);
    render_chart_widget('chart_revenue_orders', 'Revenue This Month', $data['data'], '#6f42c1', $data['labels']);
}


function get_order_stats_current_month($status_filter = null, $return_revenue = false)
{
    $results = [];
    $labels = [];
    $user_id = get_current_user_id();
    $user = wp_get_current_user();

    $days_in_month = date('t');
    $month = date('Y-m');

    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
        $label = date('d/m', strtotime($date));
        $labels[] = $label;

        $query_args = [
            'post_type' => 'tiktok_order',
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $date . ' 00:00:00',
                    'before' => $date . ' 23:59:59',
                    'inclusive' => true,
                ]
            ],
            'numberposts' => -1,
            'fields' => 'ids',
        ];

        // Nếu là seller thì chỉ lấy đơn của họ
        if (in_array('seller', (array) $user->roles)) {
            $query_args['meta_query'][] = [
                'key' => 'seller_id',
                'value' => $user_id,
                'compare' => '='
            ];
        }

        if ($status_filter !== null) {
            $query_args['meta_query'][] = [
                'key' => 'status',
                'value' => $status_filter,
                'compare' => '=',
            ];
        }

        $orders = get_posts($query_args);

        if ($return_revenue) {
            $sum = 0;
            foreach ($orders as $id) {
                $sum += (int) get_post_meta($id, 'net_revenue', true);
            }
            $results[] = $sum;
        } else {
            $results[] = count($orders);
        }
    }

    return [
        'labels' => $labels,
        'data' => $results
    ];
}

// manager dashboard

function render_manager_dashboard_widget()
{
    $selected_seller = $_GET['filter_seller'] ?? '';
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');

    echo '<form method="get" action="' . admin_url('index.php') . '" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; max-width: 600px;">';

    echo '<input type="hidden" name="dashboard_widget" value="manager_dashboard_widget">';

    echo '<div style="display: flex; align-items: center; gap: 12px;">';
    echo '<label for="filter_seller" style="font-weight: 600; min-width: 60px;">Seller:</label>';
    echo '<select name="filter_seller" id="filter_seller" style="flex: 1; padding: 6px;">';
    echo '<option value="">-- All Sellers --</option>';
    $sellers = get_users(['role' => 'seller']);
    foreach ($sellers as $seller) {
        $selected = $selected_seller == $seller->ID ? 'selected' : '';
        echo "<option value='{$seller->ID}' $selected>{$seller->display_name}</option>";
    }
    echo '</select>';
    echo '<button type="submit" class="button button-primary">Filter</button>';
    echo '</div>';

    echo '<div style="display: flex; align-items: center; gap: 12px;">';
    echo '<label for="start_date" style="font-weight: 600; min-width: 60px;">From:</label>';
    echo '<input type="date" id="start_date" name="start_date" value="' . esc_attr($start_date) . '" style="padding: 6px;">';
    echo '<label for="end_date" style="font-weight: 600; min-width: 40px;">To:</label>';
    echo '<input type="date" id="end_date" name="end_date" value="' . esc_attr($end_date) . '" style="padding: 6px;">';
    echo '</div>';

    echo '</form>';
}

function manager_render_chart_revenue()
{
    $selected_seller = $_GET['filter_seller'] ?? '';
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    $data = get_filtered_revenue_data($selected_seller, $start_date, $end_date);
    render_chart_widget('chart_manager_revenue', 'Revenue (VNĐ)', $data['data'], '#0073aa', $data['labels']);
}

function get_filtered_revenue_data($seller_id = '', $start = '', $end = '')
{
    $results = [];
    $labels = [];

    $start_time = strtotime($start);
    $end_time = strtotime($end);

    while ($start_time <= $end_time) {
        $date = date('Y-m-d', $start_time);
        $label = date('d/m', $start_time);
        $labels[] = $label;

        $args = [
            'post_type' => 'tiktok_order',
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $date . ' 00:00:00',
                    'before' => $date . ' 23:59:59',
                    'inclusive' => true,
                ]
            ],
            'numberposts' => -1,
            'fields' => 'ids',
        ];

        if (!empty($seller_id)) {
            $args['meta_query'] = [
                [
                    'key' => 'seller_id',
                    'value' => $seller_id,
                    'compare' => '='
                ]
            ];
        }

        $orders = get_posts($args);
        $sum = 0;
        foreach ($orders as $id) {
            $sum += (int) get_post_meta($id, 'net_revenue', true);
        }

        $results[] = $sum;
        $start_time = strtotime('+1 day', $start_time);
    }

    return ['data' => $results, 'labels' => $labels];
}


function render_chart_widget($widget_id, $label, $data, $color, $labels = null)
{
    if (!$labels) {
        for ($i = 6; $i >= 0; $i--) {
            $labels[] = date('d/m', strtotime("-$i days"));
        }
    }

    echo '<canvas id="' . esc_attr($widget_id) . '" height="200"></canvas>';
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
    echo '<script>
      document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById("' . esc_js($widget_id) . '").getContext("2d");
        new Chart(ctx, {
          type: "line",
          data: {
            labels: ' . json_encode($labels) . ',
            datasets: [{
              label: "' . esc_js($label) . '",
              data: ' . json_encode($data) . ',
              borderColor: "' . $color . '",
              fill: false,
              tension: 0.3
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: {
                beginAtZero: true
              }
            }
          }
        });
      });
    </script>';
}

function manager_render_chart_order_count()
{
    $selected_seller = $_GET['filter_seller'] ?? '';
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');

    $data = get_filtered_order_count_data($selected_seller, $start_date, $end_date);
    render_chart_widget('chart_manager_order_count', 'Orders per Day', $data['data'], '#fd7e14', $data['labels']);
}
function get_filtered_order_count_data($seller_id, $start_date, $end_date)
{
    $results = [];
    $labels = [];

    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

    foreach ($period as $date) {
        $day = $date->format('Y-m-d');
        $label = $date->format('d/m');
        $labels[] = $label;

        $args = [
            'post_type' => 'tiktok_order',
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $day . ' 00:00:00',
                    'before' => $day . ' 23:59:59',
                    'inclusive' => true,
                ]
            ],
            'meta_query' => [],
            'numberposts' => -1,
            'fields' => 'ids',
        ];

        if ($seller_id) {
            $args['meta_query'][] = [
                'key' => 'seller_id',
                'value' => $seller_id,
                'compare' => '='
            ];
        }

        $orders = get_posts($args);
        $results[] = count($orders);
    }

    return ['labels' => $labels, 'data' => $results];
}

?>