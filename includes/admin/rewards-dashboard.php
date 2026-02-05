<?php
/**
 * Panel Admin - System Nagród - Dashboard
 */
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('Brak uprawnień.');
}

global $wpdb;

// Statystyki
$total_achievements = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_achievements WHERE active = 1");
$total_awarded = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_child_achievements");
$total_points_given = $wpdb->get_var("SELECT SUM(points) FROM {$wpdb->prefix}ssm_points_history WHERE points > 0");
$children_with_achievements = $wpdb->get_var("SELECT COUNT(DISTINCT child_id) FROM {$wpdb->prefix}ssm_child_achievements");
$active_children = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_child_points");

// Ranking dzieci
$top_children = $wpdb->get_results("
    SELECT ch.first_name, ch.last_name, cp.points, cp.level, cp.tier,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_child_achievements WHERE child_id = ch.id) as achievement_count
    FROM {$wpdb->prefix}ssm_children ch
    JOIN {$wpdb->prefix}ssm_child_points cp ON ch.id = cp.child_id
    ORDER BY cp.points DESC
    LIMIT 10
");

// Najpopularniejsze odznaczenia
$popular_achievements = $wpdb->get_results("
    SELECT a.name, a.icon, a.points, a.type,
           COUNT(ca.id) as times_awarded
    FROM {$wpdb->prefix}ssm_achievements a
    JOIN {$wpdb->prefix}ssm_child_achievements ca ON a.id = ca.achievement_id
    GROUP BY a.id
    ORDER BY times_awarded DESC
    LIMIT 5
");

// Ostatnie aktywności
$recent_activities = $wpdb->get_results("
    SELECT ph.*, ch.first_name, ch.last_name, a.name as achievement_name, a.icon
    FROM {$wpdb->prefix}ssm_points_history ph
    JOIN {$wpdb->prefix}ssm_children ch ON ph.child_id = ch.id
    LEFT JOIN {$wpdb->prefix}ssm_achievements a ON ph.achievement_id = a.id
    ORDER BY ph.created_at DESC
    LIMIT 10
");

// Statystyki tierów
$tiers = ssm_get_tiers();
$tier_stats = array();
foreach (array_keys($tiers) as $tier_key) {
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_child_points WHERE tier = %s",
        $tier_key
    ));
    $tier_stats[$tier_key] = $count;
}

$type_colors = array(
    'bronze' => '#CD7F32',
    'silver' => '#C0C0C0',
    'gold' => '#FFD700',
    'platinum' => '#E5E4E2'
);

?>

<div class="wrap">
    <h1>🏆 System Nagród - Dashboard</h1>
    <p class="description">Przegląd statystyk i zarządzanie systemem motywacji</p>
    
    <!-- Szybkie akcje -->
    <div style="margin: 20px 0;">
        <a href="?page=swimming-school-achievements" class="button button-primary button-large">🎖️ Zarządzaj odznaczeniami</a>
        <a href="?page=swimming-school-tiers" class="button button-large">🏆 Zarządzaj tierami</a>
    </div>
    
    <!-- Główne statystyki -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6;">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;">Aktywne odznaczenia</div>
            <div style="font-size: 36px; font-weight: 700; color: #3b82f6;"><?php echo $total_achievements; ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;">Przyznane odznaczenia</div>
            <div style="font-size: 36px; font-weight: 700; color: #10b981;"><?php echo $total_awarded; ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;">Wszystkie punkty</div>
            <div style="font-size: 36px; font-weight: 700; color: #f59e0b;"><?php echo number_format($total_points_given); ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #8b5cf6;">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;">Dzieci z nagrodami</div>
            <div style="font-size: 36px; font-weight: 700; color: #8b5cf6;"><?php echo $children_with_achievements; ?> / <?php echo $active_children; ?></div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 30px;">
        
        <!-- Lewa kolumna -->
        <div>
            
            <!-- Ranking dzieci -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h2 style="margin-top: 0;">🥇 Top 10 Pływaków</h2>
                
                <table class="wp-list-table widefat" style="border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding: 12px; width: 50px; text-align: center;">Miejsce</th>
                            <th style="padding: 12px;">Imię i nazwisko</th>
                            <th style="padding: 12px;">Tier</th>
                            <th style="padding: 12px; text-align: right;">Punkty</th>
                            <th style="padding: 12px; text-align: center;">Odznaczenia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $place = 1;
                        $medals = array(1 => '🥇', 2 => '🥈', 3 => '🥉');
                        foreach ($top_children as $child): 
                            $tier = $tiers[$child->tier] ?? $tiers['beginner'];
                        ?>
                        <tr>
                            <td style="padding: 12px; text-align: center; font-size: 24px;">
                                <?php echo $medals[$place] ?? $place; ?>
                            </td>
                            <td style="padding: 12px;">
                                <strong><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></strong>
                                <div style="font-size: 12px; color: #64748b;">Poziom <?php echo $child->level; ?></div>
                            </td>
                            <td style="padding: 12px;">
                                <span style="color: <?php echo $tier['color']; ?>; font-weight: 600;">
                                    <?php echo $tier['name']; ?>
                                </span>
                            </td>
                            <td style="padding: 12px; text-align: right;">
                                <strong style="font-size: 18px; color: #667eea;"><?php echo $child->points; ?></strong>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <span style="background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 12px; font-weight: 600;">
                                    🎖️ <?php echo $child->achievement_count; ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
                        $place++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Ostatnie aktywności -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">📊 Ostatnie aktywności</h2>
                
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div style="padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 15px;">
                        <div style="font-size: 32px;">
                            <?php echo $activity->achievement_id ? $activity->icon : ($activity->points > 0 ? '✅' : '📉'); ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; margin-bottom: 3px;">
                                <?php echo esc_html($activity->first_name . ' ' . $activity->last_name); ?>
                            </div>
                            <div style="font-size: 13px; color: #64748b;">
                                <?php 
                                if ($activity->achievement_id) {
                                    echo esc_html($activity->achievement_name);
                                } else {
                                    echo esc_html($activity->description ?: $activity->reason);
                                }
                                ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 600; color: <?php echo $activity->points > 0 ? '#10b981' : '#ef4444'; ?>; font-size: 16px;">
                                <?php echo $activity->points > 0 ? '+' : ''; ?><?php echo $activity->points; ?> pkt
                            </div>
                            <div style="font-size: 11px; color: #94a3b8;">
                                <?php echo date('d.m.Y H:i', strtotime($activity->created_at)); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        </div>
        
        <!-- Prawa kolumna -->
        <div>
            
            <!-- Najpopularniejsze odznaczenia -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h2 style="margin-top: 0;">⭐ Top 5 Odznaczeń</h2>
                
                <?php foreach ($popular_achievements as $ach): ?>
                <div style="padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 15px;">
                    <div style="font-size: 36px;">
                        <?php echo $ach->icon; ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; margin-bottom: 3px;">
                            <?php echo esc_html($ach->name); ?>
                        </div>
                        <div style="font-size: 12px; color: #64748b;">
                            +<?php echo $ach->points; ?> pkt
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700; color: #667eea; font-size: 20px;">
                            <?php echo $ach->times_awarded; ?>x
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Rozkład tierów -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">🏆 Rozkład Tierów</h2>
                
                <?php foreach ($tiers as $tier_key => $tier): ?>
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="font-weight: 600; color: <?php echo $tier['color']; ?>;">
                            <?php echo $tier['name']; ?>
                        </span>
                        <span style="font-weight: 700; color: <?php echo $tier['color']; ?>;">
                            <?php echo $tier_stats[$tier_key] ?? 0; ?>
                        </span>
                    </div>
                    <div style="background: #e2e8f0; border-radius: 8px; height: 8px; overflow: hidden;">
                        <div style="background: <?php echo $tier['color']; ?>; height: 100%; width: <?php echo $active_children > 0 ? round(($tier_stats[$tier_key] ?? 0) / $active_children * 100) : 0; ?>%; transition: width 0.3s;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
        </div>
        
    </div>
</div>
