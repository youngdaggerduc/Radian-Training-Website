<?php
/**
 * Training Calendar admin — wp-admin page (admins only) for editing the
 * home-page calendar events stored in assets/data/site-data.json → "events".
 * Add / edit / duplicate / delete sessions; writes the JSON back (with a
 * rotating .bak backup) so the front-end RADIAN_DATA pipeline is untouched.
 * Required from functions.php.
 */

defined( 'ABSPATH' ) || exit;

/* ── JSON helpers ───────────────────────────────────────────── */
function radian_site_data_path() {
    return get_template_directory() . '/assets/data/site-data.json';
}

function radian_read_site_data( &$err = null ) {
    $path = radian_site_data_path();
    if ( ! file_exists( $path ) ) { $err = 'site-data.json not found at ' . esc_html( $path ) . '.'; return null; }
    $data = json_decode( file_get_contents( $path ), true );
    if ( ! is_array( $data ) ) { $err = 'site-data.json contains invalid JSON — repair the file before editing here.'; return null; }
    if ( ! isset( $data['events'] ) || ! is_array( $data['events'] ) ) $data['events'] = [];
    return $data;
}

function radian_write_site_data( $data, &$err = null ) {
    $path = radian_site_data_path();
    @copy( $path, $path . '.bak' ); // rotating one-step backup
    $json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    if ( false === @file_put_contents( $path, $json ) ) {
        $err = 'Could not write site-data.json — check file permissions.';
        return false;
    }
    return true;
}

/* ── Reference data (matches Design/course-data.js ids) ─────── */
function radian_event_courses() {
    return [
        ''                          => '— no course page (info only) —',
        'cisrs-l1'                  => 'CISRS OSTS Scaffolder Level 1',
        'cisrs-l2'                  => 'CISRS OSTS Scaffolder Level 2',
        'cisrs-l3'                  => 'CISRS OSTS Scaffolder Level 3 (Advanced)',
        'cisrs-basic-inspection'    => 'CISRS Basic Scaffold Inspection',
        'cisrs-advanced-inspection' => 'CISRS Advanced Scaffold Inspection',
        'cisrs-supervisor'          => 'CISRS Scaffolder Supervisor',
        'gms-wah'                   => 'Getmie Safe Working at Height',
        'gms-rescue-basic'          => 'Getmie Safe Rescue — Basic',
        'gms-rescue-advanced'       => 'Getmie Safe Rescue — Advanced',
        'gms-rescue-refresher'      => 'Getmie Safe Rescue — Refresher',
    ];
}

function radian_event_types() {
    return [ 'cisrs' => 'CISRS (orange)', 'getmie' => 'Getmie Safe (blue)', 'rescue' => 'Rescue (red)' ];
}

/* ── Menu — "Radian Training" parent; siblings register submenus ── */
add_action( 'admin_menu', function () {
    add_menu_page(
        'Radian Training',
        'Radian Training',
        'manage_options',
        'radian-events',
        'radian_events_admin_page',
        'dashicons-hammer',
        21
    );
    add_submenu_page( 'radian-events', 'Training Calendar', 'Training Calendar', 'manage_options', 'radian-events', 'radian_events_admin_page' );
} );

/* ── Page ───────────────────────────────────────────────────── */
function radian_events_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to manage the training calendar.' );
    }

    $notice = '';
    $error  = '';
    $data   = radian_read_site_data( $read_err );
    if ( null === $data ) $error = $read_err;

    /* ── handle actions ── */
    if ( $data && ! empty( $_POST['radian_event_action'] ) ) {
        check_admin_referer( 'radian_events' );
        $action = sanitize_key( $_POST['radian_event_action'] );
        $events = $data['events'];

        if ( 'delete' === $action ) {
            $id     = absint( $_POST['event_id'] ?? 0 );
            $before = count( $events );
            $events = array_values( array_filter( $events, fn( $e ) => (int) ( $e['id'] ?? 0 ) !== $id ) );
            if ( count( $events ) < $before ) {
                $data['events'] = $events;
                if ( radian_write_site_data( $data, $werr ) ) $notice = 'Session deleted.';
                else $error = $werr;
            } else {
                $error = 'Session not found.';
            }
        }

        if ( 'save' === $action ) {
            $courses = radian_event_courses();
            $types   = radian_event_types();

            $id       = absint( $_POST['event_id'] ?? 0 ); // 0 = new
            $date     = sanitize_text_field( $_POST['ev_date'] ?? '' );
            $title    = sanitize_text_field( $_POST['ev_title'] ?? '' );
            $type     = sanitize_key( $_POST['ev_type'] ?? '' );
            $courseId = sanitize_text_field( $_POST['ev_course'] ?? '' );
            $duration = sanitize_text_field( $_POST['ev_duration'] ?? '' );
            $time     = sanitize_text_field( $_POST['ev_time'] ?? '' );
            $venue    = sanitize_text_field( $_POST['ev_venue'] ?? '' );
            $spots    = absint( $_POST['ev_spots'] ?? 0 );

            if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) )      $error = 'Please pick a valid date.';
            elseif ( '' === $title )                                    $error = 'Please enter a session title.';
            elseif ( ! isset( $types[ $type ] ) )                       $error = 'Please choose a valid type.';
            elseif ( ! array_key_exists( $courseId, $courses ) )        $error = 'Please choose a valid course link.';
            else {
                $row = [
                    'id'       => $id,
                    'date'     => $date,
                    'title'    => $title,
                    'type'     => $type,
                    'courseId' => $courseId,
                    'duration' => $duration,
                    'venue'    => $venue,
                    'spots'    => $spots,
                    'time'     => $time,
                ];

                if ( $id > 0 ) {                       // update existing
                    $found = false;
                    foreach ( $events as $i => $e ) {
                        if ( (int) ( $e['id'] ?? 0 ) === $id ) { $events[ $i ] = $row; $found = true; break; }
                    }
                    if ( ! $found ) $error = 'Session not found — it may have been deleted.';
                    else $notice = 'Session updated.';
                } else {                               // add new
                    $max = 0;
                    foreach ( $events as $e ) $max = max( $max, (int) ( $e['id'] ?? 0 ) );
                    $row['id'] = $max + 1;
                    $events[]  = $row;
                    $notice    = 'Session added to the calendar.';
                }

                if ( ! $error ) {
                    usort( $events, fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) ?: ( $a['id'] <=> $b['id'] ) );
                    $data['events'] = $events;
                    if ( ! radian_write_site_data( $data, $werr ) ) { $error = $werr; $notice = ''; }
                }
            }
        }
    }

    /* ── prefill the form (edit / duplicate) ── */
    $form    = [ 'id' => 0, 'date' => '', 'title' => '', 'type' => 'cisrs', 'courseId' => '', 'duration' => '5 days', 'venue' => 'Radian Training Centre, Claxton Bay', 'spots' => 10, 'time' => '08:00 – 17:00' ];
    $editing = false;
    $events  = $data ? $data['events'] : [];

    foreach ( [ 'edit', 'duplicate' ] as $mode ) {
        if ( ! empty( $_GET[ $mode ] ) ) {
            $want = absint( $_GET[ $mode ] );
            foreach ( $events as $e ) {
                if ( (int) ( $e['id'] ?? 0 ) === $want ) {
                    $form = [
                        'id'       => 'edit' === $mode ? $want : 0,
                        'date'     => 'edit' === $mode ? ( $e['date'] ?? '' ) : '',
                        'title'    => $e['title'] ?? '',
                        'type'     => $e['type'] ?? 'cisrs',
                        'courseId' => $e['courseId'] ?? '',
                        'duration' => $e['duration'] ?? '',
                        'venue'    => $e['venue'] ?? '',
                        'spots'    => (int) ( $e['spots'] ?? 0 ),
                        'time'     => $e['time'] ?? '',
                    ];
                    $editing = 'edit' === $mode;
                    break;
                }
            }
        }
    }

    $base_url = admin_url( 'admin.php?page=radian-events' );
    $courses  = radian_event_courses();
    $types    = radian_event_types();
    $today    = gmdate( 'Y-m-d' );
    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline">Training Calendar</h1>
      <hr class="wp-header-end"/>
      <p style="max-width:720px;">Sessions listed here appear on the <strong>home-page training calendar</strong> immediately.
      The <em>course link</em> decides where the permit's <em>Enroll</em> button sends people (and pre-selects the date on the enrol wizard).
      Data lives in <code>assets/data/site-data.json</code>; a backup (<code>.bak</code>) is kept of the previous version on every save.</p>

      <?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
      <?php if ( $error ) : ?><div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>

      <?php if ( $data ) : ?>

      <h2><?php echo $editing ? 'Edit Session' : 'Add a Session'; ?></h2>
      <form method="post" style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;max-width:860px;">
        <?php wp_nonce_field( 'radian_events' ); ?>
        <input type="hidden" name="radian_event_action" value="save"/>
        <input type="hidden" name="event_id" value="<?php echo esc_attr( $form['id'] ); ?>"/>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="ev_date">Date</label></th>
            <td><input name="ev_date" id="ev_date" type="date" value="<?php echo esc_attr( $form['date'] ); ?>" min="2020-01-01" required/>
                <p class="description">First day of the session.</p></td>
          </tr>
          <tr>
            <th scope="row"><label for="ev_title">Title</label></th>
            <td><input name="ev_title" id="ev_title" type="text" class="regular-text" value="<?php echo esc_attr( $form['title'] ); ?>" placeholder="CISRS Operative — Level 1" required/></td>
          </tr>
          <tr>
            <th scope="row"><label for="ev_type">Type</label></th>
            <td><select name="ev_type" id="ev_type">
                <?php foreach ( $types as $k => $label ) : ?>
                  <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $form['type'], $k ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="description">Sets the colour on the calendar and the filter chip it belongs to.</p></td>
          </tr>
          <tr>
            <th scope="row"><label for="ev_course">Course link</label></th>
            <td><select name="ev_course" id="ev_course">
                <?php foreach ( $courses as $k => $label ) : ?>
                  <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $form['courseId'], $k ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="description">Where the permit's Enroll button goes. Pick "no course page" for things like Scaffolding Awareness.</p></td>
          </tr>
          <tr>
            <th scope="row"><label for="ev_duration">Duration</label></th>
            <td><input name="ev_duration" id="ev_duration" type="text" value="<?php echo esc_attr( $form['duration'] ); ?>" placeholder="5 days"/></td>
          </tr>
          <tr>
            <th scope="row"><label for="ev_time">Daily time</label></th>
            <td><input name="ev_time" id="ev_time" type="text" value="<?php echo esc_attr( $form['time'] ); ?>" placeholder="08:00 – 17:00"/></td>
          </tr>
          <tr>
            <th scope="row"><label for="ev_venue">Venue</label></th>
            <td><input name="ev_venue" id="ev_venue" type="text" class="regular-text" value="<?php echo esc_attr( $form['venue'] ); ?>" placeholder="Radian Training Centre, Claxton Bay"/></td>
          </tr>
          <tr>
            <th scope="row"><label for="ev_spots">Spots open</label></th>
            <td><input name="ev_spots" id="ev_spots" type="number" min="0" max="99" value="<?php echo esc_attr( $form['spots'] ); ?>"/>
                <p class="description">5 or fewer shows a "filling fast" pulse on the calendar.</p></td>
          </tr>
        </table>
        <p class="submit" style="margin:0;padding:8px 0 4px;">
          <button type="submit" class="button button-primary"><?php echo $editing ? 'Save Changes' : 'Add Session'; ?></button>
          <?php if ( $editing || $form['title'] ) : ?>
            <a class="button" href="<?php echo esc_url( $base_url ); ?>">Cancel</a>
          <?php endif; ?>
        </p>
      </form>

      <h2 style="margin-top:34px;">Scheduled Sessions <span style="font-weight:400;color:#646970;">(<?php echo count( $events ); ?>)</span></h2>
      <?php if ( ! $events ) : ?>
        <p>No sessions yet — add the first one above.</p>
      <?php else : ?>
      <table class="wp-list-table widefat fixed striped" style="max-width:1100px;">
        <thead>
          <tr>
            <th style="width:105px;">Date</th>
            <th>Title</th>
            <th style="width:80px;">Type</th>
            <th style="width:90px;">Duration</th>
            <th>Venue</th>
            <th style="width:60px;">Spots</th>
            <th style="width:200px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ( $events as $e ) :
            $eid  = (int) ( $e['id'] ?? 0 );
            $past = ( $e['date'] ?? '' ) < $today;
        ?>
          <tr<?php echo $past ? ' style="opacity:0.55;"' : ''; ?>>
            <td><?php echo esc_html( $e['date'] ?? '' ); ?><?php echo $past ? ' <span style="color:#b32d2e;font-size:11px;">past</span>' : ''; ?></td>
            <td><strong><?php echo esc_html( $e['title'] ?? '' ); ?></strong>
                <?php if ( empty( $e['courseId'] ) ) : ?><br/><span style="color:#646970;font-size:12px;">no course page</span><?php endif; ?></td>
            <td><?php echo esc_html( $e['type'] ?? '' ); ?></td>
            <td><?php echo esc_html( $e['duration'] ?? '' ); ?></td>
            <td><?php echo esc_html( $e['venue'] ?? '' ); ?></td>
            <td><?php echo esc_html( (string) ( $e['spots'] ?? '' ) ); ?></td>
            <td>
              <a class="button button-small" href="<?php echo esc_url( $base_url . '&edit=' . $eid ); ?>">Edit</a>
              <a class="button button-small" href="<?php echo esc_url( $base_url . '&duplicate=' . $eid ); ?>" title="Start a new session with these details">Duplicate</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete “<?php echo esc_js( $e['title'] ?? '' ); ?>” on <?php echo esc_js( $e['date'] ?? '' ); ?>?');">
                <?php wp_nonce_field( 'radian_events' ); ?>
                <input type="hidden" name="radian_event_action" value="delete"/>
                <input type="hidden" name="event_id" value="<?php echo esc_attr( $eid ); ?>"/>
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
