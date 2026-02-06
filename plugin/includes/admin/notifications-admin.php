<?php
/**
 * Panel Administracyjny - Powiadomienia
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Obsługa wysyłania powiadomienia
$message = '';
if (isset($_POST['ssm_send_notification']) && wp_verify_nonce($_POST['ssm_notification_nonce'], 'ssm_send_notification')) {
    $recipient_type = sanitize_text_field($_POST['recipient_type']);
    $recipient_ids = isset($_POST['recipients']) ? array_map('intval', $_POST['recipients']) : array();
    $title = sanitize_text_field($_POST['notification_title']);
    $notification_message = sanitize_textarea_field($_POST['notification_message']);
    $send_to_all = isset($_POST['send_to_all']) && $_POST['send_to_all'] == '1';

    if ($send_to_all) {
        if ($recipient_type === 'parent') {
            $all = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}ssm_clients");
        } else {
            $all = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}ssm_instructors WHERE active = 1");
        }
        $recipient_ids = $all;
    }

    if (!empty($recipient_ids) && !empty($title) && !empty($notification_message)) {
        $notification_system = ssm_notification_system();
        $sent_count = $notification_system->send_manual_notification(
            $recipient_type,
            $recipient_ids,
            $title,
            $notification_message
        );
        $message = '<div class="notice notice-success"><p>' . sprintf(__('Wysłano %d powiadomień.', 'swimming-school'), $sent_count) . '</p></div>';
    } else {
        $message = '<div class="notice notice-error"><p>' . __('Wypełnij wszystkie wymagane pola.', 'swimming-school') . '</p></div>';
    }
}

// Pobierz listę rodziców i instruktorów
$parents = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as name, email FROM {$wpdb->prefix}ssm_clients ORDER BY first_name, last_name");
$instructors = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as name, email FROM {$wpdb->prefix}ssm_instructors WHERE active = 1 ORDER BY first_name, last_name");

// Pobierz ostatnie powiadomienia
$recent_notifications = $wpdb->get_results("
    SELECT n.*,
           CASE
               WHEN n.recipient_type = 'parent' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM {$wpdb->prefix}ssm_clients WHERE id = n.recipient_id)
               ELSE (SELECT CONCAT(first_name, ' ', last_name) FROM {$wpdb->prefix}ssm_instructors WHERE id = n.recipient_id)
           END as recipient_name
    FROM {$wpdb->prefix}ssm_notifications n
    WHERE n.type = 'manual'
    ORDER BY n.created_at DESC
    LIMIT 20
");
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-bell" style="font-size: 30px; margin-right: 10px;"></span> Powiadomienia</h1>

    <?php echo $message; ?>

    <div class="ssm-admin-grid">
        <!-- Formularz wysyłania -->
        <div class="ssm-admin-card">
            <h2><span class="dashicons dashicons-email-alt"></span> Wyślij powiadomienie</h2>

            <form method="post" id="ssmNotificationForm">
                <?php wp_nonce_field('ssm_send_notification', 'ssm_notification_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="recipient_type">Typ odbiorcy</label></th>
                        <td>
                            <select name="recipient_type" id="recipient_type" class="regular-text" required>
                                <option value="parent">Rodzice</option>
                                <option value="instructor">Instruktorzy</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Wyślij do wszystkich</th>
                        <td>
                            <label>
                                <input type="checkbox" name="send_to_all" value="1" id="send_to_all">
                                Zaznacz, aby wysłać do wszystkich odbiorców wybranego typu
                            </label>
                        </td>
                    </tr>
                    <tr id="recipients_row">
                        <th scope="row"><label for="recipients">Wybierz odbiorców</label></th>
                        <td>
                            <select name="recipients[]" id="recipients" class="regular-text" multiple size="8" style="min-width: 400px;">
                                <!-- Dynamically populated -->
                            </select>
                            <p class="description">Przytrzymaj Ctrl (Cmd na Mac) aby wybrać wielu odbiorców</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="notification_title">Tytuł</label></th>
                        <td>
                            <input type="text" name="notification_title" id="notification_title" class="regular-text" required style="width: 100%; max-width: 400px;">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="notification_message">Treść</label></th>
                        <td>
                            <textarea name="notification_message" id="notification_message" rows="5" class="large-text" required style="max-width: 400px;"></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="ssm_send_notification" class="button button-primary button-large">
                        <span class="dashicons dashicons-email" style="margin-top: 4px;"></span>
                        Wyślij powiadomienie
                    </button>
                </p>
            </form>
        </div>

        <!-- Historia powiadomień -->
        <div class="ssm-admin-card">
            <h2><span class="dashicons dashicons-list-view"></span> Ostatnie powiadomienia ręczne</h2>

            <?php if (empty($recent_notifications)): ?>
                <p class="description">Brak wysłanych powiadomień ręcznych.</p>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Odbiorca</th>
                            <th>Tytuł</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_notifications as $notif): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', strtotime($notif->created_at)); ?></td>
                                <td>
                                    <span class="ssm-badge ssm-badge-<?php echo $notif->recipient_type === 'parent' ? 'info' : 'warning'; ?>">
                                        <?php echo $notif->recipient_type === 'parent' ? 'Rodzic' : 'Instruktor'; ?>
                                    </span>
                                    <?php echo esc_html($notif->recipient_name); ?>
                                </td>
                                <td><?php echo esc_html($notif->title); ?></td>
                                <td>
                                    <?php if ($notif->is_read): ?>
                                        <span class="ssm-badge ssm-badge-success">Przeczytane</span>
                                    <?php else: ?>
                                        <span class="ssm-badge ssm-badge-secondary">Nieprzeczytane</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.ssm-admin-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

@media (max-width: 1200px) {
    .ssm-admin-grid {
        grid-template-columns: 1fr;
    }
}

.ssm-admin-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.ssm-admin-card h2 {
    margin-top: 0;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ssm-admin-card h2 .dashicons {
    color: #2271b1;
}

.ssm-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.ssm-badge-info {
    background: #e5f3ff;
    color: #0073aa;
}

.ssm-badge-warning {
    background: #fff3cd;
    color: #856404;
}

.ssm-badge-success {
    background: #d4edda;
    color: #155724;
}

.ssm-badge-secondary {
    background: #e9ecef;
    color: #6c757d;
}
</style>

<script>
jQuery(document).ready(function($) {
    var parents = <?php echo json_encode($parents); ?>;
    var instructors = <?php echo json_encode($instructors); ?>;

    function updateRecipients() {
        var type = $('#recipient_type').val();
        var recipients = type === 'parent' ? parents : instructors;
        var $select = $('#recipients');

        $select.empty();
        recipients.forEach(function(r) {
            $select.append($('<option>', {
                value: r.id,
                text: r.name + ' (' + r.email + ')'
            }));
        });
    }

    $('#recipient_type').on('change', updateRecipients);
    updateRecipients();

    // Toggle recipients visibility based on "send to all"
    $('#send_to_all').on('change', function() {
        if ($(this).is(':checked')) {
            $('#recipients_row').hide();
        } else {
            $('#recipients_row').show();
        }
    });
});
</script>
