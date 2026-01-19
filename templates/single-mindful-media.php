<?php
/**
 * Single Template for MindfulMedia Posts
 * 
 * This template will be used for displaying individual mindful_media posts.
 */

// Suppress header deprecation warning
@get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php while (have_posts()) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('mindful-media-single-post'); ?>>
                
                <div class="entry-content">
                    <?php
                    // The content will be filtered by single_content_filter in class-post-types.php
                    // This generates the complete display with player, meta, content, etc.
                    the_content();
                    ?>
                </div><!-- .entry-content -->

            </article><!-- #post-<?php the_ID(); ?> -->

        <?php endwhile; // End of the loop. ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php @get_footer(); ?> 