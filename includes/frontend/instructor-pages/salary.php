<?php
/**
 * Panel Instruktora - Wynagrodzenie
 */
if (!defined('ABSPATH')) exit;

// Filtry
$selected_month = isset($_GET['month_filter']) ? sanitize_text_field($_GET['month_filter']) : date('Y-m');

// Pobierz datę początkową i końcową miesiąca
$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

// Pobierz zajęcia z wypełnioną frekwencją
$sessions_with_attendance = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, c.name as class_name, c.time_start, c.time_end, f.name as facility_name,
            COUNT(DISTINCT a.id) as total_attendees,
            COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.id END) as present_count
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     LEFT JOIN {$wpdb->prefix}ssm_attendance a ON s.id = a.session_id
     WHERE c.instructor_id = %d 
     AND s.session_date BETWEEN %s AND %s
     AND s.status = 'scheduled'
     GROUP BY s.id
     HAVING COUNT(DISTINCT a.id) > 0
     ORDER BY s.session_date, s.time_start",
    $instructor->id, $month_start, $month_end
));

// Wylicz wynagrodzenie
$total_sessions = count($sessions_with_attendance);
$hourly_rate = $instructor->hourly_rate ?? 0;
$total_salary = $total_sessions * $hourly_rate;

// Poprzedni i następny miesiąc
$prev_month = date('Y-m', strtotime($selected_month . '-01 -1 month'));
$next_month = date('Y-m', strtotime($selected_month . '-01 +1 month'));

?>

<div class="ssm-page-header">
    <h1>💰 Wynagrodzenie</h1>
    <p>Podsumowanie przeprowadzonych zajęć i wyliczenie wynagrodzenia.</p>
</div>

<!-- Filtry -->
<div class="ssm-filters" style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <form method="get" style="display: flex; gap: 15px; align-items: end;">
        <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
        <input type="hidden" name="instructor_page" value="salary">
        
        <div style="flex: 1;">
            <label for="month-filter" style="display: block; font-weight: 600; margin-bottom: 5px; color: #1e293b;">
                Miesiąc rozliczeniowy:
            </label>
            <input type="month" name="month_filter" id="month-filter" 
                   value="<?php echo esc_attr($selected_month); ?>" 
                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px;">
        </div>
        
        <div>
            <button type="submit" class="ssm-btn ssm-btn-primary" style="padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600;">
                🔍 Pokaż rozliczenie
            </button>
        </div>
    </form>
    
    <!-- Szybka nawigacja -->
    <div style="margin-top: 15px; display: flex; gap: 10px;">
        <a href="?instructor_page=salary&month_filter=<?php echo $prev_month; ?>" 
           style="padding: 6px 12px; background: #f1f5f9; border-radius: 6px; text-decoration: none; color: #475569; font-size: 14px;">
            ← Poprzedni miesiąc
        </a>
        <a href="?instructor_page=salary&month_filter=<?php echo date('Y-m'); ?>" 
           style="padding: 6px 12px; background: #eff6ff; border-radius: 6px; text-decoration: none; color: #1e40af; font-size: 14px; font-weight: 600;">
            Obecny miesiąc
        </a>
        <?php if ($next_month <= date('Y-m')): ?>
        <a href="?instructor_page=salary&month_filter=<?php echo $next_month; ?>" 
           style="padding: 6px 12px; background: #f1f5f9; border-radius: 6px; text-decoration: none; color: #475569; font-size: 14px;">
            Następny miesiąc →
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Podsumowanie wynagrodzenia -->
<div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; border-radius: 12px; color: white; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div>
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Miesiąc</div>
            <div style="font-size: 24px; font-weight: 700;">
                <?php 
                $month_names = array(
                    '01' => 'Styczeń', '02' => 'Luty', '03' => 'Marzec', '04' => 'Kwiecień',
                    '05' => 'Maj', '06' => 'Czerwiec', '07' => 'Lipiec', '08' => 'Sierpień',
                    '09' => 'Wrzesień', '10' => 'Październik', '11' => 'Listopad', '12' => 'Grudzień'
                );
                echo $month_names[date('m', strtotime($selected_month))]; 
                echo ' ' . date('Y', strtotime($selected_month));
                ?>
            </div>
        </div>
        
        <div>
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Przeprowadzonych zajęć</div>
            <div style="font-size: 32px; font-weight: 700;"><?php echo $total_sessions; ?></div>
        </div>
        
        <div>
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Stawka za zajęcia</div>
            <div style="font-size: 28px; font-weight: 700;"><?php echo number_format($hourly_rate, 2, ',', ' '); ?> PLN</div>
        </div>
        
        <div>
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">WYNAGRODZENIE</div>
            <div style="font-size: 36px; font-weight: 700;"><?php echo number_format($total_salary, 2, ',', ' '); ?> PLN</div>
        </div>
    </div>
</div>

<!-- Wyjaśnienie -->
<div style="background: #eff6ff; padding: 15px; border-radius: 12px; border-left: 4px solid #3b82f6; margin-bottom: 30px; color: #1e3a8a;">
    <strong>ℹ️ Sposób wyliczenia:</strong> Wynagrodzenie = Liczba przeprowadzonych zajęć × Stawka za zajęcia<br>
    <small style="color: #1e40af;">Przeprowadzone zajęcia to te, dla których wypełniono listę obecności (przynajmniej jedno dziecko obecne).</small>
</div>

<!-- Lista zajęć -->
<?php if (!empty($sessions_with_attendance)): ?>
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 20px 0; color: #1e293b;">Szczegółowa lista zajęć</h2>
        
        <table class="wp-list-table widefat" style="border-radius: 8px; overflow: hidden;">
            <thead>
                <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <th style="padding: 12px; width: 5%;">#</th>
                    <th style="width: 15%;">Data</th>
                    <th style="width: 15%;">Godzina</th>
                    <th style="width: 25%;">Kurs</th>
                    <th style="width: 20%;">Miejsce</th>
                    <th style="width: 10%;">Obecnych</th>
                    <th style="width: 10%;">Stawka</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                foreach ($sessions_with_attendance as $session): 
                    $date = new DateTime($session->session_date);
                ?>
                <tr>
                    <td style="padding: 12px; text-align: center;"><?php echo $counter++; ?></td>
                    <td>
                        <strong><?php echo $date->format('d.m.Y'); ?></strong><br>
                        <small style="color: #64748b;"><?php echo ssm_get_day_name_pl($date); ?></small>
                    </td>
                    <td><?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?></td>
                    <td><strong><?php echo esc_html($session->class_name); ?></strong></td>
                    <td><?php echo esc_html($session->facility_name); ?></td>
                    <td style="text-align: center;">
                        <span style="background: #d1fae5; color: #059669; padding: 4px 12px; border-radius: 12px; font-weight: 600;">
                            <?php echo $session->present_count; ?> / <?php echo $session->total_attendees; ?>
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #059669;">
                        <?php echo number_format($hourly_rate, 2, ',', ' '); ?> PLN
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8fafc; font-weight: 700;">
                    <td colspan="5" style="padding: 15px; text-align: right;">RAZEM:</td>
                    <td style="padding: 15px; text-align: center;">
                        <span style="background: #d1fae5; color: #059669; padding: 6px 14px; border-radius: 12px; font-size: 16px;">
                            <?php echo $total_sessions; ?> zajęć
                        </span>
                    </td>
                    <td style="padding: 15px; text-align: right; font-size: 18px; color: #059669;">
                        <?php echo number_format($total_salary, 2, ',', ' '); ?> PLN
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php else: ?>
    <div style="background: #fef3c7; padding: 40px; border-radius: 12px; text-align: center; color: #78350f;">
        <p style="margin: 0; font-size: 16px;">
            📅 Brak przeprowadzonych zajęć w wybranym miesiącu.<br>
            <small>Zajęcia są liczone po wypełnieniu listy obecności.</small>
        </p>
    </div>
<?php endif; ?>

<!-- Informacja o stawce -->
<div style="background: white; padding: 20px; border-radius: 12px; margin-top: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h3 style="margin: 0 0 10px 0; color: #1e293b;">💡 O Twojej stawce</h3>
    <p style="margin: 0; color: #64748b;">
        Twoja aktualna stawka wynosi <strong style="color: #059669;"><?php echo number_format($hourly_rate, 2, ',', ' '); ?> PLN</strong> za jedno przeprowadzone zajęcia.
        <?php if ($hourly_rate == 0): ?>
            <br><span style="color: #ef4444;">⚠️ Stawka nie została ustawiona. Skontaktuj się z administratorem.</span>
        <?php endif; ?>
    </p>
</div>
