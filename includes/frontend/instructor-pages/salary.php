<?php
/**
 * Panel Instruktora - Wynagrodzenie
 * Fila Style Design
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

// Policz obecnych
$total_present = 0;
foreach ($sessions_with_attendance as $session) {
    $total_present += $session->present_count;
}

// Poprzedni i następny miesiąc
$prev_month = date('Y-m', strtotime($selected_month . '-01 -1 month'));
$next_month = date('Y-m', strtotime($selected_month . '-01 +1 month'));

// Nazwy miesięcy
$month_names = array(
    '01' => ssm_t('month_january'), '02' => ssm_t('month_february'),
    '03' => ssm_t('month_march'), '04' => ssm_t('month_april'),
    '05' => ssm_t('month_may'), '06' => ssm_t('month_june'),
    '07' => ssm_t('month_july'), '08' => ssm_t('month_august'),
    '09' => ssm_t('month_september'), '10' => ssm_t('month_october'),
    '11' => ssm_t('month_november'), '12' => ssm_t('month_december')
);
$current_month_name = $month_names[date('m', strtotime($selected_month))] . ' ' . date('Y', strtotime($selected_month));
?>

<style>
/* Salary Page Styles - Fila Design */
.ssm-salary-page {
    max-width: 1200px;
}

.ssm-salary-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.ssm-salary-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ssm-salary-title h1 {
    font-size: 24px;
    font-weight: 700;
    color: var(--ssm-text, #1e293b);
    margin: 0;
}

.ssm-salary-title .ssm-icon-box {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

/* Filter card */
.ssm-filter-card {
    background: var(--ssm-bg, white);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-filter-form {
    display: flex;
    gap: 16px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.ssm-filter-group {
    flex: 1;
    min-width: 200px;
}

.ssm-filter-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    color: var(--ssm-text, #1e293b);
    margin-bottom: 8px;
}

.ssm-filter-group input[type="month"] {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--ssm-border, #e2e8f0);
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.2s ease;
    background: var(--ssm-bg, white);
    color: var(--ssm-text, #1e293b);
}

.ssm-filter-group input[type="month"]:focus {
    border-color: var(--ssm-primary, #667eea);
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.ssm-btn-filter {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--ssm-primary, #667eea) 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ssm-btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Month navigation */
.ssm-month-nav {
    display: flex;
    gap: 8px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-month-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    background: var(--ssm-bg-secondary, #f8fafc);
    color: var(--ssm-text-secondary, #64748b);
    border: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-month-btn:hover {
    background: var(--ssm-bg-tertiary, #f1f5f9);
    color: var(--ssm-text, #1e293b);
}

.ssm-month-btn.current {
    background: #eff6ff;
    color: #1e40af;
    border-color: #93c5fd;
}

/* Summary card */
.ssm-salary-summary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    color: white;
    box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
}

.ssm-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

@media (max-width: 768px) {
    .ssm-summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.ssm-summary-item {
    text-align: center;
}

.ssm-summary-item .summary-label {
    font-size: 13px;
    opacity: 0.9;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ssm-summary-item .summary-value {
    font-size: 28px;
    font-weight: 700;
}

.ssm-summary-item.highlight .summary-value {
    font-size: 36px;
}

.ssm-summary-divider {
    width: 1px;
    background: rgba(255,255,255,0.2);
}

/* Info box */
.ssm-info-box {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px 24px;
    background: #eff6ff;
    border: 1px solid #93c5fd;
    border-radius: 12px;
    margin-bottom: 24px;
}

.ssm-info-box .info-icon {
    width: 40px;
    height: 40px;
    background: #3b82f6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    flex-shrink: 0;
}

.ssm-info-box .info-content {
    color: #1e40af;
}

.ssm-info-box .info-content strong {
    display: block;
    margin-bottom: 4px;
}

.ssm-info-box .info-content small {
    opacity: 0.8;
}

/* Sessions table card */
.ssm-sessions-card {
    background: var(--ssm-bg, white);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--ssm-border, #e2e8f0);
}

.ssm-sessions-card h2 {
    font-size: 18px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Sessions table */
.ssm-sessions-table {
    width: 100%;
    border-collapse: collapse;
}

.ssm-sessions-table th {
    background: var(--ssm-bg-secondary, #f8fafc);
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: var(--ssm-text-secondary, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--ssm-border, #e2e8f0);
}

.ssm-sessions-table td {
    padding: 16px;
    border-bottom: 1px solid var(--ssm-border, #e2e8f0);
    color: var(--ssm-text, #1e293b);
    font-size: 14px;
}

.ssm-sessions-table tbody tr:hover {
    background: var(--ssm-bg-secondary, #f8fafc);
}

.ssm-sessions-table tbody tr:last-child td {
    border-bottom: none;
}

.ssm-sessions-table .cell-number {
    width: 50px;
    text-align: center;
}

.ssm-sessions-table .cell-number span {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--ssm-primary, #667eea);
    color: white;
    border-radius: 50%;
    font-size: 12px;
    font-weight: 600;
}

.ssm-sessions-table .cell-date {
    white-space: nowrap;
}

.ssm-sessions-table .cell-date .day-name {
    display: block;
    font-size: 12px;
    color: var(--ssm-text-secondary, #64748b);
    margin-top: 2px;
}

.ssm-sessions-table .cell-time {
    font-family: monospace;
    white-space: nowrap;
}

.ssm-sessions-table .cell-class {
    font-weight: 600;
}

.ssm-sessions-table .cell-attendance {
    text-align: center;
}

.ssm-badge-attendance {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: #d1fae5;
    color: #059669;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.ssm-sessions-table .cell-rate {
    text-align: right;
    font-weight: 600;
    color: #059669;
}

/* Table footer */
.ssm-sessions-table tfoot td {
    background: var(--ssm-bg-secondary, #f8fafc);
    padding: 16px;
    font-weight: 700;
    border-top: 2px solid var(--ssm-border, #e2e8f0);
}

.ssm-sessions-table tfoot .total-label {
    text-align: right;
    font-size: 15px;
    color: var(--ssm-text, #1e293b);
}

.ssm-sessions-table tfoot .total-sessions {
    text-align: center;
}

.ssm-badge-total {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-radius: 20px;
    font-size: 14px;
}

.ssm-sessions-table tfoot .total-amount {
    text-align: right;
    font-size: 18px;
    color: #059669;
}

/* Empty state */
.ssm-empty-sessions {
    text-align: center;
    padding: 60px 20px;
    background: #fffbeb;
    border-radius: 16px;
    border: 1px solid #fde68a;
}

.ssm-empty-sessions .empty-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: white;
}

.ssm-empty-sessions h3 {
    font-size: 18px;
    font-weight: 600;
    color: #92400e;
    margin: 0 0 8px 0;
}

.ssm-empty-sessions p {
    color: #b45309;
    margin: 0;
    font-size: 14px;
}

/* Rate info card */
.ssm-rate-card {
    background: var(--ssm-bg, white);
    border-radius: 16px;
    padding: 24px;
    margin-top: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--ssm-border, #e2e8f0);
    display: flex;
    align-items: center;
    gap: 20px;
}

.ssm-rate-card .rate-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
    flex-shrink: 0;
}

.ssm-rate-card .rate-content h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--ssm-text, #1e293b);
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ssm-rate-card .rate-content p {
    margin: 0;
    color: var(--ssm-text-secondary, #64748b);
    font-size: 14px;
}

.ssm-rate-card .rate-value {
    font-weight: 700;
    color: #059669;
}

.ssm-rate-warning {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    padding: 8px 12px;
    background: #fef2f2;
    border-radius: 8px;
    color: #dc2626;
    font-size: 13px;
}
</style>

<div class="ssm-salary-page">

    <!-- Header -->
    <div class="ssm-salary-header">
        <div class="ssm-salary-title">
            <div class="ssm-icon-box">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
            <h1><?php echo ssm_t('instr_salary'); ?></h1>
        </div>
    </div>

    <!-- Filter card -->
    <div class="ssm-filter-card">
        <form method="get" class="ssm-filter-form">
            <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
            <input type="hidden" name="instructor_page" value="salary">

            <div class="ssm-filter-group">
                <label for="month-filter">
                    <i class="ri-calendar-line"></i>
                    <?php echo ssm_t('instr_billing_month'); ?>
                </label>
                <input type="month" name="month_filter" id="month-filter"
                       value="<?php echo esc_attr($selected_month); ?>">
            </div>

            <button type="submit" class="ssm-btn-filter">
                <i class="ri-search-line"></i>
                <?php echo ssm_t('instr_show_billing'); ?>
            </button>
        </form>

        <div class="ssm-month-nav">
            <a href="?instructor_page=salary&month_filter=<?php echo $prev_month; ?>" class="ssm-month-btn">
                <i class="ri-arrow-left-s-line"></i>
                <?php echo ssm_t('instr_prev_month'); ?>
            </a>
            <a href="?instructor_page=salary&month_filter=<?php echo date('Y-m'); ?>" class="ssm-month-btn current">
                <i class="ri-calendar-check-line"></i>
                <?php echo ssm_t('instr_current_month'); ?>
            </a>
            <?php if ($next_month <= date('Y-m')): ?>
            <a href="?instructor_page=salary&month_filter=<?php echo $next_month; ?>" class="ssm-month-btn">
                <?php echo ssm_t('instr_next_month'); ?>
                <i class="ri-arrow-right-s-line"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary card -->
    <div class="ssm-salary-summary">
        <div class="ssm-summary-grid">
            <div class="ssm-summary-item">
                <div class="summary-label"><?php echo ssm_t('instr_billing_period'); ?></div>
                <div class="summary-value"><?php echo $current_month_name; ?></div>
            </div>
            <div class="ssm-summary-item">
                <div class="summary-label"><?php echo ssm_t('instr_classes_conducted'); ?></div>
                <div class="summary-value"><?php echo $total_sessions; ?></div>
            </div>
            <div class="ssm-summary-item">
                <div class="summary-label"><?php echo ssm_t('instr_rate_per_class'); ?></div>
                <div class="summary-value"><?php echo number_format($hourly_rate, 0, ',', ' '); ?> <?php echo ssm_t('currency'); ?></div>
            </div>
            <div class="ssm-summary-item highlight">
                <div class="summary-label"><?php echo ssm_t('instr_total_salary'); ?></div>
                <div class="summary-value"><?php echo number_format($total_salary, 0, ',', ' '); ?> <?php echo ssm_t('currency'); ?></div>
            </div>
        </div>
    </div>

    <!-- Info box -->
    <div class="ssm-info-box">
        <div class="info-icon">
            <i class="ri-information-line"></i>
        </div>
        <div class="info-content">
            <strong><?php echo ssm_t('instr_calculation_method'); ?></strong>
            <?php echo ssm_t('instr_calculation_formula'); ?><br>
            <small><?php echo ssm_t('instr_calculation_note'); ?></small>
        </div>
    </div>

    <!-- Sessions list -->
    <?php if (!empty($sessions_with_attendance)): ?>
        <div class="ssm-sessions-card">
            <h2>
                <i class="ri-list-check-2"></i>
                <?php echo ssm_t('instr_detailed_list'); ?>
            </h2>

            <table class="ssm-sessions-table">
                <thead>
                    <tr>
                        <th class="cell-number">#</th>
                        <th><?php echo ssm_t('date'); ?></th>
                        <th><?php echo ssm_t('time'); ?></th>
                        <th><?php echo ssm_t('course'); ?></th>
                        <th><?php echo ssm_t('location'); ?></th>
                        <th style="text-align: center;"><?php echo ssm_t('instr_present'); ?></th>
                        <th style="text-align: right;"><?php echo ssm_t('instr_rate'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    foreach ($sessions_with_attendance as $session):
                        $date = new DateTime($session->session_date);
                    ?>
                    <tr>
                        <td class="cell-number">
                            <span><?php echo $counter++; ?></span>
                        </td>
                        <td class="cell-date">
                            <strong><?php echo $date->format('d.m.Y'); ?></strong>
                            <span class="day-name"><?php echo ssm_get_day_name_pl($date); ?></span>
                        </td>
                        <td class="cell-time">
                            <?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?>
                        </td>
                        <td class="cell-class"><?php echo esc_html($session->class_name); ?></td>
                        <td><?php echo esc_html($session->facility_name); ?></td>
                        <td class="cell-attendance">
                            <span class="ssm-badge-attendance">
                                <i class="ri-user-line"></i>
                                <?php echo $session->present_count; ?> / <?php echo $session->total_attendees; ?>
                            </span>
                        </td>
                        <td class="cell-rate">
                            <?php echo number_format($hourly_rate, 0, ',', ' '); ?> <?php echo ssm_t('currency'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="total-label"><?php echo ssm_t('total'); ?>:</td>
                        <td class="total-sessions">
                            <span class="ssm-badge-total">
                                <i class="ri-calendar-check-line"></i>
                                <?php echo $total_sessions; ?> <?php echo ssm_t('instr_classes'); ?>
                            </span>
                        </td>
                        <td class="total-amount">
                            <?php echo number_format($total_salary, 0, ',', ' '); ?> <?php echo ssm_t('currency'); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php else: ?>
        <div class="ssm-empty-sessions">
            <div class="empty-icon">
                <i class="ri-calendar-close-line"></i>
            </div>
            <h3><?php echo ssm_t('instr_no_classes_month'); ?></h3>
            <p><?php echo ssm_t('instr_no_classes_note'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Rate info card -->
    <div class="ssm-rate-card">
        <div class="rate-icon">
            <i class="ri-lightbulb-line"></i>
        </div>
        <div class="rate-content">
            <h3>
                <i class="ri-information-line"></i>
                <?php echo ssm_t('instr_about_rate'); ?>
            </h3>
            <p>
                <?php echo ssm_t('instr_your_rate'); ?>
                <span class="rate-value"><?php echo number_format($hourly_rate, 0, ',', ' '); ?> <?php echo ssm_t('currency'); ?></span>
                <?php echo ssm_t('instr_per_class'); ?>
            </p>
            <?php if ($hourly_rate == 0): ?>
                <div class="ssm-rate-warning">
                    <i class="ri-error-warning-line"></i>
                    <?php echo ssm_t('instr_rate_not_set'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
