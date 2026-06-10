<?php
/**
 * Single post — one notice, served on a clipboard with the company letterhead.
 */
get_header(); ?>

<div class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-label">News &amp; Updates</div>
  <h1>SITE<br/><span class="dim">NOTICEBOARD</span></h1>
</div>

<div class="nw-single">
<?php while ( have_posts() ) : the_post(); ?>
  <a class="nw-back" href="<?php echo esc_url( home_url( '/news/' ) ); ?>">&larr; Back to the board</a>
  <article class="nw-sheet">
    <div class="nw-posted">Posted<br/><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></div>
    <div class="nw-letterhead">
      <span style="background:#c04080"></span><span style="background:#d83220"></span><span style="background:#7030a0"></span><span style="background:#f07820"></span><span style="background:#f8cc10"></span>
    </div>
    <div class="nw-meta">
      <?php $cats = get_the_category(); if ( $cats ) : ?>
        <div class="nw-cat <?php echo esc_attr( 'c' . ( ( $cats[0]->term_id % 4 ) + 1 ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></div>
      <?php endif; ?>
      <span class="date"><?php echo esc_html( get_the_date( 'l j F Y' ) ); ?></span>
    </div>
    <h1 class="nw-title"><?php the_title(); ?></h1>
    <div class="nw-content"><?php the_content(); ?></div>
  </article>
<?php endwhile; ?>
</div>

<?php get_footer(); ?>
