<?php
/**
 * Panel Instruktora - System Nagród
 * Fila Style Design
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

// Obsługa przyznania odznaczenia
$award_result = null;
if (isset($_POST['ssm_award_achievement'])) {
    $child_id = intval($_POST['child_id']);
    $achievement_id = intval($_POST['achievement_id']);
    $notes = sanitize_textarea_field($_POST['notes']);

    $result = ssm_award_achievement($child_id, $achievement_id, get_current_user_id(), $notes);

    if ($result) {
        $award_result = 'success';
    } else {
        $award_result = 'error';
    }
}

// Pobierz wszystkie odznaczenia
$achievements = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}ssm_achievements WHERE active = 1 ORDER BY category, type, name"
);

// Pobierz dzieci z kursów instruktora
$children = $wpdb->get_results($wpdb->prepare(
    "SELECT DISTINCT ch.id, ch.first_name, ch.last_name,
            cp.points, cp.level, cp.tier,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_child_achievements WHERE child_id = ch.id) as achievement_count
     FROM {$wpdb->prefix}ssm_children ch
     JOIN {$wpdb->prefix}ssm_enrollments e ON ch.id = e.child_id
     JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_child_points cp ON ch.id = cp.child_id
     WHERE c.instructor_id = %d AND e.status = 'active'
     ORDER BY ch.last_name, ch.first_name",
    $instructor->id
));

$tiers = ssm_get_tiers();

$categories = array(
    'skill' => array('name' => ssm_t('instr_cat_skills'), 'icon' => 'ri-swim-line'),
    'attendance' => array('name' => ssm_t('instr_cat_attendance'), 'icon' => 'ri-calendar-check-line'),
    'loyalty' => array('name' => ssm_t('instr_cat_loyalty'), 'icon' => 'ri-heart-line'),
    'social' => array('name' => ssm_t('instr_cat_social'), 'icon' => 'ri-team-line'),
    'special' => array('name' => ssm_t('instr_cat_special'), 'icon' => 'ri-star-line')
);
?>

<style>
/* Gamification Page Styles - Fila Design */
.ssm-gamification-page {
    max-width: 1200px;
}

.ssm-gamification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.ssm-gamification-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ssm-gamification-title h1 {
    font-size: 24px;
    font-weight: 700;
    color: var(--ssm-text, #1e293b);
    margin: 0;
}

.ssm-gamification-title .ssm-icon-box {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

/* Alert messages */
.ssm-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.ssm-alert-success {
    background: #d1fae5;
    border: 1px solid #86efac;
    color: #166534;
}

.ssm-alert-error {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.ssm-alert i {
    font-size: 20px;
}

/* Tab container */
.ssm-tab-container {
    background: var(--ssm-bg, white);
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--ssm-border, #e2e8f0);
    overflow: hidden;
}

/* Tabs */
.ssm-tabs {
    display: flex;
    background: var(--ssm-bg-secondary, #f8fafc);
    border-bottom: 1px solid var(--ssm-border, #e2e8f0);
    padding: 0;
    margin: 0;
    list-style: none;
}

.ssm-tab {
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

.ssm-tab:hover {
    color: var(--ssm-text, #1e293b);
    background: var(--ssm-bg, white);
}

.ssm-tab.active {
    color: var(--ssm-primary, #667eea);
    background: var(--ssm-bg, white);
    border-bottom-color: var(--ssm-primary, #667eea);
}

.ssm-tab .tab-badge {
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

.ssm-tab.active .tab-badge {
    background: var(--ssm-primary, #667eea);
    color: white;
}

/* Tab content */
.ssm-tab-content {
    display: none;
    padding: 24px;
}

.ssm-tab-content.active {
    display: block;
}

/* Children list */
.ssm-children-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}

.ssm-child-card {
    background: var(--ssm-bg-secondary, #f8fafc);
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s ease;
    border: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-child-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.ssm-child-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.ssm-child-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    font-weight: 700;
    flex-shrink: 0;
}

.ssm-child-card-info h4 {
    font-size: 15px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin: 0 0 4px 0;
}

.ssm-child-tier {
    font-size: 13px;
    font-weight: 600;
}

.ssm-child-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.ssm-child-stat {
    text-align: center;
    padding: 10px;
    background: var(--ssm-bg, white);
    border-radius: 8px;
}

.ssm-child-stat .stat-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--ssm-text, #1e293b);
}

.ssm-child-stat .stat-label {
    font-size: 11px;
    color: var(--ssm-text-secondary, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ssm-btn-award {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ssm-btn-award:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

/* Achievements grid */
.ssm-achievements-section {
    margin-bottom: 32px;
}

.ssm-achievements-section:last-child {
    margin-bottom: 0;
}

.ssm-section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-section-header .section-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--ssm-primary, #667eea) 0%, #764ba2 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.ssm-section-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin: 0;
}

.ssm-achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
}

.ssm-achievement-card {
    background: var(--ssm-bg, white);
    border-radius: 12px;
    padding: 16px;
    border: 2px solid var(--ssm-border, #e2e8f0);
    transition: all 0.2s ease;
}

.ssm-achievement-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.ssm-achievement-card.type-bronze { border-color: #CD7F32; }
.ssm-achievement-card.type-silver { border-color: #C0C0C0; }
.ssm-achievement-card.type-gold { border-color: #FFD700; }
.ssm-achievement-card.type-platinum { border-color: #E5E4E2; }

.ssm-achievement-icon {
    font-size: 36px;
    margin-bottom: 12px;
}

.ssm-achievement-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin-bottom: 4px;
}

.ssm-achievement-desc {
    font-size: 12px;
    color: var(--ssm-text-secondary, #64748b);
    margin-bottom: 12px;
    line-height: 1.4;
}

.ssm-achievement-points {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: linear-gradient(135deg, var(--ssm-primary, #667eea) 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

/* Help cards */
.ssm-help-grid {
    display: grid;
    gap: 20px;
}

.ssm-help-card {
    padding: 24px;
    border-radius: 12px;
    border-left: 4px solid;
}

.ssm-help-card.help-points {
    background: #eff6ff;
    border-color: #3b82f6;
}

.ssm-help-card.help-tiers {
    background: #fffbeb;
    border-color: #f59e0b;
}

.ssm-help-card.help-howto {
    background: #d1fae5;
    border-color: #10b981;
}

.ssm-help-card h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 16px 0;
}

.ssm-help-card.help-points h4 { color: #1e40af; }
.ssm-help-card.help-tiers h4 { color: #92400e; }
.ssm-help-card.help-howto h4 { color: #166534; }

.ssm-help-card ul, .ssm-help-card ol {
    margin: 0;
    padding-left: 20px;
}

.ssm-help-card li {
    margin-bottom: 8px;
    line-height: 1.5;
}

.ssm-help-card.help-points li { color: #1e40af; }
.ssm-help-card.help-tiers li { color: #92400e; }
.ssm-help-card.help-howto li { color: #166534; }

.ssm-tier-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.ssm-tier-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 12px;
}

/* Modal */
.ssm-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.ssm-modal-overlay.active {
    display: flex;
}

.ssm-modal {
    background: var(--ssm-bg, white);
    border-radius: 16px;
    max-width: 500px;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.ssm-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ssm-modal-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: var(--ssm-bg-secondary, #f1f5f9);
    color: var(--ssm-text-secondary, #64748b);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.2s ease;
}

.ssm-modal-close:hover {
    background: var(--ssm-bg-tertiary, #e2e8f0);
    color: var(--ssm-text, #1e293b);
}

.ssm-modal-body {
    padding: 24px;
}

.ssm-modal-child-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--ssm-bg-secondary, #f8fafc);
    border-radius: 10px;
    margin-bottom: 20px;
}

.ssm-modal-child-info .child-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
}

.ssm-modal-child-info .child-name {
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
}

.ssm-form-group {
    margin-bottom: 20px;
}

.ssm-form-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    color: var(--ssm-text, #1e293b);
    margin-bottom: 8px;
}

.ssm-form-group select,
.ssm-form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--ssm-border, #e2e8f0);
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: var(--ssm-bg, white);
    color: var(--ssm-text, #1e293b);
}

.ssm-form-group select:focus,
.ssm-form-group textarea:focus {
    border-color: var(--ssm-primary, #667eea);
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.ssm-modal-actions {
    display: flex;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-btn-cancel {
    flex: 1;
    padding: 12px;
    background: var(--ssm-bg-secondary, #f1f5f9);
    color: var(--ssm-text-secondary, #64748b);
    border: 1px solid var(--ssm-border, #e2e8f0);
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ssm-btn-cancel:hover {
    background: var(--ssm-bg-tertiary, #e2e8f0);
}

.ssm-btn-submit {
    flex: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ssm-btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

/* Empty state */
.ssm-empty-children {
    text-align: center;
    padding: 40px;
    color: var(--ssm-text-secondary, #64748b);
}

.ssm-empty-children i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.ssm-empty-children p {
    margin: 0;
}
</style>

<div class="ssm-gamification-page">

    <!-- Header -->
    <div class="ssm-gamification-header">
        <div class="ssm-gamification-title">
            <div class="ssm-icon-box">
                <i class="ri-trophy-line"></i>
            </div>
            <h1><?php echo ssm_t('instr_rewards'); ?></h1>
        </div>
    </div>

    <?php if ($award_result === 'success'): ?>
        <div class="ssm-alert ssm-alert-success">
            <i class="ri-checkbox-circle-line"></i>
            <span><?php echo ssm_t('instr_achievement_awarded'); ?></span>
        </div>
    <?php elseif ($award_result === 'error'): ?>
        <div class="ssm-alert ssm-alert-error">
            <i class="ri-error-warning-line"></i>
            <span><?php echo ssm_t('instr_achievement_error'); ?></span>
        </div>
    <?php endif; ?>

    <!-- Tab container -->
    <div class="ssm-tab-container">

        <!-- Tabs navigation -->
        <div class="ssm-tabs">
            <button class="ssm-tab active" onclick="switchTab('children', this)">
                <i class="ri-group-line"></i>
                <?php echo ssm_t('children'); ?>
                <span class="tab-badge"><?php echo count($children); ?></span>
            </button>
            <button class="ssm-tab" onclick="switchTab('achievements', this)">
                <i class="ri-medal-line"></i>
                <?php echo ssm_t('instr_achievements'); ?>
                <span class="tab-badge"><?php echo count($achievements); ?></span>
            </button>
            <button class="ssm-tab" onclick="switchTab('help', this)">
                <i class="ri-lightbulb-line"></i>
                <?php echo ssm_t('help'); ?>
            </button>
        </div>

        <!-- Tab: Children -->
        <div id="tab-children" class="ssm-tab-content active">
            <?php if ($children): ?>
                <div class="ssm-children-grid">
                    <?php foreach ($children as $child):
                        $child_tier = $tiers[$child->tier ?? 'beginner'];
                        $initials = strtoupper(substr($child->first_name, 0, 1) . substr($child->last_name, 0, 1));
                    ?>
                    <div class="ssm-child-card">
                        <div class="ssm-child-card-header">
                            <div class="ssm-child-avatar" style="background: <?php echo $child_tier['color']; ?>">
                                <?php echo $initials; ?>
                            </div>
                            <div class="ssm-child-card-info">
                                <h4><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></h4>
                                <div class="ssm-child-tier" style="color: <?php echo $child_tier['color']; ?>">
                                    <?php echo $child_tier['name']; ?>
                                </div>
                            </div>
                        </div>
                        <div class="ssm-child-stats">
                            <div class="ssm-child-stat">
                                <div class="stat-value"><?php echo $child->level ?: 1; ?></div>
                                <div class="stat-label"><?php echo ssm_t('level'); ?></div>
                            </div>
                            <div class="ssm-child-stat">
                                <div class="stat-value"><?php echo $child->points ?: 0; ?></div>
                                <div class="stat-label"><?php echo ssm_t('points'); ?></div>
                            </div>
                            <div class="ssm-child-stat">
                                <div class="stat-value"><?php echo $child->achievement_count; ?></div>
                                <div class="stat-label"><?php echo ssm_t('instr_badges'); ?></div>
                            </div>
                        </div>
                        <button type="button" class="ssm-btn-award"
                                onclick="openAwardModal(<?php echo $child->id; ?>, '<?php echo esc_js($child->first_name . ' ' . $child->last_name); ?>')">
                            <i class="ri-star-line"></i>
                            <?php echo ssm_t('instr_award'); ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ssm-empty-children">
                    <i class="ri-user-unfollow-line"></i>
                    <p><?php echo ssm_t('instr_no_children_courses'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Achievements -->
        <div id="tab-achievements" class="ssm-tab-content">
            <?php foreach ($categories as $cat_key => $cat_info):
                $cat_achievements = array_filter($achievements, function($a) use ($cat_key) {
                    return $a->category == $cat_key;
                });

                if (empty($cat_achievements)) continue;
            ?>
            <div class="ssm-achievements-section">
                <div class="ssm-section-header">
                    <div class="section-icon">
                        <i class="<?php echo $cat_info['icon']; ?>"></i>
                    </div>
                    <h3><?php echo $cat_info['name']; ?></h3>
                </div>
                <div class="ssm-achievements-grid">
                    <?php foreach ($cat_achievements as $ach): ?>
                    <div class="ssm-achievement-card type-<?php echo $ach->type; ?>">
                        <div class="ssm-achievement-icon"><?php echo $ach->icon; ?></div>
                        <div class="ssm-achievement-name"><?php echo esc_html($ach->name); ?></div>
                        <div class="ssm-achievement-desc"><?php echo esc_html($ach->description); ?></div>
                        <span class="ssm-achievement-points">
                            <i class="ri-star-line"></i>
                            +<?php echo $ach->points; ?> <?php echo ssm_t('points_short'); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tab: Help -->
        <div id="tab-help" class="ssm-tab-content">
            <div class="ssm-help-grid">

                <div class="ssm-help-card help-points">
                    <h4><i class="ri-star-line"></i> <?php echo ssm_t('instr_points_system'); ?></h4>
                    <ul>
                        <li><strong>+5 <?php echo ssm_t('points_short'); ?></strong> - <?php echo ssm_t('instr_points_attendance'); ?></li>
                        <li><strong>+10-150 <?php echo ssm_t('points_short'); ?></strong> - <?php echo ssm_t('instr_points_achievements'); ?></li>
                        <li><strong><?php echo ssm_t('level'); ?></strong> - <?php echo ssm_t('instr_level_formula'); ?></li>
                    </ul>
                </div>

                <div class="ssm-help-card help-tiers">
                    <h4><i class="ri-medal-line"></i> <?php echo ssm_t('instr_tiers_title'); ?></h4>
                    <ul>
                        <?php foreach ($tiers as $tier): ?>
                        <li class="ssm-tier-item">
                            <span class="ssm-tier-badge" style="background: <?php echo $tier['color']; ?>; color: white;">
                                <?php echo $tier['name']; ?>
                            </span>
                            <?php echo $tier['points_required']; ?>+ <?php echo ssm_t('points_short'); ?> - <?php echo $tier['description']; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="ssm-help-card help-howto">
                    <h4><i class="ri-lightbulb-line"></i> <?php echo ssm_t('instr_how_to_award'); ?></h4>
                    <ol>
                        <li><?php echo ssm_t('instr_howto_step1'); ?></li>
                        <li><?php echo ssm_t('instr_howto_step2'); ?></li>
                        <li><?php echo ssm_t('instr_howto_step3'); ?></li>
                        <li><?php echo ssm_t('instr_howto_step4'); ?></li>
                    </ol>
                    <p style="margin-top: 12px;"><strong><?php echo ssm_t('note'); ?>:</strong> <?php echo ssm_t('instr_award_once'); ?></p>
                </div>

            </div>
        </div>

    </div>

</div>

<!-- Award Modal -->
<div id="award-modal" class="ssm-modal-overlay">
    <div class="ssm-modal">
        <div class="ssm-modal-header">
            <h3><i class="ri-award-line"></i> <?php echo ssm_t('instr_award_achievement'); ?></h3>
            <button type="button" class="ssm-modal-close" onclick="closeAwardModal()">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <form id="award-form" method="post" action="">
            <input type="hidden" name="ssm_award_achievement" value="1">
            <input type="hidden" name="child_id" id="modal-child-id">

            <div class="ssm-modal-body">
                <div class="ssm-modal-child-info">
                    <div class="child-avatar" id="modal-child-avatar"></div>
                    <div class="child-name" id="modal-child-name"></div>
                </div>

                <div class="ssm-form-group">
                    <label for="achievement-select">
                        <i class="ri-medal-line"></i>
                        <?php echo ssm_t('instr_select_achievement'); ?>
                    </label>
                    <select name="achievement_id" id="achievement-select" required>
                        <option value="">-- <?php echo ssm_t('select'); ?> --</option>
                        <?php foreach ($achievements as $ach): ?>
                        <option value="<?php echo $ach->id; ?>">
                            <?php echo $ach->icon; ?> <?php echo esc_html($ach->name); ?> (+<?php echo $ach->points; ?> <?php echo ssm_t('points_short'); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ssm-form-group">
                    <label for="achievement-notes">
                        <i class="ri-edit-line"></i>
                        <?php echo ssm_t('instr_notes_optional'); ?>
                    </label>
                    <textarea name="notes" id="achievement-notes" rows="3"
                              placeholder="<?php echo ssm_t('instr_notes_placeholder'); ?>"></textarea>
                </div>
            </div>

            <div class="ssm-modal-actions">
                <button type="button" class="ssm-btn-cancel" onclick="closeAwardModal()">
                    <?php echo ssm_t('cancel'); ?>
                </button>
                <button type="submit" class="ssm-btn-submit">
                    <i class="ri-trophy-line"></i>
                    <?php echo ssm_t('instr_award_btn'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tab, btn) {
    // Hide all tabs
    document.querySelectorAll('.ssm-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ssm-tab-content').forEach(c => c.classList.remove('active'));

    // Show selected
    btn.classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

function openAwardModal(childId, childName) {
    document.getElementById('modal-child-id').value = childId;
    document.getElementById('modal-child-name').textContent = childName;
    document.getElementById('modal-child-avatar').textContent = childName.split(' ').map(n => n[0]).join('').toUpperCase();
    document.getElementById('award-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAwardModal() {
    document.getElementById('award-modal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal on backdrop click
document.getElementById('award-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAwardModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAwardModal();
    }
});
</script>
