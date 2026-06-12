<?php
/**
 * AI Assistant — "Ask the Site Office" lead-gen chat.
 *
 * Front end: floating widget (assets/js/assistant.js + assets/css/assistant.css)
 * on every design page, enabled only when an API key is saved + the toggle is on.
 *
 * Server: radian_ai_chat AJAX → OpenAI Chat Completions (default gpt-4o-mini —
 * the cheap tier: ~US$0.15/M input + $0.60/M output tokens; a full conversation
 * costs fractions of a cent). The model is grounded EXCLUSIVELY on
 * assets/data/ai-knowledge.md, which staff edit on the admin page.
 *
 * Scaling note (deliberate design): the knowledge base is a few KB, so we skip
 * vector embeddings entirely — the whole file (or, past ~7k chars, the
 * best-matching "## " sections by keyword overlap) is sent as context. That is
 * cheaper, simpler and more accurate than a vector store at this size; if the
 * file ever grows past ~50KB, swap radian_ai_context() for an embeddings index.
 *
 * Limits: per-visitor 8 msgs / 5 min + 40 / day (transients keyed on hashed IP),
 * 600-char messages, last 8 turns of history, max_tokens 350 per reply.
 *
 * Admin: Radian Training → AI Assistant — toggle, API key (stored in wp_options,
 * never in git, never sent to the browser), model, daily limit, knowledge editor
 * (.bak on save), connection test + today's usage counter.
 */

defined( 'ABSPATH' ) || exit;

/* ── Options / helpers ──────────────────────────────────────── */
function radian_ai_knowledge_path() {
    return get_template_directory() . '/assets/data/ai-knowledge.md';
}

function radian_ai_ready() {
    return get_option( 'radian_ai_enabled' ) && get_option( 'radian_ai_key' );
}

function radian_ai_model() {
    $m = trim( (string) get_option( 'radian_ai_model' ) );
    return $m ? $m : 'gpt-4o-mini';
}

function radian_ai_daily_limit() {
    $n = absint( get_option( 'radian_ai_daily_limit' ) );
    return $n > 0 ? $n : 40;
}

/* ── Context builder — whole file, or best sections past ~7k chars ── */
function radian_ai_context( $question ) {
    $path = radian_ai_knowledge_path();
    if ( ! file_exists( $path ) ) return '';
    $raw = (string) file_get_contents( $path );
    if ( strlen( $raw ) <= 7000 ) return $raw;

    // split on "## " headings; score each section by query-word overlap
    $sections = preg_split( '/^(?=## )/m', $raw );
    $words    = array_filter(
        preg_split( '/[^a-z0-9]+/i', strtolower( $question ) ),
        fn( $w ) => strlen( $w ) > 3
    );
    $scored = [];
    foreach ( $sections as $i => $sec ) {
        $hay   = strtolower( $sec );
        $score = 0;
        foreach ( $words as $w ) $score += substr_count( $hay, $w );
        $scored[] = [ 'i' => $i, 'score' => $score, 'sec' => $sec ];
    }
    usort( $scored, fn( $a, $b ) => $b['score'] <=> $a['score'] );

    $out = $sections[0] . ( isset( $sections[1] ) ? $sections[1] : '' ); // header + company/contact always in
    foreach ( $scored as $s ) {
        if ( $s['i'] <= 1 ) continue;
        if ( strlen( $out ) + strlen( $s['sec'] ) > 6500 ) break;
        $out .= $s['sec'];
    }
    return $out;
}

/* ── Chat endpoint ──────────────────────────────────────────── */
add_action( 'wp_ajax_nopriv_radian_ai_chat', 'radian_ai_chat' );
add_action( 'wp_ajax_radian_ai_chat',        'radian_ai_chat' );

function radian_ai_chat() {
    if ( ! check_ajax_referer( 'radian_ai_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Invalid request.' ], 403 );
    }
    if ( ! radian_ai_ready() ) {
        wp_send_json_error( [ 'message' => 'The assistant is off right now — WhatsApp the office on +1 (868) 280-4598.' ], 503 );
    }

    /* rate limits — hashed IP so no raw IPs sit in the DB */
    $ip    = hash( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) );
    $burst = 'radian_ai_b_' . substr( $ip, 0, 20 );
    $daily = 'radian_ai_d_' . substr( $ip, 0, 20 );
    $b = (int) get_transient( $burst );
    $d = (int) get_transient( $daily );
    if ( $b >= 8 || $d >= radian_ai_daily_limit() ) {
        wp_send_json_error( [ 'message' => "The office radio's run hot — give it a few minutes, or WhatsApp us on +1 (868) 280-4598 and a human will jump in. 👷" ], 429 );
    }

    $message = trim( sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ) );
    if ( '' === $message ) wp_send_json_error( [ 'message' => 'Say something first!' ], 400 );
    if ( mb_strlen( $message ) > 600 ) $message = mb_substr( $message, 0, 600 );

    /* history: [{role:'user'|'assistant', content}] — last 8, each capped */
    $history = json_decode( wp_unslash( $_POST['history'] ?? '[]' ), true );
    $msgs    = [];
    if ( is_array( $history ) ) {
        foreach ( array_slice( $history, -8 ) as $h ) {
            if ( ! is_array( $h ) ) continue;
            $role = ( $h['role'] ?? '' ) === 'assistant' ? 'assistant' : 'user';
            $text = trim( sanitize_textarea_field( $h['content'] ?? '' ) );
            if ( '' === $text ) continue;
            $msgs[] = [ 'role' => $role, 'content' => mb_substr( $text, 0, 600 ) ];
        }
    }

    $home   = home_url( '/' );
    $system =
        "You are \"the Site Office\" — the virtual assistant on the Radian H.A. Limited training website "
        . "(CISRS-accredited scaffolding & working-at-height training, Trinidad & Tobago). "
        . "Your job is to help visitors pick the right course and move them toward enrolling.\n\n"
        . "RULES:\n"
        . "- Answer ONLY from the SITE NOTES below. If the notes don't cover it, say the site office will "
        . "confirm and give the phone/WhatsApp number. NEVER invent prices, dates or policies.\n"
        . "- Stay on Radian/training topics; politely decline anything else.\n"
        . "- Keep replies short — under ~110 words, plain language, a little warm site-office character.\n"
        . "- Prices are TT$ and VAT inclusive; quote them exactly as written.\n"
        . "- Exact upcoming session dates are NOT in your notes — point to the training calendar.\n"
        . "- End most replies with one clear next step (a link, WhatsApp, or a call).\n"
        . "- If the visitor seems ready to book or wants a callback, send them to the enrol page or the "
        . "contact page — do not collect personal details in chat.\n\n"
        . "LINKS (absolute, share as plain URLs):\n"
        . "- Courses (CISRS): {$home}cisrs/\n"
        . "- Working at height & rescue: {$home}getmie-safe/\n"
        . "- Start here / career route: {$home}start-here/\n"
        . "- Training calendar: {$home}#calendar\n"
        . "- Enrol: {$home}enrol/\n"
        . "- Verify a certificate: {$home}certificate/\n"
        . "- Contact the office: {$home}contact/\n\n"
        . "SITE NOTES:\n" . radian_ai_context( $message );

    $payload = [
        'model'       => radian_ai_model(),
        'messages'    => array_merge(
            [ [ 'role' => 'system', 'content' => $system ] ],
            $msgs,
            [ [ 'role' => 'user', 'content' => $message ] ]
        ),
        'max_tokens'  => 350,
        'temperature' => 0.4,
    ];

    $res = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
        'timeout' => 25,
        'headers' => [
            'Authorization' => 'Bearer ' . get_option( 'radian_ai_key' ),
            'Content-Type'  => 'application/json',
        ],
        'body'    => wp_json_encode( $payload ),
    ] );

    if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
        wp_send_json_error( [ 'message' => "Radio static on our end — try again in a moment, or WhatsApp the office on +1 (868) 280-4598." ], 502 );
    }

    $data  = json_decode( wp_remote_retrieve_body( $res ), true );
    $reply = trim( $data['choices'][0]['message']['content'] ?? '' );
    if ( '' === $reply ) {
        wp_send_json_error( [ 'message' => 'Radio static on our end — try again in a moment.' ], 502 );
    }

    /* count usage AFTER a successful round-trip */
    set_transient( $burst, $b + 1, 5 * MINUTE_IN_SECONDS );
    set_transient( $daily, $d + 1, DAY_IN_SECONDS );

    $stats = get_option( 'radian_ai_stats', [] );
    $today = gmdate( 'Y-m-d' );
    if ( ( $stats['date'] ?? '' ) !== $today ) $stats = [ 'date' => $today, 'msgs' => 0, 'tokens' => 0 ];
    $stats['msgs']  += 1;
    $stats['tokens'] += (int) ( $data['usage']['total_tokens'] ?? 0 );
    update_option( 'radian_ai_stats', $stats, false );

    wp_send_json_success( [ 'reply' => $reply ] );
}

/* ── Admin page ─────────────────────────────────────────────── */
add_action( 'admin_menu', function () {
    add_submenu_page( 'radian-events', 'AI Assistant', 'AI Assistant', 'manage_options', 'radian-ai', 'radian_ai_admin_page' );
} );

function radian_ai_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Not allowed.' );

    $notice = '';
    $error  = '';

    if ( ! empty( $_POST['radian_ai_action'] ) ) {
        check_admin_referer( 'radian_ai' );
        $action = sanitize_key( $_POST['radian_ai_action'] );

        if ( 'settings' === $action ) {
            update_option( 'radian_ai_enabled', empty( $_POST['ai_enabled'] ) ? 0 : 1 );
            update_option( 'radian_ai_model', sanitize_text_field( $_POST['ai_model'] ?? 'gpt-4o-mini' ) );
            update_option( 'radian_ai_daily_limit', absint( $_POST['ai_daily'] ?? 40 ) );
            $key = trim( sanitize_text_field( $_POST['ai_key'] ?? '' ) );
            if ( '' !== $key ) update_option( 'radian_ai_key', $key );   // blank = keep existing
            $notice = 'Settings saved.';

            /* connection test (free endpoint) when we have a key */
            if ( get_option( 'radian_ai_key' ) ) {
                $t = wp_remote_get( 'https://api.openai.com/v1/models/' . rawurlencode( radian_ai_model() ), [
                    'timeout' => 12,
                    'headers' => [ 'Authorization' => 'Bearer ' . get_option( 'radian_ai_key' ) ],
                ] );
                if ( is_wp_error( $t ) )                                   $error = 'Could not reach OpenAI: ' . $t->get_error_message();
                elseif ( 200 !== wp_remote_retrieve_response_code( $t ) )  $error = 'OpenAI rejected the key/model (HTTP ' . wp_remote_retrieve_response_code( $t ) . ') — check both.';
                else                                                       $notice = 'Settings saved — key and model verified with OpenAI ✓';
            }
        }

        if ( 'knowledge' === $action ) {
            $text = (string) wp_unslash( $_POST['ai_knowledge'] ?? '' );
            $text = str_replace( [ "\r\n", "\0" ], [ "\n", '' ], $text );
            $path = radian_ai_knowledge_path();
            @copy( $path, $path . '.bak' );
            if ( false === @file_put_contents( $path, $text ) ) $error = 'Could not write ai-knowledge.md — check file permissions.';
            else $notice = 'Knowledge saved — the assistant uses it on the very next message.';
        }
    }

    $enabled   = (bool) get_option( 'radian_ai_enabled' );
    $key       = (string) get_option( 'radian_ai_key' );
    $model     = radian_ai_model();
    $daily     = radian_ai_daily_limit();
    $knowledge = file_exists( radian_ai_knowledge_path() ) ? file_get_contents( radian_ai_knowledge_path() ) : '';
    $stats     = get_option( 'radian_ai_stats', [] );
    $today_ok  = ( $stats['date'] ?? '' ) === gmdate( 'Y-m-d' );
    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline">AI Assistant — "Ask the Site Office"</h1>
      <hr class="wp-header-end"/>
      <p style="max-width:760px;">A floating chat on every page that answers from the <strong>site notes</strong> below and steers
      visitors toward enrolling. It can only say what's written here — keep the notes current and it stays accurate.
      Site-wide today: <strong><?php echo $today_ok ? (int) $stats['msgs'] : 0; ?></strong> messages ·
      <strong><?php echo $today_ok ? number_format( (int) $stats['tokens'] ) : 0; ?></strong> tokens
      <span style="color:#646970;">(gpt-4o-mini ≈ US$0.15 per million input tokens — typical day: well under a cent).</span></p>

      <?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
      <?php if ( $error ) : ?><div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>
      <?php if ( $enabled && ! $key ) : ?><div class="notice notice-warning"><p>The assistant is enabled but has no API key — the widget stays hidden until a key is saved.</p></div><?php endif; ?>

      <h2>Settings</h2>
      <form method="post" style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;max-width:760px;">
        <?php wp_nonce_field( 'radian_ai' ); ?>
        <input type="hidden" name="radian_ai_action" value="settings"/>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row">Status</th>
            <td><label><input type="checkbox" name="ai_enabled" <?php checked( $enabled ); ?>/> Show the assistant on the site</label></td>
          </tr>
          <tr>
            <th scope="row"><label for="ai_key">OpenAI API key</label></th>
            <td><input name="ai_key" id="ai_key" type="password" class="regular-text code" autocomplete="off"
                       placeholder="<?php echo $key ? esc_attr( 'saved — ends in …' . substr( $key, -4 ) . ' (leave blank to keep)' ) : 'sk-…'; ?>"/>
                <p class="description">From <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">platform.openai.com/api-keys</a>.
                Stored in the WordPress database only — never in git, never sent to visitors' browsers. Saving runs a live key check.</p></td>
          </tr>
          <tr>
            <th scope="row"><label for="ai_model">Model</label></th>
            <td><input name="ai_model" id="ai_model" type="text" class="regular-text code" value="<?php echo esc_attr( $model ); ?>"/>
                <p class="description"><code>gpt-4o-mini</code> is the deliberate default — the cheap tier, more than capable of
                answering from notes. Only change it if OpenAI retires the model.</p></td>
          </tr>
          <tr>
            <th scope="row"><label for="ai_daily">Per-visitor daily limit</label></th>
            <td><input name="ai_daily" id="ai_daily" type="number" min="5" max="500" value="<?php echo esc_attr( $daily ); ?>"/>
                <p class="description">Messages per visitor per day (a burst limit of 8 per 5 minutes always applies). Caps the worst-case spend.</p></td>
          </tr>
        </table>
        <p class="submit" style="margin:0;padding:8px 0 4px;"><button type="submit" class="button button-primary">Save Settings</button></p>
      </form>

      <h2 style="margin-top:34px;">Site notes — what the assistant knows</h2>
      <form method="post" style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;max-width:960px;">
        <?php wp_nonce_field( 'radian_ai' ); ?>
        <input type="hidden" name="radian_ai_action" value="knowledge"/>
        <p style="margin-top:0;" class="description">Plain text/markdown. Start sections with <code>## </code> — when the file grows large,
        the assistant automatically picks the sections most relevant to each question. Update prices here whenever they change.
        A <code>.bak</code> of the previous version is kept on every save.</p>
        <textarea name="ai_knowledge" rows="24" class="large-text code" style="font-size:12.5px;line-height:1.55;"><?php echo esc_textarea( $knowledge ); ?></textarea>
        <p class="submit" style="margin:0;padding:8px 0 4px;"><button type="submit" class="button button-primary">Save Site Notes</button></p>
      </form>
    </div>
    <?php
}
