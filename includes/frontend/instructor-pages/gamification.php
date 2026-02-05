<?php
/**
 * Panel Instruktora - System Nagród
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

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

?>

<div class="ssm-page-header">
    <h1>🏆 System Nagród</h1>
    <p>Przyznawaj punkty i odznaczenia swoim pływakom</p>
</div>

<style>
.ssm-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e2e8f0;
}
.ssm-tab {
    padding: 12px 24px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 600;
    color: #64748b;
    transition: all 0.2s;
}
.ssm-tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
}
.ssm-tab-content {
    display: none;
}
.ssm-tab-content.active {
    display: block;
}
</style>

<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    
    <!-- Zakładki -->
    <div class="ssm-tabs">
        <button class="ssm-tab active" onclick="switchTab('children')">👶 Dzieci (<?php echo count($children); ?>)</button>
        <button class="ssm-tab" onclick="switchTab('achievements')">🎖️ Odznaczenia (<?php echo count($achievements); ?>)</button>
        <button class="ssm-tab" onclick="switchTab('help')">💡 Pomoc</button>
    </div>
    
    <!-- Zakładka: Dzieci -->
    <div id="tab-children" class="ssm-tab-content active">
        <h3 style="margin-top: 0;">Lista dzieci i ich postępy</h3>
        
        <?php if ($children): ?>
        <table class="wp-list-table widefat" style="border-radius: 8px; overflow: hidden;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding: 12px;">Imię i nazwisko</th>
                    <th>Tier</th>
                    <th>Poziom</th>
                    <th>Punkty</th>
                    <th>Odznaczenia</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($children as $child): 
                    $child_tier = $tiers[$child->tier ?? 'beginner'];
                    $progress = ssm_get_child_progress($child->id);
                ?>
                <tr>
                    <td style="padding: 12px;">
                        <strong><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></strong>
                    </td>
                    <td>
                        <span style="color: <?php echo $child_tier['color']; ?>; font-weight: 600;">
                            <?php echo $child_tier['name']; ?>
                        </span>
                    </td>
                    <td><?php echo $child->level ?: 1; ?></td>
                    <td><strong><?php echo $child->points ?: 0; ?></strong> pkt</td>
                    <td><?php echo $child->achievement_count; ?></td>
                    <td>
                        <button type="button" 
                                class="button button-small"
                                onclick="openAwardModal(<?php echo $child->id; ?>, '<?php echo esc_js($child->first_name . ' ' . $child->last_name); ?>')">
                            ⭐ Przyznaj
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color: #64748b;">Brak dzieci na Twoich kursach.</p>
        <?php endif; ?>
    </div>
    
    <!-- Zakładka: Odznaczenia -->
    <div id="tab-achievements" class="ssm-tab-content">
        <h3 style="margin-top: 0;">Dostępne odznaczenia</h3>
        
        <?php 
        $categories = array(
            'skill' => 'Umiejętności',
            'attendance' => 'Frekwencja',
            'loyalty' => 'Lojalność',
            'social' => 'Społeczne',
            'special' => 'Specjalne'
        );
        
        foreach ($categories as $cat_key => $cat_name):
            $cat_achievements = array_filter($achievements, function($a) use ($cat_key) {
                return $a->category == $cat_key;
            });
            
            if (empty($cat_achievements)) continue;
        ?>
        <h4><?php echo $cat_name; ?></h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <?php foreach ($cat_achievements as $ach): 
                $type_colors = array('bronze' => '#CD7F32', 'silver' => '#C0C0C0', 'gold' => '#FFD700', 'platinum' => '#E5E4E2');
            ?>
            <div style="padding: 15px; border: 2px solid <?php echo $type_colors[$ach->type] ?? '#e2e8f0'; ?>; border-radius: 8px; background: white;">
                <div style="font-size: 32px; margin-bottom: 8px;"><?php echo $ach->icon; ?></div>
                <div style="font-weight: 600; margin-bottom: 5px;"><?php echo esc_html($ach->name); ?></div>
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;"><?php echo esc_html($ach->description); ?></div>
                <div style="font-weight: 600; color: #667eea;">+<?php echo $ach->points; ?> pkt</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Zakładka: Pomoc -->
    <div id="tab-help" class="ssm-tab-content">
        <h3 style="margin-top: 0;">💡 Jak działa system nagród?</h3>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin-bottom: 20px;">
            <h4 style="margin-top: 0;">System punktów</h4>
            <ul>
                <li><strong>+5 pkt</strong> - automatycznie za każdą obecność na zajęciach</li>
                <li><strong>+10-150 pkt</strong> - za przyznane odznaczenia</li>
                <li><strong>Poziom</strong> - każde 50 punktów = +1 poziom</li>
            </ul>
        </div>
        
        <div style="background: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; margin-bottom: 20px;">
            <h4 style="margin-top: 0;">Tiery (poziomy zaawansowania)</h4>
            <ul>
                <?php foreach ($tiers as $tier): ?>
                <li style="color: <?php echo $tier['color']; ?>">
                    <strong><?php echo $tier['name']; ?></strong> - <?php echo $tier['points_required']; ?>+ pkt - <?php echo $tier['description']; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div style="background: #d1fae5; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981;">
            <h4 style="margin-top: 0;">Jak przyznawać odznaczenia?</h4>
            <ol>
                <li>Przejdź do zakładki "Dzieci"</li>
                <li>Kliknij [⭐ Przyznaj] przy wybranym dziecku</li>
                <li>Wybierz odznaczenie z listy</li>
                <li>Dziecko automatycznie otrzyma punkty!</li>
            </ol>
            <p><strong>Uwaga:</strong> Każde odznaczenie można przyznać tylko raz!</p>
        </div>
    </div>
    
</div>

<!-- Modal przyznawania -->
<div id="award-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin-top: 0;">Przyznaj odznaczenie</h3>
        <p id="modal-child-name" style="color: #64748b;"></p>
        
        <form id="award-form" method="post" action="">
            <input type="hidden" name="ssm_award_achievement" value="1">
            <input type="hidden" name="child_id" id="modal-child-id">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px;">Wybierz odznaczenie:</label>
                <select name="achievement_id" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    <option value="">-- Wybierz --</option>
                    <?php foreach ($achievements as $ach): ?>
                    <option value="<?php echo $ach->id; ?>">
                        <?php echo $ach->icon; ?> <?php echo esc_html($ach->name); ?> (+<?php echo $ach->points; ?> pkt)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px;">Notatki (opcjonalnie):</label>
                <textarea name="notes" rows="3" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;" placeholder="np. Świetnie opanował crawla!"></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeAwardModal()" class="button">Anuluj</button>
                <button type="submit" class="button button-primary">🏆 Przyznaj odznaczenie</button>
            </div>
        </form>
    </div>
</div>

<?php
// Obsługa przyznania odznaczenia
if (isset($_POST['ssm_award_achievement'])) {
    $child_id = intval($_POST['child_id']);
    $achievement_id = intval($_POST['achievement_id']);
    $notes = sanitize_textarea_field($_POST['notes']);
    
    $result = ssm_award_achievement($child_id, $achievement_id, get_current_user_id(), $notes);
    
    if ($result) {
        echo '<script>alert("✅ Odznaczenie przyznane! Dziecko otrzymało punkty."); window.location.reload();</script>';
    } else {
        echo '<script>alert("❌ Nie udało się przyznać odznaczenia. Być może dziecko już je ma?");</script>';
    }
}
?>

<script>
function switchTab(tab) {
    // Ukryj wszystkie
    document.querySelectorAll('.ssm-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ssm-tab-content').forEach(c => c.classList.remove('active'));
    
    // Pokaż wybraną
    event.target.classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

function openAwardModal(childId, childName) {
    document.getElementById('modal-child-id').value = childId;
    document.getElementById('modal-child-name').textContent = 'Dziecko: ' + childName;
    document.getElementById('award-modal').style.display = 'flex';
}

function closeAwardModal() {
    document.getElementById('award-modal').style.display = 'none';
}

// Zamknij modal kliknięciem w tło
document.getElementById('award-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAwardModal();
    }
});
</script>
