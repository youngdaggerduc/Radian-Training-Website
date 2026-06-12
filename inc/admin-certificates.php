<?php
/**
 * Certificate Registry admin — wp-admin page (admins only) for the certificate
 * lookup registry in assets/data/certificates.csv. Add / edit / delete / search
 * records; suggests the next certificate number, auto-fills expiry (+3 years),
 * shows live valid/soon/expired status, keeps a .bak on every save.
 * The Excel → CSV UTF-8 workflow still works; this page strips any BOM Excel
 * leaves behind and writes clean UTF-8 so the front-end lookup never breaks.
 * Required from functions.php (after admin-events.php).
 */

defined( 'ABSPATH' ) || exit;

/* ── CSV helpers ────────────────────────────────────────────── */
function radian_certs_path() {
    return get_template_directory() . '/assets/data/certificates.csv';
}

/* internalLink = staff-only pointer to where the actual certificate file is
 * stored (Drive/Dropbox/server). NEVER sent to the public lookup — functions.php
 * unsets it before responding. */
const RADIAN_CERT_HEADERS = [
    'certificateNo', 'cisrsStudentId', 'firstName', 'lastName', 'courseName',
    'instructorName', 'instructorNo', 'results', 'venue', 'startDate', 'endDate', 'expiryDate',
    'internalLink',
];

function radian_read_certs( &$err = null ) {
    $path = radian_certs_path();
    if ( ! file_exists( $path ) ) { $err = 'certificates.csv not found.'; return null; }
    $fh = fopen( $path, 'r' );
    if ( ! $fh ) { $err = 'Could not open certificates.csv.'; return null; }
    $headers = fgetcsv( $fh );
    if ( ! $headers ) { fclose( $fh ); $err = 'certificates.csv is empty.'; return null; }
    $headers[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $headers[0] ); // strip Excel BOM
    $rows = [];
    while ( ( $row = fgetcsv( $fh ) ) !== false ) {
        if ( count( $row ) < count( $headers ) ) continue;
        $rows[] = array_combine( $headers, array_slice( $row, 0, count( $headers ) ) );
    }
    fclose( $fh );
    return $rows;
}

function radian_write_certs( $rows, &$err = null ) {
    $path = radian_certs_path();
    @copy( $path, $path . '.bak' );
    $fh = @fopen( $path, 'w' );
    if ( ! $fh ) { $err = 'Could not write certificates.csv — check file permissions.'; return false; }
    fputcsv( $fh, RADIAN_CERT_HEADERS );
    foreach ( $rows as $r ) {
        fputcsv( $fh, array_map( fn( $h ) => $r[ $h ] ?? '', RADIAN_CERT_HEADERS ) );
    }
    fclose( $fh );
    return true;
}

/* "14 Oct 2024" ⇄ "2024-10-14" for <input type=date> */
function radian_cert_date_to_input( $s ) {
    $ts = strtotime( trim( (string) $s ) );
    return $ts ? gmdate( 'Y-m-d', $ts ) : '';
}
function radian_cert_date_from_input( $s ) {
    $ts = strtotime( trim( (string) $s ) );
    return $ts ? gmdate( 'j M Y', $ts ) : '';
}

/* ── Menu ───────────────────────────────────────────────────── */
add_action( 'admin_menu', function () {
    add_submenu_page( 'radian-events', 'Certificate Registry', 'Certificates', 'manage_options', 'radian-certificates', 'radian_certs_admin_page' );
} );

/* ── Export — streams the registry as a CSV download ────────── */
add_action( 'admin_post_radian_certs_export', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Not allowed.' );
    check_admin_referer( 'radian_certs_export' );
    $rows = radian_read_certs();
    if ( null === $rows ) $rows = [];
    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=radian-certificates-' . gmdate( 'Y-m-d' ) . '.csv' );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, RADIAN_CERT_HEADERS );
    foreach ( $rows as $r ) {
        fputcsv( $out, array_map( fn( $h ) => $r[ $h ] ?? '', RADIAN_CERT_HEADERS ) );
    }
    fclose( $out );
    exit;
} );

/* ── Import — parses an uploaded CSV into the registry ──────── */
function radian_certs_import( array $rows, &$notice, &$error ) {
    if ( empty( $_FILES['cert_csv']['tmp_name'] ) || UPLOAD_ERR_OK !== ( $_FILES['cert_csv']['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
        $error = 'Please choose a CSV file to import.';
        return $rows;
    }
    if ( ( $_FILES['cert_csv']['size'] ?? 0 ) > 2 * 1024 * 1024 ) {
        $error = 'File too large (2 MB max).';
        return $rows;
    }
    $fh = fopen( $_FILES['cert_csv']['tmp_name'], 'r' );
    if ( ! $fh ) { $error = 'Could not read the uploaded file.'; return $rows; }

    $hdr = fgetcsv( $fh );
    if ( ! $hdr ) { fclose( $fh ); $error = 'The file is empty.'; return $rows; }
    $hdr[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $hdr[0] );           // Excel BOM
    $hdr    = array_map( fn( $h ) => strtolower( trim( $h ) ), $hdr );

    /* map our columns → position in the uploaded file (case-insensitive) */
    $pos = [];
    foreach ( RADIAN_CERT_HEADERS as $col ) {
        $i = array_search( strtolower( $col ), $hdr, true );
        if ( false !== $i ) $pos[ $col ] = $i;
    }
    if ( ! isset( $pos['certificateNo'], $pos['lastName'] ) ) {
        fclose( $fh );
        $error = 'The file needs at least "certificateNo" and "lastName" columns (header row required — export first to get a template).';
        return $rows;
    }

    $replace = 'replace' === sanitize_key( $_POST['import_mode'] ?? 'merge' );
    $current = $replace ? [] : $rows;
    $index   = [];                                                        // certNo → row position
    foreach ( $current as $i => $r ) $index[ strtoupper( trim( $r['certificateNo'] ) ) ] = $i;

    $added = 0; $updated = 0; $skipped = 0;
    while ( ( $line = fgetcsv( $fh ) ) !== false ) {
        if ( [ null ] === $line ) continue;                              // blank line
        $rec = [];
        foreach ( RADIAN_CERT_HEADERS as $col ) {
            $rec[ $col ] = isset( $pos[ $col ], $line[ $pos[ $col ] ] ) ? sanitize_text_field( $line[ $pos[ $col ] ] ) : '';
        }
        $rec['certificateNo'] = strtoupper( trim( $rec['certificateNo'] ) );
        if ( ! preg_match( '/^RDN-\d{4}-\d{5}$/', $rec['certificateNo'] ) || '' === trim( $rec['lastName'] ) ) {
            $skipped++;
            continue;
        }
        if ( isset( $index[ $rec['certificateNo'] ] ) ) {
            $current[ $index[ $rec['certificateNo'] ] ] = $rec;
            $updated++;
        } else {
            $index[ $rec['certificateNo'] ] = count( $current );
            $current[] = $rec;
            $added++;
        }
    }
    fclose( $fh );

    if ( 0 === $added + $updated ) {
        $error = 'No valid rows found — nothing was imported.' . ( $skipped ? " ({$skipped} row(s) skipped: bad certificate № or missing last name.)" : '' );
        return $rows;
    }
    if ( ! radian_write_certs( $current, $werr ) ) { $error = $werr; return $rows; }

    $notice = sprintf(
        '%s — %d added, %d updated%s. Previous registry saved as certificates.csv.bak.',
        $replace ? 'Registry replaced' : 'Import merged',
        $added, $updated,
        $skipped ? ", {$skipped} skipped (bad certificate № or missing last name)" : ''
    );
    return $current;
}

/* ── Page ───────────────────────────────────────────────────── */
function radian_certs_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to manage the certificate registry.' );
    }

    $notice = '';
    $error  = '';
    $rows   = radian_read_certs( $read_err );
    if ( null === $rows ) { $rows = []; $error = $read_err; }

    /* ── handle actions ── */
    if ( ! $error && ! empty( $_POST['radian_cert_action'] ) ) {
        check_admin_referer( 'radian_certs' );
        $action = sanitize_key( $_POST['radian_cert_action'] );

        if ( 'import' === $action ) {
            $rows = radian_certs_import( $rows, $notice, $error );
        }

        if ( 'delete' === $action ) {
            $no     = strtoupper( sanitize_text_field( $_POST['cert_no'] ?? '' ) );
            $before = count( $rows );
            $rows   = array_values( array_filter( $rows, fn( $r ) => strtoupper( trim( $r['certificateNo'] ) ) !== $no ) );
            if ( count( $rows ) < $before ) {
                if ( radian_write_certs( $rows, $werr ) ) $notice = 'Certificate record deleted.';
                else $error = $werr;
            } else {
                $error = 'Record not found.';
            }
        }

        if ( 'save' === $action ) {
            $orig = strtoupper( sanitize_text_field( $_POST['orig_no'] ?? '' ) ); // '' = new record
            $rec  = [];
            foreach ( RADIAN_CERT_HEADERS as $h ) {
                $rec[ $h ] = sanitize_text_field( wp_unslash( $_POST[ 'c_' . $h ] ?? '' ) );
            }
            $rec['certificateNo'] = strtoupper( trim( $rec['certificateNo'] ) );

            /* dates arrive as Y-m-d from <input type=date> → store as "14 Oct 2024" */
            foreach ( [ 'startDate', 'endDate', 'expiryDate' ] as $d ) {
                $rec[ $d ] = radian_cert_date_from_input( $rec[ $d ] );
            }
            /* blank expiry → end date + 3 years − 1 day (matches existing records) */
            if ( ! $rec['expiryDate'] && $rec['endDate'] ) {
                $ts = strtotime( $rec['endDate'] . ' +3 years -1 day' );
                if ( $ts ) $rec['expiryDate'] = gmdate( 'j M Y', $ts );
            }

            $dupe = false;
            foreach ( $rows as $r ) {
                $no = strtoupper( trim( $r['certificateNo'] ) );
                if ( $no === $rec['certificateNo'] && $no !== $orig ) { $dupe = true; break; }
            }

            if ( ! preg_match( '/^RDN-\d{4}-\d{5}$/', $rec['certificateNo'] ) ) $error = 'Certificate № must look like RDN-2026-00514.';
            elseif ( '' === trim( $rec['lastName'] ) )                          $error = 'Last name is required — the lookup matches on it.';
            elseif ( '' === trim( $rec['firstName'] ) )                         $error = 'First name is required.';
            elseif ( '' === trim( $rec['courseName'] ) )                        $error = 'Course name is required.';
            elseif ( '' === $rec['startDate'] || '' === $rec['endDate'] )       $error = 'Start and end dates are required.';
            elseif ( $dupe )                                                    $error = 'That certificate № is already in the registry.';
            else {
                if ( $orig ) {                          // update
                    $found = false;
                    foreach ( $rows as $i => $r ) {
                        if ( strtoupper( trim( $r['certificateNo'] ) ) === $orig ) { $rows[ $i ] = $rec; $found = true; break; }
                    }
                    if ( ! $found ) $error = 'Record not found — it may have been deleted.';
                    else $notice = 'Certificate record updated.';
                } else {                                // add
                    $rows[] = $rec;
                    $notice = 'Certificate added to the registry — it can be verified on the site immediately.';
                }
                if ( ! $error && ! radian_write_certs( $rows, $werr ) ) { $error = $werr; $notice = ''; }
            }
        }
    }

    /* ── suggested next certificate number ── */
    $max = 0;
    foreach ( $rows as $r ) {
        if ( preg_match( '/^RDN-\d{4}-(\d+)$/i', trim( $r['certificateNo'] ), $m ) ) $max = max( $max, (int) $m[1] );
    }
    $next_no = 'RDN-' . gmdate( 'Y' ) . '-' . str_pad( (string) ( $max + 1 ), 5, '0', STR_PAD_LEFT );

    /* ── prefill the form ── */
    $form = array_fill_keys( RADIAN_CERT_HEADERS, '' );
    $form['certificateNo'] = $next_no;
    $form['venue']         = 'Radian Training Centre, Claxton Bay';
    $editing = '';
    if ( ! empty( $_GET['edit'] ) ) {
        $want = strtoupper( sanitize_text_field( $_GET['edit'] ) );
        foreach ( $rows as $r ) {
            if ( strtoupper( trim( $r['certificateNo'] ) ) === $want ) {
                $form    = $r;
                $editing = $want;
                break;
            }
        }
    }

    /* instructor datalist from site-data.json (admin-events.php helper) */
    $instructors = [];
    if ( function_exists( 'radian_read_site_data' ) ) {
        $sd = radian_read_site_data();
        if ( $sd && ! empty( $sd['instructors'] ) ) $instructors = $sd['instructors'];
    }
    $course_names = array_values( array_unique( array_filter( array_map( fn( $r ) => trim( $r['courseName'] ?? '' ), $rows ) ) ) );

    /* search filter (display only) */
    $search  = sanitize_text_field( $_GET['s'] ?? '' );
    $visible = array_reverse( $rows );           // newest last in file → newest first on screen
    if ( $search ) {
        $needle  = mb_strtolower( $search );
        $visible = array_values( array_filter( $visible, function ( $r ) use ( $needle ) {
            $hay = mb_strtolower( implode( ' ', [ $r['certificateNo'] ?? '', $r['firstName'] ?? '', $r['lastName'] ?? '', $r['courseName'] ?? '', $r['cisrsStudentId'] ?? '' ] ) );
            return false !== mb_strpos( $hay, $needle );
        } ) );
    }

    $base_url = admin_url( 'admin.php?page=radian-certificates' );
    $badge    = [
        'valid'   => '<span style="background:#d5f5e3;color:#1d6e3f;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;">Valid</span>',
        'soon'    => '<span style="background:#fdf0d5;color:#b97a06;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;">Expiring soon</span>',
        'expired' => '<span style="background:#fde8e8;color:#b32d2e;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;">Expired</span>',
    ];
    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline">Certificate Registry</h1>
      <hr class="wp-header-end"/>
      <p style="max-width:760px;">Records here power the <strong>Verify a Certificate</strong> page — a delegate (or their employer) enters the
      <em>last name</em> + <em>certificate №</em> and gets the certificate on screen instantly. Add a record after every completed course.
      Data lives in <code>assets/data/certificates.csv</code> (the Excel workflow still works); a <code>.bak</code> of the previous
      version is kept on every save.</p>

      <?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
      <?php if ( $error ) : ?><div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>

      <h2><?php echo $editing ? 'Edit Certificate ' . esc_html( $editing ) : 'Add a Certificate'; ?></h2>
      <form method="post" style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;max-width:900px;">
        <?php wp_nonce_field( 'radian_certs' ); ?>
        <input type="hidden" name="radian_cert_action" value="save"/>
        <input type="hidden" name="orig_no" value="<?php echo esc_attr( $editing ); ?>"/>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="c_certificateNo">Certificate №</label></th>
            <td><input name="c_certificateNo" id="c_certificateNo" type="text" class="regular-text code" value="<?php echo esc_attr( $form['certificateNo'] ); ?>" pattern="RDN-\d{4}-\d{5}" required/>
                <p class="description">Format RDN-YYYY-NNNNN. Next free number: <code><?php echo esc_html( $next_no ); ?></code></p></td>
          </tr>
          <tr>
            <th scope="row">Delegate</th>
            <td>
              <input name="c_firstName" type="text" value="<?php echo esc_attr( $form['firstName'] ); ?>" placeholder="First name" required style="width:170px;"/>
              <input name="c_lastName" type="text" value="<?php echo esc_attr( $form['lastName'] ); ?>" placeholder="Last name" required style="width:170px;"/>
              <input name="c_cisrsStudentId" type="text" value="<?php echo esc_attr( $form['cisrsStudentId'] ); ?>" placeholder="CISRS-93817" style="width:150px;"/>
              <p class="description">The lookup matches the <strong>last name exactly as typed here</strong> (case doesn't matter). Third field = CISRS student ID.</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="c_courseName">Course</label></th>
            <td><input name="c_courseName" id="c_courseName" type="text" class="regular-text" value="<?php echo esc_attr( $form['courseName'] ); ?>" list="radian-courses" required/>
              <datalist id="radian-courses">
                <?php foreach ( $course_names as $cn ) : ?><option value="<?php echo esc_attr( $cn ); ?>"></option><?php endforeach; ?>
              </datalist></td>
          </tr>
          <tr>
            <th scope="row">Instructor</th>
            <td>
              <input name="c_instructorName" type="text" value="<?php echo esc_attr( $form['instructorName'] ); ?>" placeholder="Name" list="radian-ins-names" style="width:220px;"/>
              <input name="c_instructorNo" type="text" value="<?php echo esc_attr( $form['instructorNo'] ); ?>" placeholder="INS-00021" list="radian-ins-regs" style="width:140px;"/>
              <datalist id="radian-ins-names">
                <?php foreach ( $instructors as $ins ) : ?><option value="<?php echo esc_attr( $ins['name'] ?? '' ); ?>"></option><?php endforeach; ?>
              </datalist>
              <datalist id="radian-ins-regs">
                <?php foreach ( $instructors as $ins ) : ?><option value="<?php echo esc_attr( $ins['reg'] ?? '' ); ?>"><?php echo esc_html( $ins['name'] ?? '' ); ?></option><?php endforeach; ?>
              </datalist>
              <p class="description">Suggestions come from the About-page instructor list — keep reg №s consistent.</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="c_results">Result</label></th>
            <td><input name="c_results" id="c_results" type="text" value="<?php echo esc_attr( $form['results'] ); ?>" list="radian-results" placeholder="Pass"/>
              <datalist id="radian-results">
                <option value="Pass"></option><option value="Pass — Merit"></option><option value="Pass — Distinction"></option><option value="Attended"></option>
              </datalist></td>
          </tr>
          <tr>
            <th scope="row"><label for="c_venue">Venue</label></th>
            <td><input name="c_venue" id="c_venue" type="text" class="regular-text" value="<?php echo esc_attr( $form['venue'] ); ?>"/></td>
          </tr>
          <tr>
            <th scope="row">Dates</th>
            <td>
              <label>Start <input name="c_startDate" type="date" value="<?php echo esc_attr( radian_cert_date_to_input( $form['startDate'] ) ); ?>" required/></label>
              &nbsp; <label>End <input name="c_endDate" type="date" value="<?php echo esc_attr( radian_cert_date_to_input( $form['endDate'] ) ); ?>" required/></label>
              &nbsp; <label>Expires <input name="c_expiryDate" type="date" value="<?php echo esc_attr( radian_cert_date_to_input( $form['expiryDate'] ) ); ?>"/></label>
              <p class="description">Leave <em>Expires</em> blank to auto-set 3 years from the end date.</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="c_internalLink">Certificate file <span style="color:#646970;font-weight:400;">(internal)</span></label></th>
            <td><input name="c_internalLink" id="c_internalLink" type="text" class="large-text code" value="<?php echo esc_attr( $form['internalLink'] ); ?>" placeholder="https://drive.google.com/… (where the actual certificate is stored)"/>
                <p class="description"><strong>Staff only — never shown on the public site or in the lookup.</strong> Paste the link to the stored certificate (Drive, Dropbox, OneDrive…). When a delegate's copy request is approved, open this link and send them the file.</p></td>
          </tr>
        </table>
        <p class="submit" style="margin:0;padding:8px 0 4px;">
          <button type="submit" class="button button-primary"><?php echo $editing ? 'Save Changes' : 'Add Certificate'; ?></button>
          <?php if ( $editing ) : ?><a class="button" href="<?php echo esc_url( $base_url ); ?>">Cancel</a><?php endif; ?>
        </p>
      </form>

      <h2 style="margin-top:34px;">Import / Export</h2>
      <div style="background:#fff;border:1px solid #c3c4c7;padding:14px 20px;max-width:900px;display:flex;gap:32px;flex-wrap:wrap;align-items:flex-start;">
        <form method="post" enctype="multipart/form-data" style="flex:1;min-width:340px;"
              onsubmit="if(this.import_mode.value==='replace')return confirm('Replace the ENTIRE registry with this file? Existing records not in the file will be deleted. (A .bak of the current registry is kept.)');">
          <?php wp_nonce_field( 'radian_certs' ); ?>
          <input type="hidden" name="radian_cert_action" value="import"/>
          <p style="margin-top:0;"><strong>Import a CSV</strong> — bulk-add records (e.g. a whole course group prepared in Excel).</p>
          <p><input type="file" name="cert_csv" accept=".csv,text/csv" required/></p>
          <p>
            <label><input type="radio" name="import_mode" value="merge" checked/> <strong>Merge</strong> — add new records, update existing ones (matched by certificate №)</label><br/>
            <label><input type="radio" name="import_mode" value="replace"/> <strong>Replace</strong> — wipe the registry and use only this file</label>
          </p>
          <p style="margin-bottom:0;"><button type="submit" class="button">Import CSV</button>
             <span class="description">Needs a header row — export first to get a template. Rows with a bad certificate № or no last name are skipped.</span></p>
        </form>
        <div style="min-width:260px;">
          <p style="margin-top:0;"><strong>Export the registry</strong> — full CSV download (includes the internal file links), ready for Excel or as an import template / backup.</p>
          <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=radian_certs_export' ), 'radian_certs_export' ) ); ?>">⬇ Export CSV</a>
        </div>
      </div>

      <h2 style="margin-top:34px;">Registry <span style="font-weight:400;color:#646970;">(<?php echo count( $rows ); ?> record<?php echo 1 === count( $rows ) ? '' : 's'; ?>)</span></h2>
      <form method="get" style="margin-bottom:10px;">
        <input type="hidden" name="page" value="radian-certificates"/>
        <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search name, № or course…" style="width:280px;"/>
        <button class="button">Search</button>
        <?php if ( $search ) : ?><a class="button button-link" href="<?php echo esc_url( $base_url ); ?>">Clear</a><?php endif; ?>
      </form>

      <?php if ( ! $visible ) : ?>
        <p><?php echo $search ? 'No records match that search.' : 'No certificates yet — add the first one above.'; ?></p>
      <?php else : ?>
      <table class="wp-list-table widefat fixed striped" style="max-width:1150px;">
        <thead>
          <tr>
            <th style="width:140px;">Certificate №</th>
            <th style="width:160px;">Delegate</th>
            <th>Course</th>
            <th style="width:100px;">Completed</th>
            <th style="width:160px;">Expires</th>
            <th style="width:55px;">File</th>
            <th style="width:135px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ( $visible as $r ) :
            $no     = trim( $r['certificateNo'] ?? '' );
            $status = function_exists( 'radian_expiry_status' ) ? radian_expiry_status( $r['expiryDate'] ?? '' ) : 'valid';
        ?>
          <tr>
            <td><code><?php echo esc_html( $no ); ?></code></td>
            <td><strong><?php echo esc_html( trim( ( $r['firstName'] ?? '' ) . ' ' . ( $r['lastName'] ?? '' ) ) ); ?></strong></td>
            <td><?php echo esc_html( $r['courseName'] ?? '' ); ?></td>
            <td><?php echo esc_html( $r['endDate'] ?? '' ); ?></td>
            <td><?php echo esc_html( $r['expiryDate'] ?? '' ); ?> <?php echo wp_kses_post( $badge[ $status ] ?? '' ); ?></td>
            <td><?php if ( ! empty( $r['internalLink'] ) ) : ?>
                  <a href="<?php echo esc_url( $r['internalLink'] ); ?>" target="_blank" rel="noopener noreferrer" title="Open the stored certificate (staff only)">📄 ↗</a>
                <?php else : ?><span style="color:#c3c4c7;">—</span><?php endif; ?></td>
            <td>
              <a class="button button-small" href="<?php echo esc_url( $base_url . '&edit=' . rawurlencode( $no ) ); ?>">Edit</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete certificate <?php echo esc_js( $no ); ?> (<?php echo esc_js( trim( ( $r['firstName'] ?? '' ) . ' ' . ( $r['lastName'] ?? '' ) ) ); ?>)? The delegate will no longer be able to verify it.');">
                <?php wp_nonce_field( 'radian_certs' ); ?>
                <input type="hidden" name="radian_cert_action" value="delete"/>
                <input type="hidden" name="cert_no" value="<?php echo esc_attr( $no ); ?>"/>
                <button type="submit" class="button button-small button-link-delete">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <?php
}
