jQuery(document).ready(function ($) {
    const observer = new MutationObserver(() => {
        $('input[type="url"][name*="order_items"][name*="[image]"]').each(function () {
            const input = $(this);

            if (input.next('.image-preview').length > 0) return;

            const url = input.val();
            const img = $('<img>', {
                src: url,
                class: 'image-preview',
                css: {
                    marginTop: '8px',
                    width: '80px',
                    height: '80px',
                    objectFit: 'cover',
                    borderRadius: '4px',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.2)'
                }
            });
            input.after(img);

            input.on('input', function () {
                input.next('.image-preview').remove();
                const newUrl = input.val();
                if (newUrl.match(/\.(jpeg|jpg|gif|png|webp)$/i)) {
                    const newImg = $('<img>', {
                        src: newUrl,
                        class: 'image-preview',
                        css: {
                            marginTop: '8px',
                            width: '80px',
                            height: '80px',
                            objectFit: 'cover',
                            borderRadius: '4px',
                            boxShadow: '0 1px 3px rgba(0,0,0,0.2)'
                        }
                    });
                    input.after(newImg);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });


    $('.mark-complete').click(function (e) {
        e.preventDefault();

        const postId = $(this).data('id');

        if (!confirm('Mark this order as completed?')) return;

        $.post(ajaxurl, {
            action: 'mark_order_complete',
            post_id: postId
        }, function (res) {
            if (res.success) {
                alert('✅ Order marked as completed!');
                location.reload();
            } else {
                alert('❌ Error: ' + res.data);
            }
        });
    });

});
