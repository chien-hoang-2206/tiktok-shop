<?php
// functions.php - TikTok Custom Theme setup

// Theme setup
function tiktok_custom_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    register_nav_menus([
        'primary' => __('Primary Menu', 'tiktok-custom'),
    ]);
}
add_action('after_setup_theme', 'tiktok_custom_theme_setup');

// Enqueue scripts and styles
function tiktok_custom_enqueue_scripts() {
    wp_enqueue_style('tiktok-style', get_stylesheet_uri());
    wp_enqueue_script('tiktok-script', get_template_directory_uri() . '/assets/js/main.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'tiktok_custom_enqueue_scripts');