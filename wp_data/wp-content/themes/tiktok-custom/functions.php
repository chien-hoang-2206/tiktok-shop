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
        'deadline' => 'Deadline',
        'update_link' => 'Update Link',
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
        case 'deadline':
            $deadline = get_post_meta($post_id, 'deadline', true);
            $datetime = new DateTime($deadline);
            echo $datetime->format('d-m-Y');
            break;

        case 'actions':
            $comment_link = get_permalink($post_id) . '#respond';

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

    wp_send_json_success(['message' => 'Status updated to Completed']);
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
        $sellers = get_users(['role' => 'seller']);
        foreach ($sellers as $seller) {
            create_notification(
                $seller->ID,
                'New Comment from ' . $author_name,
                $author_name . ' commented on order #' . get_the_title($post_id),
                'comment',
                $post_id,
                $comment_id
            );
        }
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

        if (!$comment) return $caps;

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
