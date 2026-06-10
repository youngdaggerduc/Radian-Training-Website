<?php
/**
 * Shared "site information board" footer — rendered on every page,
 * outside the React #root. Styled in assets/css/polish.css (rfx-*).
 */
$rfx_home = home_url( '/' );
?>
<footer class="rfx" id="site-footer">
  <div class="rfx-tape"></div>
  <div class="rfx-inner">
    <div class="rfx-grid">

      <div class="rfx-col rfx-brand">
        <a class="rfx-logo" href="<?php echo esc_url( $rfx_home ); ?>">
          <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/media/logo.png' ); ?>" alt="Radian H.A. Limited" loading="lazy" decoding="async"/>
          <span>RADIAN H.A. LIMITED</span>
        </a>
        <p class="rfx-tag">CISRS-accredited scaffolding &amp; working-at-height training.
        Building competence, safety and careers across the workforce since 2006.</p>
        <div class="rfx-accred">CISRS OTS Approved Centre · NOCN Partner · Fully Insured</div>
      </div>

      <div class="rfx-col">
        <div class="rfx-head">Training</div>
        <ul>
          <li><a href="<?php echo esc_url( home_url( '/cisrs/' ) ); ?>">CISRS OTS Courses</a></li>
          <li><a href="<?php echo esc_url( home_url( '/getmie-safe/' ) ); ?>">Getmie Safe — At Height</a></li>
          <li><a href="<?php echo esc_url( home_url( '/start-here/' ) . '#pathway' ); ?>">Career Pathway</a></li>
          <li><a href="<?php echo esc_url( $rfx_home . '#calendar' ); ?>">Training Calendar</a></li>
          <li><a href="<?php echo esc_url( home_url( '/enrol/' ) ); ?>">Enroll Now</a></li>
        </ul>
      </div>

      <div class="rfx-col">
        <div class="rfx-head">Company</div>
        <ul>
          <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About Us</a></li>
          <li><a href="<?php echo esc_url( home_url( '/about/' ) . '#team' ); ?>">Our Instructors</a></li>
          <li><a href="<?php echo esc_url( home_url( '/news/' ) ); ?>">News &amp; Updates</a></li>
          <li><a href="<?php echo esc_url( home_url( '/certificate/' ) ); ?>">Verify a Certificate</a></li>
          <li><a href="<?php echo esc_url( home_url( '/start-here/' ) . '#faq' ); ?>">FAQ</a></li>
        </ul>
      </div>

      <div class="rfx-col">
        <div class="rfx-head">Site Office</div>
        <ul class="rfx-contact">
          <li><span>📍</span> Radian Training Centre, Trinidad &amp; Tobago</li>
          <li><span>📞</span> <a href="tel:+18685550142">+1 (868) 555-0142</a></li>
          <li><span>✉️</span> <a href="mailto:training@radianhalimited.com">training@radianhalimited.com</a></li>
          <li><span>🕗</span> Mon – Fri · 08:00 – 17:00</li>
        </ul>
        <div class="rfx-social">
          <a href="#" aria-label="Facebook">f</a>
          <a href="#" aria-label="Instagram">ig</a>
          <a href="#" aria-label="LinkedIn">in</a>
          <a href="#" aria-label="YouTube">yt</a>
        </div>
      </div>

    </div>
  </div>
  <div class="rfx-bar">
    <div class="rfx-bar-inner">
      <span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Radian H.A. Limited Training. All rights reserved.</span>
      <span class="rfx-motto">Built like a scaffold — level, braced, inspected.</span>
      <button class="rfx-top" onclick="window.scrollTo({top:0,behavior:'smooth'})">&uarr; To Base</button>
    </div>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
