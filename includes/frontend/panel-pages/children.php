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
            $success_message = '✅ Dane dziecka zostały zaktualizowane!';
            
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
            $error_message = '❌ Nie udało się zapisać zmian.';
        }
    }
}

// ID dziecka do edycji
$editing_child_id = isset($_GET['edit_child']) ? intval($_GET['edit_child']) : 0;
$editing_child = null;

if ($editing_child_id) {
    $editing_child = $wpdb->get_row($wpdb->prepare(
        "SELECT ch.* FROM {$wpdb->prefix}ssm_children ch
         JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
         WHERE cc.client_id = %d AND ch.id = %d",
        $client->id, $editing_child_id
    ));
}
?>

<div class="ssm-page-header">
    <h1>👶 Twoje dzieci</h1>
    <p>Zarządzaj danymi swoich dzieci</p>
</div>

<?php if ($success_message): ?>
    <div class="ssm-notice ssm-notice-success">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="ssm-notice ssm-notice-error">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<?php if ($editing_child): ?>
    
    <!-- Formularz edycji -->
    <div class="ssm-form-container">
        <div class="ssm-form-header">
            <h3>Edytuj dane dziecka</h3>
            <a href="<?php echo remove_query_arg('edit_child'); ?>" class="ssm-btn-close">✕</a>
        </div>
        
        <form method="post" class="ssm-edit-form">
            <input type="hidden" name="child_id" value="<?php echo $editing_child->id; ?>">
            
            <div class="ssm-form-row">
                <div class="ssm-form-group">
                    <label for="child_first_name">Imię *</label>
                    <input type="text" id="child_first_name" name="child_first_name" required
                           value="<?php echo esc_attr($editing_child->first_name); ?>"
                           class="ssm-input">
                </div>
                
                <div class="ssm-form-group">
                    <label for="child_last_name">Nazwisko *</label>
                    <input type="text" id="child_last_name" name="child_last_name" required
                           value="<?php echo esc_attr($editing_child->last_name); ?>"
                           class="ssm-input">
                </div>
            </div>
            
            <div class="ssm-form-group">
                <label for="child_dob">Data urodzenia *</label>
                <input type="date" id="child_dob" name="child_dob" required
                       value="<?php echo esc_attr($editing_child->date_of_birth); ?>"
                       class="ssm-input">
            </div>
            
            <div class="ssm-form-group">
                <label for="child_medical">Uwagi medyczne</label>
                <textarea id="child_medical" name="child_medical" rows="4"
                          class="ssm-input"
                          placeholder="Alergie, schorzenia, uwagi dla instruktora..."><?php echo esc_textarea($editing_child->medical_notes); ?></textarea>
            </div>
            
            <div class="ssm-form-group">
                <label for="child_skills">Opis umiejętności pływackich</label>
                <textarea id="child_skills" name="child_skills" rows="4"
                          class="ssm-input"
                          placeholder="Poziom pływania, ukończone kursy, style które dziecko umie..."><?php echo esc_textarea($editing_child->skills_description); ?></textarea>
                <small style="color: #64748b;">
                    Np. "Pływa stylem grzbietowym i kraulem, ukończył kurs podstawowy, potrafi nurkować"
                </small>
            </div>
            
            <div class="ssm-form-actions">
                <button type="submit" name="ssm_update_child" class="ssm-btn ssm-btn-primary">
                    💾 Zapisz zmiany
                </button>
                <a href="<?php echo remove_query_arg('edit_child'); ?>" class="ssm-btn ssm-btn-secondary">
                    Anuluj
                </a>
            </div>
        </form>
    </div>
    
<?php endif; ?>

<!-- Lista dzieci -->
<div class="ssm-children-list">
    <?php if ($children): ?>
        <?php foreach ($children as $child): 
            // Pobierz kursy dziecka
            $child_enrollments = $wpdb->get_results($wpdb->prepare(
                "SELECT e.*, c.name as class_name, c.day_of_week, c.time_start,
                        f.name as facility_name
                 FROM {$wpdb->prefix}ssm_enrollments e
                 LEFT JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
                 LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
                 WHERE e.child_id = %d AND e.status = 'active'",
                $child->id
            ));
        ?>
            <div class="ssm-child-panel">
                <div class="ssm-child-header">
                    <div class="ssm-child-avatar-lg">
                        <?php echo strtoupper(substr($child->first_name, 0, 1)); ?>
                    </div>
                    <div class="ssm-child-info">
                        <h3><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></h3>
                        <p>
                            <strong>Wiek:</strong> <?php echo $child->age; ?> lat •
                            <strong>Data ur.:</strong> <?php echo date('d.m.Y', strtotime($child->date_of_birth)); ?>
                        </p>
                        <?php if ($child->medical_notes): ?>
                            <div class="ssm-medical-note">
                                <strong>⚕️ Uwagi medyczne:</strong> <?php echo esc_html($child->medical_notes); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($child->skills_description): ?>
                            <div class="ssm-skills-note" style="margin-top: 10px; padding: 10px; background: #dbeafe; border-left: 3px solid #2563eb; border-radius: 4px;">
                                <strong>🏊 Umiejętności:</strong> <?php echo esc_html($child->skills_description); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ssm-child-actions">
                        <a href="<?php echo add_query_arg('edit_child', $child->id); ?>" 
                           class="ssm-btn ssm-btn-edit">
                            ✏️ Edytuj
                        </a>
                    </div>
                </div>
                
                <?php 
                // Pobierz postępy gamifikacji
                $progress = ssm_get_child_progress($child->id);
                $tiers = ssm_get_tiers();
                ?>
                
                <!-- GAMIFIKACJA -->
                <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h4 style="margin: 0 0 15px 0;">🏆 Postępy</h4>
                    
                    <!-- Tier i Poziom -->
                    <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px; padding: 20px; background: linear-gradient(135deg, <?php echo $progress['current_tier']['color']; ?>20 0%, <?php echo $progress['current_tier']['color']; ?>40 100%); border-radius: 8px; border-left: 4px solid <?php echo $progress['current_tier']['color']; ?>;">
                            <div style="font-size: 14px; color: #64748b; margin-bottom: 5px;">Poziom</div>
                            <div style="font-size: 32px; font-weight: 700; color: <?php echo $progress['current_tier']['color']; ?>;">
                                <?php echo $progress['current_tier']['name']; ?>
                            </div>
                            <div style="font-size: 13px; color: #64748b; margin-top: 5px;">
                                <?php echo $progress['current_tier']['description']; ?>
                            </div>
                        </div>
                        
                        <div style="flex: 1; min-width: 200px; padding: 20px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #3b82f6;">
                            <div style="font-size: 14px; color: #64748b; margin-bottom: 5px;">Punkty</div>
                            <div style="font-size: 32px; font-weight: 700; color: #3b82f6;">
                                <?php echo $progress['points']->points; ?> pkt
                            </div>
                            <div style="font-size: 13px; color: #64748b; margin-top: 5px;">
                                Poziom <?php echo $progress['points']->level; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Postęp do następnego tieru -->
                    <?php if ($progress['next_tier']): ?>
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px;">
                            <span>Do następnego poziomu: <strong><?php echo $progress['next_tier']['name']; ?></strong></span>
                            <span><strong><?php echo $progress['points_to_next']; ?></strong> pkt</span>
                        </div>
                        <div style="background: #e2e8f0; border-radius: 8px; height: 12px; overflow: hidden;">
                            <div style="background: linear-gradient(90deg, <?php echo $progress['current_tier']['color']; ?> 0%, <?php echo $progress['next_tier']['color']; ?> 100%); height: 100%; width: <?php echo $progress['progress_percent']; ?>%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Odznaczenia -->
                    <div>
                        <h5 style="margin: 0 0 10px 0;">🎖️ Odznaczenia (<?php echo $progress['achievement_count']; ?>)</h5>
                        
                        <?php if ($progress['achievements']): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">
                            <?php 
                            $type_colors = array(
                                'bronze' => '#CD7F32',
                                'silver' => '#C0C0C0',
                                'gold' => '#FFD700',
                                'platinum' => '#E5E4E2'
                            );
                            foreach (array_slice($progress['achievements'], 0, 6) as $ach): 
                            ?>
                            <div style="padding: 12px; background: white; border: 2px solid <?php echo $type_colors[$ach->type] ?? '#e2e8f0'; ?>; border-radius: 8px;">
                                <div style="font-size: 24px; margin-bottom: 5px;"><?php echo $ach->icon; ?></div>
                                <div style="font-size: 13px; font-weight: 600; color: #1e293b;"><?php echo esc_html($ach->name); ?></div>
                                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">+<?php echo $ach->points; ?> pkt</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($progress['achievements']) > 6): ?>
                        <div style="text-align: center; margin-top: 10px;">
                            <small style="color: #64748b;">... i <?php echo count($progress['achievements']) - 6; ?> więcej</small>
                        </div>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <p style="color: #64748b; font-size: 14px; margin: 0;">Brak odznaczeń. Zachęcamy do aktywności na zajęciach!</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="ssm-child-courses">
                    <h4>Kursy (<?php echo count($child_enrollments); ?>)</h4>
                    <?php if ($child_enrollments): ?>
                        <div class="ssm-courses-grid">
                            <?php foreach ($child_enrollments as $enrollment): ?>
                                <div class="ssm-course-badge">
                                    <strong><?php echo esc_html($enrollment->class_name); ?></strong><br>
                                    <small>
                                        <?php echo esc_html($enrollment->facility_name); ?> • 
                                        <?php echo $days_pl[$enrollment->day_of_week]; ?> 
                                        <?php echo substr($enrollment->time_start, 0, 5); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color:#6c757d; font-size:14px;">Brak aktywnych kursów</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="ssm-no-children">
            <p>Nie masz przypisanych dzieci.</p>
            <p><small>Skontaktuj się z administracją aby dodać dzieci do systemu.</small></p>
        </div>
    <?php endif; ?>
</div>

<style>
.ssm-form-container {
    background: white;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 25px;
    border: 2px solid #3498db;
}

.ssm-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.ssm-form-header h3 {
    margin: 0;
    color: #2c3e50;
}

.ssm-btn-close {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    border-radius: 50%;
    text-decoration: none;
    color: #6c757d;
    font-size: 18px;
    transition: all 0.3s;
}

.ssm-btn-close:hover {
    background: #dc3545;
    color: white;
}

.ssm-children-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.ssm-child-panel {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ssm-child-header {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.ssm-child-avatar-lg {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    font-weight: bold;
    flex-shrink: 0;
}

.ssm-child-info {
    flex: 1;
}

.ssm-child-info h3 {
    margin: 0 0 8px 0;
    font-size: 22px;
}

.ssm-child-info p {
    margin: 0;
    opacity: 0.9;
    font-size: 14px;
}

.ssm-medical-note {
    margin-top: 10px;
    padding: 10px;
    background: rgba(255,255,255,0.2);
    border-radius: 4px;
    font-size: 14px;
}

.ssm-btn-edit {
    padding: 10px 20px;
    background: white;
    color: #667eea;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    transition: all 0.3s;
}

.ssm-btn-edit:hover {
    background: rgba(255,255,255,0.9);
    transform: translateY(-2px);
}

.ssm-child-courses {
    padding: 25px;
}

.ssm-child-courses h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.ssm-courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.ssm-course-badge {
    padding: 15px;
    background: #f8f9fa;
    border-left: 4px solid #3498db;
    border-radius: 4px;
}

.ssm-course-badge strong {
    color: #2c3e50;
}

.ssm-course-badge small {
    color: #6c757d;
}

.ssm-btn-secondary {
    padding: 12px 30px;
    background: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    display: inline-block;
    transition: all 0.3s;
}

.ssm-btn-secondary:hover {
    background: #545b62;
}

.ssm-no-children {
    background: white;
    padding: 40px;
    border-radius: 8px;
    text-align: center;
    color: #6c757d;
}

@media (max-width: 768px) {
    .ssm-child-header {
        flex-direction: column;
        text-align: center;
    }
    
    .ssm-courses-grid {
        grid-template-columns: 1fr;
    }
}
</style>
