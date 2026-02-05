<?php
/**
 * Panel Admin - Zarządzanie Odznaczeniami
 */
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('Brak uprawnień.');
}

global $wpdb;

// Obsługa dodawania/edycji
if (isset($_POST['ssm_save_achievement']) && check_admin_referer('ssm_achievement_form')) {
    $achievement_id = isset($_POST['achievement_id']) ? intval($_POST['achievement_id']) : 0;
    
    $data = array(
        'name' => sanitize_text_field($_POST['name']),
        'description' => sanitize_textarea_field($_POST['description']),
        'icon' => sanitize_text_field($_POST['icon']),
        'category' => sanitize_text_field($_POST['category']),
        'type' => sanitize_text_field($_POST['type']),
        'points' => intval($_POST['points']),
        'criteria' => sanitize_textarea_field($_POST['criteria']),
        'active' => isset($_POST['active']) ? 1 : 0
    );
    
    if ($achievement_id) {
        // Edycja
        $wpdb->update(
            $wpdb->prefix . 'ssm_achievements',
            $data,
            array('id' => $achievement_id)
        );
        echo '<div class="notice notice-success"><p>✅ Odznaczenie zaktualizowane!</p></div>';
    } else {
        // Dodawanie
        $data['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'ssm_achievements', $data);
        echo '<div class="notice notice-success"><p>✅ Odznaczenie dodane!</p></div>';
    }
}

// Obsługa usuwania
if (isset($_GET['delete']) && check_admin_referer('delete_achievement_' . intval($_GET['delete']))) {
    $achievement_id = intval($_GET['delete']);
    
    // Sprawdź czy ktoś ma to odznaczenie
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_child_achievements WHERE achievement_id = %d",
        $achievement_id
    ));
    
    if ($count > 0) {
        echo '<div class="notice notice-error"><p>❌ Nie można usunąć odznaczenia - zostało już przyznane ' . $count . ' razy!</p></div>';
    } else {
        $wpdb->delete($wpdb->prefix . 'ssm_achievements', array('id' => $achievement_id));
        echo '<div class="notice notice-success"><p>✅ Odznaczenie usunięte!</p></div>';
    }
}

// Tryb edycji
$edit_mode = false;
$achievement_edit = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $achievement_edit = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_achievements WHERE id = %d",
        intval($_GET['edit'])
    ));
}

// Pobierz wszystkie odznaczenia
$achievements = $wpdb->get_results(
    "SELECT a.*, 
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_child_achievements WHERE achievement_id = a.id) as times_awarded
     FROM {$wpdb->prefix}ssm_achievements a
     ORDER BY a.category, a.type, a.name"
);

$categories = array(
    'skill' => 'Umiejętności',
    'attendance' => 'Frekwencja',
    'loyalty' => 'Lojalność',
    'social' => 'Społeczne',
    'special' => 'Specjalne'
);

$types = array(
    'bronze' => 'Brązowe',
    'silver' => 'Srebrne',
    'gold' => 'Złote',
    'platinum' => 'Platynowe'
);

?>

<div class="wrap">
    <h1>🎖️ Zarządzanie Odznaczeniami
        <?php if (!$edit_mode): ?>
        <a href="#" class="page-title-action" onclick="document.getElementById('achievement-form').style.display='block'; return false;">Dodaj odznaczenie</a>
        <?php endif; ?>
    </h1>
    
    <!-- Formularz dodawania/edycji -->
    <div id="achievement-form" style="<?php echo $edit_mode ? 'display:block;' : 'display:none;'; ?> background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2><?php echo $edit_mode ? 'Edytuj odznaczenie' : 'Dodaj nowe odznaczenie'; ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('ssm_achievement_form'); ?>
            <?php if ($edit_mode): ?>
                <input type="hidden" name="achievement_id" value="<?php echo $achievement_edit->id; ?>">
            <?php endif; ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="name">Nazwa *</label></th>
                    <td>
                        <input type="text" id="name" name="name" class="regular-text" 
                               value="<?php echo $edit_mode ? esc_attr($achievement_edit->name) : ''; ?>" required>
                        <p class="description">np. "Pierwszy rzut", "Mistrz baku"</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="icon">Ikona (emoji) *</label></th>
                    <td>
                        <input type="text" id="icon" name="icon" class="small-text" 
                               value="<?php echo $edit_mode ? esc_attr($achievement_edit->icon) : ''; ?>" 
                               placeholder="🏊" required maxlength="10">
                        <p class="description">Emoji lub tekst (maks. 10 znaków). Przykłady: 🏊 🌊 🏁 🥇 ⭐ 🎯 💯</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="description">Opis</label></th>
                    <td>
                        <textarea id="description" name="description" rows="3" class="large-text"><?php echo $edit_mode ? esc_textarea($achievement_edit->description) : ''; ?></textarea>
                        <p class="description">Krótki opis osiągnięcia</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="category">Kategoria *</label></th>
                    <td>
                        <select id="category" name="category" required>
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($edit_mode && $achievement_edit->category == $key) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="type">Typ/Ranga *</label></th>
                    <td>
                        <select id="type" name="type" required>
                            <?php foreach ($types as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($edit_mode && $achievement_edit->type == $key) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Wpływa na kolor ramki i trudność</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="points">Punkty *</label></th>
                    <td>
                        <input type="number" id="points" name="points" class="small-text" min="0" max="1000" 
                               value="<?php echo $edit_mode ? esc_attr($achievement_edit->points) : '10'; ?>" required>
                        <p class="description">Ile punktów otrzymuje dziecko za to odznaczenie</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="criteria">Kryteria (opcjonalnie)</label></th>
                    <td>
                        <textarea id="criteria" name="criteria" rows="2" class="large-text"><?php echo $edit_mode ? esc_textarea($achievement_edit->criteria) : ''; ?></textarea>
                        <p class="description">Szczegółowe warunki przyznania (do użytku wewnętrznego)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <label>
                            <input type="checkbox" name="active" value="1" <?php echo ($edit_mode && $achievement_edit->active) ? 'checked' : 'checked'; ?>>
                            Aktywne (widoczne dla instruktorów)
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="ssm_save_achievement" class="button button-primary">
                    💾 <?php echo $edit_mode ? 'Zapisz zmiany' : 'Dodaj odznaczenie'; ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="?page=swimming-school-achievements" class="button">Anuluj</a>
                <?php else: ?>
                    <button type="button" class="button" onclick="document.getElementById('achievement-form').style.display='none';">Anuluj</button>
                <?php endif; ?>
            </p>
        </form>
    </div>
    
    <!-- Lista odznaczeń -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>Lista odznaczeń (<?php echo count($achievements); ?>)</h2>
        
        <?php
        $type_colors = array(
            'bronze' => '#CD7F32',
            'silver' => '#C0C0C0',
            'gold' => '#FFD700',
            'platinum' => '#E5E4E2'
        );
        
        foreach ($categories as $cat_key => $cat_name):
            $cat_achievements = array_filter($achievements, function($a) use ($cat_key) {
                return $a->category == $cat_key;
            });
            
            if (empty($cat_achievements)) continue;
        ?>
        
        <h3 style="margin-top: 30px; margin-bottom: 15px; color: #667eea;">📌 <?php echo $cat_name; ?></h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
            <?php foreach ($cat_achievements as $ach): ?>
            <div style="border: 3px solid <?php echo $type_colors[$ach->type]; ?>; border-radius: 8px; padding: 15px; position: relative; background: white;">
                <!-- Badge statusu -->
                <?php if (!$ach->active): ?>
                <span style="position: absolute; top: 10px; right: 10px; background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                    NIEAKTYWNE
                </span>
                <?php endif; ?>
                
                <!-- Ikona -->
                <div style="font-size: 48px; margin-bottom: 10px;"><?php echo $ach->icon; ?></div>
                
                <!-- Nazwa i typ -->
                <div style="font-weight: 700; font-size: 16px; margin-bottom: 5px;"><?php echo esc_html($ach->name); ?></div>
                <div style="font-size: 12px; color: <?php echo $type_colors[$ach->type]; ?>; font-weight: 600; margin-bottom: 10px;">
                    <?php echo $types[$ach->type]; ?>
                </div>
                
                <!-- Opis -->
                <?php if ($ach->description): ?>
                <div style="font-size: 13px; color: #64748b; margin-bottom: 10px; line-height: 1.4;">
                    <?php echo esc_html($ach->description); ?>
                </div>
                <?php endif; ?>
                
                <!-- Punkty -->
                <div style="background: #f0f9ff; padding: 8px; border-radius: 6px; margin-bottom: 10px;">
                    <strong style="color: #667eea; font-size: 18px;">+<?php echo $ach->points; ?> pkt</strong>
                </div>
                
                <!-- Statystyki -->
                <div style="font-size: 12px; color: #64748b; margin-bottom: 10px;">
                    Przyznano: <strong><?php echo $ach->times_awarded; ?> razy</strong>
                </div>
                
                <!-- Akcje -->
                <div style="display: flex; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 10px;">
                    <a href="?page=swimming-school-achievements&edit=<?php echo $ach->id; ?>" 
                       class="button button-small">
                        ✏️ Edytuj
                    </a>
                    <?php if ($ach->times_awarded == 0): ?>
                    <a href="?page=swimming-school-achievements&delete=<?php echo $ach->id; ?>&_wpnonce=<?php echo wp_create_nonce('delete_achievement_' . $ach->id); ?>" 
                       class="button button-small"
                       onclick="return confirm('Czy na pewno usunąć to odznaczenie?');">
                        🗑️ Usuń
                    </a>
                    <?php else: ?>
                    <span style="font-size: 11px; color: #94a3b8; padding: 5px;">
                        Nie można usunąć (przyznane)
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endforeach; ?>
        
        <?php if (empty($achievements)): ?>
        <p style="text-align: center; color: #64748b; padding: 40px;">
            Brak odznaczeń. Kliknij "Dodaj odznaczenie" aby utworzyć pierwsze.
        </p>
        <?php endif; ?>
    </div>
</div>
