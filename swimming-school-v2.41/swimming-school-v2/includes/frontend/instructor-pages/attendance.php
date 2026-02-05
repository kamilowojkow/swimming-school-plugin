<?php
/**
 * Panel Instruktora - Frekwencja (Sprawdzanie obecności)
 */
if (!defined('ABSPATH')) exit;

// Obsługa zapisu frekwencji
if (isset($_POST['ssm_save_attendance']) && isset($_POST['session_id'])) {
    $session_id = intval($_POST['session_id']);
    $attendance_data = isset($_POST['attendance']) ? $_POST['attendance'] : array();
    
    foreach ($attendance_data as $child_id => $status) {
        $child_id = intval($child_id);
        $status = sanitize_text_field($status);
        
        // Sprawdź czy już istnieje
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ssm_attendance 
             WHERE session_id = %d AND child_id = %d",
            $session_id, $child_id
        ));
        
        if ($existing) {
            $wpdb->update(
                $wpdb->prefix . 'ssm_attendance',
                array(
                    'status' => $status,
                    'marked_at' => current_time('mysql'),
                    'marked_by' => $current_user->ID
                ),
                array('id' => $existing)
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'ssm_attendance',
                array(
                    'session_id' => $session_id,
                    'child_id' => $child_id,
                    'status' => $status,
                    'marked_at' => current_time('mysql'),
                    'marked_by' => $current_user->ID
                )
            );
        }
        
        // Hook: Gamifikacja - automatyczne punkty za obecność
        if ($status == 'present') {
            do_action('ssm_attendance_marked', $child_id, $session_id, $status);
        }
    }
    
    // Policz ile dzieci dostało punkty
    $points_awarded = 0;
    foreach ($attendance_data as $child_id => $status) {
        if ($status == 'present') {
            $points_awarded++;
        }
    }
    
    echo '<div class="ssm-notice ssm-notice-success" style="background: #d1fae5; border-left: 4px solid #10b981; padding: 20px; margin-bottom: 20px; border-radius: 8px; color: #064e3b;">
        <div style="font-size: 16px; font-weight: 600; margin-bottom: 10px;">✅ Frekwencja została zapisana!</div>
        <div style="font-size: 14px;">
            🏆 Dzieci obecne na zajęciach otrzymały <strong>+5 punktów</strong> każde! 
            (' . $points_awarded . ' ' . ($points_awarded == 1 ? 'dziecko' : 'dzieci') . ')
        </div>
    </div>';
}

// Pobierz session_id z GET lub POST
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : (isset($_POST['session_id']) ? intval($_POST['session_id']) : 0);

if ($session_id) {
    // Pobierz szczegóły sesji
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, c.name as class_name, c.time_start, c.time_end,
                f.name as facility_name, c.instructor_id
         FROM {$wpdb->prefix}ssm_sessions s
         JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
         LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
         WHERE s.id = %d",
        $session_id
    ));
    
    // Sprawdź uprawnienia
    if (!$session || $session->instructor_id != $instructor->id) {
        echo '<div class="ssm-notice ssm-notice-error">Brak uprawnień do tych zajęć.</div>';
        return;
    }
    
    // Pobierz listę dzieci
    $children = $wpdb->get_results($wpdb->prepare(
        "SELECT ch.id, ch.first_name, ch.last_name,
                CONCAT(cl.first_name, ' ', cl.last_name) as parent_name,
                a.status as attendance_status,
                (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences 
                 WHERE session_id = %d AND child_id = ch.id) as has_absence_report
         FROM {$wpdb->prefix}ssm_enrollments e
         JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
         JOIN {$wpdb->prefix}ssm_clients cl ON e.client_id = cl.id
         LEFT JOIN {$wpdb->prefix}ssm_attendance a ON a.session_id = %d AND a.child_id = ch.id
         WHERE e.class_id = %d AND e.status = 'active'
         ORDER BY ch.last_name, ch.first_name",
        $session_id, $session_id, $session->class_id
    ));
    
    $date = new DateTime($session->session_date);
    ?>
    
    <div class="ssm-page-header">
        <div>
            <h1>✓ Sprawdzanie obecności</h1>
            <p style="color: #64748b; margin: 8px 0 0 0;">
                <strong><?php echo esc_html($session->class_name); ?></strong> • 
                <?php echo $date->format('d.m.Y'); ?> (<?php echo ssm_get_day_name_pl($date); ?>) • 
                <?php echo substr($session->time_start, 0, 5); ?>-<?php echo substr($session->time_end, 0, 5); ?> • 
                <?php echo esc_html($session->facility_name); ?>
            </p>
        </div>
    </div>
    
    <?php if (empty($children)): ?>
        <div style="background: #fef3c7; padding: 20px; border-radius: 12px; border-left: 4px solid #f59e0b; color: #78350f;">
            ⚠️ Brak zapisanych dzieci na ten kurs.
        </div>
    <?php else: ?>
        
        <form method="post" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <input type="hidden" name="ssm_save_attendance" value="1">
            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
            
            <!-- Lista dzieci -->
            <div style="margin-bottom: 30px;">
                <table class="wp-list-table widefat" style="border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <th style="padding: 12px; width: 5%;">#</th>
                            <th style="width: 25%;">Imię i nazwisko</th>
                            <th style="width: 20%;">Rodzic</th>
                            <th style="width: 15%;">Zgłoszenie</th>
                            <th style="width: 35%;">Obecność</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($children as $child): 
                            $default_status = 'present';
                            if ($child->has_absence_report > 0) {
                                $default_status = 'excused';
                            } elseif ($child->attendance_status) {
                                $default_status = $child->attendance_status;
                            }
                        ?>
                        <tr>
                            <td style="padding: 12px; text-align: center;"><?php echo $counter++; ?></td>
                            <td><strong><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></strong></td>
                            <td><?php echo esc_html($child->parent_name); ?></td>
                            <td>
                                <?php if ($child->has_absence_report > 0): ?>
                                    <span style="background: #fef3c7; color: #78350f; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                        ⚠️ Zgłoszona
                                    </span>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 13px;">Brak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 10px;">
                                    <label class="ssm-radio-label" style="flex: 1; padding: 10px; border: 2px solid #10b981; border-radius: 8px; cursor: pointer; text-align: center; background: <?php echo ($default_status == 'present') ? '#d1fae5' : 'white'; ?>; transition: all 0.2s;">
                                        <input type="radio" name="attendance[<?php echo $child->id; ?>]" value="present" 
                                               <?php checked($default_status, 'present'); ?>
                                               onchange="this.parentElement.parentElement.querySelectorAll('label').forEach(l => l.style.background='white'); this.parentElement.style.background='#d1fae5';"
                                               style="margin-right: 5px;">
                                        <span style="font-weight: 600; color: #059669;">✅ Obecny</span>
                                    </label>
                                    
                                    <label class="ssm-radio-label" style="flex: 1; padding: 10px; border: 2px solid #f59e0b; border-radius: 8px; cursor: pointer; text-align: center; background: <?php echo ($default_status == 'excused') ? '#fef3c7' : 'white'; ?>; transition: all 0.2s;">
                                        <input type="radio" name="attendance[<?php echo $child->id; ?>]" value="excused" 
                                               <?php checked($default_status, 'excused'); ?>
                                               onchange="this.parentElement.parentElement.querySelectorAll('label').forEach(l => l.style.background='white'); this.parentElement.style.background='#fef3c7';"
                                               style="margin-right: 5px;">
                                        <span style="font-weight: 600; color: #d97706;">⚠️ Zgłoszony</span>
                                    </label>
                                    
                                    <label class="ssm-radio-label" style="flex: 1; padding: 10px; border: 2px solid #ef4444; border-radius: 8px; cursor: pointer; text-align: center; background: <?php echo ($default_status == 'absent') ? '#fee2e2' : 'white'; ?>; transition: all 0.2s;">
                                        <input type="radio" name="attendance[<?php echo $child->id; ?>]" value="absent" 
                                               <?php checked($default_status, 'absent'); ?>
                                               onchange="this.parentElement.parentElement.querySelectorAll('label').forEach(l => l.style.background='white'); this.parentElement.style.background='#fee2e2';"
                                               style="margin-right: 5px;">
                                        <span style="font-weight: 600; color: #dc2626;">❌ Nieobecny</span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Statystyki -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <div style="background: #f0fdf4; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">
                    <div style="font-size: 13px; color: #064e3b; font-weight: 600; margin-bottom: 5px;">Zapisanych</div>
                    <div style="font-size: 28px; font-weight: 700; color: #059669;"><?php echo count($children); ?></div>
                </div>
                
                <div style="background: #f0fdf4; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">
                    <div style="font-size: 13px; color: #064e3b; font-weight: 600; margin-bottom: 5px;">Obecnych</div>
                    <div style="font-size: 28px; font-weight: 700; color: #059669;">
                        <span class="present-count">0</span>
                    </div>
                </div>
                
                <div style="background: #fffbeb; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                    <div style="font-size: 13px; color: #78350f; font-weight: 600; margin-bottom: 5px;">Zgłoszone nieobecności</div>
                    <div style="font-size: 28px; font-weight: 700; color: #d97706;">
                        <span class="excused-count">0</span>
                    </div>
                </div>
                
                <div style="background: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444;">
                    <div style="font-size: 13px; color: #7f1d1d; font-weight: 600; margin-bottom: 5px;">Nieobecnych</div>
                    <div style="font-size: 28px; font-weight: 700; color: #dc2626;">
                        <span class="absent-count">0</span>
                    </div>
                </div>
            </div>
            
            <!-- Przyciski -->
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <a href="?instructor_page=schedule" 
                   style="padding: 12px 24px; border-radius: 8px; text-decoration: none; background: #f1f5f9; color: #475569; font-weight: 600;">
                    ← Wróć do harmonogramu
                </a>
                <button type="submit" 
                        style="padding: 12px 32px; border-radius: 8px; border: none; cursor: pointer; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; font-size: 16px;">
                    💾 Zapisz frekwencję
                </button>
            </div>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            function updateCounts() {
                var presentCount = $('input[value="present"]:checked').length;
                var excusedCount = $('input[value="excused"]:checked').length;
                var absentCount = $('input[value="absent"]:checked').length;
                
                $('.present-count').text(presentCount);
                $('.excused-count').text(excusedCount);
                $('.absent-count').text(absentCount);
            }
            
            $('input[type="radio"]').on('change', updateCounts);
            updateCounts();
        });
        </script>
        
    <?php endif; ?>
    
<?php } else { ?>
    
    <!-- Brak wybranej sesji - pokaż listę do wyboru -->
    <div class="ssm-page-header">
        <h1>✓ Frekwencja</h1>
        <p>Wybierz zajęcia aby sprawdzić obecność uczestników.</p>
    </div>
    
    <div style="background: #eff6ff; padding: 20px; border-radius: 12px; border-left: 4px solid #3b82f6; color: #1e3a8a;">
        ℹ️ Przejdź do zakładki <a href="?instructor_page=schedule" style="color: #1e40af; font-weight: 600;">Moje zajęcia</a> 
        i kliknij "Sprawdź obecność" przy wybranych zajęciach.
    </div>
    
<?php } ?>
