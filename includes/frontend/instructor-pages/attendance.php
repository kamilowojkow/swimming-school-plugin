<?php
/**
 * Panel Instruktora - Frekwencja (Sprawdzanie obecności)
 * Fila Style Design
 */
if (!defined('ABSPATH')) exit;

// Obsługa zapisu frekwencji
$attendance_saved = false;
$points_awarded = 0;

if (isset($_POST['ssm_save_attendance']) && isset($_POST['session_id'])) {
    $session_id = intval($_POST['session_id']);
    $attendance_data = isset($_POST['attendance']) ? $_POST['attendance'] : array();

    foreach ($attendance_data as $child_id => $status) {
        $child_id = intval($child_id);
        $status = sanitize_text_field($status);

        // Sprawdź czy już istnieje
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ssm_attendance
             WHERE session_id = %d AND child_id = %d",
            $session_id, $child_id
        ));

        if ($existing) {
            $wpdb->update(
                $wpdb->prefix . 'ssm_attendance',
                array(
                    'status' => $status,
                    'marked_at' => current_time('mysql'),
                    'marked_by' => $current_user->ID
                ),
                array('id' => $existing)
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'ssm_attendance',
                array(
                    'session_id' => $session_id,
                    'child_id' => $child_id,
                    'status' => $status,
                    'marked_at' => current_time('mysql'),
                    'marked_by' => $current_user->ID
                )
            );
        }

        // Hook: Gamifikacja - automatyczne punkty za obecność
        if ($status == 'present') {
            do_action('ssm_attendance_marked', $child_id, $session_id, $status);
            $points_awarded++;
        }
    }

    $attendance_saved = true;
}

// Pobierz session_id z GET lub POST
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : (isset($_POST['session_id']) ? intval($_POST['session_id']) : 0);
?>

<style>
/* Attendance Page Styles - Fila Design */
.ssm-attendance-page {
    max-width: 1200px;
}

.ssm-attendance-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.ssm-attendance-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ssm-attendance-title h1 {
    font-size: 24px;
    font-weight: 700;
    color: var(--ssm-text, #1e293b);
    margin: 0;
}

.ssm-attendance-title .ssm-icon-box {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

/* Session info card */
.ssm-session-info-card {
    background: var(--ssm-bg, white);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-session-info-card .ssm-session-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 20px;
}

.ssm-session-info-card .ssm-session-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    flex-shrink: 0;
}

.ssm-session-info-card .ssm-session-details h2 {
    font-size: 20px;
    font-weight: 700;
    color: var(--ssm-text, #1e293b);
    margin: 0 0 8px 0;
}

.ssm-session-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    color: var(--ssm-text-secondary, #64748b);
    font-size: 14px;
}

.ssm-session-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ssm-session-meta i {
    color: var(--ssm-primary, #667eea);
}

/* Stats grid */
.ssm-attendance-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .ssm-attendance-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

.ssm-stat-card {
    background: var(--ssm-bg, white);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--ssm-border, #e2e8f0);
    text-align: center;
}

.ssm-stat-card.stat-enrolled {
    border-left: 4px solid #3b82f6;
}

.ssm-stat-card.stat-present {
    border-left: 4px solid #10b981;
}

.ssm-stat-card.stat-excused {
    border-left: 4px solid #f59e0b;
}

.ssm-stat-card.stat-absent {
    border-left: 4px solid #ef4444;
}

.ssm-stat-card .stat-value {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 4px;
}

.ssm-stat-card.stat-enrolled .stat-value { color: #3b82f6; }
.ssm-stat-card.stat-present .stat-value { color: #10b981; }
.ssm-stat-card.stat-excused .stat-value { color: #f59e0b; }
.ssm-stat-card.stat-absent .stat-value { color: #ef4444; }

.ssm-stat-card .stat-label {
    font-size: 13px;
    color: var(--ssm-text-secondary, #64748b);
    font-weight: 500;
}

/* Children list */
.ssm-children-list {
    background: var(--ssm-bg, white);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-children-list-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-children-list-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Child row */
.ssm-child-row {
    display: flex;
    align-items: center;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 12px;
    background: var(--ssm-bg-secondary, #f8fafc);
    transition: all 0.2s ease;
}

.ssm-child-row:hover {
    background: var(--ssm-bg-tertiary, #f1f5f9);
}

.ssm-child-row:last-child {
    margin-bottom: 0;
}

.ssm-child-number {
    width: 32px;
    height: 32px;
    background: var(--ssm-primary, #667eea);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    font-weight: 600;
    margin-right: 16px;
    flex-shrink: 0;
}

.ssm-child-info {
    flex: 1;
    min-width: 0;
}

.ssm-child-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin-bottom: 4px;
}

.ssm-child-parent {
    font-size: 13px;
    color: var(--ssm-text-secondary, #64748b);
    display: flex;
    align-items: center;
    gap: 4px;
}

.ssm-child-badges {
    margin: 0 16px;
}

.ssm-badge-reported {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: #fef3c7;
    color: #92400e;
    font-size: 12px;
    font-weight: 600;
    border-radius: 20px;
}

/* Attendance radio buttons */
.ssm-attendance-options {
    display: flex;
    gap: 8px;
}

.ssm-attendance-option {
    position: relative;
}

.ssm-attendance-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.ssm-attendance-option label {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.ssm-attendance-option.option-present label {
    background: #f0fdf4;
    color: #166534;
    border-color: #86efac;
}

.ssm-attendance-option.option-present input:checked + label {
    background: #10b981;
    color: white;
    border-color: #10b981;
}

.ssm-attendance-option.option-excused label {
    background: #fffbeb;
    color: #92400e;
    border-color: #fde68a;
}

.ssm-attendance-option.option-excused input:checked + label {
    background: #f59e0b;
    color: white;
    border-color: #f59e0b;
}

.ssm-attendance-option.option-absent label {
    background: #fef2f2;
    color: #991b1b;
    border-color: #fecaca;
}

.ssm-attendance-option.option-absent input:checked + label {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
}

/* Actions bar */
.ssm-attendance-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.ssm-btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: var(--ssm-bg-secondary, #f1f5f9);
    color: var(--ssm-text-secondary, #64748b);
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
    border: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-btn-back:hover {
    background: var(--ssm-bg-tertiary, #e2e8f0);
    color: var(--ssm-text, #1e293b);
}

.ssm-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 32px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.ssm-btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

/* Quick select buttons */
.ssm-quick-select {
    display: flex;
    gap: 8px;
}

.ssm-quick-btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid var(--ssm-border, #e2e8f0);
    background: var(--ssm-bg, white);
    color: var(--ssm-text-secondary, #64748b);
    transition: all 0.2s ease;
}

.ssm-quick-btn:hover {
    border-color: var(--ssm-primary, #667eea);
    color: var(--ssm-primary, #667eea);
}

/* Success message */
.ssm-success-banner {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 20px 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.ssm-success-banner .success-icon {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.ssm-success-banner .success-content h4 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.ssm-success-banner .success-content p {
    margin: 0;
    opacity: 0.9;
    font-size: 14px;
}

/* Empty state */
.ssm-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--ssm-bg, white);
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-empty-state .empty-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: white;
}

.ssm-empty-state h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin: 0 0 12px 0;
}

.ssm-empty-state p {
    color: var(--ssm-text-secondary, #64748b);
    margin: 0 0 24px 0;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.ssm-empty-state .ssm-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.ssm-empty-state .ssm-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Warning state */
.ssm-warning-state {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 24px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 12px;
    margin-bottom: 24px;
}

.ssm-warning-state .warning-icon {
    width: 48px;
    height: 48px;
    background: #f59e0b;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    flex-shrink: 0;
}

.ssm-warning-state .warning-content {
    color: #92400e;
}

.ssm-warning-state .warning-content strong {
    display: block;
    margin-bottom: 4px;
}

@media (max-width: 768px) {
    .ssm-attendance-options {
        flex-direction: column;
        width: 100%;
    }

    .ssm-attendance-option label {
        justify-content: center;
    }

    .ssm-child-row {
        flex-wrap: wrap;
    }

    .ssm-child-info {
        width: calc(100% - 48px);
        margin-bottom: 12px;
    }

    .ssm-child-badges {
        width: 100%;
        margin: 0 0 12px 48px;
    }

    .ssm-attendance-options {
        width: 100%;
        margin-left: 0;
    }
}
</style>

<div class="ssm-attendance-page">

    <?php if ($attendance_saved): ?>
        <div class="ssm-success-banner">
            <div class="success-icon">
                <i class="ri-checkbox-circle-line"></i>
            </div>
            <div class="success-content">
                <h4><?php echo ssm_t('instr_attendance_saved'); ?></h4>
                <p>
                    <i class="ri-trophy-line"></i>
                    <?php echo sprintf(ssm_t('instr_points_awarded'), $points_awarded, 5); ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($session_id):
        // Pobierz szczegóły sesji
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, c.name as class_name, c.time_start, c.time_end,
                    f.name as facility_name, c.instructor_id
             FROM {$wpdb->prefix}ssm_sessions s
             JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
             LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
             WHERE s.id = %d",
            $session_id
        ));

        // Sprawdź uprawnienia
        if (!$session || $session->instructor_id != $instructor->id):
    ?>
        <div class="ssm-warning-state">
            <div class="warning-icon">
                <i class="ri-error-warning-line"></i>
            </div>
            <div class="warning-content">
                <strong><?php echo ssm_t('error'); ?></strong>
                <?php echo ssm_t('instr_no_access'); ?>
            </div>
        </div>
    <?php
        return;
        endif;

        // Pobierz listę dzieci
        $children = $wpdb->get_results($wpdb->prepare(
            "SELECT ch.id, ch.first_name, ch.last_name,
                    CONCAT(cl.first_name, ' ', cl.last_name) as parent_name,
                    a.status as attendance_status,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences
                     WHERE session_id = %d AND child_id = ch.id) as has_absence_report
             FROM {$wpdb->prefix}ssm_enrollments e
             JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
             JOIN {$wpdb->prefix}ssm_clients cl ON e.client_id = cl.id
             LEFT JOIN {$wpdb->prefix}ssm_attendance a ON a.session_id = %d AND a.child_id = ch.id
             WHERE e.class_id = %d AND e.status = 'active'
             ORDER BY ch.last_name, ch.first_name",
            $session_id, $session_id, $session->class_id
        ));

        $date = new DateTime($session->session_date);

        // Policz statystyki
        $stats = array(
            'enrolled' => count($children),
            'present' => 0,
            'excused' => 0,
            'absent' => 0
        );
        foreach ($children as $child) {
            if ($child->attendance_status) {
                $stats[$child->attendance_status]++;
            }
        }
    ?>

    <!-- Header -->
    <div class="ssm-attendance-header">
        <div class="ssm-attendance-title">
            <div class="ssm-icon-box">
                <i class="ri-user-follow-line"></i>
            </div>
            <h1><?php echo ssm_t('attendance'); ?></h1>
        </div>
        <a href="?instructor_page=schedule" class="ssm-btn-back">
            <i class="ri-arrow-left-line"></i>
            <?php echo ssm_t('back'); ?>
        </a>
    </div>

    <!-- Session info card -->
    <div class="ssm-session-info-card">
        <div class="ssm-session-header">
            <div class="ssm-session-icon">
                <i class="ri-swim-line"></i>
            </div>
            <div class="ssm-session-details">
                <h2><?php echo esc_html($session->class_name); ?></h2>
                <div class="ssm-session-meta">
                    <span>
                        <i class="ri-calendar-line"></i>
                        <?php echo $date->format('d.m.Y'); ?> (<?php echo ssm_get_day_name_pl($date); ?>)
                    </span>
                    <span>
                        <i class="ri-time-line"></i>
                        <?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?>
                    </span>
                    <span>
                        <i class="ri-map-pin-line"></i>
                        <?php echo esc_html($session->facility_name); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($children)): ?>
        <div class="ssm-warning-state">
            <div class="warning-icon">
                <i class="ri-user-unfollow-line"></i>
            </div>
            <div class="warning-content">
                <strong><?php echo ssm_t('instr_no_children'); ?></strong>
                <?php echo ssm_t('instr_no_children_enrolled'); ?>
            </div>
        </div>
    <?php else: ?>

        <form method="post">
            <input type="hidden" name="ssm_save_attendance" value="1">
            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">

            <!-- Stats grid -->
            <div class="ssm-attendance-stats">
                <div class="ssm-stat-card stat-enrolled">
                    <div class="stat-value"><?php echo $stats['enrolled']; ?></div>
                    <div class="stat-label"><?php echo ssm_t('instr_enrolled'); ?></div>
                </div>
                <div class="ssm-stat-card stat-present">
                    <div class="stat-value present-count"><?php echo $stats['present']; ?></div>
                    <div class="stat-label"><?php echo ssm_t('instr_present'); ?></div>
                </div>
                <div class="ssm-stat-card stat-excused">
                    <div class="stat-value excused-count"><?php echo $stats['excused']; ?></div>
                    <div class="stat-label"><?php echo ssm_t('instr_excused'); ?></div>
                </div>
                <div class="ssm-stat-card stat-absent">
                    <div class="stat-value absent-count"><?php echo $stats['absent']; ?></div>
                    <div class="stat-label"><?php echo ssm_t('instr_absent'); ?></div>
                </div>
            </div>

            <!-- Children list -->
            <div class="ssm-children-list">
                <div class="ssm-children-list-header">
                    <h3>
                        <i class="ri-group-line"></i>
                        <?php echo ssm_t('instr_participants'); ?>
                    </h3>
                    <div class="ssm-quick-select">
                        <button type="button" class="ssm-quick-btn" onclick="selectAll('present')">
                            <i class="ri-checkbox-circle-line"></i> <?php echo ssm_t('instr_all_present'); ?>
                        </button>
                    </div>
                </div>

                <?php
                $counter = 1;
                foreach ($children as $child):
                    $default_status = 'present';
                    if ($child->has_absence_report > 0) {
                        $default_status = 'excused';
                    } elseif ($child->attendance_status) {
                        $default_status = $child->attendance_status;
                    }
                ?>
                <div class="ssm-child-row">
                    <div class="ssm-child-number"><?php echo $counter++; ?></div>
                    <div class="ssm-child-info">
                        <div class="ssm-child-name"><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></div>
                        <div class="ssm-child-parent">
                            <i class="ri-parent-line"></i>
                            <?php echo esc_html($child->parent_name); ?>
                        </div>
                    </div>
                    <div class="ssm-child-badges">
                        <?php if ($child->has_absence_report > 0): ?>
                            <span class="ssm-badge-reported">
                                <i class="ri-information-line"></i>
                                <?php echo ssm_t('instr_absence_reported'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="ssm-attendance-options">
                        <div class="ssm-attendance-option option-present">
                            <input type="radio"
                                   name="attendance[<?php echo $child->id; ?>]"
                                   value="present"
                                   id="present_<?php echo $child->id; ?>"
                                   <?php checked($default_status, 'present'); ?>>
                            <label for="present_<?php echo $child->id; ?>">
                                <i class="ri-checkbox-circle-line"></i>
                                <?php echo ssm_t('instr_status_present'); ?>
                            </label>
                        </div>
                        <div class="ssm-attendance-option option-excused">
                            <input type="radio"
                                   name="attendance[<?php echo $child->id; ?>]"
                                   value="excused"
                                   id="excused_<?php echo $child->id; ?>"
                                   <?php checked($default_status, 'excused'); ?>>
                            <label for="excused_<?php echo $child->id; ?>">
                                <i class="ri-error-warning-line"></i>
                                <?php echo ssm_t('instr_status_excused'); ?>
                            </label>
                        </div>
                        <div class="ssm-attendance-option option-absent">
                            <input type="radio"
                                   name="attendance[<?php echo $child->id; ?>]"
                                   value="absent"
                                   id="absent_<?php echo $child->id; ?>"
                                   <?php checked($default_status, 'absent'); ?>>
                            <label for="absent_<?php echo $child->id; ?>">
                                <i class="ri-close-circle-line"></i>
                                <?php echo ssm_t('instr_status_absent'); ?>
                            </label>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Actions -->
            <div class="ssm-attendance-actions">
                <a href="?instructor_page=schedule" class="ssm-btn-back">
                    <i class="ri-arrow-left-line"></i>
                    <?php echo ssm_t('instr_back_to_schedule'); ?>
                </a>
                <button type="submit" class="ssm-btn-save">
                    <i class="ri-save-line"></i>
                    <?php echo ssm_t('instr_save_attendance'); ?>
                </button>
            </div>
        </form>

        <script>
        jQuery(document).ready(function($) {
            function updateCounts() {
                var presentCount = $('input[value="present"]:checked').length;
                var excusedCount = $('input[value="excused"]:checked').length;
                var absentCount = $('input[value="absent"]:checked').length;

                $('.present-count').text(presentCount);
                $('.excused-count').text(excusedCount);
                $('.absent-count').text(absentCount);
            }

            $('input[type="radio"]').on('change', updateCounts);
            updateCounts();
        });

        function selectAll(status) {
            jQuery('input[value="' + status + '"]').prop('checked', true).trigger('change');
        }
        </script>

    <?php endif; ?>

    <?php else: ?>

    <!-- No session selected - show empty state -->
    <div class="ssm-attendance-header">
        <div class="ssm-attendance-title">
            <div class="ssm-icon-box">
                <i class="ri-user-follow-line"></i>
            </div>
            <h1><?php echo ssm_t('attendance'); ?></h1>
        </div>
    </div>

    <div class="ssm-empty-state">
        <div class="empty-icon">
            <i class="ri-calendar-check-line"></i>
        </div>
        <h3><?php echo ssm_t('instr_select_session'); ?></h3>
        <p><?php echo ssm_t('instr_select_session_desc'); ?></p>
        <a href="?instructor_page=schedule" class="ssm-btn-primary">
            <i class="ri-calendar-line"></i>
            <?php echo ssm_t('instr_go_to_schedule'); ?>
        </a>
    </div>

    <?php endif; ?>

</div>
