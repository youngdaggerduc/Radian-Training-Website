<?php
/**
 * Radian Training Theme — functions.php
 */

/* ── Admin: "Radian Training" pages (admins only) ─────────────
 * events = Training Calendar (site-data.json → events)
 * certificates = Certificate Registry (certificates.csv)
 * about = About Page content (site-data.json → instructors/testimonials/accreds)
 */
require get_template_directory() . '/inc/admin-events.php';
require get_template_directory() . '/inc/admin-certificates.php';
require get_template_directory() . '/inc/admin-about.php';
require get_template_directory() . '/inc/ai-assistant.php';

/* ── Determine which "design page" we are rendering ───────────
 * Returns one of: home | cisrs | getmie | certificate | course | enrol | other
 */
function radian_current_page() {
    if ( is_front_page() ) {
        return 'home';
    }
    if ( is_page() ) {
        $slug = get_post_field( 'post_name', get_queried_object_id() );
        switch ( $slug ) {
            case 'cisrs':        return 'cisrs';
            case 'getmie-safe':  return 'getmie';
            case 'certificate':  return 'certificate';
            case 'course':       return 'course';
            case 'enrol':        return 'enrol';
            case 'news':         return 'news';
            case 'about':        return 'about';
            case 'start-here':   return 'start';
            case 'contact':      return 'contact';
        }
    }
    /* single posts + post archives share the noticeboard styling */
    if ( is_singular( 'post' ) || is_home() || is_category() || is_tag() || is_date() ) {
        return 'news';
    }
    return 'other';
}

/* ── Theme support ─────────────────────────────────────────── */
add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'gallery', 'caption' ] );
    register_nav_menus( [ 'primary' => __( 'Primary Navigation', 'radian-training' ) ] );
} );

/* ── Enqueue scripts & styles (per design page) ────────────── */
add_action( 'wp_enqueue_scripts', function () {
    $theme = get_template_directory_uri();
    $ver   = '3.2.1';
    $page  = radian_current_page();

    /* Google Fonts — every page */
    wp_enqueue_style(
        'radian-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Caveat:wght@500;600&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&display=swap',
        [], null
    );

    /* Per-page stylesheet (each is the design page's full <style> block) */
    $css_map = [
        'home'        => 'home.css',
        'cisrs'       => 'cisrs.css',
        'getmie'      => 'getmie.css',
        'certificate' => 'certificate.css',
        'course'      => 'course.css',
        'enrol'       => 'enrol.css',
        'news'        => 'news.css',
        'about'       => 'home.css',   /* moved-from-home sections use home styles */
        'start'       => 'home.css',
        'contact'     => 'home.css',   /* reuses ct-* contact styles + co-* page section */
    ];
    $css_file = isset( $css_map[ $page ] ) ? $css_map[ $page ] : 'home.css';
    wp_enqueue_style( 'radian-page', $theme . '/assets/css/' . $css_file, [ 'radian-fonts' ], $ver );

    /* Shared interaction polish layer — loads after the page CSS */
    wp_enqueue_style( 'radian-polish', $theme . '/assets/css/polish.css', [ 'radian-page' ], $ver );
    wp_enqueue_script( 'radian-polish', $theme . '/assets/js/polish.js', [], $ver, true );

    /* React + Babel — every design page.
     * Production builds (dev react-dom alone is ~1MB) + defer so they don't
     * block first paint. Deferred scripts still execute in order, before
     * DOMContentLoaded — which is when Babel compiles the inline JSX. */
    $defer = [ 'in_footer' => false, 'strategy' => 'defer' ];
    wp_enqueue_script( 'react',
        'https://unpkg.com/react@18.3.1/umd/react.production.min.js', [], null, $defer );
    wp_enqueue_script( 'react-dom',
        'https://unpkg.com/react-dom@18.3.1/umd/react-dom.production.min.js', [ 'react' ], null, $defer );
    wp_enqueue_script( 'babel-standalone',
        'https://unpkg.com/@babel/standalone@7.29.0/babel.min.js', [], null, $defer );

    /* GSAP + motion choreography — every page with animated sections */
    if ( in_array( $page, [ 'home', 'cisrs', 'getmie', 'about', 'start', 'contact' ], true ) ) {
        wp_enqueue_script( 'gsap',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', [], null, $defer );
        wp_enqueue_script( 'gsap-scrolltrigger',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js', [ 'gsap' ], null, $defer );
        wp_enqueue_script( 'radian-motion',     $theme . '/Design/motion.js',     [ 'gsap-scrolltrigger' ], $ver, $defer );
    }

    /* Three.js 3D heroes — home / cisrs / getmie only */
    if ( in_array( $page, [ 'home', 'cisrs', 'getmie' ], true ) ) {
        wp_enqueue_script( 'threejs',
            'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js', [], null, $defer );
        wp_enqueue_script( 'radian-image-slot', $theme . '/Design/image-slot.js', [], $ver, $defer );
        wp_enqueue_script( 'radian-scaffold3d', $theme . '/Design/scaffold3d.js', [ 'threejs', 'gsap' ], $ver, $defer );
    }

    /* Course data — needed by course + enrol pages */
    if ( in_array( $page, [ 'course', 'enrol' ], true ) ) {
        wp_enqueue_script( 'radian-course-data', $theme . '/Design/course-data.js', [], $ver, $defer );
    }

    /* Construction site HUD — every design page (tower auto-skips short pages) */
    if ( $page !== 'other' ) {
        wp_enqueue_style( 'radian-site-hud', $theme . '/assets/css/site-hud.css', [ 'radian-page' ], $ver );
        wp_enqueue_script( 'radian-site-hud', $theme . '/assets/js/site-hud.js', [], $ver, true );
    }

    /* "Ask the Site Office" AI chat — only when enabled + keyed (inc/ai-assistant.php) */
    if ( $page !== 'other' && function_exists( 'radian_ai_ready' ) && radian_ai_ready() ) {
        wp_enqueue_style( 'radian-assistant', $theme . '/assets/css/assistant.css', [ 'radian-polish' ], $ver );
        wp_enqueue_script( 'radian-assistant', $theme . '/assets/js/assistant.js', [], $ver, true );
    }

    /* Intro loader — front page only (deferred: must execute after THREE/GSAP) */
    if ( $page === 'home' ) {
        wp_enqueue_script( 'radian-loader', $theme . '/Design/loader.js', [ 'threejs', 'gsap' ], $ver,
            [ 'in_footer' => true, 'strategy' => 'defer' ] );
    }
} );

/* ── Performance: preconnect to CDNs + mobile theme colour ──── */
add_action( 'wp_head', function () {
    echo '<link rel="preconnect" href="https://unpkg.com" crossorigin/>' . "\n";
    echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin/>' . "\n";
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"/>' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>' . "\n";
    echo '<meta name="theme-color" content="#0a1628"/>' . "\n";
    $icons = get_template_directory_uri() . '/assets/media/icons';
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $icons . '/favicon-32.png' ) . '"/>' . "\n";
    echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url( $icons . '/icon-192.png' ) . '"/>' . "\n";
    echo '<link rel="apple-touch-icon" href="' . esc_url( $icons . '/apple-touch-icon.png' ) . '"/>' . "\n";
    echo '<link rel="manifest" href="' . esc_url( $icons . '/site.webmanifest' ) . '"/>' . "\n";
}, 0 );

/* ── SEO: meta description, canonical, Open Graph, Twitter ──── */
function radian_seo_meta() {
    $page = radian_current_page();
    $meta = [
        'home' => [
            'title' => 'Radian H.A. Limited — CISRS-Accredited Scaffolding Training',
            'desc'  => 'CISRS-accredited scaffolding and working-at-height training in Trinidad & Tobago. 20+ years of industry-recognised qualifications: operative, inspection, supervision and rescue courses.',
            'path'  => '/',
        ],
        'cisrs' => [
            'title' => 'CISRS OTS Courses — Scaffolding Operative, Inspection & Supervision',
            'desc'  => 'CISRS Overseas Training Scheme courses: scaffolding operative Levels 1–3, basic and advanced inspection, supervision. Practical + theory with full assessment.',
            'path'  => '/cisrs/',
        ],
        'getmie' => [
            'title' => 'Getmie Safe — Working at Height & Rescue Training',
            'desc'  => 'Working-at-height and emergency rescue training with the Getmie Safe Rescue System. Risk assessment, PPE, safe work practice and life-saving rescue technique.',
            'path'  => '/getmie-safe/',
        ],
        'certificate' => [
            'title' => 'Verify a Certificate — Radian Training Certificate Lookup',
            'desc'  => 'Instantly verify the authenticity of a Radian H.A. Limited training certificate, or check a CISRS card through the official NOCN portal.',
            'path'  => '/certificate/',
        ],
        'course' => [
            'title' => 'Course Details — Radian Scaffolding & Safety Training',
            'desc'  => 'Course content, duration, prerequisites and upcoming dates for Radian H.A. Limited scaffolding and working-at-height training.',
            'path'  => '/course/',
        ],
        'enrol' => [
            'title' => 'Enroll — Book Your Scaffolding Training Course',
            'desc'  => 'Enroll in CISRS scaffolding or Getmie Safe working-at-height training. Pick a course, choose a date and reserve your place.',
            'path'  => '/enrol/',
        ],
        'about' => [
            'title' => 'About Radian — The Team & Credentials Behind the Training',
            'desc'  => 'Meet Radian H.A. Limited: 20+ years of CISRS-accredited scaffolding training, our instructors, delegate success stories and our accreditations.',
            'path'  => '/about/',
        ],
        'start' => [
            'title' => 'Start Here — Your Scaffolding Career Route & New Delegate FAQ',
            'desc'  => 'New to scaffolding training? See the career route from new entrant to supervisor or inspector, and get answers to the questions every new delegate asks.',
            'path'  => '/start-here/',
        ],
        'news' => [
            'title' => 'News & Updates — Radian Training Noticeboard',
            'desc'  => 'Course dates, CISRS scheme updates and company news from Radian H.A. Limited — the site noticeboard.',
            'path'  => '/news/',
        ],
        'contact' => [
            'title' => 'Contact Us — Talk to the Radian Site Office',
            'desc'  => 'Call, WhatsApp or message the Radian H.A. Limited site office — Building 2, Plaisance Park Industrial Estate, Claxton Bay, Trinidad. Mon–Fri 07:00–16:00.',
            'path'  => '/contact/',
        ],
    ];
    if ( ! isset( $meta[ $page ] ) ) return;
    $m     = $meta[ $page ];
    $url   = home_url( $m['path'] );
    $img   = get_template_directory_uri() . '/assets/media/logo.png';
    $title = esc_attr( $m['title'] );
    $desc  = esc_attr( $m['desc'] );

    echo '<meta name="description" content="' . $desc . '"/>' . "\n";
    echo '<link rel="canonical" href="' . esc_url( $url ) . '"/>' . "\n";
    echo '<meta property="og:type" content="website"/>' . "\n";
    echo '<meta property="og:site_name" content="Radian H.A. Limited Training"/>' . "\n";
    echo '<meta property="og:title" content="' . $title . '"/>' . "\n";
    echo '<meta property="og:description" content="' . $desc . '"/>' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $url ) . '"/>' . "\n";
    echo '<meta property="og:image" content="' . esc_url( $img ) . '"/>' . "\n";
    echo '<meta property="og:locale" content="en_GB"/>' . "\n";
    echo '<meta name="twitter:card" content="summary"/>' . "\n";
    echo '<meta name="twitter:title" content="' . $title . '"/>' . "\n";
    echo '<meta name="twitter:description" content="' . $desc . '"/>' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url( $img ) . '"/>' . "\n";

    // Structured data — Organization on every page, courses on the catalogue pages
    $org = [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => 'Radian H.A. Limited',
        'url'      => home_url( '/' ),
        'logo'     => $img,
        'description' => 'CISRS-accredited scaffolding and working-at-height training provider.',
        'email'    => 'training@rhatt.com',
    ];
    echo '<script type="application/ld+json">' . wp_json_encode( $org ) . '</script>' . "\n";

    $webpage = [
        '@context'    => 'https://schema.org',
        '@type'       => 'WebPage',
        'name'        => $m['title'],
        'description' => $m['desc'],
        'url'         => $url,
        'isPartOf'    => [ '@type' => 'WebSite', 'name' => 'Radian H.A. Limited Training', 'url' => home_url( '/' ) ],
    ];
    echo '<script type="application/ld+json">' . wp_json_encode( $webpage ) . '</script>' . "\n";

    // Course list schema on the two catalogue pages
    $course_lists = [
        'cisrs' => [
            'CISRS OSTS Scaffolder Level 1',
            'CISRS OSTS Scaffolder Level 2',
            'CISRS OSTS Scaffolder Level 3 (Advanced)',
            'CISRS OSTS Basic Scaffolder Inspection',
            'CISRS OSTS Advanced Scaffolder Inspection',
            'CISRS OSTS Scaffolder Supervisor',
        ],
        'getmie' => [
            'Getmie Safe Working at Height',
            'Getmie Safe Rescue Training',
        ],
    ];
    /* FAQPage schema — keep in sync with FAQS in page-start-here.php */
    if ( $page === 'start' ) {
        $faqs = [
            [ 'Do I need experience before CISRS Level 1?', 'No. Level 1 is the entry point — you need basic fitness, a head for heights and steel-toe boots. Everything else, including PPE and tools, is provided and taught from day one.' ],
            [ 'What should I bring on the day?', 'Photo ID and steel-toe boots. We supply helmets, harnesses, gloves, all tools and all scaffolding materials. Wear work clothes you can move in.' ],
            [ 'Is the training practical or classroom-based?', 'Both — roughly 70% of your time is hands-on in the training yard, with theory sessions and a full written and practical assessment to finish.' ],
            [ 'How long does my certification last?', 'Most CISRS OTS certifications are valid for three years. We recommend booking your refresher before expiry — and any Radian certificate can be checked on our Verify a Certificate page.' ],
            [ 'What happens if I fail the assessment?', 'You get detailed feedback from your instructor and one resit is included in your course fee. Our instructors work with you until the standard is met.' ],
            [ 'Which course do I need — scaffolding or working at height?', 'If you erect, alter or strike scaffolds, you need the CISRS route. If you work on or around scaffolds and are exposed to fall risk, Getmie Safe Working at Height covers you.' ],
            [ 'Can you train our whole crew?', 'Yes. We run group and corporate bookings, and can schedule dedicated sessions for crews. Contact the site office for group rates and availability.' ],
            [ 'How do I pay?', 'Prices are in TT$. We take deposits to secure a seat and offer company invoicing for corporate bookings — payment details are confirmed when you enroll.' ],
        ];
        $faq_items = [];
        foreach ( $faqs as $f ) {
            $faq_items[] = [
                '@type'          => 'Question',
                'name'           => $f[0],
                'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $f[1] ],
            ];
        }
        $faq_schema = [ '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faq_items ];
        echo '<script type="application/ld+json">' . wp_json_encode( $faq_schema ) . '</script>' . "\n";
    }

    if ( isset( $course_lists[ $page ] ) ) {
        $items = [];
        foreach ( $course_lists[ $page ] as $i => $name ) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'item'     => [
                    '@type'    => 'Course',
                    'name'     => $name,
                    'provider' => [ '@type' => 'Organization', 'name' => 'Radian H.A. Limited' ],
                ],
            ];
        }
        $list = [ '@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $items ];
        echo '<script type="application/ld+json">' . wp_json_encode( $list ) . '</script>' . "\n";
    }
}
add_action( 'wp_head', 'radian_seo_meta', 1 );

/* ── SEO: cleaner <title> tags per design page ──────────────── */
add_filter( 'pre_get_document_title', function ( $title ) {
    $titles = [
        'home'        => 'Radian H.A. Limited — CISRS-Accredited Scaffolding Training',
        'cisrs'       => 'CISRS OTS Courses | Radian H.A. Limited',
        'getmie'      => 'Getmie Safe — Working at Height & Rescue | Radian H.A. Limited',
        'certificate' => 'Verify a Certificate | Radian H.A. Limited',
        'course'      => 'Course Details | Radian H.A. Limited',
        'enrol'       => 'Enroll | Radian H.A. Limited',
        'news'        => 'News & Updates | Radian H.A. Limited',
        'about'       => 'About Us | Radian H.A. Limited',
        'start'       => 'Start Here | Radian H.A. Limited',
        'contact'     => 'Contact Us | Radian H.A. Limited',
    ];
    /* keep real post titles on single posts */
    if ( is_singular( 'post' ) ) return $title;
    $page = radian_current_page();
    return isset( $titles[ $page ] ) ? $titles[ $page ] : $title;
} );

/* ── Editable site data (assets/data/site-data.json) ────────── */
add_action( 'wp_head', function () {
    if ( ! in_array( radian_current_page(), [ 'home', 'about' ], true ) ) return;
    $file = get_template_directory() . '/assets/data/site-data.json';
    if ( ! file_exists( $file ) ) return;
    $json = file_get_contents( $file );
    if ( null === json_decode( $json ) ) return;   // invalid JSON: fail silent
    echo '<script>window.RADIAN_DATA=' . $json . ';</script>' . "\n";
}, 3 );

/* ── Expose WordPress URLs to the React components ─────────── */
add_action( 'wp_head', function () {
    $urls = [
        'home'   => home_url( '/' ),
        'cisrs'  => home_url( '/cisrs/' ),
        'getmie' => home_url( '/getmie-safe/' ),
        'cert'   => home_url( '/certificate/' ),
        'course' => home_url( '/course/' ),
        'enrol'  => home_url( '/enrol/' ),
        'theme'  => get_template_directory_uri(),
    ];
    echo '<script>window.RADIAN_URLS=' . wp_json_encode( $urls ) . ';</script>' . "\n";
}, 1 );

/* ── Body classes (rad-home, rad-cisrs, …) ─────────────────── */
add_filter( 'body_class', function ( $classes ) {
    $classes[] = 'rad-' . radian_current_page();
    return $classes;
} );

/* ── Certificate lookup AJAX ───────────────────────────────── */
// Accessible to both logged-in and logged-out visitors
add_action( 'wp_ajax_nopriv_radian_cert_lookup', 'radian_cert_lookup' );
add_action( 'wp_ajax_radian_cert_lookup',        'radian_cert_lookup' );

function radian_cert_lookup() {
    // Verify the nonce printed by wp_head
    if ( ! check_ajax_referer( 'radian_cert_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Invalid request' ], 403 );
    }

    $cert_no   = strtoupper( sanitize_text_field( $_POST['cert_no']   ?? '' ) );
    $last_name = strtolower( sanitize_text_field( $_POST['last_name'] ?? '' ) );

    if ( ! $cert_no || ! $last_name ) {
        wp_send_json_error( [ 'message' => 'Missing fields' ], 400 );
    }

    $csv = get_template_directory() . '/assets/data/certificates.csv';
    if ( ! file_exists( $csv ) ) {
        wp_send_json_error( [ 'message' => 'Database unavailable' ], 500 );
    }

    $fh      = fopen( $csv, 'r' );
    $headers = fgetcsv( $fh );   // first row = column names

    while ( ( $row = fgetcsv( $fh ) ) !== false ) {
        if ( count( $row ) < count( $headers ) ) continue;
        $rec = array_combine( $headers, $row );

        if (
            strtoupper( trim( $rec['certificateNo'] ) ) === $cert_no &&
            strtolower( trim( $rec['lastName'] ) )      === $last_name
        ) {
            fclose( $fh );
            // Compute expiryStatus from expiryDate so the CSV never needs that column
            $rec['expiryStatus'] = radian_expiry_status( $rec['expiryDate'] ?? '' );
            // internalLink = staff-only pointer to the stored certificate file —
            // NEVER expose it in the public lookup response
            unset( $rec['internalLink'] );
            wp_send_json_success( $rec );
        }
    }
    fclose( $fh );
    wp_send_json_error( [ 'message' => 'not_found' ], 404 );
}

/**
 * Returns 'valid', 'soon' (≤90 days), or 'expired' from a date string.
 * Accepts "DD Mon YYYY" (e.g. "17 Oct 2027") or any format PHP's strtotime understands.
 */
function radian_expiry_status( $date_str ) {
    $ts = strtotime( trim( $date_str ) );
    if ( ! $ts ) return 'valid';
    $diff = $ts - time();
    if ( $diff < 0 )             return 'expired';
    if ( $diff < 90 * 86400 )    return 'soon';
    return 'valid';
}

/* ── Contact form AJAX ─────────────────────────────────────── */
add_action( 'wp_ajax_nopriv_radian_contact', 'radian_contact_submit' );
add_action( 'wp_ajax_radian_contact',        'radian_contact_submit' );

function radian_contact_submit() {
    if ( ! check_ajax_referer( 'radian_contact_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Invalid request' ], 403 );
    }
    // honeypot: bots fill the hidden "company" field — pretend success, send nothing
    if ( ! empty( $_POST['company'] ) ) {
        wp_send_json_success( [ 'ok' => true ] );
    }
    $name  = sanitize_text_field( $_POST['name'] ?? '' );
    $email = sanitize_email( $_POST['email'] ?? '' );
    $phone = sanitize_text_field( $_POST['phone'] ?? '' );
    $msg   = sanitize_textarea_field( $_POST['message'] ?? '' );

    if ( ! $name || ! is_email( $email ) || ! $msg ) {
        wp_send_json_error( [ 'message' => 'Missing fields' ], 400 );
    }

    $body  = "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\n\n{$msg}\n\n— Sent from the website contact form";
    $sent  = wp_mail(
        get_option( 'admin_email' ),
        '[Radian Training] Website enquiry — ' . $name,
        $body,
        [ 'Reply-To: ' . $name . ' <' . $email . '>' ]
    );

    if ( $sent ) {
        wp_send_json_success( [ 'ok' => true ] );
    }
    wp_send_json_error( [ 'message' => 'Mail failed' ], 500 );
}

/* ── Enrol wizard AJAX — no payment gateway: the request summary is
 *    emailed to the site admin so staff can follow up manually ──── */
add_action( 'wp_ajax_nopriv_radian_enrol', 'radian_enrol_submit' );
add_action( 'wp_ajax_radian_enrol',        'radian_enrol_submit' );

function radian_enrol_submit() {
    if ( ! check_ajax_referer( 'radian_enrol_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Invalid request' ], 403 );
    }
    // honeypot: bots fill the hidden "company" field — pretend success, send nothing
    if ( ! empty( $_POST['company'] ) ) {
        wp_send_json_success( [ 'ok' => true ] );
    }

    $ref       = sanitize_text_field( $_POST['ref'] ?? '' );
    $title     = sanitize_text_field( $_POST['courseTitle'] ?? '' );
    $code      = sanitize_text_field( $_POST['courseCode'] ?? '' );
    $course_id = sanitize_text_field( $_POST['courseId'] ?? '' );
    $dates     = sanitize_text_field( $_POST['dateRange'] ?? '' );
    $venue     = sanitize_text_field( $_POST['venue'] ?? '' );
    $count     = absint( $_POST['count'] ?? 0 );
    $total     = sanitize_text_field( $_POST['total'] ?? '' );
    $delegates = json_decode( wp_unslash( $_POST['delegates'] ?? '[]' ), true );

    if ( ! $ref || ! $title || ! $dates || ! $count || ! is_array( $delegates ) || ! $delegates ) {
        wp_send_json_error( [ 'message' => 'Missing fields' ], 400 );
    }
    $delegates = array_slice( $delegates, 0, 50 );

    $lines = [];
    $i     = 0;
    foreach ( $delegates as $d ) {
        if ( ! is_array( $d ) ) continue;
        $i++;
        $lines[] = sprintf(
            '%2d. %s · DOB %s · %s',
            $i,
            sanitize_text_field( $d['name'] ?? '' ),
            sanitize_text_field( $d['dob'] ?? '' ),
            sanitize_text_field( $d['number'] ?? '' )
        );
    }

    $body = "NEW ENROLLMENT REQUEST — {$ref}\n"
          . "──────────────────────────────────────────────\n\n"
          . "Course:     {$title}" . ( $code ? " ({$code})" : '' ) . "\n"
          . ( $course_id ? "Course ID:  {$course_id}\n" : '' )
          . "Dates:      {$dates}\n"
          . ( $venue ? "Venue:      {$venue}\n" : '' )
          . "Delegates:  {$count}\n"
          . ( $total ? "Total est.: TT\${$total} (VAT inclusive)\n" : '' )
          . "\nDELEGATES\n" . implode( "\n", $lines ) . "\n\n"
          . "No payment has been taken — call the first delegate to confirm\n"
          . "availability and send payment instructions.\n\n"
          . "— Sent from the website enrol wizard";

    $sent = wp_mail(
        get_option( 'admin_email' ),
        '[Radian Training] Enrollment request ' . $ref . ' — ' . ( $code ? $code : $title ) . ' × ' . $count,
        $body
    );

    if ( $sent ) {
        wp_send_json_success( [ 'ok' => true ] );
    }
    wp_send_json_error( [ 'message' => 'Mail failed' ], 500 );
}

/* ── Expose AI assistant config (design pages, only when ready) ── */
add_action( 'wp_head', function () {
    if ( radian_current_page() === 'other' || ! function_exists( 'radian_ai_ready' ) || ! radian_ai_ready() ) return;
    echo '<script>window.RADIAN_AI={ajaxUrl:' . wp_json_encode( admin_url( 'admin-ajax.php' ) )
         . ',nonce:' . wp_json_encode( wp_create_nonce( 'radian_ai_nonce' ) ) . '};</script>' . "\n";
}, 2 );

/* ── Expose enrol AJAX config on the enrol page ─────────────── */
add_action( 'wp_head', function () {
    if ( radian_current_page() !== 'enrol' ) return;
    echo '<script>window.RADIAN_ENROL={ajaxUrl:' . wp_json_encode( admin_url( 'admin-ajax.php' ) )
         . ',nonce:' . wp_json_encode( wp_create_nonce( 'radian_enrol_nonce' ) ) . '};</script>' . "\n";
}, 2 );

/* ── Expose contact AJAX config (home section + contact page) ── */
add_action( 'wp_head', function () {
    if ( ! in_array( radian_current_page(), [ 'home', 'contact' ], true ) ) return;
    echo '<script>window.RADIAN_CONTACT={ajaxUrl:' . wp_json_encode( admin_url( 'admin-ajax.php' ) )
         . ',nonce:' . wp_json_encode( wp_create_nonce( 'radian_contact_nonce' ) ) . '};</script>' . "\n";
}, 2 );

/* ── Expose AJAX URL + nonce on the certificate page ────────── */
add_action( 'wp_head', function () {
    if ( radian_current_page() !== 'certificate' ) return;
    echo '<script>window.RADIAN_CERT={ajaxUrl:' . wp_json_encode( admin_url( 'admin-ajax.php' ) )
         . ',nonce:' . wp_json_encode( wp_create_nonce( 'radian_cert_nonce' ) ) . '};</script>' . "\n";
}, 2 );

/* ── Tidy up ───────────────────────────────────────────────── */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
