<?php
// Panel główny - Dashboard Premium
if (!defined('ABSPATH')) exit;

// Pobierz zapisy
$enrollments = $wpdb->get_results($wpdb->prepare(
    "SELECT e.*, c.name as class_name, c.day_of_week, c.time_start,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            f.name as facility_name
     FROM {$wpdb->prefix}ssm_enrollments e
     LEFT JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
     LEFT JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     WHERE e.client_id = %d AND e.status = 'active'",
    $client->id
));

// Pobierz najbliższe zajęcia
$upcoming = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, c.name as class_name, c.time_start, c.time_end,
            f.name as facility_name,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            e.id as enrollment_id
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_enrollments e ON s.class_id = e.class_id
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
     JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     WHERE e.client_id = %d
     AND s.session_date >= CURDATE()
     AND s.status = 'scheduled'
     ORDER BY s.session_date ASC, s.time_start ASC
     LIMIT 5",
    $client->id
));

// Sprawdź nieobecności do odrobienia
$pending_makeups = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences a
     JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
     WHERE e.client_id = %d AND a.can_makeup = 1
     AND a.makeup_session_id IS NULL AND a.status = 'reported'",
    $client->id
));

// Sprawdź zaległe płatności
$pending_payments = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_payments
     WHERE client_id = %d AND status = 'pending'",
    $client->id
));

// Oblicz frekwencję
$total_sessions_attended = 0;
$total_sessions_count = 0;
foreach ($children as $child) {
    $attended = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance
         WHERE child_id = %d AND status = 'present'",
        $child->id
    ));
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance
         WHERE child_id = %d",
        $child->id
    ));
    $total_sessions_attended += intval($attended);
    $total_sessions_count += intval($total);
}
$attendance_rate = $total_sessions_count > 0 ? round(($total_sessions_attended / $total_sessions_count) * 100) : 100;

// Najbliższe zajęcia - jedno
$next_class = !empty($upcoming) ? $upcoming[0] : null;
?>

<!-- Welcome Banner -->
<div class="ssm-welcome-banner">
    <div class="ssm-welcome-content">
        <h1><?php echo ssm_t('welcome'); ?>, <?php echo esc_html($client->first_name); ?>!</h1>
        <p><?php echo ssm_t('dashboard_subtitle'); ?></p>
    </div>
    <div class="ssm-welcome-illustration">
        <i class="ri-swimming-line"></i>
    </div>
</div>

<!-- Stats Grid -->
<div class="ssm-dashboard-stats">
    <div class="ssm-stat-widget">
        <div class="ssm-stat-widget-icon blue">
            <i class="ri-user-heart-line"></i>
        </div>
        <div class="ssm-stat-widget-content">
            <span class="ssm-stat-widget-value"><?php echo count($children); ?></span>
            <span class="ssm-stat-widget-label"><?php echo ssm_t('children'); ?></span>
        </div>
    </div>

    <div class="ssm-stat-widget">
        <div class="ssm-stat-widget-icon green">
            <i class="ri-swimming-line"></i>
        </div>
        <div class="ssm-stat-widget-content">
            <span class="ssm-stat-widget-value"><?php echo count($enrollments); ?></span>
            <span class="ssm-stat-widget-label"><?php echo ssm_t('active_courses'); ?></span>
        </div>
    </div>

    <div class="ssm-stat-widget">
        <div class="ssm-stat-widget-icon orange">
            <i class="ri-calendar-check-line"></i>
        </div>
        <div class="ssm-stat-widget-content">
            <span class="ssm-stat-widget-value"><?php echo count($upcoming); ?></span>
            <span class="ssm-stat-widget-label"><?php echo ssm_t('upcoming_classes'); ?></span>
        </div>
    </div>

    <div class="ssm-stat-widget">
        <div class="ssm-stat-widget-icon <?php echo $attendance_rate >= 80 ? 'green' : ($attendance_rate >= 50 ? 'orange' : 'red'); ?>">
            <i class="ri-bar-chart-box-line"></i>
        </div>
        <div class="ssm-stat-widget-content">
            <span class="ssm-stat-widget-value"><?php echo $attendance_rate; ?>%</span>
            <span class="ssm-stat-widget-label"><?php echo ssm_t('attendance_rate'); ?></span>
        </div>
    </div>
</div>

<!-- Alerts Section -->
<?php if ($pending_makeups > 0 || $pending_payments > 0): ?>
<div class="ssm-alerts-grid">
    <?php if ($pending_makeups > 0): ?>
    <a href="<?php echo add_query_arg('panel_page', 'makeup', get_permalink()); ?>" class="ssm-alert-widget ssm-alert-warning">
        <div class="ssm-alert-icon">
            <i class="ri-calendar-todo-line"></i>
        </div>
        <div class="ssm-alert-content">
            <span class="ssm-alert-title"><?php echo $pending_makeups; ?> <?php echo ssm_t('makeups_pending'); ?></span>
            <span class="ssm-alert-action"><?php echo ssm_t('schedule_now'); ?> <i class="ri-arrow-right-line"></i></span>
        </div>
    </a>
    <?php endif; ?>

    <?php if ($pending_payments > 0): ?>
    <a href="<?php echo add_query_arg('panel_page', 'payments', get_permalink()); ?>" class="ssm-alert-widget ssm-alert-danger">
        <div class="ssm-alert-icon">
            <i class="ri-money-dollar-circle-line"></i>
        </div>
        <div class="ssm-alert-content">
            <span class="ssm-alert-title"><?php echo $pending_payments; ?> <?php echo ssm_t('payments_pending'); ?></span>
            <span class="ssm-alert-action"><?php echo ssm_t('pay_now'); ?> <i class="ri-arrow-right-line"></i></span>
        </div>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Main Dashboard Grid -->
<div class="ssm-dashboard-grid">

    <!-- Quick Actions -->
    <div class="ssm-widget ssm-widget-actions">
        <div class="ssm-widget-header">
            <h3><i class="ri-flashlight-line"></i> <?php echo ssm_t('quick_actions'); ?></h3>
        </div>
        <div class="ssm-quick-actions">
            <a href="<?php echo add_query_arg('panel_page', 'schedule', get_permalink()); ?>" class="ssm-quick-action">
                <div class="ssm-quick-action-icon blue">
                    <i class="ri-calendar-line"></i>
                </div>
                <span><?php echo ssm_t('schedule'); ?></span>
            </a>
            <a href="<?php echo add_query_arg('panel_page', 'children', get_permalink()); ?>" class="ssm-quick-action">
                <div class="ssm-quick-action-icon green">
                    <i class="ri-user-heart-line"></i>
                </div>
                <span><?php echo ssm_t('children'); ?></span>
            </a>
            <a href="<?php echo add_query_arg('panel_page', 'payments', get_permalink()); ?>" class="ssm-quick-action">
                <div class="ssm-quick-action-icon orange">
                    <i class="ri-bank-card-line"></i>
                </div>
                <span><?php echo ssm_t('payments'); ?></span>
            </a>
            <a href="<?php echo add_query_arg('panel_page', 'makeup', get_permalink()); ?>" class="ssm-quick-action">
                <div class="ssm-quick-action-icon purple">
                    <i class="ri-calendar-todo-line"></i>
                </div>
                <span><?php echo ssm_t('makeup'); ?></span>
            </a>
            <a href="<?php echo add_query_arg('panel_page', 'history', get_permalink()); ?>" class="ssm-quick-action">
                <div class="ssm-quick-action-icon cyan">
                    <i class="ri-history-line"></i>
                </div>
                <span><?php echo ssm_t('history'); ?></span>
            </a>
            <a href="<?php echo add_query_arg('panel_page', 'referrals', get_permalink()); ?>" class="ssm-quick-action">
                <div class="ssm-quick-action-icon pink">
                    <i class="ri-gift-line"></i>
                </div>
                <span><?php echo ssm_t('referrals'); ?></span>
            </a>
        </div>
    </div>

    <!-- Next Class Widget -->
    <?php if ($next_class):
        $next_date = new DateTime($next_class->session_date);
        $is_today = ($next_class->session_date == date('Y-m-d'));
        $is_tomorrow = ($next_class->session_date == date('Y-m-d', strtotime('+1 day')));
    ?>
    <div class="ssm-widget ssm-widget-next-class">
        <div class="ssm-widget-header">
            <h3><i class="ri-time-line"></i> <?php echo ssm_t('next_class'); ?></h3>
            <?php if ($is_today): ?>
                <span class="ssm-widget-badge today"><?php echo ssm_t('today'); ?></span>
            <?php elseif ($is_tomorrow): ?>
                <span class="ssm-widget-badge tomorrow"><?php echo ssm_t('tomorrow'); ?></span>
            <?php endif; ?>
        </div>
        <div class="ssm-next-class-content">
            <div class="ssm-next-class-date">
                <span class="ssm-next-class-day"><?php echo $next_date->format('d'); ?></span>
                <span class="ssm-next-class-month"><?php echo $next_date->format('M'); ?></span>
            </div>
            <div class="ssm-next-class-info">
                <h4><?php echo esc_html($next_class->class_name); ?></h4>
                <p class="ssm-next-class-child">
                    <i class="ri-user-line"></i> <?php echo esc_html($next_class->child_name); ?>
                </p>
                <p class="ssm-next-class-details">
                    <span><i class="ri-map-pin-line"></i> <?php echo esc_html($next_class->facility_name); ?></span>
                    <span><i class="ri-time-line"></i> <?php echo substr($next_class->time_start, 0, 5); ?> - <?php echo substr($next_class->time_end, 0, 5); ?></span>
                </p>
            </div>
        </div>
        <a href="<?php echo add_query_arg('panel_page', 'schedule', get_permalink()); ?>" class="ssm-widget-link">
            <?php echo ssm_t('view_full_schedule'); ?> <i class="ri-arrow-right-line"></i>
        </a>
    </div>
    <?php else: ?>
    <div class="ssm-widget ssm-widget-next-class">
        <div class="ssm-widget-header">
            <h3><i class="ri-time-line"></i> <?php echo ssm_t('next_class'); ?></h3>
        </div>
        <div class="ssm-widget-empty">
            <i class="ri-calendar-line"></i>
            <p><?php echo ssm_t('no_upcoming_classes'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Children Widget -->
    <div class="ssm-widget ssm-widget-children">
        <div class="ssm-widget-header">
            <h3><i class="ri-user-heart-line"></i> <?php echo ssm_t('your_children'); ?></h3>
            <a href="<?php echo add_query_arg('panel_page', 'children', get_permalink()); ?>" class="ssm-widget-header-link">
                <?php echo ssm_t('view_all'); ?> <i class="ri-arrow-right-s-line"></i>
            </a>
        </div>
        <?php if ($children): ?>
        <div class="ssm-children-widget-list">
            <?php foreach ($children as $child):
                $progress = ssm_get_child_progress($child->id);
                $child_courses = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments
                     WHERE child_id = %d AND status = 'active'",
                    $child->id
                ));
            ?>
            <a href="<?php echo add_query_arg(array('panel_page' => 'children', 'view_child' => $child->id), get_permalink()); ?>" class="ssm-child-widget-item">
                <div class="ssm-child-widget-avatar" style="background: <?php echo $progress['current_tier']['color']; ?>;">
                    <?php echo strtoupper(substr($child->first_name, 0, 1)); ?>
                </div>
                <div class="ssm-child-widget-info">
                    <span class="ssm-child-widget-name"><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></span>
                    <span class="ssm-child-widget-meta">
                        <?php echo $child->age; ?> <?php echo ssm_t('years'); ?> •
                        <?php echo $child_courses; ?> <?php echo ssm_t('courses'); ?> •
                        <?php echo $progress['current_tier']['name']; ?>
                    </span>
                </div>
                <div class="ssm-child-widget-arrow">
                    <i class="ri-arrow-right-s-line"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="ssm-widget-empty">
            <i class="ri-user-heart-line"></i>
            <p><?php echo ssm_t('no_children'); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Upcoming Classes List -->
    <div class="ssm-widget ssm-widget-upcoming">
        <div class="ssm-widget-header">
            <h3><i class="ri-calendar-check-line"></i> <?php echo ssm_t('upcoming_classes'); ?></h3>
            <a href="<?php echo add_query_arg('panel_page', 'schedule', get_permalink()); ?>" class="ssm-widget-header-link">
                <?php echo ssm_t('view_all'); ?> <i class="ri-arrow-right-s-line"></i>
            </a>
        </div>
        <?php if ($upcoming): ?>
        <div class="ssm-upcoming-widget-list">
            <?php foreach (array_slice($upcoming, 0, 4) as $session):
                $date = new DateTime($session->session_date);
                $is_today = ($session->session_date == date('Y-m-d'));
            ?>
            <div class="ssm-upcoming-widget-item <?php echo $is_today ? 'is-today' : ''; ?>">
                <div class="ssm-upcoming-widget-date">
                    <span class="day"><?php echo $date->format('d'); ?></span>
                    <span class="month"><?php echo $date->format('M'); ?></span>
                </div>
                <div class="ssm-upcoming-widget-info">
                    <span class="ssm-upcoming-widget-class"><?php echo esc_html($session->class_name); ?></span>
                    <span class="ssm-upcoming-widget-meta">
                        <?php echo esc_html($session->child_name); ?> •
                        <?php echo substr($session->time_start, 0, 5); ?>
                    </span>
                </div>
                <?php if ($is_today): ?>
                <span class="ssm-upcoming-widget-badge"><?php echo ssm_t('today'); ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="ssm-widget-empty">
            <i class="ri-calendar-line"></i>
            <p><?php echo ssm_t('no_upcoming_classes'); ?></p>
        </div>
        <?php endif; ?>
    </div>

</div>

<style>
/* ========================================
   DASHBOARD PREMIUM STYLES
   ======================================== */

/* Welcome Banner */
.ssm-welcome-banner {
    background: linear-gradient(135deg, var(--ssm-primary) 0%, #6366f1 100%);
    border-radius: var(--ssm-radius-lg);
    padding: 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    color: white;
    position: relative;
    overflow: hidden;
}

.ssm-welcome-content h1 {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.ssm-welcome-content p {
    font-size: 16px;
    margin: 0;
    opacity: 0.9;
}

.ssm-welcome-illustration {
    font-size: 80px;
    opacity: 0.3;
}

/* Stats Grid */
.ssm-dashboard-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.ssm-stat-widget {
    background: var(--ssm-bg-white);
    border: 1px solid var(--ssm-border);
    border-radius: var(--ssm-radius-lg);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
}

.ssm-stat-widget:hover {
    box-shadow: var(--ssm-shadow-md);
    transform: translateY(-2px);
}

.ssm-stat-widget-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
}

.ssm-stat-widget-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.ssm-stat-widget-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.ssm-stat-widget-icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.ssm-stat-widget-icon.red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
.ssm-stat-widget-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.ssm-stat-widget-icon.cyan { background: rgba(6, 182, 212, 0.1); color: #06b6d4; }
.ssm-stat-widget-icon.pink { background: rgba(236, 72, 153, 0.1); color: #ec4899; }

.ssm-stat-widget-content {
    display: flex;
    flex-direction: column;
}

.ssm-stat-widget-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--ssm-text);
    line-height: 1;
}

.ssm-stat-widget-label {
    font-size: 13px;
    color: var(--ssm-text-muted);
    margin-top: 4px;
}

/* Alerts Grid */
.ssm-alerts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.ssm-alert-widget {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    border-radius: var(--ssm-radius-lg);
    text-decoration: none;
    transition: all 0.2s ease;
}

.ssm-alert-widget:hover {
    transform: translateX(4px);
}

.ssm-alert-warning {
    background: var(--ssm-warning-light);
    border-left: 4px solid var(--ssm-warning);
}

.ssm-alert-danger {
    background: var(--ssm-danger-light);
    border-left: 4px solid var(--ssm-danger);
}

.ssm-alert-icon {
    font-size: 28px;
}

.ssm-alert-warning .ssm-alert-icon { color: var(--ssm-warning); }
.ssm-alert-danger .ssm-alert-icon { color: var(--ssm-danger); }

.ssm-alert-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ssm-alert-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-alert-action {
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.ssm-alert-warning .ssm-alert-action { color: var(--ssm-warning); }
.ssm-alert-danger .ssm-alert-action { color: var(--ssm-danger); }

/* Dashboard Grid */
.ssm-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

/* Widget Base */
.ssm-widget {
    background: var(--ssm-bg-white);
    border: 1px solid var(--ssm-border);
    border-radius: var(--ssm-radius-lg);
    overflow: hidden;
}

.ssm-widget-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--ssm-border);
}

.ssm-widget-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--ssm-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ssm-widget-header h3 i {
    color: var(--ssm-primary);
}

.ssm-widget-header-link {
    font-size: 13px;
    color: var(--ssm-primary);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
}

.ssm-widget-header-link:hover {
    text-decoration: underline;
}

.ssm-widget-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.ssm-widget-badge.today {
    background: var(--ssm-success-light);
    color: var(--ssm-success);
}

.ssm-widget-badge.tomorrow {
    background: var(--ssm-info-light);
    color: var(--ssm-info);
}

.ssm-widget-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px;
    background: var(--ssm-bg);
    color: var(--ssm-primary);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.ssm-widget-link:hover {
    background: rgba(59, 130, 246, 0.1);
}

.ssm-widget-empty {
    padding: 40px 24px;
    text-align: center;
    color: var(--ssm-text-muted);
}

.ssm-widget-empty i {
    font-size: 48px;
    opacity: 0.3;
    margin-bottom: 12px;
    display: block;
}

.ssm-widget-empty p {
    margin: 0;
    font-size: 14px;
}

/* Quick Actions Widget */
.ssm-quick-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 20px;
}

.ssm-quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px 12px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
    text-decoration: none;
    transition: all 0.2s ease;
}

.ssm-quick-action:hover {
    background: rgba(59, 130, 246, 0.08);
    transform: translateY(-2px);
}

.ssm-quick-action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.ssm-quick-action span {
    font-size: 13px;
    font-weight: 500;
    color: var(--ssm-text);
}

/* Next Class Widget */
.ssm-next-class-content {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
}

.ssm-next-class-date {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--ssm-primary) 0%, #6366f1 100%);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.ssm-next-class-day {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.ssm-next-class-month {
    font-size: 12px;
    text-transform: uppercase;
    opacity: 0.9;
}

.ssm-next-class-info h4 {
    font-size: 18px;
    font-weight: 600;
    color: var(--ssm-text);
    margin: 0 0 8px 0;
}

.ssm-next-class-child {
    font-size: 14px;
    color: var(--ssm-text-muted);
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ssm-next-class-details {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: var(--ssm-text-muted);
    margin: 0;
}

.ssm-next-class-details span {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Children Widget */
.ssm-children-widget-list {
    padding: 8px;
}

.ssm-child-widget-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: var(--ssm-radius);
    text-decoration: none;
    transition: all 0.2s ease;
}

.ssm-child-widget-item:hover {
    background: var(--ssm-bg);
}

.ssm-child-widget-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    font-weight: 600;
    flex-shrink: 0;
}

.ssm-child-widget-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ssm-child-widget-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-child-widget-meta {
    font-size: 12px;
    color: var(--ssm-text-muted);
}

.ssm-child-widget-arrow {
    color: var(--ssm-text-light);
    font-size: 20px;
}

/* Upcoming Widget */
.ssm-upcoming-widget-list {
    padding: 8px;
}

.ssm-upcoming-widget-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: var(--ssm-radius);
    transition: background 0.2s ease;
}

.ssm-upcoming-widget-item:hover {
    background: var(--ssm-bg);
}

.ssm-upcoming-widget-item.is-today {
    background: var(--ssm-success-light);
}

.ssm-upcoming-widget-date {
    width: 50px;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
}

.ssm-upcoming-widget-date .day {
    font-size: 20px;
    font-weight: 700;
    color: var(--ssm-text);
    line-height: 1;
}

.ssm-upcoming-widget-date .month {
    font-size: 11px;
    color: var(--ssm-text-muted);
    text-transform: uppercase;
}

.ssm-upcoming-widget-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ssm-upcoming-widget-class {
    font-size: 14px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-upcoming-widget-meta {
    font-size: 12px;
    color: var(--ssm-text-muted);
}

.ssm-upcoming-widget-badge {
    padding: 4px 10px;
    background: var(--ssm-success);
    color: white;
    font-size: 10px;
    font-weight: 600;
    border-radius: 20px;
    text-transform: uppercase;
}

/* Responsive */
@media (max-width: 1200px) {
    .ssm-dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .ssm-dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .ssm-dashboard-stats {
        grid-template-columns: 1fr;
    }

    .ssm-welcome-banner {
        padding: 24px;
    }

    .ssm-welcome-content h1 {
        font-size: 22px;
    }

    .ssm-welcome-illustration {
        display: none;
    }

    .ssm-quick-actions {
        grid-template-columns: repeat(2, 1fr);
    }

    .ssm-next-class-content {
        flex-direction: column;
        align-items: flex-start;
    }

    .ssm-next-class-details {
        flex-direction: column;
        gap: 6px;
    }
}
</style>
