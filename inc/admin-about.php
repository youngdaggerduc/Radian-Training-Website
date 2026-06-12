<?php
/**
 * About Page content admin — wp-admin page (admins only) for the three
 * site-data.json arrays shown on the About page: instructors (lanyard
 * badges), testimonials (scafftags) and accreds (plaques). Tabbed CRUD
 * driven by a per-section schema; reuses the read/write helpers from
 * admin-events.php. Required from functions.php (after admin-events.php).
 */

defined( 'ABSPATH' ) || exit;

/* ── Section schemas ────────────────────────────────────────── */
function radian_about_sections() {
    return [
        'instructors' => [
            'label'    => 'Instructors',
            'singular' => 'instructor',
            'hint'     => 'Shown as lanyard site passes under "Meet Your Instructors". Keep CISRS reg №s in sync with the Certificate Registry — the cert form suggests names from this list.',
            'fields'   => [
                'name'  => [ 'Name',              'text',   'required', 'e.g. David Okafor' ],
                'role'  => [ 'Role',              'text',   '',         'e.g. Lead CISRS Instructor' ],
                'reg'   => [ 'CISRS Reg №',       'text',   'required', 'e.g. INS-00021' ],
                'years' => [ 'Years on the tools','number', '',         '18' ],
                'tags'  => [ 'Specialty tags',    'tags',   '',         'Operative L1–L3, Supervision' ],
            ],
            'cols'     => [ 'name', 'role', 'reg', 'years', 'tags' ],
        ],
        'testimonials' => [
            'label'    => 'Testimonials',
            'singular' => 'testimonial',
            'hint'     => 'Shown as green scafftags on the steel rail ("Signed Off By Our Delegates"). Keep quotes short — two sentences max reads best on the tag.',
            'fields'   => [
                'quote'  => [ 'Quote',          'textarea', 'required', 'What the delegate said — no surrounding quote marks needed' ],
                'name'   => [ 'Delegate name',  'text',     'required', 'e.g. Kerron Baptiste' ],
                'org'    => [ 'Role · Company', 'text',     '',         'e.g. Scaffolder · Caribbean Industrial Services' ],
                'course' => [ 'Course taken',   'text',     '',         'e.g. CISRS Level 1' ],
            ],
            'cols'     => [ 'name', 'org', 'course', 'quote' ],
        ],
        'accreds' => [
            'label'    => 'Accreditations',
            'singular' => 'accreditation',
            'hint'     => 'Shown as framed plaques under "Accredited. Audited. Insured." Seal = one character or symbol engraved on the plaque (★ ✓ ⛨ §).',
            'fields'   => [
                'seal'  => [ 'Seal symbol', 'text', 'required', '★' ],
                'title' => [ 'Title',       'text', 'required', 'e.g. CISRS OTS' ],
                'sub'   => [ 'Subtitle',    'text', '',         'e.g. Approved Training Centre' ],
                'ref'   => [ 'Reference №', 'text', '',         'e.g. Centre № OTS-TT-0042' ],
                'since' => [ 'Since / note','text', '',         'e.g. Since 2006' ],
            ],
            'cols'     => [ 'seal', 'title', 'sub', 'ref', 'since' ],
        ],
    ];
}

/* ── Menu ───────────────────────────────────────────────────── */
add_action( 'admin_menu', function () {
    add_submenu_page( 'radian-events', 'About Page Content', 'About Page', 'manage_options', 'radian-about', 'radian_about_admin_page' );
} );

/* ── Field sanitising / form value helpers ──────────────────── */
function radian_about_sanitize_field( $type, $raw ) {
    switch ( $type ) {
        case 'number':   return absint( $raw );
        case 'textarea': return sanitize_textarea_field( wp_unslash( $raw ) );
        case 'tags':     return array_values( array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $raw ) ) ) ) ) );
        default:         return sanitize_text_field( wp_unslash( $raw ) );
    }
}
function radian_about_display_field( $type, $val ) {
    if ( 'tags' === $type && is_array( $val ) ) return implode( ', ', $val );
    return (string) ( $val ?? '' );
}

/* ── Page ───────────────────────────────────────────────────── */
function radian_about_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to manage About-page content.' );
    }

    $sections = radian_about_sections();
    $tab      = sanitize_key( $_GET['tab'] ?? 'instructors' );
    if ( ! isset( $sections[ $tab ] ) ) $tab = 'instructors';
    $sec = $sections[ $tab ];

    $notice = '';
    $error  = '';
    $data   = radian_read_site_data( $read_err );
    if ( null === $data ) $error = $read_err;
    if ( $data && ( ! isset( $data[ $tab ] ) || ! is_array( $data[ $tab ] ) ) ) $data[ $tab ] = [];

    /* ── handle actions ── */
    if ( $data && ! empty( $_POST['radian_about_action'] ) ) {
        check_admin_referer( 'radian_about' );
        $action = sanitize_key( $_POST['radian_about_action'] );
        $items  = $data[ $tab ];

        if ( 'delete' === $action ) {
            $idx = (int) ( $_POST['item_index'] ?? -1 );
            if ( isset( $items[ $idx ] ) ) {
                array_splice( $items, $idx, 1 );
                $data[ $tab ] = $items;
                if ( radian_write_site_data( $data, $werr ) ) $notice = ucfirst( $sec['singular'] ) . ' removed.';
                else $error = $werr;
            } else {
                $error = ucfirst( $sec['singular'] ) . ' not found.';
            }
        }

        if ( 'save' === $action ) {
            $idx  = (int) ( $_POST['item_index'] ?? -1 ); // -1 = new
            $item = [];
            foreach ( $sec['fields'] as $key => $def ) {
                $item[ $key ] = radian_about_sanitize_field( $def[1], $_POST[ 'f_' . $key ] ?? '' );
                if ( 'required' === $def[2] && '' === trim( radian_about_display_field( $def[1], $item[ $key ] ) ) ) {
                    $error = $def[0] . ' is required.';
                    break;
                }
            }
            if ( ! $error ) {
                if ( $idx >= 0 ) {
                    if ( ! isset( $items[ $idx ] ) ) $error = ucfirst( $sec['singular'] ) . ' not found — it may have been deleted.';
                    else { $items[ $idx ] = $item; $notice = ucfirst( $sec['singular'] ) . ' updated.'; }
                } else {
                    $items[] = $item;
                    $notice  = ucfirst( $sec['singular'] ) . ' added — it appears on the About page immediately.';
                }
                if ( ! $error ) {
                    $data[ $tab ] = array_values( $items );
                    if ( ! radian_write_site_data( $data, $werr ) ) { $error = $werr; $notice = ''; }
                }
            }
        }
    }

    $items = $data ? array_values( $data[ $tab ] ) : [];

    /* ── prefill form (edit) ── */
    $form_idx = -1;
    $form     = array_fill_keys( array_keys( $sec['fields'] ), '' );
    if ( isset( $_GET['edit'] ) && '' !== $_GET['edit'] ) {
        $want = (int) $_GET['edit'];
        if ( isset( $items[ $want ] ) ) {
            $form_idx = $want;
            foreach ( $sec['fields'] as $key => $def ) {
                $form[ $key ] = radian_about_display_field( $def[1], $items[ $want ][ $key ] ?? '' );
            }
        }
    }
    $editing  = $form_idx >= 0;
    $base_url = admin_url( 'admin.php?page=radian-about&tab=' . $tab );
    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline">About Page Content</h1>
      <hr class="wp-header-end"/>

      <nav class="nav-tab-wrapper" style="margin-bottom:14px;">
        <?php foreach ( $sections as $key => $s ) : ?>
          <a class="nav-tab <?php echo $key === $tab ? 'nav-tab-active' : ''; ?>"
             href="<?php echo esc_url( admin_url( 'admin.php?page=radian-about&tab=' . $key ) ); ?>">
            <?php echo esc_html( $s['label'] ); ?>
            <span style="color:#646970;font-weight:400;">(<?php echo $data ? count( $data[ $key ] ?? [] ) : 0; ?>)</span>
          </a>
        <?php endforeach; ?>
      </nav>

      <p style="max-width:760px;"><?php echo esc_html( $sec['hint'] ); ?>
      Data lives in <code>assets/data/site-data.json</code>; a <code>.bak</code> of the previous version is kept on every save.</p>

      <?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
      <?php if ( $error ) : ?><div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>

      <?php if ( $data ) : ?>

      <h2><?php echo $editing ? 'Edit ' . esc_html( ucfirst( $sec['singular'] ) ) : 'Add ' . esc_html( ( 'a' === substr( $sec['singular'], 0, 1 ) ? 'an ' : 'a ' ) . ucfirst( $sec['singular'] ) ); ?></h2>
      <form method="post" style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;max-width:860px;">
        <?php wp_nonce_field( 'radian_about' ); ?>
        <input type="hidden" name="radian_about_action" value="save"/>
        <input type="hidden" name="item_index" value="<?php echo esc_attr( $form_idx ); ?>"/>
        <table class="form-table" role="presentation">
          <?php foreach ( $sec['fields'] as $key => $def ) :
              list( $label, $type, $req, $placeholder ) = $def; ?>
          <tr>
            <th scope="row"><label for="f_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td>
              <?php if ( 'textarea' === $type ) : ?>
                <textarea name="f_<?php echo esc_attr( $key ); ?>" id="f_<?php echo esc_attr( $key ); ?>" rows="3" class="large-text"
                          placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php echo 'required' === $req ? 'required' : ''; ?>><?php echo esc_textarea( $form[ $key ] ); ?></textarea>
              <?php elseif ( 'number' === $type ) : ?>
                <input name="f_<?php echo esc_attr( $key ); ?>" id="f_<?php echo esc_attr( $key ); ?>" type="number" min="0" max="99"
                       value="<?php echo esc_attr( $form[ $key ] ); ?>" <?php echo 'required' === $req ? 'required' : ''; ?>/>
              <?php else : ?>
                <input name="f_<?php echo esc_attr( $key ); ?>" id="f_<?php echo esc_attr( $key ); ?>" type="text" class="regular-text"
                       value="<?php echo esc_attr( $form[ $key ] ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php echo 'required' === $req ? 'required' : ''; ?>/>
                <?php if ( 'tags' === $type ) : ?><p class="description">Separate with commas.</p><?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
        <p class="submit" style="margin:0;padding:8px 0 4px;">
          <button type="submit" class="button button-primary"><?php echo $editing ? 'Save Changes' : 'Add ' . esc_html( ucfirst( $sec['singular'] ) ); ?></button>
          <?php if ( $editing ) : ?><a class="button" href="<?php echo esc_url( $base_url ); ?>">Cancel</a><?php endif; ?>
        </p>
      </form>

      <h2 style="margin-top:34px;"><?php echo esc_html( $sec['label'] ); ?> <span style="font-weight:400;color:#646970;">(<?php echo count( $items ); ?>)</span></h2>
      <?php if ( ! $items ) : ?>
        <p>Nothing here yet — add the first one above.</p>
      <?php else : ?>
      <table class="wp-list-table widefat fixed striped" style="max-width:1100px;">
        <thead>
          <tr>
            <?php foreach ( $sec['cols'] as $col ) : ?>
              <th><?php echo esc_html( $sec['fields'][ $col ][0] ); ?></th>
            <?php endforeach; ?>
            <th style="width:135px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ( $items as $i => $item ) : ?>
          <tr>
            <?php foreach ( $sec['cols'] as $col ) :
                $val = radian_about_display_field( $sec['fields'][ $col ][1], $item[ $col ] ?? '' );
                if ( 'quote' === $col && mb_strlen( $val ) > 90 ) $val = mb_substr( $val, 0, 90 ) . '…';
            ?>
              <td><?php echo esc_html( $val ); ?></td>
            <?php endforeach; ?>
            <td>
              <a class="button button-small" href="<?php echo esc_url( $base_url . '&edit=' . $i ); ?>">Edit</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Remove this <?php echo esc_js( $sec['singular'] ); ?> from the About page?');">
                <?php wp_nonce_field( 'radian_about' ); ?>
                <input type="hidden" name="radian_about_action" value="delete"/>
                <input type="hidden" name="item_index" value="<?php echo esc_attr( $i ); ?>"/>
                <button type="submit" class="button button-small button-link-delete">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>

      <?php endif; /* $data */ ?>
    </div>
    <?php
}
