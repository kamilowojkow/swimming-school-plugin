<?php
/**
 * Panel Admin - Oceny zajęć
 */
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('Brak uprawnień.');
}

global $wpdb;

// Filtry
$instructor_filter = isset($_GET['instructor']) ? intval($_GET['instructor']) : 0;
$rating_filter = isset($_GET['rating_filter']) ? intval($_GET['rating_filter']) : 0;
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

// Query
$where = array("1=1");
$params = array();

if ($instructor_filter > 0) {
    $where[] = "sr.instructor_id = %d";
    $params[] = $instructor_filter;
}

if ($rating_filter > 0) {
    $where[] = "sr.rating = %d";
    $params[] = $rating_filter;
}

$where[] = "s.session_date BETWEEN %s AND %s";
$params[] = $date_from;
$params[] = $date_to;

$where_sql = implode(' AND ', $where);

// Pobierz oceny
$ratings = $wpdb->get_results($wpdb->prepare(
    "SELECT sr.*, 
            s.session_date,
            c.name as class_name,
            CONCAT(cl.first_name, ' ', cl.last_name) as client_name,
            cl.email as client_email,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name
     FROM {$wpdb->prefix}ssm_session_ratings sr
     JOIN {$wpdb->prefix}ssm_sessions s ON sr.session_id = s.id
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     JOIN {$wpdb->prefix}ssm_clients cl ON sr.client_id = cl.id
     JOIN {$wpdb->prefix}ssm_children ch ON sr.child_id = ch.id
     JOIN {$wpdb->prefix}ssm_instructors i ON sr.instructor_id = i.id
     WHERE $where_sql
     ORDER BY sr.created_at DESC",
    ...$params
));

// Pobierz instruktorów
$instructors = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}ssm_instructors ORDER BY last_name, first_name");

// Statystyki
$total_ratings = count($ratings);
$avg_rating = $wpdb->get_var("SELECT AVG(rating) FROM {$wpdb->prefix}ssm_session_ratings");

$rating_distribution = array();
for ($i = 1; $i <= 5; $i++) {
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_session_ratings WHERE rating = %d",
        $i
    ));
    $rating_distribution[$i] = $count;
}

?>

<div class="wrap">
    <h1>⭐ Oceny zajęć</h1>
    <p class="description">Opinie rodziców o zajęciach i instruktorach</p>
    
    <!-- Statystyki -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6;">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;">Wszystkie oceny</div>
            <div style="font-size: 36px; font-weight: 700; color: #3b82f6;"><?php echo $total_ratings; ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;">Średnia ocena</div>
            <div style="font-size: 36px; font-weight: 700; color: #f59e0b;">
                <?php echo $avg_rating ? number_format($avg_rating, 2) : '—'; ?> 
                <span style="font-size: 20px;">⭐</span>
            </div>
        </div>
        
        <?php for ($i = 5; $i >= 1; $i--): ?>
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 20px; margin-bottom: 5px;">
                <?php for ($j = 0; $j < $i; $j++) echo '⭐'; ?>
            </div>
            <div style="font-weight: 700; font-size: 24px; color: #667eea;">
                <?php echo $rating_distribution[$i]; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    
    <!-- Filtry -->
    <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <form method="get" action="">
            <input type="hidden" name="page" value="swimming-school-ratings">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Data od:</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="regular-text">
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Data do:</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="regular-text">
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Instruktor:</label>
                    <select name="instructor" class="regular-text">
                        <option value="0">Wszyscy</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?php echo $instructor->id; ?>" <?php selected($instructor_filter, $instructor->id); ?>>
                                <?php echo esc_html($instructor->first_name . ' ' . $instructor->last_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Ocena:</label>
                    <select name="rating_filter" class="regular-text">
                        <option value="0">Wszystkie</option>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php selected($rating_filter, $i); ?>>
                                <?php echo $i; ?> ⭐
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="button button-primary">🔍 Filtruj</button>
            <a href="?page=swimming-school-ratings" class="button">↺ Reset</a>
        </form>
    </div>
    
    <!-- Lista ocen -->
    <div style="background: white; padding: 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <?php if ($ratings): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 100px;">Data zajęć</th>
                    <th>Zajęcia</th>
                    <th>Dziecko</th>
                    <th>Rodzic</th>
                    <th>Instruktor</th>
                    <th style="width: 100px;">Ocena</th>
                    <th>Komentarz</th>
                    <th style="width: 120px;">Wystawiono</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ratings as $rating): ?>
                <tr>
                    <td><?php echo date('d.m.Y', strtotime($rating->session_date)); ?></td>
                    <td><strong><?php echo esc_html($rating->class_name); ?></strong></td>
                    <td><?php echo esc_html($rating->child_name); ?></td>
                    <td>
                        <?php echo esc_html($rating->client_name); ?><br>
                        <small style="color: #666;"><?php echo esc_html($rating->client_email); ?></small>
                    </td>
                    <td><strong style="color: #667eea;"><?php echo esc_html($rating->instructor_name); ?></strong></td>
                    <td style="font-size: 20px; text-align: center;">
                        <?php for ($i = 0; $i < $rating->rating; $i++) echo '⭐'; ?>
                    </td>
                    <td>
                        <?php if ($rating->comment): ?>
                            <em style="color: #64748b;">"<?php echo esc_html($rating->comment); ?>"</em>
                        <?php else: ?>
                            <span style="color: #94a3b8;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <small><?php echo date('d.m.Y H:i', strtotime($rating->created_at)); ?></small>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="padding: 60px; text-align: center; color: #64748b;">
            <div style="font-size: 64px; margin-bottom: 20px;">⭐</div>
            <p>Brak ocen w wybranym okresie</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Podsumowanie -->
    <div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
        <strong>Znaleziono: <?php echo count($ratings); ?> ocen</strong>
        <?php if ($instructor_filter): ?>
            (instruktor: <?php 
                $selected_instructor = array_filter($instructors, function($i) use ($instructor_filter) {
                    return $i->id == $instructor_filter;
                });
                $selected_instructor = reset($selected_instructor);
                echo $selected_instructor ? esc_html($selected_instructor->first_name . ' ' . $selected_instructor->last_name) : '';
            ?>)
        <?php endif; ?>
    </div>
</div>
