<?php

require_once get_template_directory() . '/includes/tiktok-api.php';
require_once get_template_directory() . '/handle/functions.php';
require_once get_template_directory() . '/acf/acf-fields.php';

add_action('admin_enqueue_scripts', function ($hook) {
    wp_enqueue_script(
        'tiktok-order-admin-js',
        get_stylesheet_directory_uri() . '/assets/js/main.js',
        ['jquery'],
        '1.0',
        true
    );
});

// add roles
function add_custom_roles()
{
    add_role('manager', 'Manager', ['read' => true]);
    add_role('seller', 'Seller', ['read' => true]);
    add_role('designer', 'Designer', ['read' => true]);
}
add_action('init', 'add_custom_roles');

// disable category
add_action('init', function () {
    unregister_taxonomy_for_object_type('category', 'tiktok_order');
}, 100);

// call api
add_action('load-edit.php', function () {
    if ($_GET['post_type'] === 'tiktok_order') {
        $last_call = get_transient('tiktok_order_last_call');

        sync_tiktok_orders();
        // if (!$last_call || time() - $last_call > 300) {
        //     set_transient('tiktok_order_last_call', time(), 300);
        // }
    }
});

// custom order list start
add_filter('pre_comment_approved', function ($approved, $commentdata) {
    return 1; // 1 = approved
}, 10, 2);


add_filter('manage_tiktok_order_posts_columns', function ($columns) {
    unset($columns['title']);

    return [
        'cb' => $columns['cb'],
        'order_number' => 'Order Number',
        'order_items' => 'Products',
        'designer' => 'Designer',
        'published_at' => 'Published At',
        'deadline' => 'Deadline',
        // 'update_link' => 'Update Link',
        'status' => 'Status',
        'actions' => 'Actions',
    ];
});

add_action('manage_tiktok_order_posts_custom_column', function ($column, $post_id) {
    switch ($column) {
        case 'order_number':
            $order_number = get_post_meta($post_id, 'order_number', true) ?: '_';
            $edit_link = get_edit_post_link($post_id);

            echo '<a href="' . esc_url($edit_link) . '">' . esc_html($order_number) . '</a>';
            break;

        case 'order_items':
            $items = get_post_meta($post_id, 'order_items', true);
            if (!is_array($items)) {
                echo 'No items';
                return;
            }

            echo '<div style="display: flex; gap: 6px;">';
            foreach ($items as $item) {
                $img = $item['image'] ?? '';
                if ($img) {
                    echo '<img src="' . esc_url($img) . '" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;" />';
                }
            }
            echo '</div>';
            break;


        case 'status':
            $status_code = get_post_meta($post_id, 'status', true);

            $status_map = [
                '1' => ['label' => 'Waiting for Design', 'color' => '#6c757d'],
                '2' => ['label' => 'Revising Design', 'color' => '#fd7e14'],
                '4' => ['label' => 'Pending', 'color' => '#FF9F00'],
                '3' => ['label' => 'Completed', 'color' => '#28a745'],
            ];

            $status = $status_map[$status_code] ?? ['label' => 'Waiting for Design', 'color' => '#6c757d'];

            echo '<span style="
                    display: inline-block;
                    padding: 3px 8px;
                    border-radius: 6px;
                    background-color: ' . $status['color'] . ';
                    color: white;
                    font-size: 12px;
                ">' . esc_html($status['label']) . '</span>';
            break;

        case 'designer':
            $designer_id = get_post_meta($post_id, 'designer', true);

            if ($designer_id) {
                $user = get_user_by('ID', $designer_id);
                if ($user && in_array('designer', (array) $user->roles)) {
                    echo esc_html($user->display_name);
                } else {
                    echo '<span style="color: red;">Data error</span>';
                }
            } else {
                echo 'Not assigned yet';
                echo 'Chưa phân công';
            }

            break;

        case 'update_link':
            $link = get_post_meta($post_id, 'design_link', true);
            $button_style = "display: inline-block;
                padding: 4px 10px;
                background-color: #0073aa;
                color: #fff;
                border-radius: 4px;
                text-decoration: none;
                font-size: 13px;
            ";
            echo $link ? "<a href='$link' target='_blank' style='$button_style'>View Design</a>" : 'Not updated yet';
            break;

        case 'published_at':
            echo get_the_date('d/m/Y H:i', $post_id);
            break;

        case 'deadline':
            $deadline = get_post_meta($post_id, 'deadline', true);
            if (!$deadline) {
                echo 'No deadline';
                return;
            }

            $formats = ['Ymd'];
            $datetime = null;

            foreach ($formats as $format) {
                $dt = DateTime::createFromFormat($format, $deadline);
                if ($dt && $dt->format($format) === $deadline) {
                    $datetime = $dt;
                    break;
                }
            }

            if ($datetime) {
                echo $datetime->format('d-m-Y');
            } else {
                echo 'Invalid date format: ' . htmlspecialchars($deadline);
            }
            break;

        case 'actions':
            $comment_link = get_permalink($post_id);

            echo '
                    <div style="display: flex; gap: 8px;">
                        <a  href="' . esc_url($comment_link) . '" class="request-revision" data-id="' . $post_id . '" style="
                            display: inline-block;
                            padding: 4px 10px;
                            background-color: #fd7e14;
                            color: #fff;
                            border-radius: 4px;
                            text-decoration: none;
                            font-size: 13px;
                        ">Comments</a>
            
                        <a href="#" class="mark-complete" data-id="' . $post_id . '" style="
                            display: inline-block;
                            padding: 4px 10px;
                            background-color: #28a745;
                            color: #fff;
                            border-radius: 4px;
                            text-decoration: none;
                            font-size: 13px;
                        ">Complete</a>
                    </div>
                ';
            break;


    }
}, 10, 2);

// update status done
add_action('wp_ajax_mark_order_complete', function () {
    $post_id = intval($_POST['post_id'] ?? 0);

    if (!$post_id || get_post_type($post_id) !== 'tiktok_order') {
        wp_send_json_error('Invalid post ID');
    }

    update_post_meta($post_id, 'status', '3');

    $seller_id = get_post_meta($post_id, 'seller_id', true);
    $order_number = get_post_meta($post_id, 'order_number', true);
    create_notification(
        $seller_id,
        'Design Completed for Order #' . $order_number,
        'Completed the design for order #' . get_the_title($post_id),
        'Design Completed',
        $post_id
    );

    wp_send_json_success(['message' => 'Status updated to Completed']);
});

// filter date order
add_action('restrict_manage_posts', function () {
    global $typenow;

    if ($typenow === 'tiktok_order') {
        // Lọc theo ngày
        $date_filter = $_GET['filter_by_date'] ?? '';
        echo '<input type="date" name="filter_by_date" value="' . esc_attr($date_filter) . '" style="margin-right:10px;" />';

        // Lọc theo trạng thái đơn hàng
        ?>
        <select name="order_status">
            <option value="">All Status</option>
            <option value="1" <?php selected($_GET['order_status'] ?? '', '1'); ?>>Waiting for Design</option>
            <option value="2" <?php selected($_GET['order_status'] ?? '', '2'); ?>>Revising Design</option>
            <option value="4" <?php selected($_GET['order_status'] ?? '', '3'); ?>>Pending</option>
            <option value="3" <?php selected($_GET['order_status'] ?? '', '3'); ?>>Completed</option>
        </select>
        <?php
    }
});

add_action('pre_get_posts', function ($query) {
    if (
        is_admin() &&
        $query->is_main_query() &&
        $query->get('post_type') === 'tiktok_order'
    ) {
        // Lọc theo ngày
        if (!empty($_GET['filter_by_date'])) {
            $filter_date = sanitize_text_field($_GET['filter_by_date']);
            $start = date('Y-m-d 00:00:00', strtotime($filter_date));
            $end = date('Y-m-d 23:59:59', strtotime($filter_date));

            $query->set('date_query', [
                [
                    'after' => $start,
                    'before' => $end,
                    'inclusive' => true,
                ],
            ]);
        }

        // Lọc theo status nếu có
        if (!empty($_GET['order_status'])) {
            $meta_query = $query->get('meta_query') ?: [];
            $meta_query[] = [
                'key' => 'status',
                'value' => intval($_GET['order_status']),
                'compare' => '=',
            ];
            $query->set('meta_query', $meta_query);
        }

        // Sắp xếp mặc định
        $query->set('orderby', 'date');
        $query->set('order', 'DESC');
    }
});

// custom order list end


// fix comment form upload
add_action('wp_footer', function () {
    if (!is_singular())
        return;
    ?>
    <script>
        const commentForm = document.getElementById('commentform');
        if (commentForm && !commentForm.querySelector('#comment-attachment')) {
            const input = document.createElement('input');
            input.type = 'file';
            input.name = 'comment_attachment';
            input.id = 'comment-attachment';
            input.style = 'display: none;';
            input.multiple = true;
            input.style.marginTop = '12px';
            commentForm.appendChild(input);

            commentForm.setAttribute('enctype', 'multipart/form-data');
        }
    </script>
    <?php
});

// custom filter post with role
add_action('pre_get_posts', function ($query) {
    if (
        is_admin() &&
        $query->is_main_query() &&
        $query->get('post_type') === 'notification' &&
        !current_user_can('administrator')
    ) {
        $user_id = get_current_user_id();

        $query->set('meta_query', [
            [
                'key' => 'user_id',
                'value' => $user_id,
                'compare' => '=',
            ]
        ]);
    }

    if (
        is_admin() &&
        $query->is_main_query() &&
        $query->get('post_type') === 'tiktok_order' &&
        current_user_can('designer')
    ) {
        $user_id = get_current_user_id();

        $query->set('meta_query', [
            [
                'key' => 'designer',
                'value' => $user_id,
                'compare' => '=',
            ]
        ]);
        $query->set('orderby', 'date');
        $query->set('order', 'DESC');
    }
});


// send notification
add_action('comment_post', function ($comment_id, $approved) {
    if (!$approved)
        return;

    $comment = get_comment($comment_id);
    $user = get_userdata($comment->user_id);
    if (!$user)
        return;

    $roles = (array) $user->roles;
    $post_id = $comment->comment_post_ID;
    $author_name = $user->display_name;

    // Nếu là designer → gửi tới seller
    if (in_array('designer', $roles)) {
        $seller_id = get_post_meta($post_id, 'seller_id', true);
        create_notification(
            $seller_id,
            'New Comment from ' . $author_name,
            $author_name . ' commented on order #' . get_the_title($post_id),
            'comment',
            $post_id,
            $comment_id
        );
    }

    if (in_array('seller', $roles) || in_array('manager', $roles)) {
        $designer_id = get_post_meta($post_id, 'designer', true);
        if ($designer_id) {
            create_notification(
                $designer_id,
                'New Comment from ' . $author_name,
                $author_name . ' replied on order #' . get_the_title($post_id),
                'comment',
                $post_id,
                $comment_id
            );
        }
    }

}, 10, 2);

add_filter('map_meta_cap', function ($caps, $cap, $user_id, $args) {
    if ($cap === 'edit_comment') {
        $comment_id = $args[0] ?? 0;
        $comment = get_comment($comment_id);

        if (!$comment)
            return $caps;

        // Nếu user không phải là tác giả của comment → từ chối
        if ((int) $comment->user_id !== (int) $user_id) {
            return ['do_not_allow'];
        }
    }

    return $caps;
}, 10, 4);

add_filter('manage_notification_posts_columns', function ($columns) {
    $columns['view_link'] = 'View';
    return $columns;
});

add_action('manage_notification_posts_custom_column', function ($column, $post_id) {
    if ($column === 'view_link') {
        $related_post_id = get_post_meta($post_id, 'related_post', true);
        $comment_id = get_post_meta($post_id, 'comment_id', true);

        if ($related_post_id) {
            $link = get_permalink($related_post_id);
            if ($comment_id) {
                $link .= '#comment-' . $comment_id;
            }

            echo '<a href="' . esc_url($link) . '" 
            style="
            display: inline-block;
            padding: 4px 10px;
            background-color: #28a745;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            "
            class="button button-small">View</a>';
        } else {
            echo '—';
        }
    }
}, 10, 2);


// dashboard custom

add_action('wp_dashboard_setup', function () {
    $user = wp_get_current_user();
    if (in_array('designer', (array) $user->roles)) {
        wp_add_dashboard_widget(
            'tiktok_order_summary_widget',
            '📊 TikTok Order Dashboard',
            'render_tiktok_order_summary_widget'
        );
        wp_add_dashboard_widget('widget_revising_orders', '✏️ Revising Orders', 'render_widget_revising_orders');
        wp_add_dashboard_widget('widget_completed_orders', '✅ Completed Orders', 'render_widget_completed_orders');
    }
    if (in_array('seller', (array) $user->roles)) {
        wp_add_dashboard_widget('tiktok_order_summary_widget', "📅 Today's Summary", 'render_tiktok_order_today_summary_widget');
        wp_add_dashboard_widget('tiktok_order_month_summary', '📊 Monthly Summary', 'render_tiktok_order_month_summary_widget');
        wp_add_dashboard_widget('widget_total_orders', '📦 Total Orders', 'render_widget_total_orders_month');
        wp_add_dashboard_widget('widget_revising_orders', '✏️ Revising Orders', 'render_widget_revising_orders');
        wp_add_dashboard_widget('widget_completed_orders', '✅ Completed Orders', 'render_widget_completed_orders');
        wp_add_dashboard_widget('widget_revenue', '💰 Revenue', 'render_widget_revenue');
    };
    if (in_array('manager', (array) $user->roles)) {
        wp_add_dashboard_widget('tiktok_order_month_summary', '📊 Monthly Summary', 'render_tiktok_order_month_summary_widget');
        wp_add_dashboard_widget('widget_total_orders', '📦 Total Orders', 'render_widget_total_orders_month');
        wp_add_dashboard_widget('widget_revenue', '💰 Revenue', 'render_widget_revenue');

        wp_add_dashboard_widget(
            'manager_dashboard_widget',
            '📈 Manager Seller',
            'render_manager_dashboard_widget'
        );
        wp_add_dashboard_widget(
            'manager_render_chart_revenue',
            '💰 Revenue',
            'manager_render_chart_revenue'
        );
        wp_add_dashboard_widget(
            'render_order_count',
            '🎨 Order Count',
            'manager_render_chart_order_count'
        );
    }
});

add_action('admin_footer-post.php', function () {
    $user = wp_get_current_user();
    if (!in_array('designer', $user->roles)) return;

    $readonly_fields = [
        '_post_meta[order_number]',
        '_post_meta[order_notice]',
        '_post_meta[shop_code]',
        '_post_meta[total]',
        '_post_meta[net_revenue]',
        '_post_meta[customer_name]',
        'acf[field_6812677e8a305]',
    ];
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const fieldNames = <?php echo json_encode($readonly_fields); ?>;
        const maxAttempts = 20;
        let attempts = 0;

        const lockFields = () => {
            let allFound = true;

            fieldNames.forEach(name => {
                const input = document.querySelector(`[name="${name}"]`);
                if (input && !input.readOnly) {
                    input.readOnly = true;
                    input.style.backgroundColor = '#f9f9f9';
                    input.title = 'Chỉ admin có thể sửa';
                }
                if (!input) allFound = false;
            });

            if (++attempts >= maxAttempts || allFound) clearInterval(timer);
        };

        const timer = setInterval(lockFields, 300);
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
    const selectIds = [
        'acf-field_681871d83bd20',
        'acf-field_6812677e8a305',
    ];

    selectIds.forEach(id => {
        const select = document.getElementById(id);
        if (select) {
        // Tạo input hidden để giữ giá trị
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = select.name;
        hidden.value = select.value;

        // Thêm hidden trước select
        select.parentNode.insertBefore(hidden, select);

        // Vô hiệu hóa select
        select.disabled = true;
        select.title = 'Chỉ admin có thể sửa';

        // Optional: Làm mờ giao diện Select2 nếu có
        const wrapper = select.closest('.acf-input');
        if (wrapper) {
            wrapper.style.pointerEvents = 'none';
            wrapper.style.opacity = 0.7;
        }
        }
    });

    });
    </script>
    <?php
});


function enqueue_custom_admin_styles($hook) {
    wp_enqueue_style(
        'custom-admin-style',
        get_stylesheet_directory_uri() . '/assets/css/admin-style.css',
        [],
        '1.0'
    );
}
add_action('admin_enqueue_scripts', 'enqueue_custom_admin_styles');
