<?php get_header(); ?>
<main>
  <h1>Welcome to TikTok Custom Theme</h1>
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <article>
        <h2><?php the_title(); ?></h2>
        <?php the_content(); ?>
      </article>
    <?php endwhile;
  else : ?>
    <p>No posts found.</p>
  <?php endif; ?>
</main>
<?php get_footer(); ?>