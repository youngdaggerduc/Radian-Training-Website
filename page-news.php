<?php
/**
 * News — a physical site noticeboard. Latest post on a clipboard, the rest
 * pinned/taped to a steel-framed board. Applied to the page with slug `news`.
 */
get_header(); ?>

<div class="page-hero">
  <div class="page-hero-bg"></div>
  <div class="page-hero-label">News &amp; Updates</div>
  <h1>SITE<br/><span class="dim">NOTICEBOARD</span></h1>
  <p>Course dates, CISRS scheme updates and company news — pinned to the board as it happens.</p>
</div>

<div class="nw-wrap">
<?php
$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$news  = new WP_Query( [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 10,            // 1 clipboard + 9 pinned
    'paged'          => $paged,
] );

/* helpers: rotation/attachment/category variants cycle deterministically */
$rot_classes = [ 'r1', 'r2', 'r3', 'r4', 'r5' ];
$cat_class   = function () {
    $cats = get_the_category();
    if ( ! $cats ) return '';
    return 'c' . ( ( $cats[0]->term_id % 4 ) + 1 );
};
?>

<div class="nw-frame">
  <div class="nw-plate">RADIAN · SITE NOTICES</div>

<?php if ( $news->have_posts() ) :
    $i = 0;
    $cards = '';
    ob_start();

    while ( $news->have_posts() ) : $news->the_post();
        $i++;
        $is_new = ( time() - get_the_date( 'U' ) ) < 14 * DAY_IN_SECONDS;
        $cats   = get_the_category();

        /* ── the latest post rides the clipboard (page 1 only) ── */
        if ( 1 === $paged && 1 === $i ) : ?>
          <article class="nw-clip">
            <span class="nw-clip-clip"></span>
            <div class="nw-clip-head">
              <span class="nw-clip-brand">RADIAN H.A. LIMITED — SITE BULLETIN</span>
              <span class="nw-clip-tag">Latest</span>
            </div>
            <div class="nw-clip-date">Posted <?php echo esc_html( get_the_date( 'l j F Y' ) ); ?><?php if ( $cats ) echo ' · ' . esc_html( $cats[0]->name ); ?></div>
            <h2 class="nw-clip-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <div class="nw-clip-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 40 ) ); ?></div>
            <a class="nw-clip-more" href="<?php the_permalink(); ?>">Read the full bulletin &rarr;</a>
          </article>
          <div class="nw-grid">
        <?php continue;
        endif;

        if ( ( 1 === $paged && 2 === $i ) || ( 1 !== $paged && 1 === $i ) ) {
            if ( 1 !== $paged ) echo '<div class="nw-grid">';
        }

        /* attachment style: every 3rd notice is taped, others pinned (alternating colour) */
        $attach = ( 0 === $i % 3 ) ? 'tape' : ( ( 0 === $i % 2 ) ? 'pin alt' : 'pin' );
        $rot    = $rot_classes[ $i % 5 ];
        ?>
        <article class="nw-card <?php echo esc_attr( "$attach $rot" ); ?>">
          <?php if ( $is_new ) : ?><span class="nw-new">New</span><?php endif; ?>
          <div class="nw-head">
            <div class="nw-date">
              <span class="m"><?php echo esc_html( get_the_date( 'M' ) ); ?></span>
              <span class="d"><?php echo esc_html( get_the_date( 'd' ) ); ?></span>
            </div>
            <?php if ( $cats ) : ?>
              <div class="nw-cat <?php echo esc_attr( $cat_class() ); ?>"><?php echo esc_html( $cats[0]->name ); ?></div>
            <?php endif; ?>
          </div>
          <h2 class="nw-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <div class="nw-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></div>
          <a class="nw-more" href="<?php the_permalink(); ?>">read this one &rarr;</a>
        </article>

        <?php
        /* a handwritten sticky note lives between the notices */
        if ( ( 1 === $paged && 4 === $i ) || ( 1 !== $paged && 3 === $i ) ) : ?>
          <aside class="nw-sticky" aria-hidden="true">
            Toolbox talk every Monday, 07:30 sharp — bring your boots, the kettle's on. ☕
            <small>— the Site Office</small>
          </aside>
        <?php endif;

    endwhile;
    echo '</div>'; // .nw-grid
    echo ob_get_clean();
    ?>

  <?php if ( $news->max_num_pages > 1 ) : ?>
    <div class="nw-pages">
      <?php echo paginate_links( [
          'total'     => $news->max_num_pages,
          'current'   => $paged,
          'prev_text' => '&lsaquo;',
          'next_text' => '&rsaquo;',
      ] ); ?>
    </div>
  <?php endif; ?>

<?php else : ?>
  <div class="nw-empty">
    Nothing pinned yet — check back after the morning toolbox talk. ✏️
  </div>
<?php endif;
wp_reset_postdata(); ?>
</div><!-- /.nw-frame -->
</div><!-- /.nw-wrap -->

<?php get_footer(); ?>
