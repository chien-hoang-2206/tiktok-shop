<style>
  .tiktok-order-detail {
    max-width: 960px;
    margin: 70px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    font-family: 'Segoe UI', sans-serif;
  }

  .tiktok-order-detail h1 {
    font-size: 28px;
    margin-bottom: 20px;
    color: #222;
  }

  .order-meta p {
    margin-bottom: 8px;
    font-size: 15px;
  }

  .order-meta strong {
    display: inline-block;
    min-width: 140px;
    color: #444;
  }

  .order-items-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 14px;
  }

  .order-items-table thead {
    background-color: #f8f9fa;
  }

  .order-items-table th,
  .order-items-table td {
    border: 1px solid #dee2e6;
    padding: 10px;
    text-align: center;
  }

  .order-items-table th {
    font-weight: 600;
    color: #333;
  }

  .order-items-table img {
    display: block;
    margin: 0 auto;
  }

  .badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    color: #fff;
    font-size: 12px;
  }

  .badge.gray {
    background-color: #6c757d;
  }

  .badge.orange {
    background-color: #fd7e14;
  }

  .badge.green {
    background-color: #28a745;
  }

  .iframe-wrap {
    margin-top: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    overflow: hidden;
  }

  .iframe-wrap iframe {
    width: 100%;
    height: 480px;
    border: none;
  }

  #respond {
    margin-top: 40px;
    background: #f8f9fa;
    padding: 24px;
    border-radius: 10px;
    box-shadow: 0 0 4px rgba(0, 0, 0, 0.05);
  }

  #respond h3.comment-reply-title {
    font-size: 20px;
    margin-bottom: 20px;
    color: #333;
  }

  .comment-form label {
    font-weight: 600;
    display: block;
    margin-bottom: 6px;
    color: #444;
  }

  .comment-form-comment textarea {
    width: 100%;
    padding: 12px;
    font-size: 15px;
    border-radius: 6px;
    border: 1px solid #ccc;
    resize: vertical;
    box-sizing: border-box;
    min-height: 120px;
    transition: border-color 0.3s ease;
  }

  .comment-form-comment textarea:focus {
    border-color: #0073aa;
    outline: none;
  }

  .form-submit input[type="submit"] {
    background-color: #0073aa;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    font-size: 15px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .form-submit input[type="submit"]:hover {
    background-color: #005d8f;
  }

  .comment-form-attachment {
    margin-top: 16px;
    font-size: 14px;
    color: #555;
  }

  .comment-form-attachment input[type="file"] {
    margin-top: 6px;
  }

  .comment-form-attachment__file-size-notice,
  .comment-form-attachment__file-types-notice,
  .comment-form-attachment__autoembed-links-notice {
    display: block;
    font-size: 13px;
    color: #777;
    margin-top: 8px;
  }

  .commentlist {
    list-style: none;
    padding-left: 0;
    margin-top: 40px;
  }

  .comment {
    margin-bottom: 20px;
    background-color: #f9f9f9;
    border: 1px solid #eaeaea;
    border-radius: 10px;
    padding: 16px;
    position: relative;
  }

  .comment .comment-author {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
  }

  .comment .comment-author img.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
  }

  .comment .fn {
    font-weight: 600;
    font-size: 15px;
    color: #333;
  }

  .comment .commentmetadata {
    font-size: 12px;
    color: #888;
    margin-bottom: 8px;
  }

  .comment p {
    margin: 0 0 10px;
    font-size: 15px;
    color: #222;
  }

  .comment .reply {
    text-align: right;
  }

  .comment .reply a {
    font-size: 13px;
    color: #0073aa;
    text-decoration: none;
  }

  .comment .reply a:hover {
    text-decoration: underline;
  }

  /* Children (replies) */
  .comment .children {
    list-style: none;
    padding-left: 24px;
    margin-top: 16px;
  }

  .comment .children .comment {
    background-color: #fff;
    border-left: 3px solid #0073aa;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
  }
</style>

<main id="primary" class="site-main">
  <?php if (have_posts()):
    while (have_posts()):
      the_post(); ?>
      <div class="tiktok-order-detail">
        <a href="javascript:history.back()" style="
          display: inline-block;
          margin-bottom: 20px;
          padding: 6px 14px;
          background-color: #6c757d;
          color: #fff;
          border-radius: 6px;
          text-decoration: none;
          font-size: 14px;
        ">
          ← Quay lại
        </a>
        <h1>Order #<?php echo esc_html(get_post_meta(get_the_ID(), 'order_number', true)); ?></h1>
        <div class="order-meta">

          <h4>Product List</h4>
          <table class="order-items-table">
            <thead>
              <tr>
                <th style="width: 80px;">Image</th>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Quantity</th>
                <th>Price</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $items = get_post_meta(get_the_ID(), 'order_items', true);
              if (is_array($items)) {
                foreach ($items as $item) {
                  echo '<tr>';
                  echo '<td><img src="' . esc_url($item['image'] ?? '') . '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;" /></td>';
                  echo '<td style="text-align: left;">' . esc_html($item['product_name'] ?? '-') . '</td>';
                  echo '<td>' . esc_html($item['sku'] ?? '-') . '</td>';
                  echo '<td>' . intval($item['quantity'] ?? 1) . '</td>';
                  echo '<td>' . number_format($item['price'] ?? 0) . 'VNĐ</td>';
                  echo '</tr>';
                }
              } else {
                echo '<tr><td colspan="5">No items found.</td></tr>';
              }
              ?>
            </tbody>
          </table>


          <?php
          // Status
          $status_code = get_post_meta(get_the_ID(), 'status', true);
          $status_map = [
            '1' => ['label' => 'Waiting for Design', 'class' => 'gray'],
            '2' => ['label' => 'Revising Design', 'class' => 'orange'],
            '3' => ['label' => 'Completed', 'class' => 'green'],
          ];
          $status = $status_map[$status_code] ?? $status_map['1'];
          echo '<p><strong>Status:</strong> <span class="badge ' . $status['class'] . '">' . $status['label'] . '</span></p>';

          // Designer
          $designer_id = get_post_meta(get_the_ID(), 'designer', true);
          $designer = 'Not assigned';
          if ($designer_id) {
            $user = get_user_by('ID', $designer_id);
            if ($user && in_array('designer', (array) $user->roles)) {
              $designer = esc_html($user->display_name);
            }
          }
          echo '<p><strong>Designer:</strong> ' . $designer . '</p>';


          // Design link
          $design_link = get_post_meta(get_the_ID(), 'design_link', true);
          echo '<p><strong>Design Link:</strong> ';
          if ($design_link) {
            echo '<a href="' . esc_url($design_link) . '" target="_blank">Open Design</a>';
          } else {
            echo 'Not available';
          }
          echo '</p>';

          if ($design_link) {
            // Nếu là Google Drive
            if (strpos($design_link, 'drive.google.com/file/d/') !== false) {
              // Lấy file ID từ URL Google Drive
              preg_match('/\/d\/(.*?)\//', $design_link, $matches);
              if (!empty($matches[1])) {
                $file_id = $matches[1];
                $preview_url = "https://drive.google.com/file/d/$file_id/preview";
                echo '<div class="iframe-wrap"><iframe src="' . esc_url($preview_url) . '" allow="autoplay"></iframe></div>';
              }
            }
            // Nếu là Figma embed sẵn
            elseif (strpos($design_link, 'figma.com/embed') !== false) {
              echo '<div class="iframe-wrap"><iframe src="' . esc_url($design_link) . '" allowfullscreen></iframe></div>';
            }
          }

          ?>
        </div>

        <?php
        if ($design_link && strpos($design_link, 'figma.com/embed') !== false):
          ?>
          <div class="iframe-wrap">
            <iframe src="<?php echo esc_url($design_link); ?>" allowfullscreen></iframe>
          </div>
        <?php endif; ?>

        <?php
        // Comments
        if (comments_open() || get_comments_number()) {
          echo '<div class="comments-area">';
          comments_template();
          echo '</div>';
        }
        ?>
      </div>
    <?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>