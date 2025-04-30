<?php get_header(); ?>
<main>
    <section class="container">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h2><?php the_title(); ?></h2>
                <div class="entry"><?php the_content(); ?></div>
            </article>
        <?php endwhile; else : ?>
            <p><?php esc_html_e('No posts found.', 'tiktok-custom'); ?></p>
        <?php endif; ?>
    </section>
</main>
<?php get_footer(); ?>
