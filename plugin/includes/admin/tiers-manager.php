<?php
/**
 * Panel Admin - Zarządzanie Tierami
 */
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('Brak uprawnień.');
}

// Tiery zapisywane w opcjach WordPress
$tiers = get_option('ssm_custom_tiers', ssm_get_tiers());

// Obsługa zapisywania
if (isset($_POST['ssm_save_tiers']) && check_admin_referer('ssm_tiers_form')) {
    $new_tiers = array();
    
    if (isset($_POST['tiers']) && is_array($_POST['tiers'])) {
        foreach ($_POST['tiers'] as $key => $tier_data) {
            $safe_key = sanitize_key($key);
            $new_tiers[$safe_key] = array(
                'name' => sanitize_text_field($tier_data['name']),
                'points_required' => intval($tier_data['points_required']),
                'color' => sanitize_hex_color($tier_data['color']),
                'description' => sanitize_text_field($tier_data['description'])
            );
        }
        
        // Sortuj po punktach
        uasort($new_tiers, function($a, $b) {
            return $a['points_required'] - $b['points_required'];
        });
    }
    
    update_option('ssm_custom_tiers', $new_tiers);
    $tiers = $new_tiers;
    
    echo '<div class="notice notice-success"><p>✅ Tiery zapisane! Dzieci zostaną automatycznie zaktualizowane.</p></div>';
    
    // Przelicz tiery wszystkich dzieci
    ssm_recalculate_all_tiers();
}

// Obsługa resetu do domyślnych
if (isset($_GET['reset']) && check_admin_referer('reset_tiers')) {
    delete_option('ssm_custom_tiers');
    $tiers = ssm_get_tiers();
    ssm_recalculate_all_tiers();
    echo '<div class="notice notice-success"><p>✅ Przywrócono domyślne tiery!</p></div>';
}

/**
 * Przelicz tiery wszystkich dzieci
 */
function ssm_recalculate_all_tiers() {
    global $wpdb;
    $children = $wpdb->get_results("SELECT id, points FROM {$wpdb->prefix}ssm_child_points");
    
    foreach ($children as $child) {
        $new_tier = ssm_calculate_tier($child->points);
        $wpdb->update(
            $wpdb->prefix . 'ssm_child_points',
            array('tier' => $new_tier),
            array('id' => $child->id)
        );
    }
}

?>

<div class="wrap">
    <h1>🏆 Zarządzanie Tierami (Poziomy zaawansowania)</h1>
    <p class="description">Definiuj poziomy zaawansowania dla pływaków. Każdy tier to kamień milowy w rozwoju dziecka.</p>
    
    <!-- Info -->
    <div style="background: #f0f9ff; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #3b82f6;">
        <h3 style="margin-top: 0;">💡 Jak działają tiery?</h3>
        <ul>
            <li>Każdy tier reprezentuje poziom zaawansowania pływaka</li>
            <li><strong>Punkty wymagane</strong> - minimalna liczba punktów do osiągnięcia tieru</li>
            <li><strong>Nazwa</strong> - powinna zawierać emoji i nazwę (np. "🐠 Żabka")</li>
            <li><strong>Kolor</strong> - wyświetlany w panelach (używaj kodów HEX)</li>
            <li>Pierwszy tier powinien mieć 0 punktów wymaganych</li>
            <li>Dzieci automatycznie przechodzą na wyższy tier gdy zbiorą wystarczająco punktów</li>
        </ul>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('ssm_tiers_form'); ?>
        
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
            
            <div id="tiers-container">
                <?php 
                $counter = 0;
                foreach ($tiers as $tier_key => $tier): 
                    $counter++;
                ?>
                <div class="tier-row" style="border: 2px solid <?php echo $tier['color']; ?>; border-radius: 8px; padding: 20px; margin-bottom: 20px; position: relative; background: <?php echo $tier['color']; ?>10;">
                    
                    <div style="position: absolute; top: 10px; right: 10px; font-weight: 600; color: <?php echo $tier['color']; ?>;">
                        Tier #<?php echo $counter; ?>
                    </div>
                    
                    <input type="hidden" name="tiers[<?php echo esc_attr($tier_key); ?>][key]" value="<?php echo esc_attr($tier_key); ?>">
                    
                    <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 20px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">Nazwa (z emoji) *</label>
                            <input type="text" 
                                   name="tiers[<?php echo esc_attr($tier_key); ?>][name]" 
                                   value="<?php echo esc_attr($tier['name']); ?>" 
                                   class="regular-text" 
                                   placeholder="🐠 Żabka"
                                   required>
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">Punkty wymagane *</label>
                            <input type="number" 
                                   name="tiers[<?php echo esc_attr($tier_key); ?>][points_required]" 
                                   value="<?php echo esc_attr($tier['points_required']); ?>" 
                                   class="regular-text" 
                                   min="0"
                                   required>
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">Kolor (HEX) *</label>
                            <input type="color" 
                                   name="tiers[<?php echo esc_attr($tier_key); ?>][color]" 
                                   value="<?php echo esc_attr($tier['color']); ?>" 
                                   style="height: 40px; width: 100px;">
                            <input type="text" 
                                   name="tiers[<?php echo esc_attr($tier_key); ?>][color]" 
                                   value="<?php echo esc_attr($tier['color']); ?>" 
                                   class="regular-text" 
                                   placeholder="#3b82f6"
                                   pattern="^#[0-9A-Fa-f]{6}$"
                                   style="width: 120px; margin-left: 10px;">
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Opis</label>
                        <input type="text" 
                               name="tiers[<?php echo esc_attr($tier_key); ?>][description]" 
                               value="<?php echo esc_attr($tier['description']); ?>" 
                               class="large-text" 
                               placeholder="Początkujący pływak">
                    </div>
                    
                    <?php if ($counter > 1): ?>
                    <button type="button" class="button button-small" 
                            style="margin-top: 10px; background: #fee2e2; border-color: #ef4444; color: #dc2626;"
                            onclick="if(confirm('Usunąć ten tier?')) this.parentElement.remove();">
                        🗑️ Usuń tier
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="button" class="button" onclick="addNewTier()">
                    ➕ Dodaj nowy tier
                </button>
            </div>
            
        </div>
        
        <p class="submit">
            <button type="submit" name="ssm_save_tiers" class="button button-primary button-large">
                💾 Zapisz wszystkie tiery
            </button>
            <a href="?page=swimming-school-tiers&reset=1&_wpnonce=<?php echo wp_create_nonce('reset_tiers'); ?>" 
               class="button"
               onclick="return confirm('Przywrócić domyślne tiery? Zmiany zostaną utracone.');">
                ↺ Przywróć domyślne
            </a>
        </p>
    </form>
    
    <!-- Podgląd -->
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>Podgląd tierów</h2>
        
        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
            <?php foreach ($tiers as $tier): ?>
            <div style="flex: 1; min-width: 200px; padding: 20px; background: linear-gradient(135deg, <?php echo $tier['color']; ?>20 0%, <?php echo $tier['color']; ?>40 100%); border-radius: 12px; border-left: 4px solid <?php echo $tier['color']; ?>;">
                <div style="font-size: 14px; color: #64748b; margin-bottom: 5px;">
                    <?php echo $tier['points_required']; ?>+ punktów
                </div>
                <div style="font-size: 28px; font-weight: 700; color: <?php echo $tier['color']; ?>; margin-bottom: 5px;">
                    <?php echo $tier['name']; ?>
                </div>
                <div style="font-size: 13px; color: #64748b;">
                    <?php echo $tier['description']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Statystyki -->
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
        <h2>Statystyki dzieci w tierach</h2>
        
        <?php
        global $wpdb;
        $tier_stats = array();
        foreach (array_keys($tiers) as $tier_key) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_child_points WHERE tier = %s",
                $tier_key
            ));
            $tier_stats[$tier_key] = $count;
        }
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($tiers as $tier_key => $tier): ?>
            <div style="padding: 20px; background: <?php echo $tier['color']; ?>10; border-left: 4px solid <?php echo $tier['color']; ?>; border-radius: 8px;">
                <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;">
                    <?php echo $tier['name']; ?>
                </div>
                <div style="font-size: 36px; font-weight: 700; color: <?php echo $tier['color']; ?>;">
                    <?php echo $tier_stats[$tier_key] ?? 0; ?>
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    <?php echo ($tier_stats[$tier_key] ?? 0) == 1 ? 'dziecko' : 'dzieci'; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
let tierCounter = <?php echo count($tiers); ?>;

function addNewTier() {
    tierCounter++;
    const container = document.getElementById('tiers-container');
    const newKey = 'tier_' + Date.now();
    const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
    
    const html = `
        <div class="tier-row" style="border: 2px solid ${randomColor}; border-radius: 8px; padding: 20px; margin-bottom: 20px; position: relative; background: ${randomColor}10;">
            <div style="position: absolute; top: 10px; right: 10px; font-weight: 600; color: ${randomColor};">
                Tier #${tierCounter}
            </div>
            
            <input type="hidden" name="tiers[${newKey}][key]" value="${newKey}">
            
            <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 20px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Nazwa (z emoji) *</label>
                    <input type="text" name="tiers[${newKey}][name]" value="" class="regular-text" placeholder="🦈 Nowy Tier" required>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Punkty wymagane *</label>
                    <input type="number" name="tiers[${newKey}][points_required]" value="0" class="regular-text" min="0" required>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Kolor (HEX) *</label>
                    <input type="color" name="tiers[${newKey}][color]" value="${randomColor}" style="height: 40px; width: 100px;">
                    <input type="text" name="tiers[${newKey}][color]" value="${randomColor}" class="regular-text" placeholder="#3b82f6" pattern="^#[0-9A-Fa-f]{6}$" style="width: 120px; margin-left: 10px;">
                </div>
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Opis</label>
                <input type="text" name="tiers[${newKey}][description]" value="" class="large-text" placeholder="Opis poziomu">
            </div>
            
            <button type="button" class="button button-small" 
                    style="margin-top: 10px; background: #fee2e2; border-color: #ef4444; color: #dc2626;"
                    onclick="if(confirm('Usunąć ten tier?')) this.parentElement.remove();">
                🗑️ Usuń tier
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
}
</script>
