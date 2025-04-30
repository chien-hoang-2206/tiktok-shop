<?php get_header(); ?>
<main class="container">
    <h2><?php post_type_archive_title(); ?></h2>
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article <?php post_class(); ?>>
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <div class="excerpt"><?php the_excerpt(); ?></div>
        </article>
    <?php endwhile; else : ?>
        <p><?php esc_html_e('No posts found.', 'tiktok-custom'); ?></p>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
