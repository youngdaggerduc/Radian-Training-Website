<?php
/**
 * Fallback template — only reached when no page-{slug}.php matched.
 * Renders a styled notice so a slug mismatch is obvious instead of a blank page.
 */
get_header(); ?>

<div style="min-height:55vh;display:flex;align-items:center;justify-content:center;padding:160px 24px 80px;">
  <div style="max-width:640px;text-align:center;font-family:'DM Sans',sans-serif;color:#f8faff;">
    <div style="font-family:'Bebas Neue',sans-serif;font-size:3rem;letter-spacing:0.06em;line-height:1;">
      PAGE UNDER<br/><span style="color:rgba(248,250,255,0.25);">SCAFFOLDING</span>
    </div>
    <p style="margin-top:18px;font-size:0.95rem;line-height:1.7;color:rgba(248,250,255,0.55);">
      This page doesn't have a template assigned yet.
    </p>
    <?php if ( current_user_can( 'manage_options' ) ) : ?>
    <div style="margin-top:24px;text-align:left;border:1px dashed rgba(232,137,10,0.45);background:rgba(232,137,10,0.06);padding:18px 22px;font-size:0.85rem;line-height:1.8;color:rgba(248,250,255,0.7);">
      <strong style="color:#ffb547;">Admin note — slug check:</strong>
      this URL's slug is <code style="color:#ffb547;">&ldquo;<?php echo esc_html( get_post_field( 'post_name', get_queried_object_id() ) ); ?>&rdquo;</code>.
      Theme templates bind to these exact slugs:
      <code style="color:#ffb547;">cisrs · getmie-safe · certificate · course · enrol · news · about · start-here</code>.
      If WordPress appended <code>-2</code>, permanently delete the old page from the Trash, then fix the slug
      under <em>Quick&nbsp;Edit&nbsp;&rarr;&nbsp;Slug</em>.
    </div>
    <?php endif; ?>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:inline-block;margin-top:28px;background:#e8890a;color:#0a1628;font-weight:700;font-size:0.85rem;letter-spacing:0.1em;text-transform:uppercase;padding:14px 36px;text-decoration:none;">Back to Base</a>
  </div>
</div>

<?php
if ( have_posts() ) {
    echo '<div style="max-width:760px;margin:0 auto;padding:0 24px 80px;color:#f8faff;font-family:\'DM Sans\',sans-serif;">';
    while ( have_posts() ) { the_post(); the_content(); }
    echo '</div>';
}
get_footer();
