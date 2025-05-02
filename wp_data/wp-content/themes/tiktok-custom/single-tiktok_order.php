<?php get_header(); 

$post_id = get_the_ID(); // ✅ khai báo biến đúng

$post = get_post($post_id);
if ($post && $post->post_type === 'tiktok_order') {
    echo '<h2>' . esc_html($post->post_title) . '</h2>';
    echo '<div>' . apply_filters('the_content', $post->post_content) . '</div>';

    // Lấy toàn bộ custom field (meta)
    $order_items = get_post_meta($post_id, 'order_items', true);

    var_dump($order_items);
    exit;
    if (is_array($order_items)) {
        echo '<h3>Danh sách sản phẩm:</h3>';
        foreach ($order_items as $index => $item) {
            echo '<div style="margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">';
            echo '<strong>Sản phẩm ' . ($index + 1) . '</strong><br>';
            
            if (!empty($item['image'][0])) {
                echo '<img src="' . esc_url($item['image'][0]) . '" width="100" style="margin-bottom:10px;"><br>';
            }
    
            echo 'Tên: ' . esc_html($item['product_name']) . '<br>';
            echo 'SKU: ' . esc_html($item['sku']) . '<br>';
            echo 'Số lượng: ' . intval($item['quantity']) . '<br>';
            echo 'Giá: ' . number_format((int) $item['price']) . ' VND<br>';
            echo '</div>';
        }
    } else {
        echo '<p><em>Không có sản phẩm nào trong đơn hàng.</em></p>';
  }
}

?>
<main>
  <article>
    <h1><?php the_title(); ?></h1>
    <?php the_content(); ?>
  </article>
</main>
<?php get_footer(); ?>
