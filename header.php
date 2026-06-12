<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php if ( is_front_page() ) : ?>
<!-- INTRO LOADER -->
<div id="radian-loader" aria-hidden="true">
  <div class="rl-curtain">
    <div class="rl-slat"></div><div class="rl-slat"></div><div class="rl-slat"></div>
    <div class="rl-slat"></div><div class="rl-slat"></div><div class="rl-slat"></div>
    <div class="rl-slat"></div>
  </div>
  <div class="rl-stage">
    <div class="rl-canvas"></div>
    <div class="rl-brand">
      <div class="rl-wordmark">RADIAN H.A.<span class="dot"> ·</span> LIMITED</div>
      <div class="rl-tag">Scaffold Training</div>
    </div>
    <div class="rl-progress">
      <div class="rl-bar"><div class="rl-bar-fill"></div></div>
      <div class="rl-meta">
        <div class="rl-status">Setting base plates</div>
        <div class="rl-pct-wrap"><span class="rl-pct">0</span>%</div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$rad_page  = function_exists( 'radian_current_page' ) ? radian_current_page() : '';
$enrol_url = home_url( '/enrol/' );
/* item: [url, label, active, children?] — children render as a hover dropdown */
$nav_items = [
    [ home_url( '/cisrs/' ),       'CISRS OTS',    $rad_page === 'cisrs' ],
    [ home_url( '/getmie-safe/' ), 'Getmie Safe',  $rad_page === 'getmie' ],
    [ home_url( '/start-here/' ),  'Start Here',   $rad_page === 'start' ],
    [ home_url( '/certificate/' ), 'Certificates', $rad_page === 'certificate' ],
    [ home_url( '/about/' ),       'About',        $rad_page === 'about' || $rad_page === 'contact', [
        [ home_url( '/about/' ),   'About Us',     $rad_page === 'about' ],
        [ home_url( '/contact/' ), 'Contact Us',   $rad_page === 'contact' ],
    ] ],
    [ home_url( '/news/' ),        'News',         $rad_page === 'news' ],
];
?>

<nav id="navbar" class="<?php echo is_front_page() ? '' : 'scrolled'; ?>">
  <a class="nav-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
    <div class="logo-mark"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/media/logo.png" alt="Radian" style="width:100%;height:100%;object-fit:contain;"></div>
    <span class="nav-brand-text">RADIAN H.A. LIMITED</span>
  </a>
  <ul class="nav-links">
    <?php foreach ( $nav_items as $it ) : $kids = $it[3] ?? null; ?>
    <li<?php if ( $kids ) echo ' class="nav-drop"'; ?>>
      <a href="<?php echo esc_url( $it[0] ); ?>"<?php if ( $it[2] ) echo ' class="active" aria-current="page"'; ?>><?php echo esc_html( $it[1] ); ?><?php if ( $kids ) echo '<span class="nav-caret" aria-hidden="true">▾</span>'; ?></a>
      <?php if ( $kids ) : ?>
      <div class="nav-drop-menu">
        <div class="nav-drop-inner">
          <?php foreach ( $kids as $k ) : ?>
          <a href="<?php echo esc_url( $k[0] ); ?>"<?php if ( $k[2] ) echo ' class="active"'; ?>><?php echo esc_html( $k[1] ); ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
  <button class="nav-cta" onclick="(function(){
    var c=document.getElementById('cta'); if(c){c.scrollIntoView({behavior:'smooth'});return;}
    var s=document.querySelector('.sb-btn'); if(s){s.click();return;}
    window.location.href='<?php echo esc_url( $enrol_url ); ?>';
  })()">Enroll Now</button>
</nav>
