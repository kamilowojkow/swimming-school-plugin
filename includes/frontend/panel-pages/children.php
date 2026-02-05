<?php
// Dzieci - edycja danych dzieci
if (!defined('ABSPATH')) exit;

// Obsługa zapisu
$success_message = '';
$error_message = '';

if (isset($_POST['ssm_update_child'])) {
    $child_id = intval($_POST['child_id']);
    $first_name = sanitize_text_field($_POST['child_first_name']);
    $last_name = sanitize_text_field($_POST['child_last_name']);
    $date_of_birth = sanitize_text_field($_POST['child_dob']);
    $medical_notes = sanitize_textarea_field($_POST['child_medical']);
    $skills_description = sanitize_textarea_field($_POST['child_skills']);

    // Sprawdź czy dziecko należy do tego rodzica
    $is_parent = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_client_children
         WHERE client_id = %d AND child_id = %d",
        $client->id, $child_id
    ));

    if ($is_parent) {
        $result = $wpdb->update(
            $wpdb->prefix . 'ssm_children',
            array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'date_of_birth' => $date_of_birth,
                'medical_notes' => $medical_notes,
                'skills_description' => $skills_description
            ),
            array('id' => $child_id)
        );

        if ($result !== false) {
            $success_message = ssm_t('child_updated');

            // Odśwież listę dzieci
            $children = $wpdb->get_results($wpdb->prepare(
                "SELECT ch.*, TIMESTAMPDIFF(YEAR, ch.date_of_birth, CURDATE()) as age
                 FROM {$wpdb->prefix}ssm_children ch
                 JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
                 WHERE cc.client_id = %d AND ch.active = 1
                 ORDER BY ch.first_name",
                $client->id
            ));
        } else {
            $error_message = ssm_t('save_error');
        }
    }
}

// ID dziecka do edycji lub podglądu szczegółów
$editing_child_id = isset($_GET['edit_child']) ? intval($_GET['edit_child']) : 0;
$viewing_child_id = isset($_GET['view_child']) ? intval($_GET['view_child']) : 0;
$editing_child = null;
$viewing_child = null;

if ($editing_child_id) {
    $editing_child = $wpdb->get_row($wpdb->prepare(
        "SELECT ch.* FROM {$wpdb->prefix}ssm_children ch
         JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
         WHERE cc.client_id = %d AND ch.id = %d",
        $client->id, $editing_child_id
    ));
}

if ($viewing_child_id) {
    $viewing_child = $wpdb->get_row($wpdb->prepare(
        "SELECT ch.*, TIMESTAMPDIFF(YEAR, ch.date_of_birth, CURDATE()) as age
         FROM {$wpdb->prefix}ssm_children ch
         JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
         WHERE cc.client_id = %d AND ch.id = %d",
        $client->id, $viewing_child_id
    ));
}
?>

<div class="ssm-page-header">
    <h1><?php echo ssm_t('children'); ?></h1>
    <p><?php echo ssm_t('manage_children_data'); ?></p>
</div>

<?php if ($success_message): ?>
    <div class="ssm-notice ssm-notice-success">
        <i class="ri-checkbox-circle-line"></i>
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="ssm-notice ssm-notice-error">
        <i class="ri-error-warning-line"></i>
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<?php if ($editing_child): ?>

    <!-- Formularz edycji -->
    <div class="ssm-section">
        <div class="ssm-section-header">
            <h3 class="ssm-section-title">
                <i class="ri-edit-line"></i>
                <?php echo ssm_t('edit_child_data'); ?>
            </h3>
            <a href="<?php echo remove_query_arg('edit_child'); ?>" class="ssm-btn ssm-btn-outline">
                <i class="ri-close-line"></i> <?php echo ssm_t('cancel'); ?>
            </a>
        </div>

        <form method="post" class="ssm-edit-form">
            <input type="hidden" name="child_id" value="<?php echo $editing_child->id; ?>">

            <div class="ssm-form-row">
                <div class="ssm-form-group">
                    <label for="child_first_name"><?php echo ssm_t('first_name'); ?> *</label>
                    <input type="text" id="child_first_name" name="child_first_name" required
                           value="<?php echo esc_attr($editing_child->first_name); ?>"
                           class="ssm-input">
                </div>

                <div class="ssm-form-group">
                    <label for="child_last_name"><?php echo ssm_t('last_name'); ?> *</label>
                    <input type="text" id="child_last_name" name="child_last_name" required
                           value="<?php echo esc_attr($editing_child->last_name); ?>"
                           class="ssm-input">
                </div>
            </div>

            <div class="ssm-form-group">
                <label for="child_dob"><?php echo ssm_t('date_of_birth'); ?> *</label>
                <input type="date" id="child_dob" name="child_dob" required
                       value="<?php echo esc_attr($editing_child->date_of_birth); ?>"
                       class="ssm-input">
            </div>

            <div class="ssm-form-group">
                <label for="child_medical"><?php echo ssm_t('medical_notes'); ?></label>
                <textarea id="child_medical" name="child_medical" rows="3"
                          class="ssm-textarea"
                          placeholder="<?php echo ssm_t('medical_notes_placeholder'); ?>"><?php echo esc_textarea($editing_child->medical_notes); ?></textarea>
            </div>

            <div class="ssm-form-group">
                <label for="child_skills"><?php echo ssm_t('swimming_skills'); ?></label>
                <textarea id="child_skills" name="child_skills" rows="3"
                          class="ssm-textarea"
                          placeholder="<?php echo ssm_t('skills_placeholder'); ?>"><?php echo esc_textarea($editing_child->skills_description); ?></textarea>
            </div>

            <div class="ssm-form-actions">
                <button type="submit" name="ssm_update_child" class="ssm-btn ssm-btn-primary">
                    <i class="ri-save-line"></i> <?php echo ssm_t('save_changes'); ?>
                </button>
            </div>
        </form>
    </div>

<?php elseif ($viewing_child): ?>

    <!-- Szczegółowy widok dziecka -->
    <?php
    $progress = ssm_get_child_progress($viewing_child->id);
    $child_enrollments = $wpdb->get_results($wpdb->prepare(
        "SELECT e.*, c.name as class_name, c.day_of_week, c.time_start,
                f.name as facility_name
         FROM {$wpdb->prefix}ssm_enrollments e
         LEFT JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
         LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
         WHERE e.child_id = %d AND e.status = 'active'",
        $viewing_child->id
    ));
    ?>

    <div class="ssm-child-detail-view">
        <a href="<?php echo remove_query_arg('view_child'); ?>" class="ssm-back-link">
            <i class="ri-arrow-left-line"></i> <?php echo ssm_t('back_to_list'); ?>
        </a>

        <!-- Header z danymi dziecka -->
        <div class="ssm-child-detail-header">
            <div class="ssm-child-detail-avatar" style="background: <?php echo $progress['current_tier']['color']; ?>;">
                <?php echo strtoupper(substr($viewing_child->first_name, 0, 1) . substr($viewing_child->last_name, 0, 1)); ?>
            </div>
            <div class="ssm-child-detail-info">
                <h2><?php echo esc_html($viewing_child->first_name . ' ' . $viewing_child->last_name); ?></h2>
                <div class="ssm-child-detail-meta">
                    <span><i class="ri-cake-2-line"></i> <?php echo $viewing_child->age; ?> <?php echo ssm_t('years'); ?></span>
                    <span><i class="ri-calendar-line"></i> <?php echo date('d.m.Y', strtotime($viewing_child->date_of_birth)); ?></span>
                    <span class="ssm-tier-badge" style="background: <?php echo $progress['current_tier']['color']; ?>20; color: <?php echo $progress['current_tier']['color']; ?>;">
                        <i class="ri-medal-line"></i> <?php echo $progress['current_tier']['name']; ?>
                    </span>
                </div>
            </div>
            <div class="ssm-child-detail-actions">
                <a href="<?php echo add_query_arg('edit_child', $viewing_child->id, remove_query_arg('view_child')); ?>" class="ssm-btn ssm-btn-primary">
                    <i class="ri-edit-line"></i> <?php echo ssm_t('edit'); ?>
                </a>
            </div>
        </div>

        <!-- Statystyki -->
        <div class="ssm-stats-grid">
            <div class="ssm-stat-card">
                <div class="ssm-stat-icon blue">
                    <i class="ri-star-line"></i>
                </div>
                <div class="ssm-stat-content">
                    <h3><?php echo $progress['points']->points; ?></h3>
                    <p><?php echo ssm_t('points'); ?></p>
                </div>
            </div>
            <div class="ssm-stat-card">
                <div class="ssm-stat-icon green">
                    <i class="ri-trophy-line"></i>
                </div>
                <div class="ssm-stat-content">
                    <h3><?php echo $progress['points']->level; ?></h3>
                    <p><?php echo ssm_t('level'); ?></p>
                </div>
            </div>
            <div class="ssm-stat-card">
                <div class="ssm-stat-icon orange">
                    <i class="ri-medal-2-line"></i>
                </div>
                <div class="ssm-stat-content">
                    <h3><?php echo $progress['achievement_count']; ?></h3>
                    <p><?php echo ssm_t('achievements'); ?></p>
                </div>
            </div>
            <div class="ssm-stat-card">
                <div class="ssm-stat-icon purple">
                    <i class="ri-book-open-line"></i>
                </div>
                <div class="ssm-stat-content">
                    <h3><?php echo count($child_enrollments); ?></h3>
                    <p><?php echo ssm_t('courses'); ?></p>
                </div>
            </div>
        </div>

        <!-- Postęp do następnego poziomu -->
        <?php if ($progress['next_tier']): ?>
        <div class="ssm-section">
            <div class="ssm-section-header">
                <h3 class="ssm-section-title">
                    <i class="ri-bar-chart-line"></i>
                    <?php echo ssm_t('progress_to_next_level'); ?>
                </h3>
            </div>
            <div class="ssm-progress-detail">
                <div class="ssm-progress-labels">
                    <span style="color: <?php echo $progress['current_tier']['color']; ?>;"><?php echo $progress['current_tier']['name']; ?></span>
                    <span style="color: <?php echo $progress['next_tier']['color']; ?>;"><?php echo $progress['next_tier']['name']; ?></span>
                </div>
                <div class="ssm-progress-bar-large">
                    <div class="ssm-progress-fill" style="width: <?php echo $progress['progress_percent']; ?>%; background: linear-gradient(90deg, <?php echo $progress['current_tier']['color']; ?>, <?php echo $progress['next_tier']['color']; ?>);"></div>
                </div>
                <div class="ssm-progress-info">
                    <?php echo ssm_t('points_to_next'); ?>: <strong><?php echo $progress['points_to_next']; ?></strong>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Odznaczenia -->
        <div class="ssm-section">
            <div class="ssm-section-header">
                <h3 class="ssm-section-title">
                    <i class="ri-award-line"></i>
                    <?php echo ssm_t('achievements'); ?>
                </h3>
            </div>
            <?php if ($progress['achievements']): ?>
            <div class="ssm-achievements-grid">
                <?php
                $type_colors = array(
                    'bronze' => '#CD7F32',
                    'silver' => '#9CA3AF',
                    'gold' => '#F59E0B',
                    'platinum' => '#8B5CF6'
                );
                foreach ($progress['achievements'] as $ach):
                ?>
                <div class="ssm-achievement-card" style="border-color: <?php echo $type_colors[$ach->type] ?? '#e5e7eb'; ?>;">
                    <div class="ssm-achievement-icon"><?php echo $ach->icon; ?></div>
                    <div class="ssm-achievement-info">
                        <div class="ssm-achievement-name"><?php echo esc_html($ach->name); ?></div>
                        <div class="ssm-achievement-points">+<?php echo $ach->points; ?> pkt</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="ssm-empty-state">
                <i class="ri-award-line"></i>
                <p><?php echo ssm_t('no_achievements'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Kursy -->
        <div class="ssm-section">
            <div class="ssm-section-header">
                <h3 class="ssm-section-title">
                    <i class="ri-swimming-line"></i>
                    <?php echo ssm_t('active_courses'); ?>
                </h3>
            </div>
            <?php if ($child_enrollments): ?>
            <div class="ssm-courses-list">
                <?php foreach ($child_enrollments as $enrollment): ?>
                <div class="ssm-course-item">
                    <div class="ssm-course-icon">
                        <i class="ri-water-flash-line"></i>
                    </div>
                    <div class="ssm-course-info">
                        <div class="ssm-course-name"><?php echo esc_html($enrollment->class_name); ?></div>
                        <div class="ssm-course-details">
                            <span><i class="ri-map-pin-line"></i> <?php echo esc_html($enrollment->facility_name); ?></span>
                            <span><i class="ri-time-line"></i> <?php echo $days_pl[$enrollment->day_of_week]; ?> <?php echo substr($enrollment->time_start, 0, 5); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="ssm-empty-state">
                <i class="ri-swimming-line"></i>
                <p><?php echo ssm_t('no_active_courses'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Obecność -->
        <?php
        // Pobierz obecność dziecka z bieżącego miesiąca
        $current_month = isset($_GET['attendance_month']) ? sanitize_text_field($_GET['attendance_month']) : date('Y-m');
        $month_start = $current_month . '-01';
        $month_end = date('Y-m-t', strtotime($month_start));
        $days_in_month = date('t', strtotime($month_start));
        $month_name = date_i18n('F Y', strtotime($month_start));

        // Pobierz sesje i obecność dla dziecka w danym miesiącu
        $attendance_data = $wpdb->get_results($wpdb->prepare(
            "SELECT s.session_date, a.status, c.name as class_name
             FROM {$wpdb->prefix}ssm_sessions s
             LEFT JOIN {$wpdb->prefix}ssm_attendance a ON s.id = a.session_id AND a.child_id = %d
             JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
             JOIN {$wpdb->prefix}ssm_enrollments e ON e.class_id = c.id AND e.child_id = %d AND e.status = 'active'
             WHERE s.session_date BETWEEN %s AND %s
             ORDER BY s.session_date",
            $viewing_child->id,
            $viewing_child->id,
            $month_start,
            $month_end
        ));

        // Zorganizuj dane po dniach
        $attendance_by_day = array();
        foreach ($attendance_data as $record) {
            $day = intval(date('j', strtotime($record->session_date)));
            if (!isset($attendance_by_day[$day])) {
                $attendance_by_day[$day] = array();
            }
            $attendance_by_day[$day][] = $record;
        }

        // Policz statystyki
        $total_sessions = count($attendance_data);
        $present_count = 0;
        $absent_count = 0;
        foreach ($attendance_data as $record) {
            if ($record->status === 'present') $present_count++;
            elseif ($record->status === 'absent') $absent_count++;
        }
        $attendance_rate = $total_sessions > 0 ? round(($present_count / $total_sessions) * 100) : 0;

        // Nawigacja miesięcy
        $prev_month = date('Y-m', strtotime($month_start . ' -1 month'));
        $next_month = date('Y-m', strtotime($month_start . ' +1 month'));
        $current_url = remove_query_arg('attendance_month');
        ?>
        <div class="ssm-section">
            <div class="ssm-section-header">
                <h3 class="ssm-section-title">
                    <i class="ri-calendar-check-line"></i>
                    <?php echo ssm_t('attendance'); ?>
                </h3>
                <div class="ssm-attendance-nav">
                    <a href="<?php echo add_query_arg('attendance_month', $prev_month, $current_url); ?>" class="ssm-btn ssm-btn-outline ssm-btn-sm">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    <span class="ssm-attendance-month"><?php echo $month_name; ?></span>
                    <a href="<?php echo add_query_arg('attendance_month', $next_month, $current_url); ?>" class="ssm-btn ssm-btn-outline ssm-btn-sm">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                </div>
            </div>

            <!-- Statystyki obecności -->
            <div class="ssm-attendance-stats">
                <div class="ssm-attendance-stat">
                    <span class="ssm-attendance-stat-value ssm-text-success"><?php echo $present_count; ?></span>
                    <span class="ssm-attendance-stat-label"><?php echo ssm_t('present'); ?></span>
                </div>
                <div class="ssm-attendance-stat">
                    <span class="ssm-attendance-stat-value ssm-text-danger"><?php echo $absent_count; ?></span>
                    <span class="ssm-attendance-stat-label"><?php echo ssm_t('absent'); ?></span>
                </div>
                <div class="ssm-attendance-stat">
                    <span class="ssm-attendance-stat-value"><?php echo $total_sessions; ?></span>
                    <span class="ssm-attendance-stat-label"><?php echo ssm_t('total_classes'); ?></span>
                </div>
                <div class="ssm-attendance-stat">
                    <span class="ssm-attendance-stat-value <?php echo $attendance_rate >= 80 ? 'ssm-text-success' : ($attendance_rate >= 50 ? 'ssm-text-warning' : 'ssm-text-danger'); ?>">
                        <?php echo $attendance_rate; ?>%
                    </span>
                    <span class="ssm-attendance-stat-label"><?php echo ssm_t('attendance_rate'); ?></span>
                </div>
            </div>

            <!-- Kalendarz obecności -->
            <div class="ssm-attendance-calendar">
                <div class="ssm-attendance-header">
                    <div class="ssm-attendance-label"><?php echo ssm_t('day'); ?></div>
                    <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                    <div class="ssm-attendance-day-header <?php echo date('j') == $day && date('Y-m') == $current_month ? 'ssm-today' : ''; ?>">
                        <?php echo str_pad($day, 2, '0', STR_PAD_LEFT); ?>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="ssm-attendance-row">
                    <div class="ssm-attendance-label">
                        <div class="ssm-attendance-child-info">
                            <div class="ssm-attendance-avatar" style="background: <?php echo $progress['current_tier']['color']; ?>;">
                                <?php echo strtoupper(substr($viewing_child->first_name, 0, 1)); ?>
                            </div>
                            <span><?php echo esc_html($viewing_child->first_name . ' ' . substr($viewing_child->last_name, 0, 1) . '.'); ?></span>
                        </div>
                    </div>
                    <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                    <div class="ssm-attendance-cell">
                        <?php if (isset($attendance_by_day[$day])): ?>
                            <?php
                            $day_status = 'present';
                            foreach ($attendance_by_day[$day] as $session) {
                                if ($session->status === 'absent') {
                                    $day_status = 'absent';
                                    break;
                                } elseif ($session->status !== 'present') {
                                    $day_status = 'unknown';
                                }
                            }
                            ?>
                            <?php if ($day_status === 'present'): ?>
                                <span class="ssm-attendance-present"><i class="ri-check-line"></i></span>
                            <?php elseif ($day_status === 'absent'): ?>
                                <span class="ssm-attendance-absent"><i class="ri-close-line"></i></span>
                            <?php else: ?>
                                <span class="ssm-attendance-unknown">--</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="ssm-attendance-none">--</span>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Legenda -->
            <div class="ssm-attendance-legend">
                <div class="ssm-legend-item">
                    <span class="ssm-attendance-present"><i class="ri-check-line"></i></span>
                    <span><?php echo ssm_t('present'); ?></span>
                </div>
                <div class="ssm-legend-item">
                    <span class="ssm-attendance-absent"><i class="ri-close-line"></i></span>
                    <span><?php echo ssm_t('absent'); ?></span>
                </div>
                <div class="ssm-legend-item">
                    <span class="ssm-attendance-none">--</span>
                    <span><?php echo ssm_t('no_class'); ?></span>
                </div>
            </div>
        </div>

        <!-- Dodatkowe info -->
        <?php if ($viewing_child->medical_notes || $viewing_child->skills_description): ?>
        <div class="ssm-section">
            <div class="ssm-section-header">
                <h3 class="ssm-section-title">
                    <i class="ri-file-info-line"></i>
                    <?php echo ssm_t('additional_info'); ?>
                </h3>
            </div>
            <?php if ($viewing_child->medical_notes): ?>
            <div class="ssm-info-block ssm-info-medical">
                <h4><i class="ri-heart-pulse-line"></i> <?php echo ssm_t('medical_notes'); ?></h4>
                <p><?php echo esc_html($viewing_child->medical_notes); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($viewing_child->skills_description): ?>
            <div class="ssm-info-block ssm-info-skills">
                <h4><i class="ri-swimming-line"></i> <?php echo ssm_t('swimming_skills'); ?></h4>
                <p><?php echo esc_html($viewing_child->skills_description); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>

<!-- Lista dzieci jako karty -->
<div class="ssm-children-cards">
    <?php if ($children): ?>
        <?php foreach ($children as $child):
            $progress = ssm_get_child_progress($child->id);
            $child_enrollments_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments
                 WHERE child_id = %d AND status = 'active'",
                $child->id
            ));
        ?>
            <div class="ssm-child-card">
                <!-- Badges na górze -->
                <div class="ssm-card-badges">
                    <span class="ssm-badge-tier" style="background: <?php echo $progress['current_tier']['color']; ?>;">
                        <?php echo $progress['current_tier']['name']; ?>
                    </span>
                    <?php if ($progress['achievement_count'] > 0): ?>
                    <span class="ssm-badge-achievements">
                        <i class="ri-award-fill"></i> <?php echo $progress['achievement_count']; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($child_enrollments_count > 0): ?>
                    <span class="ssm-badge-courses">
                        <i class="ri-swimming-fill"></i> <?php echo $child_enrollments_count; ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Avatar -->
                <div class="ssm-card-avatar" style="background: linear-gradient(135deg, <?php echo $progress['current_tier']['color']; ?> 0%, <?php echo $progress['current_tier']['color']; ?>99 100%);">
                    <span><?php echo strtoupper(substr($child->first_name, 0, 1) . substr($child->last_name, 0, 1)); ?></span>
                </div>

                <!-- Imię i nazwisko -->
                <h3 class="ssm-card-name"><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></h3>

                <!-- Opis / Info -->
                <p class="ssm-card-description">
                    <?php echo $child->age; ?> <?php echo ssm_t('years'); ?> •
                    <?php echo $progress['points']->points; ?> <?php echo ssm_t('points'); ?> •
                    <?php echo ssm_t('level'); ?> <?php echo $progress['points']->level; ?>
                </p>

                <!-- Pasek postępu -->
                <?php if ($progress['next_tier']): ?>
                <div class="ssm-card-progress">
                    <div class="ssm-card-progress-bar">
                        <div class="ssm-card-progress-fill" style="width: <?php echo $progress['progress_percent']; ?>%; background: <?php echo $progress['current_tier']['color']; ?>;"></div>
                    </div>
                    <div class="ssm-card-progress-text">
                        <?php echo $progress['progress_percent']; ?>% do <?php echo $progress['next_tier']['name']; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Przycisk szczegóły -->
                <a href="<?php echo add_query_arg('view_child', $child->id); ?>" class="ssm-card-btn">
                    <?php echo ssm_t('view_details'); ?>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="ssm-empty-state" style="grid-column: 1 / -1;">
            <i class="ri-user-smile-line"></i>
            <p><?php echo ssm_t('no_children'); ?></p>
            <small><?php echo ssm_t('contact_admin_to_add'); ?></small>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<style>
/* ========================================
   KARTY DZIECI - FILA STYLE
   ======================================== */

.ssm-children-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.ssm-child-card {
    background: var(--ssm-bg-white);
    border-radius: var(--ssm-radius-lg);
    border: 1px solid var(--ssm-border);
    padding: 24px;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.ssm-child-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ssm-shadow-lg);
}

/* Badges */
.ssm-card-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    margin-bottom: 20px;
}

.ssm-badge-tier {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

.ssm-badge-achievements {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: var(--ssm-warning-light);
    color: var(--ssm-warning);
    display: flex;
    align-items: center;
    gap: 4px;
}

.ssm-badge-courses {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: var(--ssm-success-light);
    color: var(--ssm-success);
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Avatar */
.ssm-card-avatar {
    width: 120px;
    height: 120px;
    border-radius: var(--ssm-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    box-shadow: var(--ssm-shadow-md);
}

.ssm-card-avatar span {
    font-size: 42px;
    font-weight: 700;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Name */
.ssm-card-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--ssm-text);
    margin: 0 0 8px 0;
}

/* Description */
.ssm-card-description {
    font-size: 14px;
    color: var(--ssm-text-muted);
    margin: 0 0 16px 0;
    line-height: 1.5;
}

/* Progress bar */
.ssm-card-progress {
    width: 100%;
    margin-bottom: 20px;
}

.ssm-card-progress-bar {
    height: 6px;
    background: var(--ssm-border-light);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 6px;
}

.ssm-card-progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.ssm-card-progress-text {
    font-size: 11px;
    color: var(--ssm-text-light);
}

/* Button */
.ssm-card-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 24px;
    background: rgba(59, 130, 246, 0.1);
    color: var(--ssm-primary);
    font-size: 14px;
    font-weight: 600;
    border-radius: var(--ssm-radius);
    text-decoration: none;
    transition: all 0.2s ease;
    margin-top: auto;
}

.ssm-card-btn:hover {
    background: var(--ssm-primary);
    color: white;
}

/* ========================================
   SZCZEGÓŁOWY WIDOK DZIECKA
   ======================================== */

.ssm-back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--ssm-text-muted);
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 20px;
    transition: color 0.2s;
}

.ssm-back-link:hover {
    color: var(--ssm-primary);
}

.ssm-child-detail-header {
    display: flex;
    align-items: center;
    gap: 24px;
    background: var(--ssm-bg-white);
    border-radius: var(--ssm-radius-lg);
    border: 1px solid var(--ssm-border);
    padding: 24px;
    margin-bottom: 24px;
}

.ssm-child-detail-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
}

.ssm-child-detail-info {
    flex: 1;
}

.ssm-child-detail-info h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    color: var(--ssm-text);
}

.ssm-child-detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
    color: var(--ssm-text-muted);
    font-size: 14px;
}

.ssm-child-detail-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ssm-tier-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

/* Progress detail */
.ssm-progress-detail {
    padding: 8px 0;
}

.ssm-progress-labels {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
}

.ssm-progress-bar-large {
    height: 12px;
    background: var(--ssm-border-light);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 8px;
}

.ssm-progress-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.3s ease;
}

.ssm-progress-info {
    font-size: 13px;
    color: var(--ssm-text-muted);
}

/* Achievements grid */
.ssm-achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.ssm-achievement-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--ssm-bg);
    border: 2px solid;
    border-radius: var(--ssm-radius);
}

.ssm-achievement-icon {
    font-size: 28px;
}

.ssm-achievement-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-achievement-points {
    font-size: 11px;
    color: var(--ssm-text-muted);
}

/* Courses list */
.ssm-courses-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ssm-course-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
}

.ssm-course-icon {
    width: 48px;
    height: 48px;
    background: var(--ssm-primary);
    border-radius: var(--ssm-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.ssm-course-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--ssm-text);
    margin-bottom: 4px;
}

.ssm-course-details {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-course-details span {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Info blocks */
.ssm-info-block {
    padding: 16px;
    border-radius: var(--ssm-radius);
    margin-bottom: 12px;
}

.ssm-info-block:last-child {
    margin-bottom: 0;
}

.ssm-info-block h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 600;
}

.ssm-info-block p {
    margin: 0;
    font-size: 14px;
    line-height: 1.6;
}

.ssm-info-medical {
    background: var(--ssm-danger-light);
    border-left: 4px solid var(--ssm-danger);
}

.ssm-info-medical h4 {
    color: var(--ssm-danger);
}

.ssm-info-skills {
    background: var(--ssm-info-light);
    border-left: 4px solid var(--ssm-info);
}

.ssm-info-skills h4 {
    color: var(--ssm-info);
}

/* Form styles */
.ssm-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.ssm-form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.ssm-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--ssm-border);
    border-radius: var(--ssm-radius);
    font-size: 14px;
    color: var(--ssm-text);
    background: var(--ssm-bg-white);
    resize: vertical;
    font-family: inherit;
}

.ssm-textarea:focus {
    outline: none;
    border-color: var(--ssm-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* ========================================
   ATTENDANCE CALENDAR
   ======================================== */

.ssm-attendance-nav {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ssm-attendance-month {
    font-size: 14px;
    font-weight: 600;
    color: var(--ssm-text);
    min-width: 120px;
    text-align: center;
}

.ssm-btn-sm {
    padding: 6px 10px !important;
    font-size: 14px !important;
}

.ssm-attendance-stats {
    display: flex;
    gap: 24px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.ssm-attendance-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.ssm-attendance-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--ssm-text);
}

.ssm-attendance-stat-label {
    font-size: 12px;
    color: var(--ssm-text-muted);
}

.ssm-text-success {
    color: var(--ssm-success) !important;
}

.ssm-text-danger {
    color: var(--ssm-danger) !important;
}

.ssm-text-warning {
    color: var(--ssm-warning) !important;
}

.ssm-attendance-calendar {
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
    overflow-x: auto;
    margin-bottom: 16px;
}

.ssm-attendance-header,
.ssm-attendance-row {
    display: flex;
    min-width: max-content;
}

.ssm-attendance-header {
    background: var(--ssm-bg-white);
    border-bottom: 1px solid var(--ssm-border);
}

.ssm-attendance-label {
    min-width: 160px;
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 500;
    color: var(--ssm-text-muted);
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.ssm-attendance-child-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ssm-attendance-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 13px;
    font-weight: 600;
}

.ssm-attendance-day-header {
    width: 36px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 500;
    color: var(--ssm-text-muted);
    flex-shrink: 0;
}

.ssm-attendance-day-header.ssm-today {
    background: var(--ssm-primary);
    color: white;
    border-radius: 4px;
}

.ssm-attendance-cell {
    width: 36px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ssm-attendance-present {
    color: var(--ssm-success);
    font-size: 18px;
}

.ssm-attendance-absent {
    color: var(--ssm-danger);
    font-size: 18px;
}

.ssm-attendance-unknown,
.ssm-attendance-none {
    color: var(--ssm-text-light);
    font-size: 12px;
    font-weight: 500;
}

.ssm-attendance-legend {
    display: flex;
    gap: 24px;
    justify-content: center;
    padding-top: 8px;
}

.ssm-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-legend-item .ssm-attendance-present,
.ssm-legend-item .ssm-attendance-absent,
.ssm-legend-item .ssm-attendance-none {
    font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
    .ssm-children-cards {
        grid-template-columns: 1fr;
    }

    .ssm-child-detail-header {
        flex-direction: column;
        text-align: center;
    }

    .ssm-child-detail-meta {
        justify-content: center;
    }

    .ssm-form-row {
        grid-template-columns: 1fr;
    }

    .ssm-achievements-grid {
        grid-template-columns: 1fr;
    }

    .ssm-attendance-stats {
        justify-content: center;
    }

    .ssm-attendance-label {
        min-width: 120px;
        padding: 8px 12px;
    }

    .ssm-section-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start !important;
    }
}
</style>
