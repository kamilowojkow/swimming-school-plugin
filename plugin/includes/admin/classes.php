<?php
// Panel Kursy - PEŁNA WERSJA
if (!defined('ABSPATH')) exit;

global $wpdb;

// Pobierz kursy z pełnymi danymi
$classes = $wpdb->get_results("
    SELECT c.*, 
           f.name as facility_name,
           CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments WHERE class_id = c.id AND status = 'active') as enrolled_count
    FROM {$wpdb->prefix}ssm_classes c
    LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
    LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
    ORDER BY c.status DESC, c.start_date DESC
");

// Pobierz obiekty i instruktorów dla selectów
$facilities = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}ssm_facilities ORDER BY name");
$instructors = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}ssm_instructors WHERE active = 1 ORDER BY last_name");

$days_pl = array(1 => 'Poniedziałek', 2 => 'Wtorek', 3 => 'Środa', 4 => 'Czwartek', 5 => 'Piątek', 6 => 'Sobota', 7 => 'Niedziela');
?>

<div class="wrap">
    <h1>Kursy
        <button type="button" class="page-title-action" id="ssm-add-class-btn">Dodaj kurs</button>
    </h1>
    
    <!-- Formularz dodawania/edycji -->
    <div id="ssm-class-form-container" style="display:none; margin:20px 0;">
        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; max-width:700px;">
            <h2 id="ssm-form-title">Dodaj kurs</h2>
            <form id="ssm-class-form">
                <input type="hidden" id="class-id" name="id" value="">
                
                <table class="form-table">
                    <tr>
                        <th><label for="class-name">Nazwa kursu *</label></th>
                        <td><input type="text" id="class-name" name="name" class="regular-text" required 
                                placeholder="np. Pływanie dla dzieci 4-6 lat"></td>
                    </tr>
                    <tr>
                        <th><label for="class-facility">Obiekt (basen) *</label></th>
                        <td>
                            <select id="class-facility" name="facility_id" class="regular-text" required>
                                <option value="">-- Wybierz obiekt --</option>
                                <?php foreach ($facilities as $facility): ?>
                                <option value="<?php echo $facility->id; ?>"><?php echo esc_html($facility->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="class-instructor">Instruktor *</label></th>
                        <td>
                            <select id="class-instructor" name="instructor_id" class="regular-text" required>
                                <option value="">-- Wybierz instruktora --</option>
                                <?php foreach ($instructors as $instructor): ?>
                                <option value="<?php echo $instructor->id; ?>">
                                    <?php echo esc_html($instructor->first_name . ' ' . $instructor->last_name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="class-day">Dzień tygodnia *</label></th>
                        <td>
                            <select id="class-day" name="day_of_week" class="regular-text" required>
                                <option value="">-- Wybierz dzień --</option>
                                <?php foreach ($days_pl as $num => $name): ?>
                                <option value="<?php echo $num; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="class-time-start">Godzina rozpoczęcia *</label></th>
                        <td><input type="time" id="class-time-start" name="time_start" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="class-time-end">Godzina zakończenia *</label></th>
                        <td><input type="time" id="class-time-end" name="time_end" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="class-start-date">Data pierwszych zajęć *</label></th>
                        <td>
                            <input type="date" id="class-start-date" name="start_date" class="regular-text" required>
                            <p class="description">Data pierwszych zajęć kursu</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="class-session-count">Liczba zajęć *</label></th>
                        <td>
                            <input type="number" id="class-session-count" name="session_count" class="small-text" 
                                   value="10" min="1" max="52" required>
                            <p class="description">Ile zajęć w całym kursie (np. 10, 12, 20)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="class-price-session">Cena za zajęcia *</label></th>
                        <td>
                            <input type="number" id="class-price-session" name="price_per_session" class="small-text" 
                                   value="50.00" min="0" step="0.01" required> PLN
                            <p class="description">Cena za jedno zajęcia</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Cena kursu</label></th>
                        <td>
                            <strong id="class-total-price">500.00</strong> PLN
                            <p class="description">Obliczane automatycznie: liczba zajęć × cena za zajęcia</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="class-level">Poziom</label></th>
                        <td>
                            <input type="text" id="class-level" name="level" class="regular-text" 
                                   placeholder="np. początkujący, średniozaawansowany">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="class-max-participants">Max uczestników</label></th>
                        <td><input type="number" id="class-max-participants" name="max_participants" class="small-text" value="10" min="1"></td>
                    </tr>
                    <tr>
                        <th><label for="class-max-absences">Max nieobecności do odrobienia</label></th>
                        <td>
                            <input type="number" id="class-max-absences" name="max_absences" class="small-text" value="2" min="0" max="10">
                            <p class="description">Ile nieobecności rodzic może zgłosić z możliwością odrobienia (0 = brak możliwości)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="class-allow-makeups">Odrabianie zajęć</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="class-allow-makeups" name="allow_makeups" value="1" checked>
                                Zezwól na odrabianie nieobecności
                            </label>
                            <p class="description">Wymaga zgłoszenia min. 24h przed zajęciami</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="class-url">Link do opisu</label></th>
                        <td><input type="url" id="class-url" name="url" class="regular-text" placeholder="https://..."></td>
                    </tr>
                    <tr>
                        <th><label for="class-description">Opis kursu</label></th>
                        <td><textarea id="class-description" name="description" class="large-text" rows="4"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="class-status">Status</label></th>
                        <td>
                            <select id="class-status" name="status" class="regular-text">
                                <option value="active">Aktywny</option>
                                <option value="completed">Zakończony</option>
                                <option value="cancelled">Anulowany</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        💾 Zapisz kurs
                    </button>
                    <button type="button" class="button button-large" id="ssm-cancel-class-btn">Anuluj</button>
                </p>
                
                <div id="ssm-regenerate-info" style="display:none; margin-top:15px; padding:15px; background:#fff3cd; border-left:4px solid #ffc107;">
                    <p style="margin:0;">
                        <strong>ℹ️ Uwaga:</strong> Harmonogram nie został automatycznie zaktualizowany. 
                        <button type="button" class="button" id="ssm-regenerate-from-form" data-class-id="">
                            🔄 Przelicz harmonogram teraz
                        </button>
                    </p>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Lista kursów -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th>Nazwa kursu</th>
                <th>Obiekt</th>
                <th>Instruktor</th>
                <th>Termin</th>
                <th>Data start</th>
                <th>Zajęcia</th>
                <th>Cena</th>
                <th>Zapisy</th>
                <th>Status</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($classes): ?>
                <?php foreach ($classes as $class): ?>
                <tr>
                    <td><?php echo $class->id; ?></td>
                    <td>
                        <strong><?php echo esc_html($class->name); ?></strong>
                        <?php if ($class->level): ?>
                            <br><small><?php echo esc_html($class->level); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($class->facility_name); ?></td>
                    <td><?php echo esc_html($class->instructor_name); ?></td>
                    <td>
                        <?php echo $days_pl[$class->day_of_week]; ?><br>
                        <small><?php echo substr($class->time_start, 0, 5); ?> - <?php echo substr($class->time_end, 0, 5); ?></small>
                    </td>
                    <td><?php echo date('d.m.Y', strtotime($class->start_date)); ?></td>
                    <td><?php echo $class->session_count; ?>x</td>
                    <td>
                        <?php echo number_format($class->price_per_session, 2); ?> PLN<br>
                        <strong><?php echo number_format($class->total_price, 2); ?> PLN</strong>
                    </td>
                    <td>
                        <strong><?php echo $class->enrolled_count; ?></strong> / <?php echo $class->max_participants; ?>
                    </td>
                    <td>
                        <?php if ($class->status == 'active'): ?>
                            <span style="color:#00a32a;">✓ Aktywny</span>
                        <?php elseif ($class->status == 'completed'): ?>
                            <span style="color:#2271b1;">✓ Zakończony</span>
                        <?php else: ?>
                            <span style="color:#d63638;">× Anulowany</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?page=ssm-sessions&class_id=<?php echo $class->id; ?>" class="button button-small">
                            📅 Harmonogram
                        </a>
                        
                        <button class="button button-small ssm-edit-class"
                                data-id="<?php echo $class->id; ?>"
                                data-name="<?php echo esc_attr($class->name); ?>"
                                data-facility-id="<?php echo $class->facility_id; ?>"
                                data-instructor-id="<?php echo $class->instructor_id; ?>"
                                data-day="<?php echo $class->day_of_week; ?>"
                                data-time-start="<?php echo $class->time_start; ?>"
                                data-time-end="<?php echo $class->time_end; ?>"
                                data-start-date="<?php echo $class->start_date; ?>"
                                data-session-count="<?php echo $class->session_count; ?>"
                                data-price-session="<?php echo $class->price_per_session; ?>"
                                data-level="<?php echo esc_attr($class->level); ?>"
                                data-max="<?php echo $class->max_participants; ?>"
                                data-max-absences="<?php echo $class->max_absences; ?>"
                                data-allow-makeups="<?php echo $class->allow_makeups; ?>"
                                data-url="<?php echo esc_attr($class->url); ?>"
                                data-description="<?php echo esc_attr($class->description); ?>"
                                data-status="<?php echo $class->status; ?>">
                            Edytuj
                        </button>
                        
                        <a href="?page=ssm-sessions&class_id=<?php echo $class->id; ?>" 
                           class="button button-small">
                            📅 Harmonogram
                        </a>
                        
                        <button class="button button-small ssm-regenerate-class" 
                                data-id="<?php echo $class->id; ?>"
                                data-name="<?php echo esc_attr($class->name); ?>"
                                title="Przelicz harmonogram od nowa">
                            🔄 Przelicz
                        </button>
                        
                        <button class="button button-small button-link-delete ssm-delete-class" 
                                data-id="<?php echo $class->id; ?>">
                            Usuń
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10">Brak kursów. Dodaj pierwszy kurs!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="ssm-info-box" style="margin-top:20px;">
        <h3>💡 Jak to działa?</h3>
        <p><strong>Tworzenie i edycja kursów:</strong></p>
        <ol>
            <li><strong>Przy dodawaniu NOWEGO kursu:</strong> Harmonogram generuje się automatycznie</li>
            <li><strong>Przy edycji kursu:</strong> Harmonogram NIE jest automatycznie aktualizowany</li>
            <li><strong>Przycisk "🔄 Przelicz":</strong> Przelicz harmonogram od nowa (usuwa stary i tworzy nowy)</li>
            <li><strong>Przycisk "📅 Harmonogram":</strong> Zobacz szczegóły, pomiń zajęcia, zmień instruktora</li>
        </ol>
        
        <p><strong>Przykład:</strong> Kurs 10 zajęć, start 10.02.2026 (Poniedziałek)<br>
        → System utworzy zajęcia: 10.02, 17.02, 24.02, 03.03, 10.03... itd.</p>
    </div>
</div>

<script>
// Automatyczne obliczanie ceny kursu
jQuery(document).ready(function($) {
    function updateTotalPrice() {
        var count = parseFloat($('#class-session-count').val()) || 0;
        var price = parseFloat($('#class-price-session').val()) || 0;
        var total = count * price;
        $('#class-total-price').text(total.toFixed(2));
    }
    
    $('#class-session-count, #class-price-session').on('input', updateTotalPrice);
});
</script>

