<?php
/**
 * Panel Instruktora - Zastępstwa
 * Fila Style Design
 */
if (!defined('ABSPATH')) exit;

// Pobierz moje niedyspozycje
$my_unavailability = $wpdb->get_results($wpdb->prepare("
    SELECT u.*,
           s.session_date, s.time_start, s.time_end,
           c.name as class_name,
           f.name as facility_name,
           CONCAT(i.first_name, ' ', i.last_name) as replacement_name
    FROM {$wpdb->prefix}ssm_instructor_unavailability u
    JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
    JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
    LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
    LEFT JOIN {$wpdb->prefix}ssm_instructors i ON u.replacement_instructor_id = i.id
    WHERE u.instructor_id = %d
    ORDER BY s.session_date DESC, s.time_start DESC
", $instructor->id));

// Pobierz dostępne zastępstwa (zgłoszone przez innych)
$available_substitutions = $wpdb->get_results($wpdb->prepare("
    SELECT u.*,
           s.session_date, s.time_start, s.time_end,
           c.name as class_name,
           f.name as facility_name,
           CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
           i.id as original_instructor_id
    FROM {$wpdb->prefix}ssm_instructor_unavailability u
    JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
    JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
    LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
    JOIN {$wpdb->prefix}ssm_instructors i ON u.instructor_id = i.id
    WHERE u.instructor_id != %d
    AND u.replacement_instructor_id IS NULL
    AND u.status = 'pending'
    AND s.session_date >= CURDATE()
    ORDER BY s.session_date ASC, s.time_start ASC
", $instructor->id));

$available_count = count($available_substitutions);

$status_labels = array(
    'pending' => ssm_t('instr_status_pending'),
    'covered' => ssm_t('instr_status_covered'),
    'cancelled' => ssm_t('instr_status_cancelled')
);
?>

<style>
/* Substitutions Page Styles - Fila Design */
.ssm-substitutions-page {
    max-width: 1200px;
}

.ssm-substitutions-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.ssm-substitutions-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ssm-substitutions-title h1 {
    font-size: 24px;
    font-weight: 700;
    color: var(--ssm-text, #1e293b);
    margin: 0;
}

.ssm-substitutions-title .ssm-icon-box {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

/* Tab container */
.ssm-sub-tab-container {
    background: var(--ssm-bg, white);
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--ssm-border, #e2e8f0);
    overflow: hidden;
}

/* Tabs */
.ssm-sub-tabs {
    display: flex;
    background: var(--ssm-bg-secondary, #f8fafc);
    border-bottom: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-sub-tab {
    padding: 16px 24px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    color: var(--ssm-text-secondary, #64748b);
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 3px solid transparent;
    margin-bottom: -1px;
    transition: all 0.2s ease;
}

.ssm-sub-tab:hover {
    color: var(--ssm-text, #1e293b);
    background: var(--ssm-bg, white);
}

.ssm-sub-tab.active {
    color: #8b5cf6;
    background: var(--ssm-bg, white);
    border-bottom-color: #8b5cf6;
}

.ssm-sub-tab .tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    background: var(--ssm-bg-tertiary, #e2e8f0);
    color: var(--ssm-text-secondary, #64748b);
}

.ssm-sub-tab.active .tab-badge {
    background: #8b5cf6;
    color: white;
}

.ssm-sub-tab .tab-badge.urgent {
    background: #ef4444;
    color: white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Tab content */
.ssm-sub-tab-content {
    display: none;
    padding: 24px;
}

.ssm-sub-tab-content.active {
    display: block;
}

/* Info card */
.ssm-info-card {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    background: #eff6ff;
    border-radius: 12px;
    border: 1px solid #93c5fd;
    margin-bottom: 24px;
}

.ssm-info-card .info-icon {
    width: 44px;
    height: 44px;
    background: #3b82f6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 22px;
    flex-shrink: 0;
}

.ssm-info-card .info-content h4 {
    font-size: 15px;
    font-weight: 600;
    color: #1e40af;
    margin: 0 0 8px 0;
}

.ssm-info-card .info-content p {
    margin: 0;
    color: #1e40af;
    font-size: 14px;
    line-height: 1.6;
}

/* Alert card */
.ssm-alert-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: #fffbeb;
    border-radius: 12px;
    border: 1px solid #fde68a;
    margin-bottom: 24px;
}

.ssm-alert-card .alert-icon {
    width: 44px;
    height: 44px;
    background: #f59e0b;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 22px;
    flex-shrink: 0;
}

.ssm-alert-card .alert-content strong {
    display: block;
    color: #92400e;
    margin-bottom: 4px;
}

.ssm-alert-card .alert-content p {
    margin: 0;
    color: #92400e;
    font-size: 14px;
}

/* Unavailability cards */
.ssm-unavailability-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.ssm-unavailability-card {
    background: var(--ssm-bg-secondary, #f8fafc);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid var(--ssm-border, #e2e8f0);
    transition: all 0.2s ease;
}

.ssm-unavailability-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.ssm-unavailability-card.status-pending {
    border-left: 4px solid #f59e0b;
}

.ssm-unavailability-card.status-covered {
    border-left: 4px solid #10b981;
}

.ssm-unavailability-card.status-cancelled {
    border-left: 4px solid #ef4444;
    opacity: 0.7;
}

.ssm-unav-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
}

.ssm-unav-class {
    font-size: 16px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin: 0 0 8px 0;
}

.ssm-unav-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 14px;
    color: var(--ssm-text-secondary, #64748b);
}

.ssm-unav-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ssm-unav-meta i {
    color: #8b5cf6;
}

.ssm-unav-status {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}

.ssm-unav-status.status-pending {
    background: #fef3c7;
    color: #d97706;
}

.ssm-unav-status.status-covered {
    background: #d1fae5;
    color: #059669;
}

.ssm-unav-status.status-cancelled {
    background: #fee2e2;
    color: #dc2626;
}

.ssm-unav-reason {
    padding: 12px 16px;
    background: var(--ssm-bg, white);
    border-left: 3px solid var(--ssm-text-secondary, #94a3b8);
    border-radius: 0 8px 8px 0;
    margin-bottom: 16px;
    font-size: 14px;
    color: var(--ssm-text-secondary, #64748b);
}

.ssm-unav-reason strong {
    color: var(--ssm-text, #1e293b);
}

.ssm-unav-replacement {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #d1fae5;
    border-radius: 10px;
    margin-bottom: 16px;
}

.ssm-unav-replacement i {
    color: #059669;
    font-size: 20px;
}

.ssm-unav-replacement span {
    color: #059669;
    font-weight: 600;
}

.ssm-unav-actions {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-btn-cancel-unav {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ssm-btn-cancel-unav:hover {
    background: #dc2626;
    color: white;
    border-color: #dc2626;
}

.ssm-unav-actions small {
    color: var(--ssm-text-secondary, #64748b);
    font-size: 13px;
}

/* Available substitution cards */
.ssm-sub-card {
    background: var(--ssm-bg-secondary, #f8fafc);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid var(--ssm-border, #e2e8f0);
    border-left: 4px solid #f59e0b;
    transition: all 0.2s ease;
}

.ssm-sub-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.ssm-sub-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
}

.ssm-sub-info {
    flex: 1;
}

.ssm-sub-class {
    font-size: 16px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin: 0 0 8px 0;
}

.ssm-sub-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 14px;
    color: var(--ssm-text-secondary, #64748b);
    margin-bottom: 12px;
}

.ssm-sub-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ssm-sub-meta i {
    color: #8b5cf6;
}

.ssm-sub-instructor {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: var(--ssm-bg, white);
    border-radius: 8px;
    margin-bottom: 12px;
}

.ssm-sub-instructor .instructor-avatar {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    font-weight: 600;
}

.ssm-sub-instructor .instructor-label {
    font-size: 12px;
    color: var(--ssm-text-secondary, #64748b);
}

.ssm-sub-instructor .instructor-name {
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
}

.ssm-sub-reason {
    padding: 12px 16px;
    background: var(--ssm-bg, white);
    border-left: 3px solid var(--ssm-text-secondary, #94a3b8);
    border-radius: 0 8px 8px 0;
    font-size: 14px;
    color: var(--ssm-text-secondary, #64748b);
}

.ssm-btn-take-sub {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 24px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.ssm-btn-take-sub:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
}

.ssm-btn-take-sub:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

/* Empty state */
.ssm-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.ssm-empty-state .empty-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: #94a3b8;
}

.ssm-empty-state h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin: 0 0 8px 0;
}

.ssm-empty-state p {
    color: var(--ssm-text-secondary, #64748b);
    margin: 0;
    font-size: 14px;
}

@media (max-width: 768px) {
    .ssm-sub-header {
        flex-direction: column;
    }

    .ssm-btn-take-sub {
        width: 100%;
        justify-content: center;
    }

    .ssm-unav-header {
        flex-direction: column;
    }

    .ssm-unav-status {
        align-self: flex-start;
    }
}
</style>

<div class="ssm-substitutions-page">

    <!-- Header -->
    <div class="ssm-substitutions-header">
        <div class="ssm-substitutions-title">
            <div class="ssm-icon-box">
                <i class="ri-exchange-line"></i>
            </div>
            <h1><?php echo ssm_t('instr_substitutions'); ?></h1>
        </div>
    </div>

    <!-- Tab container -->
    <div class="ssm-sub-tab-container">

        <!-- Tabs navigation -->
        <div class="ssm-sub-tabs">
            <button class="ssm-sub-tab active" data-tab="my-unavailability">
                <i class="ri-calendar-close-line"></i>
                <?php echo ssm_t('instr_my_unavailability'); ?>
                <span class="tab-badge"><?php echo count($my_unavailability); ?></span>
            </button>
            <button class="ssm-sub-tab" data-tab="available">
                <i class="ri-hand-heart-line"></i>
                <?php echo ssm_t('instr_available_subs'); ?>
                <?php if ($available_count > 0): ?>
                    <span class="tab-badge urgent"><?php echo $available_count; ?></span>
                <?php else: ?>
                    <span class="tab-badge">0</span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Tab: My unavailability -->
        <div class="ssm-sub-tab-content active" data-tab="my-unavailability">

            <div class="ssm-info-card">
                <div class="info-icon">
                    <i class="ri-information-line"></i>
                </div>
                <div class="info-content">
                    <h4><?php echo ssm_t('instr_how_to_report'); ?></h4>
                    <p><?php echo ssm_t('instr_how_to_report_desc'); ?></p>
                </div>
            </div>

            <?php if (empty($my_unavailability)): ?>
                <div class="ssm-empty-state">
                    <div class="empty-icon">
                        <i class="ri-calendar-check-line"></i>
                    </div>
                    <h3><?php echo ssm_t('instr_no_unavailability'); ?></h3>
                    <p><?php echo ssm_t('instr_no_unavailability_desc'); ?></p>
                </div>
            <?php else: ?>
                <div class="ssm-unavailability-list">
                    <?php foreach ($my_unavailability as $unav):
                        $date = new DateTime($unav->session_date);
                        $is_future = ($unav->session_date >= date('Y-m-d'));
                        $status_label = $status_labels[$unav->status] ?? $status_labels['pending'];
                    ?>
                    <div class="ssm-unavailability-card status-<?php echo $unav->status; ?>">
                        <div class="ssm-unav-header">
                            <div>
                                <h3 class="ssm-unav-class"><?php echo esc_html($unav->class_name); ?></h3>
                                <div class="ssm-unav-meta">
                                    <span>
                                        <i class="ri-calendar-line"></i>
                                        <?php echo $date->format('d.m.Y'); ?> (<?php echo ssm_get_day_name_pl($date); ?>)
                                    </span>
                                    <span>
                                        <i class="ri-time-line"></i>
                                        <?php echo substr($unav->time_start, 0, 5); ?> - <?php echo substr($unav->time_end, 0, 5); ?>
                                    </span>
                                    <span>
                                        <i class="ri-map-pin-line"></i>
                                        <?php echo esc_html($unav->facility_name); ?>
                                    </span>
                                </div>
                            </div>
                            <span class="ssm-unav-status status-<?php echo $unav->status; ?>">
                                <?php echo $status_label; ?>
                            </span>
                        </div>

                        <?php if ($unav->reason): ?>
                            <div class="ssm-unav-reason">
                                <strong><?php echo ssm_t('instr_reason'); ?>:</strong> <?php echo esc_html($unav->reason); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($unav->status == 'covered' && $unav->replacement_name): ?>
                            <div class="ssm-unav-replacement">
                                <i class="ri-checkbox-circle-line"></i>
                                <span><?php echo ssm_t('instr_covered_by'); ?>: <?php echo esc_html($unav->replacement_name); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($unav->status == 'pending' && $is_future): ?>
                            <div class="ssm-unav-actions">
                                <button type="button" class="ssm-btn-cancel-unav"
                                        data-id="<?php echo $unav->id; ?>">
                                    <i class="ri-close-circle-line"></i>
                                    <?php echo ssm_t('instr_cancel_report'); ?>
                                </button>
                                <small><?php echo ssm_t('instr_cancel_report_note'); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Available substitutions -->
        <div class="ssm-sub-tab-content" data-tab="available">

            <?php if (empty($available_substitutions)): ?>
                <div class="ssm-empty-state">
                    <div class="empty-icon">
                        <i class="ri-hand-heart-line"></i>
                    </div>
                    <h3><?php echo ssm_t('instr_no_available_subs'); ?></h3>
                    <p><?php echo ssm_t('instr_no_available_subs_desc'); ?></p>
                </div>
            <?php else: ?>

                <div class="ssm-alert-card">
                    <div class="alert-icon">
                        <i class="ri-error-warning-line"></i>
                    </div>
                    <div class="alert-content">
                        <strong><?php echo sprintf(ssm_t('instr_available_count'), $available_count); ?></strong>
                        <p><?php echo ssm_t('instr_available_desc'); ?></p>
                    </div>
                </div>

                <div class="ssm-unavailability-list">
                    <?php foreach ($available_substitutions as $sub):
                        $date = new DateTime($sub->session_date);
                        $initials = '';
                        $name_parts = explode(' ', $sub->instructor_name);
                        foreach ($name_parts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                    ?>
                    <div class="ssm-sub-card">
                        <div class="ssm-sub-header">
                            <div class="ssm-sub-info">
                                <h3 class="ssm-sub-class"><?php echo esc_html($sub->class_name); ?></h3>
                                <div class="ssm-sub-meta">
                                    <span>
                                        <i class="ri-calendar-line"></i>
                                        <?php echo $date->format('d.m.Y'); ?> (<?php echo ssm_get_day_name_pl($date); ?>)
                                    </span>
                                    <span>
                                        <i class="ri-time-line"></i>
                                        <?php echo substr($sub->time_start, 0, 5); ?> - <?php echo substr($sub->time_end, 0, 5); ?>
                                    </span>
                                    <span>
                                        <i class="ri-map-pin-line"></i>
                                        <?php echo esc_html($sub->facility_name); ?>
                                    </span>
                                </div>
                                <div class="ssm-sub-instructor">
                                    <div class="instructor-avatar"><?php echo $initials; ?></div>
                                    <div>
                                        <div class="instructor-label"><?php echo ssm_t('instr_substitution_for'); ?></div>
                                        <div class="instructor-name"><?php echo esc_html($sub->instructor_name); ?></div>
                                    </div>
                                </div>
                                <?php if ($sub->reason): ?>
                                    <div class="ssm-sub-reason">
                                        <strong><?php echo ssm_t('instr_reason'); ?>:</strong> <?php echo esc_html($sub->reason); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="ssm-btn-take-sub"
                                    data-id="<?php echo $sub->id; ?>"
                                    data-date="<?php echo $date->format('d.m.Y'); ?>"
                                    data-time="<?php echo substr($sub->time_start, 0, 5); ?>"
                                    data-class="<?php echo esc_attr($sub->class_name); ?>">
                                <i class="ri-checkbox-circle-line"></i>
                                <?php echo ssm_t('instr_take_substitution'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

    // Tabs
    $('.ssm-sub-tab').on('click', function() {
        var tab = $(this).data('tab');

        // Update buttons
        $('.ssm-sub-tab').removeClass('active');
        $(this).addClass('active');

        // Show content
        $('.ssm-sub-tab-content').removeClass('active');
        $('.ssm-sub-tab-content[data-tab="' + tab + '"]').addClass('active');
    });

    // Cancel unavailability
    $('.ssm-btn-cancel-unav').on('click', function() {
        var btn = $(this);
        var unavailabilityId = btn.data('id');

        if (!confirm('<?php echo esc_js(ssm_t('instr_confirm_cancel')); ?>')) {
            return;
        }

        btn.prop('disabled', true).html('<i class="ri-loader-4-line"></i> <?php echo esc_js(ssm_t('instr_cancelling')); ?>');

        $.post(ajaxurl, {
            action: 'ssm_cancel_unavailability',
            unavailability_id: unavailabilityId
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data);
                btn.prop('disabled', false).html('<i class="ri-close-circle-line"></i> <?php echo esc_js(ssm_t('instr_cancel_report')); ?>');
            }
        })
        .fail(function() {
            alert('<?php echo esc_js(ssm_t('error_connection')); ?>');
            btn.prop('disabled', false).html('<i class="ri-close-circle-line"></i> <?php echo esc_js(ssm_t('instr_cancel_report')); ?>');
        });
    });

    // Take substitution
    $('.ssm-btn-take-sub').on('click', function() {
        var btn = $(this);
        var unavailabilityId = btn.data('id');
        var date = btn.data('date');
        var time = btn.data('time');
        var className = btn.data('class');

        var confirmMsg = '<?php echo esc_js(ssm_t('instr_confirm_take_sub')); ?>\n\n';
        confirmMsg += '<?php echo esc_js(ssm_t('course')); ?>: ' + className + '\n';
        confirmMsg += '<?php echo esc_js(ssm_t('date')); ?>: ' + date + '\n';
        confirmMsg += '<?php echo esc_js(ssm_t('time')); ?>: ' + time;

        if (!confirm(confirmMsg)) {
            return;
        }

        btn.prop('disabled', true).html('<i class="ri-loader-4-line"></i> <?php echo esc_js(ssm_t('instr_assigning')); ?>');

        $.post(ajaxurl, {
            action: 'ssm_take_substitution',
            unavailability_id: unavailabilityId
        })
        .done(function(response) {
            if (response.success) {
                alert('<?php echo esc_js(ssm_t('instr_sub_taken')); ?>');
                location.reload();
            } else {
                alert(response.data);
                btn.prop('disabled', false).html('<i class="ri-checkbox-circle-line"></i> <?php echo esc_js(ssm_t('instr_take_substitution')); ?>');
            }
        })
        .fail(function() {
            alert('<?php echo esc_js(ssm_t('error_connection')); ?>');
            btn.prop('disabled', false).html('<i class="ri-checkbox-circle-line"></i> <?php echo esc_js(ssm_t('instr_take_substitution')); ?>');
        });
    });
});
</script>
