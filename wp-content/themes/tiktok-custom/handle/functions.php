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

function get_order_stats_last_7_days($status_filter = null, $return_revenue = false)
{
    $results = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = date('d/m', strtotime($date));

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

        if ($status_filter !== null) {
            $query_args['meta_query'] = [
                [
                    'key' => 'status',
                    'value' => $status_filter,
                    'compare' => '=',
                ]
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

    return $results;
}

function render_chart_widget($widget_id, $label, $data, $color)
{
    $labels = [];
    for ($i = 6; $i >= 0; $i--) {
        $labels[] = date('d/m', strtotime("-$i days"));
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
                beginAtZero: true,
                ticks: {
                  stepSize: 1
                }
              }
            }
          }
        });
      });
    </script>';
}


?>