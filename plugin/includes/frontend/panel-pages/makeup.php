<?php
/**
 * Panel Rodzica - Odrabianie zajęć (Fila Style)
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

// Sprawdź czy zalogowany
if (!is_user_logged_in()) {
    echo '<p>' . ssm_t('login_required') . '</p>';
    return;
}

$current_user = wp_get_current_user();

// Pobierz dane klienta
$client = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
    $current_user->user_email
));

if (!$client) {
    echo '<p>' . ssm_t('client_not_found') . '</p>';
    return;
}

// Obsługa zapisu na zajęcia odrabiające
if (isset($_POST['book_makeup']) && isset($_POST['absence_id']) && isset($_POST['makeup_session_id'])) {
    $absence_id = intval($_POST['absence_id']);
    $makeup_session_id = intval($_POST['makeup_session_id']);

    // Sprawdź czy nieobecność należy do klienta
    $absence = $wpdb->get_row($wpdb->prepare(
        "SELECT a.*, e.client_id
         FROM {$wpdb->prefix}ssm_absences a
         JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
         WHERE a.id = %d AND e.client_id = %d",
        $absence_id, $client->id
    ));

    if ($absence) {
        // Sprawdź czy są wolne miejsca
        $makeup_session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, c.max_participants,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments
                     WHERE class_id = c.id AND status = 'active') as enrolled_count,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences
                     WHERE session_id = s.id AND makeup_session_id IS NULL AND status = 'reported') as absences_count
             FROM {$wpdb->prefix}ssm_sessions s
             JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
             WHERE s.id = %d",
            $makeup_session_id
        ));

        if ($makeup_session) {
            $available_spots = $makeup_session->max_participants - $makeup_session->enrolled_count + $makeup_session->absences_count;

            if ($available_spots > 0) {
                $wpdb->update(
                    $wpdb->prefix . 'ssm_absences',
                    array(
                        'makeup_session_id' => $makeup_session_id,
                        'status' => 'makeup_scheduled'
                    ),
                    array('id' => $absence_id)
                );

                echo '<div class="ssm-notice ssm-notice-success"><i class="ri-check-line"></i> ' . ssm_t('makeup_booked_success') . '</div>';
            } else {
                echo '<div class="ssm-notice ssm-notice-error"><i class="ri-close-line"></i> ' . ssm_t('no_spots_available') . '</div>';
            }
        }
    }
}

// Pobierz zgłoszone nieobecności bez przypisanych zajęć odrabiających
$pending_absences = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*,
            s.session_date, s.time_start, s.time_end,
            c.name as class_name,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            c.id as class_id
     FROM {$wpdb->prefix}ssm_absences a
     JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
     JOIN {$wpdb->prefix}ssm_sessions s ON a.session_id = s.id
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     JOIN {$wpdb->prefix}ssm_children ch ON a.child_id = ch.id
     WHERE e.client_id = %d
     AND a.can_makeup = 1
     AND a.makeup_session_id IS NULL
     AND a.status = 'reported'
     ORDER BY s.session_date DESC",
    $client->id
));

// Pobierz zaplanowane odrobienia
$scheduled_makeups = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*,
            s.session_date as original_date, s.time_start as original_time,
            c.name as class_name,
            ms.session_date as makeup_date, ms.time_start as makeup_time, ms.time_end as makeup_time_end,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            f.name as facility_name
     FROM {$wpdb->prefix}ssm_absences a
     JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
     JOIN {$wpdb->prefix}ssm_sessions s ON a.session_id = s.id
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     JOIN {$wpdb->prefix}ssm_children ch ON a.child_id = ch.id
     JOIN {$wpdb->prefix}ssm_sessions ms ON a.makeup_session_id = ms.id
     JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     WHERE e.client_id = %d
     AND a.makeup_session_id IS NOT NULL
     ORDER BY ms.session_date ASC",
    $client->id
));

// Statystyki
$pending_count = count($pending_absences);
$scheduled_count = count($scheduled_makeups);
$completed_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences a
     JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
     JOIN {$wpdb->prefix}ssm_sessions ms ON a.makeup_session_id = ms.id
     WHERE e.client_id = %d AND ms.session_date < CURDATE()",
    $client->id
));
?>

<div class="ssm-page-header">
    <h1><i class="ri-refresh-line"></i> <?php echo ssm_t('makeup'); ?></h1>
    <p><?php echo ssm_t('makeup_description'); ?></p>
</div>

<!-- Statystyki -->
<div class="ssm-stats-grid ssm-stats-small">
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon orange">
            <i class="ri-error-warning-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $pending_count; ?></h3>
            <p><?php echo ssm_t('pending_makeups'); ?></p>
        </div>
    </div>
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon blue">
            <i class="ri-calendar-check-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $scheduled_count; ?></h3>
            <p><?php echo ssm_t('scheduled_makeups'); ?></p>
        </div>
    </div>
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon green">
            <i class="ri-checkbox-circle-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $completed_count; ?></h3>
            <p><?php echo ssm_t('completed_makeups'); ?></p>
        </div>
    </div>
</div>

<?php if ($pending_count > 0): ?>
<div class="ssm-alert ssm-alert-warning">
    <i class="ri-error-warning-line"></i>
    <span><?php echo sprintf(ssm_t('pending_makeups_alert'), $pending_count); ?></span>
</div>
<?php endif; ?>

<!-- Nieobecności do odrobienia -->
<?php if (!empty($pending_absences)): ?>
<div class="ssm-section">
    <div class="ssm-section-header">
        <h3 class="ssm-section-title">
            <i class="ri-calendar-close-line"></i>
            <?php echo ssm_t('absences_to_makeup'); ?>
        </h3>
    </div>

    <div class="ssm-makeup-list">
        <?php foreach ($pending_absences as $absence):
            $absence_date = new DateTime($absence->session_date);

            // Pobierz dostępne zajęcia do odrobienia
            $available_sessions = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*,
                        c.max_participants,
                        f.name as facility_name,
                        (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments
                         WHERE class_id = c.id AND status = 'active') as enrolled_count,
                        (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences
                         WHERE session_id = s.id AND makeup_session_id IS NULL AND status = 'reported') as absences_count
                 FROM {$wpdb->prefix}ssm_sessions s
                 JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
                 JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
                 WHERE s.class_id = %d
                 AND s.session_date > CURDATE()
                 AND s.status = 'scheduled'
                 AND s.id != %d
                 ORDER BY s.session_date ASC
                 LIMIT 10",
                $absence->class_id, $absence->session_id
            ));

            // Filtruj tylko te z wolnymi miejscami
            $available_sessions = array_filter($available_sessions, function($session) {
                $available_spots = $session->max_participants - $session->enrolled_count + $session->absences_count;
                return $available_spots > 0;
            });
        ?>

        <div class="ssm-makeup-card ssm-makeup-pending">
            <div class="ssm-makeup-header">
                <div class="ssm-makeup-icon">
                    <i class="ri-calendar-close-line"></i>
                </div>
                <div class="ssm-makeup-info">
                    <h4><?php echo esc_html($absence->child_name); ?></h4>
                    <span class="ssm-makeup-class"><?php echo esc_html($absence->class_name); ?></span>
                </div>
                <div class="ssm-makeup-date-badge">
                    <span class="ssm-badge ssm-badge-warning">
                        <i class="ri-time-line"></i>
                        <?php echo $absence_date->format('d.m.Y'); ?>
                    </span>
                </div>
            </div>

            <div class="ssm-makeup-original">
                <div class="ssm-original-label"><?php echo ssm_t('missed_class'); ?>:</div>
                <div class="ssm-original-details">
                    <span><i class="ri-calendar-line"></i> <?php echo $absence_date->format('d.m.Y'); ?> (<?php echo $days_pl[$absence_date->format('N')]; ?>)</span>
                    <span><i class="ri-time-line"></i> <?php echo substr($absence->time_start, 0, 5); ?> - <?php echo substr($absence->time_end, 0, 5); ?></span>
                </div>
                <?php if ($absence->reason): ?>
                <div class="ssm-original-reason">
                    <i class="ri-chat-3-line"></i> <?php echo esc_html($absence->reason); ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($available_sessions)): ?>
            <div class="ssm-makeup-options">
                <div class="ssm-options-header">
                    <i class="ri-calendar-check-line"></i>
                    <?php echo ssm_t('available_dates'); ?> (<?php echo count($available_sessions); ?>):
                </div>

                <div class="ssm-options-list">
                    <?php foreach ($available_sessions as $session):
                        $session_date = new DateTime($session->session_date);
                        $available_spots = $session->max_participants - $session->enrolled_count + $session->absences_count;
                        $is_today = ($session->session_date == date('Y-m-d'));
                    ?>

                    <form method="post" class="ssm-option-form">
                        <input type="hidden" name="book_makeup" value="1">
                        <input type="hidden" name="absence_id" value="<?php echo $absence->id; ?>">
                        <input type="hidden" name="makeup_session_id" value="<?php echo $session->id; ?>">

                        <div class="ssm-option-item">
                            <div class="ssm-option-date">
                                <span class="day"><?php echo $session_date->format('d'); ?></span>
                                <span class="month"><?php echo ssm_get_month_name_pl($session_date); ?></span>
                            </div>
                            <div class="ssm-option-info">
                                <span class="ssm-option-weekday">
                                    <?php echo $days_pl[$session_date->format('N')]; ?>
                                    <?php if ($is_today): ?>
                                        <span class="ssm-badge ssm-badge-warning"><?php echo ssm_t('today'); ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="ssm-option-details">
                                    <i class="ri-time-line"></i> <?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?>
                                    <i class="ri-map-pin-line"></i> <?php echo esc_html($session->facility_name); ?>
                                </span>
                            </div>
                            <div class="ssm-option-spots <?php echo $available_spots <= 2 ? 'low' : ''; ?>">
                                <i class="ri-user-line"></i> <?php echo $available_spots; ?>
                            </div>
                            <button type="submit" class="ssm-btn ssm-btn-primary ssm-btn-sm">
                                <i class="ri-check-line"></i> <?php echo ssm_t('book'); ?>
                            </button>
                        </div>
                    </form>

                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="ssm-makeup-no-options">
                <i class="ri-calendar-close-line"></i>
                <span><?php echo ssm_t('no_available_dates'); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="ssm-section">
    <div class="ssm-empty-state ssm-empty-success">
        <i class="ri-checkbox-circle-line"></i>
        <p><?php echo ssm_t('no_pending_makeups'); ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Zaplanowane odrobienia -->
<?php if (!empty($scheduled_makeups)): ?>
<div class="ssm-section">
    <div class="ssm-section-header">
        <h3 class="ssm-section-title">
            <i class="ri-calendar-check-line"></i>
            <?php echo ssm_t('scheduled_makeup_classes'); ?>
        </h3>
    </div>

    <div class="ssm-scheduled-list">
        <?php foreach ($scheduled_makeups as $makeup):
            $original_date = new DateTime($makeup->original_date);
            $makeup_date = new DateTime($makeup->makeup_date);
            $is_future = ($makeup->makeup_date >= date('Y-m-d'));
        ?>

        <div class="ssm-scheduled-card <?php echo $is_future ? '' : 'is-past'; ?>">
            <div class="ssm-scheduled-status">
                <?php if ($is_future): ?>
                    <span class="ssm-badge ssm-badge-success"><i class="ri-calendar-check-line"></i> <?php echo ssm_t('scheduled'); ?></span>
                <?php else: ?>
                    <span class="ssm-badge ssm-badge-muted"><i class="ri-check-double-line"></i> <?php echo ssm_t('completed'); ?></span>
                <?php endif; ?>
            </div>

            <div class="ssm-scheduled-header">
                <h4><?php echo esc_html($makeup->child_name); ?></h4>
                <span><?php echo esc_html($makeup->class_name); ?></span>
            </div>

            <div class="ssm-scheduled-dates">
                <div class="ssm-date-compare">
                    <div class="ssm-date-item ssm-date-original">
                        <span class="ssm-date-label"><?php echo ssm_t('missed'); ?>:</span>
                        <span class="ssm-date-value">
                            <i class="ri-close-circle-line"></i>
                            <?php echo $original_date->format('d.m.Y'); ?> • <?php echo substr($makeup->original_time, 0, 5); ?>
                        </span>
                    </div>
                    <div class="ssm-date-arrow">
                        <i class="ri-arrow-right-line"></i>
                    </div>
                    <div class="ssm-date-item ssm-date-makeup">
                        <span class="ssm-date-label"><?php echo ssm_t('makeup_class'); ?>:</span>
                        <span class="ssm-date-value">
                            <i class="ri-checkbox-circle-line"></i>
                            <?php echo $makeup_date->format('d.m.Y'); ?> • <?php echo substr($makeup->makeup_time, 0, 5); ?> - <?php echo substr($makeup->makeup_time_end, 0, 5); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="ssm-scheduled-location">
                <i class="ri-map-pin-line"></i> <?php echo esc_html($makeup->facility_name); ?>
            </div>
        </div>

        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<style>
/* ========================================
   MAKEUP PAGE - FILA STYLE
   ======================================== */

/* Alert */
.ssm-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: var(--ssm-radius);
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 500;
}

.ssm-alert-warning {
    background: var(--ssm-warning-light);
    color: #92400e;
    border-left: 4px solid var(--ssm-warning);
}

.ssm-alert i {
    font-size: 20px;
}

/* Makeup Cards */
.ssm-makeup-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.ssm-makeup-card {
    background: var(--ssm-bg-white);
    border-radius: var(--ssm-radius-lg);
    border: 1px solid var(--ssm-border);
    overflow: hidden;
}

.ssm-makeup-pending {
    border-left: 4px solid var(--ssm-warning);
}

.ssm-makeup-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: var(--ssm-bg);
    border-bottom: 1px solid var(--ssm-border);
}

.ssm-makeup-icon {
    width: 48px;
    height: 48px;
    background: var(--ssm-warning-light);
    color: var(--ssm-warning);
    border-radius: var(--ssm-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.ssm-makeup-info {
    flex: 1;
}

.ssm-makeup-info h4 {
    margin: 0 0 4px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-makeup-class {
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-badge-warning {
    background: var(--ssm-warning-light);
    color: #92400e;
}

/* Original session info */
.ssm-makeup-original {
    padding: 16px 20px;
    border-bottom: 1px solid var(--ssm-border);
}

.ssm-original-label {
    font-size: 12px;
    color: var(--ssm-text-muted);
    margin-bottom: 6px;
}

.ssm-original-details {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 14px;
    color: var(--ssm-text);
}

.ssm-original-details span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ssm-original-details i {
    color: var(--ssm-text-light);
}

.ssm-original-reason {
    margin-top: 8px;
    font-size: 13px;
    color: var(--ssm-text-muted);
    font-style: italic;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Available options */
.ssm-makeup-options {
    padding: 20px;
}

.ssm-options-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--ssm-text);
    margin-bottom: 16px;
}

.ssm-options-header i {
    color: var(--ssm-success);
}

.ssm-options-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ssm-option-form {
    margin: 0;
}

.ssm-option-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
    transition: all 0.2s ease;
}

.ssm-option-item:hover {
    background: rgba(59, 130, 246, 0.05);
}

.ssm-option-date {
    width: 44px;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
}

.ssm-option-date .day {
    font-size: 18px;
    font-weight: 700;
    color: var(--ssm-text);
    line-height: 1;
}

.ssm-option-date .month {
    font-size: 10px;
    color: var(--ssm-text-muted);
    text-transform: uppercase;
}

.ssm-option-info {
    flex: 1;
    min-width: 0;
}

.ssm-option-weekday {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--ssm-text);
    margin-bottom: 4px;
}

.ssm-option-details {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12px;
    color: var(--ssm-text-muted);
}

.ssm-option-details i {
    margin-right: 2px;
}

.ssm-option-spots {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 10px;
    background: var(--ssm-success-light);
    color: var(--ssm-success);
    font-size: 12px;
    font-weight: 600;
    border-radius: var(--ssm-radius);
    flex-shrink: 0;
}

.ssm-option-spots.low {
    background: var(--ssm-danger-light);
    color: var(--ssm-danger);
}

.ssm-btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* No options available */
.ssm-makeup-no-options {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 24px;
    background: var(--ssm-danger-light);
    color: var(--ssm-danger);
    font-size: 14px;
}

/* Scheduled makeups */
.ssm-scheduled-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.ssm-scheduled-card {
    background: var(--ssm-bg-white);
    border-radius: var(--ssm-radius-lg);
    border: 1px solid var(--ssm-border);
    padding: 20px;
    border-left: 4px solid var(--ssm-success);
}

.ssm-scheduled-card.is-past {
    border-left-color: var(--ssm-text-light);
    opacity: 0.7;
}

.ssm-scheduled-status {
    margin-bottom: 12px;
}

.ssm-badge-success {
    background: var(--ssm-success-light);
    color: var(--ssm-success);
}

.ssm-badge-muted {
    background: var(--ssm-border-light);
    color: var(--ssm-text-muted);
}

.ssm-scheduled-header {
    margin-bottom: 16px;
}

.ssm-scheduled-header h4 {
    margin: 0 0 4px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-scheduled-header span {
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-scheduled-dates {
    margin-bottom: 12px;
}

.ssm-date-compare {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.ssm-date-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ssm-date-label {
    font-size: 11px;
    color: var(--ssm-text-light);
    text-transform: uppercase;
}

.ssm-date-value {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    font-weight: 500;
}

.ssm-date-original .ssm-date-value {
    color: var(--ssm-danger);
}

.ssm-date-makeup .ssm-date-value {
    color: var(--ssm-success);
}

.ssm-date-arrow {
    color: var(--ssm-text-light);
    font-size: 20px;
}

.ssm-scheduled-location {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

/* Empty success state */
.ssm-empty-success {
    background: var(--ssm-success-light);
}

.ssm-empty-success i {
    color: var(--ssm-success);
}

.ssm-empty-success p {
    color: #065f46;
}

/* Responsive */
@media (max-width: 768px) {
    .ssm-makeup-header {
        flex-wrap: wrap;
    }

    .ssm-option-item {
        flex-wrap: wrap;
    }

    .ssm-option-item .ssm-btn {
        width: 100%;
        margin-top: 8px;
    }

    .ssm-date-compare {
        flex-direction: column;
        align-items: flex-start;
    }

    .ssm-date-arrow {
        transform: rotate(90deg);
        align-self: center;
    }
}
</style>
