<?php
require_once get_template_directory() . '/includes/tiktok-api.php';
require_once get_template_directory() . '/handle/functions.php';

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
});


add_filter('piklist_post_types', function($post_types) {
    $post_types['tiktok_order'] = [
        'post_type' => 'tiktok_order',
        'labels' => piklist('post_type_labels', 'TikTok Order'),
        'title' => __('TikTok Order'),
        'public' => true,
        'menu_icon' => 'dashicons-cart',
        'supports' => ['title'],
    ];
    return $post_types;
});
