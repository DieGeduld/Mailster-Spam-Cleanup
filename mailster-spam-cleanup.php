<?php
/**
 * Plugin Name: Mailster Spam Cleanup
 * Description: Subscriber manuell per Textarea oder CSV-Upload deaktivieren/löschen.
 * Version: 1.2
 * Author: Unkonventionell
 *
 * INSTALLATION:
 * 1. Diese Datei in /wp-content/plugins/mailster-spam-cleanup/ kopieren
 * 2. Plugin im WordPress-Backend aktivieren
 * 3. Im Backend unter "Newsletter → Spam Cleanup" aufrufen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Admin-Menü registrieren
add_action( 'admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=newsletter',
        'Mailster Spam Cleanup',
        'Spam Cleanup',
        'manage_options',
        'mailster-spam-cleanup',
        'msc_render_page'
    );
} );

// Hauptseite rendern
function msc_render_page() {
    $results = [];
    $action  = '';
    $step    = 1; // 1: Input, 2: Preview, 3: Done

    if ( isset( $_POST['msc_check'] ) && check_admin_referer( 'msc_run_action' ) ) {
        $step   = 2;
        $action = sanitize_text_field( $_POST['msc_action'] ?? 'unsubscribe' );
        $source = sanitize_text_field( $_POST['msc_source'] ?? 'textarea' );

        $emails = [];

        if ( $source === 'csv' ) {
            if ( isset( $_FILES['msc_csv_file'] ) && $_FILES['msc_csv_file']['error'] === UPLOAD_ERR_OK ) {
                $tmp_name = $_FILES['msc_csv_file']['tmp_name'];
                $col_email = isset($_POST['msc_col_email']) && $_POST['msc_col_email'] !== '' ? (int)$_POST['msc_col_email'] : -1;
                
                if ( $col_email >= 0 ) {
                    $handle = fopen( $tmp_name, 'r' );
                    if ( $handle ) {
                        // detect delimiter
                        $first_line = fgets($handle);
                        $delimiter = strpos($first_line, ';') !== false ? ';' : ',';
                        rewind($handle);
                        
                        $header = fgetcsv( $handle, 0, $delimiter );
                        while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
                            if ( !isset($row[$col_email]) ) continue;
                            
                            $email = sanitize_email(trim($row[$col_email]));
                            if ( is_email($email) ) {
                                $emails[] = $email;
                            }
                        }
                        fclose( $handle );
                    }
                }
            }
        } else {
            // Aus Textarea: eine E-Mail pro Zeile
            $raw = wp_unslash( $_POST['msc_emails'] ?? '' );
            foreach ( explode( "\n", $raw ) as $line ) {
                $line = sanitize_email( trim( $line ) );
                if ( is_email( $line ) ) {
                    $emails[] = $line;
                }
            }
        }

        $emails = array_unique( array_filter( $emails ) );
        
        $found_emails     = [];
        $not_found_emails = [];
        
        foreach ( $emails as $email ) {
            $subscriber = mailster( 'subscribers' )->get_by_mail( $email );
            if ( ! $subscriber || is_wp_error( $subscriber ) ) {
                $not_found_emails[] = $email;
            } else {
                $found_emails[] = $email;
            }
        }

    } elseif ( isset( $_POST['msc_run_confirmed'] ) && check_admin_referer( 'msc_run_action_confirmed' ) ) {
        $step   = 3;
        $action = sanitize_text_field( $_POST['msc_action'] ?? 'unsubscribe' );
        $raw    = wp_unslash( $_POST['msc_confirmed_emails'] ?? '' );
        $emails = json_decode( $raw, true );
        
        if ( ! is_array( $emails ) ) {
            $emails = [];
        }
        
        $results = msc_process( $emails, $action );
    }
    ?>
    <div class="wrap">
        <h1>Mailster Spam Cleanup</h1>

        <?php if ( $step === 3 ) : ?>
            <?php
            $ok      = count( array_filter( $results, fn( $r ) => $r['success'] ) );
            $missing = count( array_filter( $results, fn( $r ) => ! $r['success'] ) );
            $label   = $action === 'delete' ? 'gelöscht' : 'deaktiviert';
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Fertig!</strong> <?php echo $ok; ?> Subscriber <?php echo $label; ?>.
                (<?php echo $missing; ?> Fehler / nicht gefunden).</p>
            </div>
            <h2>Protokoll</h2>
            <table class="widefat striped">
                <thead>
                    <tr><th>E-Mail</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ( $results as $r ) : ?>
                    <tr>
                        <td><?php echo esc_html( $r['email'] ); ?></td>
                        <td><?php echo $r['success']
                            ? '<span style="color:green">✓ ' . esc_html( $label ) . '</span>'
                            : '<span style="color:#999">⚠ Fehler</span>'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=newsletter&page=mailster-spam-cleanup' ) ); ?>"
               class="button">← Zurück</a>

        <?php elseif ( $step === 2 ) : ?>
            <h2>Vorschau</h2>
            
            <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:20px;">
                <div style="flex:1; min-width:300px;">
                    <h3 style="color:#135e96;">Gefunden (werden verarbeitet): <?php echo count( $found_emails ); ?></h3>
                    <textarea readonly rows="14" style="width:100%;font-family:monospace;font-size:13px;background:#f0f0f1"><?php echo esc_textarea( implode( "\n", $found_emails ) ); ?></textarea>
                </div>
                <div style="flex:1; min-width:300px;">
                    <h3 style="color:#666;">Nicht gefunden (werden ignoriert): <?php echo count( $not_found_emails ); ?></h3>
                    <textarea readonly rows="14" style="width:100%;font-family:monospace;font-size:13px;background:#f0f0f1;color:#666"><?php echo esc_textarea( implode( "\n", $not_found_emails ) ); ?></textarea>
                </div>
            </div>

            <form method="post">
                <?php wp_nonce_field( 'msc_run_action_confirmed' ); ?>
                <input type="hidden" name="msc_action" value="<?php echo esc_attr( $action ); ?>">
                <input type="hidden" name="msc_confirmed_emails" value="<?php echo esc_attr( json_encode( $found_emails ) ); ?>">

                <?php
                $label = $action === 'delete' ? 'Löschen' : 'Deaktivieren';
                $disabled = empty( $found_emails ) ? 'disabled' : '';
                ?>
                <p>Möchtest du diese <strong><?php echo count( $found_emails ); ?></strong> gefundenen Abonnenten wirklich <strong><?php echo esc_html( $label ); ?></strong>?</p>
                <input type="submit" name="msc_run_confirmed" class="button button-primary button-large"
                       value="Jetzt endgültig ausführen" <?php echo $disabled; ?>>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=newsletter&page=mailster-spam-cleanup' ) ); ?>" class="button button-large">Abbrechen</a>
            </form>

        <?php else : ?>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'msc_run_action' ); ?>

                <h2>1. E-Mail-Adressen</h2>

                <?php
                $tab = sanitize_text_field( $_GET['tab'] ?? 'textarea' );
                ?>
                <nav class="nav-tab-wrapper" style="margin-bottom:16px">
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=newsletter&page=mailster-spam-cleanup&tab=textarea' ) ); ?>"
                       class="nav-tab <?php echo $tab === 'textarea' ? 'nav-tab-active' : ''; ?>">
                        Manuell eingeben
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=newsletter&page=mailster-spam-cleanup&tab=csv' ) ); ?>"
                       class="nav-tab <?php echo $tab === 'csv' ? 'nav-tab-active' : ''; ?>">
                        Eigene CSV hochladen
                    </a>
                </nav>

                <?php if ( $tab === 'csv' ) : ?>
                    <input type="hidden" name="msc_source" value="csv">
                    <p>Wähle eine CSV-Datei aus. Die Datei wird nicht dauerhaft gespeichert, sondern nur für diesen Vorgang verarbeitet.</p>
                    <p>
                        <input type="file" name="msc_csv_file" id="msc_csv_file" accept=".csv" required>
                    </p>
                    
                    <div id="msc_csv_mapping" style="display:none; background:#f9f9f9; border:1px solid #ddd; padding:16px; max-width:600px; border-radius:4px; margin-top: 16px;">
                        <h3 style="margin-top:0;">Spaltenzuordnung</h3>
                        <p style="margin-bottom:0;">
                            <label style="display:block; font-weight:bold; margin-bottom:4px;">E-Mail Spalte *</label>
                            <select name="msc_col_email" id="msc_col_email" required></select>
                        </p>
                    </div>

                    <script>
                    document.getElementById('msc_csv_file').addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (!file) {
                            document.getElementById('msc_csv_mapping').style.display = 'none';
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const text = e.target.result;
                            const firstLine = text.split('\n')[0];
                            const delimiter = firstLine.includes(';') ? ';' : ',';
                            const headers = firstLine.split(delimiter).map(s => s.replace(/^"|"$/g, '').trim());
                            
                            let htmlEmail = '<option value="">-- Bitte wählen --</option>';
                            
                            headers.forEach((h, i) => {
                                const label = h ? `${h} (Spalte ${i+1})` : `Spalte ${i+1}`;
                                htmlEmail += `<option value="${i}">${label}</option>`;
                            });
                            
                            document.getElementById('msc_col_email').innerHTML = htmlEmail;
                            document.getElementById('msc_csv_mapping').style.display = 'block';
                        };
                        reader.readAsText(file.slice(0, 1024));
                    });
                    </script>

                <?php else : ?>
                    <input type="hidden" name="msc_source" value="textarea">
                    <p style="color:#666">Eine E-Mail-Adresse pro Zeile:</p>
                    <textarea name="msc_emails" rows="14"
                              style="width:100%;max-width:600px;font-family:monospace;font-size:13px"
                              placeholder="spam@example.com&#10;another@example.com&#10;..."></textarea>
                <?php endif; ?>

                <h2 style="margin-top:28px">2. Aktion wählen</h2>
                <fieldset style="background:#f9f9f9;border:1px solid #ddd;padding:16px;max-width:600px;border-radius:4px">
                    <label style="display:block;margin-bottom:12px;cursor:pointer">
                        <input type="radio" name="msc_action" value="unsubscribe" checked>
                        &nbsp;<strong>Deaktivieren (unsubscribe)</strong><br>
                        <span style="margin-left:20px;color:#666;font-size:13px">
                            Adresse bleibt in Mailster gespeichert, bekommt aber keine E-Mails mehr.
                        </span>
                    </label>
                    <label style="display:block;cursor:pointer">
                        <input type="radio" name="msc_action" value="delete">
                        &nbsp;<strong style="color:#c00">Komplett löschen</strong><br>
                        <span style="margin-left:20px;color:#666;font-size:13px">
                            Adresse wird dauerhaft aus Mailster entfernt. Nicht rückgängig machbar!
                        </span>
                    </label>
                </fieldset>

                <br>
                <input type="submit" name="msc_check" class="button button-primary button-large"
                       value="E-Mails prüfen (Vorschau)">
            </form>

        <?php endif; ?>
    </div>
    <?php
}

// E-Mails verarbeiten (unsubscribe oder delete)
function msc_process( array $emails, string $action ) {
    $results = [];

    foreach ( $emails as $email ) {
        $result     = [ 'email' => $email, 'success' => false ];
        $subscriber = mailster( 'subscribers' )->get_by_mail( $email );

        if ( ! $subscriber || is_wp_error( $subscriber ) ) {
            $results[] = $result;
            continue;
        }

        if ( $action === 'delete' ) {
            $update = mailster( 'subscribers' )->remove( $subscriber->ID );
        } else {
            $update = mailster( 'subscribers' )->unsubscribe( $subscriber->ID );
        }

        $result['success'] = ( $update && ! is_wp_error( $update ) );
        $results[]         = $result;
    }

    return $results;
}
