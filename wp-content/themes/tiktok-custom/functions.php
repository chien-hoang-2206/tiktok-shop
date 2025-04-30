<?php
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
