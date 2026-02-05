<?php
/**
 * Panel Instruktora - Zastępstwa
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

// Policz dostępne zastępstwa dla badge
$available_count = count($available_substitutions);

$status_colors = array(
    'pending' => array('bg' => '#fef3c7', 'text' => '#d97706', 'label' => 'Oczekuje'),
    'covered' => array('bg' => '#d1fae5', 'text' => '#059669', 'label' => 'Zastąpione'),
    'cancelled' => array('bg' => '#fee2e2', 'text' => '#dc2626', 'label' => 'Odwołane')
);
?>

<div class="ssm-page-header">
    <h1>🔄 Zastępstwa</h1>
    <p>Zgłaszaj niedyspozycje i przejmuj zastępstwa za innych instruktorów</p>
</div>

<!-- Tabs -->
<div style="margin-bottom: 30px; border-bottom: 2px solid #e2e8f0;">
    <div style="display: flex; gap: 0;">
        <button class="ssm-tab-btn active" data-tab="my-unavailability" 
                style="padding: 15px 30px; border: none; background: none; cursor: pointer; border-bottom: 3px solid #667eea; font-weight: 600; color: #667eea;">
            📋 Moje niedyspozycje
        </button>
        <button class="ssm-tab-btn" data-tab="available" 
                style="padding: 15px 30px; border: none; background: none; cursor: pointer; border-bottom: 3px solid transparent; font-weight: 600; color: #64748b;">
            🆘 Dostępne zastępstwa
            <?php if ($available_count > 0): ?>
                <span style="background: #dc2626; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: 5px;">
                    <?php echo $available_count; ?>
                </span>
            <?php endif; ?>
        </button>
    </div>
</div>

<!-- Tab: Moje niedyspozycje -->
<div class="ssm-tab-content" data-tab="my-unavailability">
    
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin: 0 0 15px 0;">ℹ️ Jak zgłosić niedyspozycję?</h3>
        <p style="margin: 0; color: #64748b; line-height: 1.6;">
            Jeśli nie możesz poprowadzić swoich zajęć, przejdź do zakładki <strong>Moje zajęcia</strong> 
            i kliknij przycisk <strong>[🚫 Zgłoś niedyspozycję]</strong> przy konkretnych zajęciach. 
            Inni instruktorzy zostaną powiadomieni emailem o dostępnym zastępstwie.
        </p>
    </div>
    
    <?php if (empty($my_unavailability)): ?>
        <div style="background: white; padding: 40px; text-align: center; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 16px;">
                📋 Nie zgłosiłeś jeszcze żadnej niedyspozycji
            </p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($my_unavailability as $unav): 
                $date = new DateTime($unav->session_date);
                $is_future = ($unav->session_date >= date('Y-m-d'));
                $status = $status_colors[$unav->status] ?? $status_colors['pending'];
            ?>
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: start; gap: 20px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 10px 0; color: #1e293b;">
                            <?php echo esc_html($unav->class_name); ?>
                        </h3>
                        <div style="display: flex; gap: 20px; flex-wrap: wrap; color: #64748b; font-size: 14px;">
                            <span>📅 <?php echo $date->format('d.m.Y'); ?> (<?php echo ssm_get_day_name_pl($date); ?>)</span>
                            <span>⏰ <?php echo substr($unav->time_start, 0, 5); ?>-<?php echo substr($unav->time_end, 0, 5); ?></span>
                            <span>📍 <?php echo esc_html($unav->facility_name); ?></span>
                        </div>
                        <?php if ($unav->reason): ?>
                            <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border-left: 3px solid #64748b; border-radius: 4px;">
                                <strong>Powód:</strong> <?php echo esc_html($unav->reason); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span style="background: <?php echo $status['bg']; ?>; color: <?php echo $status['text']; ?>; padding: 8px 16px; border-radius: 12px; font-weight: 600; white-space: nowrap;">
                        <?php echo $status['label']; ?>
                    </span>
                </div>
                
                <?php if ($unav->status == 'covered' && $unav->replacement_name): ?>
                    <div style="padding: 15px; background: #d1fae5; border-radius: 8px; margin-top: 15px;">
                        <strong style="color: #059669;">✅ Zastępstwo przejęte przez:</strong>
                        <span style="color: #059669;"><?php echo esc_html($unav->replacement_name); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($unav->status == 'pending' && $is_future): ?>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                        <button type="button" class="ssm-cancel-unavailability" 
                                data-id="<?php echo $unav->id; ?>"
                                style="padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            ❌ Anuluj zgłoszenie
                        </button>
                        <small style="color: #64748b; margin-left: 15px;">
                            Jeśli jednak możesz poprowadzić zajęcia
                        </small>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Tab: Dostępne zastępstwa -->
<div class="ssm-tab-content" data-tab="available" style="display: none;">
    
    <?php if (empty($available_substitutions)): ?>
        <div style="background: white; padding: 40px; text-align: center; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 16px;">
                🆘 Brak dostępnych zastępstw
            </p>
            <p style="margin: 10px 0 0 0; color: #999; font-size: 14px;">
                Gdy inny instruktor zgłosi niedyspozycję, zobaczysz to tutaj
            </p>
        </div>
    <?php else: ?>
        <div style="background: #fef3c7; padding: 20px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #f59e0b;">
            <strong style="color: #d97706;">⚠️ Dostępne zastępstwa: <?php echo $available_count; ?></strong>
            <p style="margin: 5px 0 0 0; color: #92400e;">
                Instruktorzy potrzebują zastępstwa. Jeśli możesz pomóc, kliknij "Wezmę zastępstwo".
            </p>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($available_substitutions as $sub): 
                $date = new DateTime($sub->session_date);
            ?>
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: start; gap: 20px;">
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 10px 0; color: #1e293b;">
                            <?php echo esc_html($sub->class_name); ?>
                        </h3>
                        <div style="display: flex; gap: 20px; flex-wrap: wrap; color: #64748b; font-size: 14px; margin-bottom: 10px;">
                            <span>📅 <?php echo $date->format('d.m.Y'); ?> (<?php echo ssm_get_day_name_pl($date); ?>)</span>
                            <span>⏰ <?php echo substr($sub->time_start, 0, 5); ?>-<?php echo substr($sub->time_end, 0, 5); ?></span>
                            <span>📍 <?php echo esc_html($sub->facility_name); ?></span>
                        </div>
                        <div style="padding: 10px; background: #f8fafc; border-radius: 8px; margin-bottom: 10px;">
                            <strong>👤 Zastępstwo za:</strong> <?php echo esc_html($sub->instructor_name); ?>
                        </div>
                        <?php if ($sub->reason): ?>
                            <div style="padding: 10px; background: #f8fafc; border-left: 3px solid #64748b; border-radius: 4px;">
                                <strong>Powód:</strong> <?php echo esc_html($sub->reason); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="ssm-take-substitution" 
                            data-id="<?php echo $sub->id; ?>"
                            data-date="<?php echo $date->format('d.m.Y'); ?>"
                            data-time="<?php echo substr($sub->time_start, 0, 5); ?>"
                            data-class="<?php echo esc_attr($sub->class_name); ?>"
                            style="padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; white-space: nowrap;">
                        ✅ Wezmę zastępstwo
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    // Tabs
    $('.ssm-tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        
        // Update buttons
        $('.ssm-tab-btn').css({
            'border-bottom': '3px solid transparent',
            'color': '#64748b'
        }).removeClass('active');
        
        $(this).css({
            'border-bottom': '3px solid #667eea',
            'color': '#667eea'
        }).addClass('active');
        
        // Show content
        $('.ssm-tab-content').hide();
        $('.ssm-tab-content[data-tab="' + tab + '"]').show();
    });
    
    // Anuluj niedyspozycję
    $('.ssm-cancel-unavailability').on('click', function() {
        var btn = $(this);
        var unavailabilityId = btn.data('id');
        
        if (!confirm('Czy na pewno chcesz anulować zgłoszenie niedyspozycji?\nInni instruktorzy nie będą już widzieć tego zastępstwa.')) {
            return;
        }
        
        btn.prop('disabled', true).text('Anuluję...');
        
        $.post(ajaxurl, {
            action: 'ssm_cancel_unavailability',
            unavailability_id: unavailabilityId
        })
        .done(function(response) {
            if (response.success) {
                alert('✅ Zgłoszenie anulowane');
                location.reload();
            } else {
                alert('❌ ' + response.data);
                btn.prop('disabled', false).text('❌ Anuluj zgłoszenie');
            }
        })
        .fail(function(xhr, status, error) {
            console.log('AJAX Error:', xhr.responseText);
            alert('❌ Błąd połączenia: ' + error);
            btn.prop('disabled', false).text('❌ Anuluj zgłoszenie');
        });
    });
    
    // Weź zastępstwo
    $('.ssm-take-substitution').on('click', function() {
        var btn = $(this);
        var unavailabilityId = btn.data('id');
        var date = btn.data('date');
        var time = btn.data('time');
        var className = btn.data('class');
        
        if (!confirm('Czy chcesz przejąć zastępstwo?\n\nZajęcia: ' + className + '\nData: ' + date + '\nGodzina: ' + time + '\n\nPo potwierdzeniu zostaniesz przypisany do tych zajęć.')) {
            return;
        }
        
        btn.prop('disabled', true).text('Przypisuję...');
        
        $.post(ajaxurl, {
            action: 'ssm_take_substitution',
            unavailability_id: unavailabilityId
        })
        .done(function(response) {
            if (response.success) {
                alert('✅ Zastępstwo przejęte!\n\nZajęcia zostały dodane do Twojego harmonogramu.');
                location.reload();
            } else {
                alert('❌ ' + response.data);
                btn.prop('disabled', false).text('✅ Wezmę zastępstwo');
            }
        })
        .fail(function(xhr, status, error) {
            console.log('AJAX Error:', xhr.responseText);
            alert('❌ Błąd połączenia: ' + error + '\n\nSprawdź konsolę (F12) dla szczegółów.\n\nOdpowiedź serwera:\n' + xhr.responseText.substring(0, 500));
            btn.prop('disabled', false).text('✅ Wezmę zastępstwo');
        });
    });
});
</script>
