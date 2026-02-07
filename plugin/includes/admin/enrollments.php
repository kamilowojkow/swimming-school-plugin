<?php
// Panel Zapisy - PEŁNA WERSJA
if (!defined('ABSPATH')) exit;

global $wpdb;

// Pobierz wszystkie zapisy z pełnymi danymi
$enrollments = $wpdb->get_results("
    SELECT e.*,
           CONCAT(cl.first_name, ' ', cl.last_name) as client_name,
           CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
           TIMESTAMPDIFF(YEAR, ch.date_of_birth, CURDATE()) as child_age,
           c.name as class_name,
           c.total_price,
           c.start_date,
           c.session_count,
           f.name as facility_name,
           CONCAT(i.first_name, ' ', i.last_name) as instructor_name
    FROM {$wpdb->prefix}ssm_enrollments e
    LEFT JOIN {$wpdb->prefix}ssm_clients cl ON e.client_id = cl.id
    LEFT JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
    LEFT JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
    LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
    LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
    ORDER BY e.enrollment_date DESC
");

// Pobierz rodziców dla selecta
$clients = $wpdb->get_results("
    SELECT id, first_name, last_name, email 
    FROM {$wpdb->prefix}ssm_clients 
    ORDER BY last_name, first_name
");

// Pobierz kursy aktywne Z OBLICZANIEM POZOSTAŁYCH ZAJĘĆ
$classes = $wpdb->get_results("
    SELECT c.*, f.name as facility_name,
           CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments WHERE class_id = c.id AND status = 'active') as enrolled_count,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions 
            WHERE class_id = c.id AND session_date < CURDATE() AND status != 'cancelled') as sessions_past,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions 
            WHERE class_id = c.id AND session_date >= CURDATE() AND status = 'scheduled') as sessions_remaining
    FROM {$wpdb->prefix}ssm_classes c
    LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
    LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
    WHERE c.status = 'active'
    ORDER BY c.start_date DESC
");

// Dla każdego kursu oblicz proporcjonalną cenę
foreach ($classes as $class) {
    $class->sessions_past = (int)$class->sessions_past;
    $class->sessions_remaining = (int)$class->sessions_remaining;
    
    // Jeśli brak sesji, użyj domyślnych wartości z kursu
    if ($class->sessions_remaining == 0 && $class->sessions_past == 0) {
        $class->sessions_remaining = $class->session_count;
    }
    
    // Oblicz cenę proporcjonalną (tylko za pozostałe zajęcia)
    if ($class->sessions_remaining > 0) {
        $class->price_proportional = $class->price_per_session * $class->sessions_remaining;
    } else {
        $class->price_proportional = 0;
    }
}

$days_pl = array(1 => 'Poniedziałek', 2 => 'Wtorek', 3 => 'Środa', 4 => 'Czwartek', 5 => 'Piątek', 6 => 'Sobota', 7 => 'Niedziela');
?>

<div class="wrap">
    <h1>Zapisy
        <button type="button" class="page-title-action" id="ssm-add-enrollment-btn">Zapisz dziecko na kurs</button>
    </h1>
    
    <!-- Formularz zapisu -->
    <div id="ssm-enrollment-form-container" style="display:none; margin:20px 0;">
        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; max-width:700px;">
            <h2>Zapisz dziecko na kurs</h2>
            <form id="ssm-enrollment-form">
                
                <table class="form-table">
                    <tr>
                        <th><label for="enrollment-client">1. Wybierz rodzica (płatnik) *</label></th>
                        <td>
                            <select id="enrollment-client" name="client_id" class="regular-text" required>
                                <option value="">-- Wybierz rodzica --</option>
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
                        <th><label for="enrollment-child">2. Wybierz dziecko *</label></th>
                        <td>
                            <select id="enrollment-child" name="child_id" class="regular-text" required disabled>
                                <option value="">-- Najpierw wybierz rodzica --</option>
                            </select>
                            <p class="description">Wybierz jedno z dzieci przypisanych do rodzica</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enrollment-class">3. Wybierz kurs *</label></th>
                        <td>
                            <select id="enrollment-class" name="class_id" class="regular-text" required>
                                <option value="">-- Wybierz kurs --</option>
                                <?php foreach ($classes as $class): 
                                    $is_full = ($class->enrolled_count >= $class->max_participants);
                                    $has_sessions = ($class->sessions_remaining > 0);
                                ?>
                                <option value="<?php echo $class->id; ?>" 
                                        data-price="<?php echo $class->price_proportional; ?>"
                                        data-price-full="<?php echo $class->total_price; ?>"
                                        data-facility="<?php echo esc_attr($class->facility_name); ?>"
                                        data-instructor="<?php echo esc_attr($class->instructor_name); ?>"
                                        data-day="<?php echo $class->day_of_week; ?>"
                                        data-time="<?php echo substr($class->time_start, 0, 5); ?>"
                                        data-start="<?php echo date('d.m.Y', strtotime($class->start_date)); ?>"
                                        data-sessions="<?php echo $class->sessions_remaining; ?>"
                                        data-sessions-past="<?php echo $class->sessions_past; ?>"
                                        <?php echo ($is_full || !$has_sessions) ? 'disabled' : ''; ?>>
                                    <?php echo esc_html($class->name); ?>
                                    <?php if ($class->sessions_past > 0): ?>
                                        - Pozostało: <?php echo $class->sessions_remaining; ?>/<?php echo $class->session_count; ?> zajęć
                                    <?php else: ?>
                                        - <?php echo $class->session_count; ?> zajęć
                                    <?php endif; ?>
                                    - <?php echo number_format($class->price_proportional, 2); ?> PLN
                                    (<?php echo $class->enrolled_count; ?>/<?php echo $class->max_participants; ?>)
                                    <?php if ($is_full): ?>
                                        - PEŁNY
                                    <?php elseif (!$has_sessions): ?>
                                        - ZAKOŃCZONY
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Cena jest proporcjonalna - płacisz tylko za pozostałe zajęcia</p>
                        </td>
                    </tr>
                    <tr id="enrollment-class-details" style="display:none;">
                        <th><label>Szczegóły kursu</label></th>
                        <td>
                            <div style="background:#f0f6fc; padding:15px; border-left:4px solid #2271b1;">
                                <p style="margin:5px 0;"><strong>Obiekt:</strong> <span id="detail-facility"></span></p>
                                <p style="margin:5px 0;"><strong>Instruktor:</strong> <span id="detail-instructor"></span></p>
                                <p style="margin:5px 0;"><strong>Termin:</strong> <span id="detail-schedule"></span></p>
                                <p style="margin:5px 0;"><strong>Start:</strong> <span id="detail-start"></span></p>
                                <p style="margin:5px 0;" id="detail-sessions-info">
                                    <strong>Pozostałe zajęcia:</strong> 
                                    <span id="detail-sessions"></span>
                                    <span id="detail-sessions-past" style="color:#6c757d;"></span>
                                </p>
                                <p style="margin:5px 0; font-size:18px;">
                                    <strong>Cena do zapłaty:</strong> 
                                    <span id="detail-price" style="color:#2271b1;"></span> PLN
                                    <span id="detail-price-info" style="font-size:14px; color:#6c757d;"></span>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enrollment-notes">Notatki</label></th>
                        <td><textarea id="enrollment-notes" name="notes" class="large-text" rows="3"></textarea></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">✅ Zapisz na kurs</button>
                    <button type="button" class="button button-large" id="ssm-cancel-enrollment-btn">Anuluj</button>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Statystyki -->
    <div style="background:#fff; border:1px solid #ccd0d4; padding:15px; margin:20px 0;">
        <strong>Aktywne zapisy:</strong> <?php echo count(array_filter($enrollments, function($e) { return $e->status == 'active'; })); ?> |
        <strong>Zakończone:</strong> <?php echo count(array_filter($enrollments, function($e) { return $e->status == 'completed'; })); ?> |
        <strong>Anulowane:</strong> <?php echo count(array_filter($enrollments, function($e) { return $e->status == 'cancelled'; })); ?>
    </div>
    
    <!-- Lista zapisów -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th>Dziecko</th>
                <th>Wiek</th>
                <th>Rodzic (płatnik)</th>
                <th>Kurs</th>
                <th>Obiekt</th>
                <th>Instruktor</th>
                <th>Cena</th>
                <th>Data zapisu</th>
                <th>Status</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($enrollments): ?>
                <?php foreach ($enrollments as $enrollment): ?>
                <tr>
                    <td><?php echo $enrollment->id; ?></td>
                    <td><strong><?php echo esc_html($enrollment->child_name); ?></strong></td>
                    <td><?php echo $enrollment->child_age; ?> lat</td>
                    <td><?php echo esc_html($enrollment->client_name); ?></td>
                    <td>
                        <?php echo esc_html($enrollment->class_name); ?><br>
                        <small><?php echo $enrollment->session_count; ?> zajęć</small>
                    </td>
                    <td><?php echo esc_html($enrollment->facility_name); ?></td>
                    <td><?php echo esc_html($enrollment->instructor_name); ?></td>
                    <td>
                        <strong style="color:#2271b1;">
                            <?php echo number_format($enrollment->total_price, 2); ?> PLN
                        </strong>
                    </td>
                    <td><?php echo date('d.m.Y', strtotime($enrollment->enrollment_date)); ?></td>
                    <td>
                        <?php if ($enrollment->status == 'active'): ?>
                            <span class="ssm-badge ssm-badge-active">✓ Aktywny</span>
                        <?php elseif ($enrollment->status == 'completed'): ?>
                            <span class="ssm-badge ssm-badge-completed">✓ Zakończony</span>
                        <?php else: ?>
                            <span class="ssm-badge ssm-badge-cancelled">× Anulowany</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($enrollment->status == 'active'): ?>
                            <button class="button button-small ssm-unenroll" 
                                    data-id="<?php echo $enrollment->id; ?>"
                                    data-child="<?php echo esc_attr($enrollment->child_name); ?>"
                                    data-class="<?php echo esc_attr($enrollment->class_name); ?>">
                                Wypisz
                            </button>
                        <?php endif; ?>
                        
                        <button class="button button-small button-link-delete ssm-delete-enrollment" 
                                data-id="<?php echo $enrollment->id; ?>">
                            Usuń
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11">Brak zapisów. Zapisz pierwsze dziecko na kurs!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Wybór rodzica → załaduj jego dzieci
    $('#enrollment-client').on('change', function() {
        var clientId = $(this).val();
        
        if (!clientId) {
            $('#enrollment-child').html('<option value="">-- Najpierw wybierz rodzica --</option>').prop('disabled', true);
            return;
        }
        
        $('#enrollment-child').html('<option value="">Ładowanie...</option>').prop('disabled', true);
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_get_client_children',
            nonce: ssmAdmin.nonce,
            client_id: clientId
        }, function(response) {
            if (response.success && response.data.length > 0) {
                var html = '<option value="">-- Wybierz dziecko --</option>';
                response.data.forEach(function(child) {
                    html += '<option value="' + child.id + '">' + child.name + ' (' + child.age + ' lat)</option>';
                });
                $('#enrollment-child').html(html).prop('disabled', false);
            } else {
                $('#enrollment-child').html('<option value="">Ten rodzic nie ma przypisanych dzieci</option>');
            }
        });
    });
    
    // Wybór kursu → pokaż szczegóły
    $('#enrollment-class').on('change', function() {
        var option = $(this).find('option:selected');
        
        if (!option.val()) {
            $('#enrollment-class-details').hide();
            return;
        }
        
        $('#detail-facility').text(option.data('facility'));
        $('#detail-instructor').text(option.data('instructor'));
        
        var days = ['', 'Poniedziałki', 'Wtorki', 'Środy', 'Czwartki', 'Piątki', 'Soboty', 'Niedziele'];
        $('#detail-schedule').text(days[option.data('day')] + ' ' + option.data('time'));
        $('#detail-start').text(option.data('start'));
        
        var sessionsRemaining = parseInt(option.data('sessions'));
        var sessionsPast = parseInt(option.data('sessions-past'));
        var price = parseFloat(option.data('price'));
        var priceFull = parseFloat(option.data('price-full'));
        
        // Pokaż info o zajęciach
        $('#detail-sessions').text(sessionsRemaining);
        if (sessionsPast > 0) {
            $('#detail-sessions-past').text('(z ' + (sessionsRemaining + sessionsPast) + ' zajęć w kursie, ' + sessionsPast + ' już minęło)');
        } else {
            $('#detail-sessions-past').text('');
        }
        
        // Pokaż cenę proporcjonalną
        $('#detail-price').text(price.toFixed(2));
        if (sessionsPast > 0 && price < priceFull) {
            $('#detail-price-info').html('<br>(cena oryginalna: ' + priceFull.toFixed(2) + ' PLN, płacisz tylko za pozostałe zajęcia)');
        } else {
            $('#detail-price-info').text('');
        }
        
        $('#enrollment-class-details').show();
    });
});
</script>

