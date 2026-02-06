<?php
// Panel Dzieci
if (!defined('ABSPATH')) exit;

global $wpdb;

// Pobierz wszystkie dzieci
$children = $wpdb->get_results("
    SELECT ch.*, 
           GROUP_CONCAT(CONCAT(c.first_name, ' ', c.last_name) SEPARATOR ', ') as parents,
           cp.points,
           cp.level,
           cp.tier,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_child_achievements WHERE child_id = ch.id) as achievement_count
    FROM {$wpdb->prefix}ssm_children ch
    LEFT JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
    LEFT JOIN {$wpdb->prefix}ssm_clients c ON cc.client_id = c.id
    LEFT JOIN {$wpdb->prefix}ssm_child_points cp ON ch.id = cp.child_id
    GROUP BY ch.id
    ORDER BY ch.last_name, ch.first_name
");

// Pobierz wszystkich rodziców dla select
$clients = $wpdb->get_results("
    SELECT id, first_name, last_name, email 
    FROM {$wpdb->prefix}ssm_clients 
    ORDER BY last_name, first_name
");

// Funkcja obliczania wieku
function calculate_age($dob) {
    if (!$dob) return '-';
    $birth = new DateTime($dob);
    $today = new DateTime();
    $age = $birth->diff($today)->y;
    return $age . ' lat';
}
?>

<div class="wrap">
    <h1>Dzieci 
        <button type="button" class="page-title-action" id="ssm-add-child-btn">Dodaj dziecko</button>
    </h1>
    
    <!-- Formularz dodawania/edycji dziecka -->
    <div id="ssm-child-form-container" style="display:none; margin:20px 0;">
        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; max-width:600px;">
            <h2 id="ssm-form-title">Dodaj dziecko</h2>
            <form id="ssm-child-form">
                <input type="hidden" id="child-id" name="id" value="">
                
                <table class="form-table">
                    <tr>
                        <th><label for="child-first-name">Imię *</label></th>
                        <td><input type="text" id="child-first-name" name="first_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="child-last-name">Nazwisko *</label></th>
                        <td><input type="text" id="child-last-name" name="last_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="child-dob">Data urodzenia</label></th>
                        <td><input type="date" id="child-dob" name="date_of_birth" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="child-medical">Uwagi medyczne</label></th>
                        <td>
                            <textarea id="child-medical" name="medical_notes" class="large-text" rows="3" 
                                placeholder="Alergie, choroby, inne uwagi..."></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="child-skills">Opis umiejętności</label></th>
                        <td>
                            <textarea id="child-skills" name="skills_description" class="large-text" rows="4" 
                                placeholder="Poziom pływania, ukończone kursy, umiejętności..."></textarea>
                            <p class="description">
                                Np. "Pływa stylem grzbietowym i kraulem, ukończył kurs podstawowy, potrafi nurkować"
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="child-active">Status</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="child-active" name="active" value="1" checked>
                                Aktywne
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Zapisz dziecko</button>
                    <button type="button" class="button" id="ssm-cancel-child-btn">Anuluj</button>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Lista dzieci -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imię i nazwisko</th>
                <th>Wiek</th>
                <th>Rodzice/Opiekunowie</th>
                <th>🏆 Punkty</th>
                <th>🎖️ Nagrody</th>
                <th>Uwagi medyczne</th>
                <th>Umiejętności</th>
                <th>Status</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($children): ?>
                <?php foreach ($children as $child): ?>
                <tr>
                    <td><?php echo $child->id; ?></td>
                    <td><strong><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></strong></td>
                    <td><?php echo calculate_age($child->date_of_birth); ?></td>
                    <td>
                        <?php if ($child->parents): ?>
                            <span style="color:#2271b1;">👤 <?php echo esc_html($child->parents); ?></span>
                        <?php else: ?>
                            <span style="color:#d63638;">⚠️ Brak przypisanych rodziców</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($child->points): ?>
                            <strong style="color:#667eea; font-size: 16px;"><?php echo $child->points; ?></strong>
                            <div style="font-size: 11px; color:#64748b;">Poziom <?php echo $child->level ?: 1; ?></div>
                        <?php else: ?>
                            <span style="color:#94a3b8;">0</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($child->achievement_count > 0): ?>
                            <span style="background:#fef3c7; color:#d97706; padding:4px 10px; border-radius:12px; font-weight:600; font-size:13px;">
                                🎖️ <?php echo $child->achievement_count; ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#94a3b8;">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($child->medical_notes): ?>
                            <span style="color:#d63638;" title="<?php echo esc_attr($child->medical_notes); ?>">
                                ⚕️ <?php echo esc_html(mb_substr($child->medical_notes, 0, 30)) . '...'; ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#50575e;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($child->skills_description): ?>
                            <span style="color:#2271b1;" title="<?php echo esc_attr($child->skills_description); ?>">
                                🏊 <?php echo esc_html(mb_substr($child->skills_description, 0, 40)) . '...'; ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#50575e;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($child->active): ?>
                            <span style="color:#00a32a;">✓ Aktywne</span>
                        <?php else: ?>
                            <span style="color:#646970;">○ Nieaktywne</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button button-small ssm-edit-child" 
                                data-id="<?php echo $child->id; ?>"
                                data-first-name="<?php echo esc_attr($child->first_name); ?>"
                                data-last-name="<?php echo esc_attr($child->last_name); ?>"
                                data-dob="<?php echo esc_attr($child->date_of_birth); ?>"
                                data-medical="<?php echo esc_attr($child->medical_notes); ?>"
                                data-skills="<?php echo esc_attr($child->skills_description); ?>"
                                data-active="<?php echo $child->active; ?>">
                            Edytuj
                        </button>
                        
                        <button class="button button-small ssm-assign-parent-btn" 
                                data-child-id="<?php echo $child->id; ?>"
                                data-child-name="<?php echo esc_attr($child->first_name . ' ' . $child->last_name); ?>">
                            👤 Rodzice
                        </button>
                        
                        <button class="button button-small button-link-delete ssm-delete-child" 
                                data-id="<?php echo $child->id; ?>">
                            Usuń
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">Brak dzieci w bazie. Dodaj pierwsze dziecko!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal przypisywania rodziców -->
<div id="ssm-assign-parent-modal" style="display:none;">
    <div class="ssm-modal-overlay"></div>
    <div class="ssm-modal-content">
        <div class="ssm-modal-header">
            <h2>Przypisz rodzica do dziecka: <span id="modal-child-name"></span></h2>
            <button class="ssm-modal-close">&times;</button>
        </div>
        <div class="ssm-modal-body">
            <input type="hidden" id="modal-child-id">
            
            <h3>Dodaj rodzica/opiekuna:</h3>
            <table class="form-table">
                <tr>
                    <th><label for="modal-parent-select">Wybierz rodzica</label></th>
                    <td>
                        <select id="modal-parent-select" class="regular-text">
                            <option value="">-- Wybierz --</option>
                            <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client->id; ?>">
                                <?php echo esc_html($client->first_name . ' ' . $client->last_name); ?>
                                (<?php echo esc_html($client->email); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="modal-relationship">Relacja</label></th>
                    <td>
                        <select id="modal-relationship" class="regular-text">
                            <option value="parent">Rodzic</option>
                            <option value="guardian">Opiekun prawny</option>
                            <option value="grandparent">Babcia/Dziadek</option>
                            <option value="other">Inny</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="modal-primary">Główny kontakt?</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="modal-primary" value="1">
                            Tak, to główny kontakt
                        </label>
                    </td>
                </tr>
            </table>
            
            <button type="button" class="button button-primary" id="ssm-assign-parent-save">Przypisz rodzica</button>
            
            <hr style="margin:30px 0;">
            
            <h3>Obecnie przypisani rodzice:</h3>
            <div id="modal-current-parents">
                <p>Ładowanie...</p>
            </div>
        </div>
    </div>
</div>

<style>
.ssm-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 99999;
}

.ssm-modal-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 0;
    border-radius: 4px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    z-index: 100000;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.ssm-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ccd0d4;
    position: relative;
}

.ssm-modal-header h2 {
    margin: 0;
    padding-right: 30px;
}

.ssm-modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 30px;
    cursor: pointer;
    color: #646970;
}

.ssm-modal-close:hover {
    color: #d63638;
}

.ssm-modal-body {
    padding: 20px;
}

#modal-current-parents .parent-item {
    padding: 10px;
    background: #f0f0f1;
    margin: 10px 0;
    border-left: 3px solid #2271b1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#modal-current-parents .parent-item.primary {
    border-left-color: #00a32a;
    background: #ecf7ed;
}
</style>
