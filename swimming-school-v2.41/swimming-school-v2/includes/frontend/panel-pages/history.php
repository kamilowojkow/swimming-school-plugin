<?php
/**
 * Panel Rodzica - Historia zajęć (z możliwością oceny)
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

// Obsługa dodawania oceny
if (isset($_POST['ssm_add_rating']) && check_admin_referer('ssm_rating_form')) {
    $session_id = intval($_POST['session_id']);
    $child_id = intval($_POST['child_id']);
    $rating = intval($_POST['rating']);
    $comment = sanitize_textarea_field($_POST['comment']);
    
    // Pobierz instruktora dla sesji
    $session_data = $wpdb->get_row($wpdb->prepare(
        "SELECT c.instructor_id 
         FROM {$wpdb->prefix}ssm_sessions s
         JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
         WHERE s.id = %d",
        $session_id
    ));
    
    if ($session_data) {
        // Sprawdź czy już ocenił
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ssm_session_ratings 
             WHERE session_id = %d AND client_id = %d",
            $session_id, $client->id
        ));
        
        if ($existing) {
            // Aktualizuj
            $wpdb->update(
                $wpdb->prefix . 'ssm_session_ratings',
                array(
                    'rating' => $rating,
                    'comment' => $comment
                ),
                array('id' => $existing)
            );
            echo '<div class="ssm-notice success">✅ Ocena zaktualizowana!</div>';
        } else {
            // Dodaj nową
            $wpdb->insert(
                $wpdb->prefix . 'ssm_session_ratings',
                array(
                    'session_id' => $session_id,
                    'client_id' => $client->id,
                    'child_id' => $child_id,
                    'instructor_id' => $session_data->instructor_id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'created_at' => current_time('mysql')
                )
            );
            echo '<div class="ssm-notice success">✅ Dziękujemy za ocenę!</div>';
        }
    }
}

// Pobierz przeszłe zajęcia (ostatnie 3 miesiące)
$past_sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, c.name as class_name, c.time_start, c.time_end,
            f.name as facility_name,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            ch.id as child_id,
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
            i.id as instructor_id,
            e.id as enrollment_id,
            sr.rating as user_rating,
            sr.comment as user_comment,
            sr.created_at as rated_at
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_enrollments e ON s.class_id = e.class_id
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
     JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
     LEFT JOIN {$wpdb->prefix}ssm_session_ratings sr ON s.id = sr.session_id AND sr.client_id = %d
     WHERE e.client_id = %d 
     AND s.session_date < CURDATE()
     AND s.session_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
     AND s.status IN ('scheduled', 'completed')
     AND e.status = 'active'
     ORDER BY s.session_date DESC, s.time_start DESC",
    $client->id, $client->id
));

$days_pl = array(1 => 'Pon', 2 => 'Wt', 3 => 'Śr', 4 => 'Czw', 5 => 'Pt', 6 => 'Sob', 7 => 'Niedz');

?>

<div class="ssm-page-header">
    <h1>📚 Historia zajęć</h1>
    <p>Przeszłe zajęcia i Twoje oceny (ostatnie 3 miesiące)</p>
</div>

<?php if (!empty($past_sessions)): ?>

<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    
    <?php foreach ($past_sessions as $session): 
        $date = new DateTime($session->session_date);
    ?>
    <div style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; <?php echo $session->user_rating ? 'background: #f0fdf4;' : ''; ?>">
        
        <!-- Nagłówek zajęć -->
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; flex-wrap: wrap; gap: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0 0 8px 0; color: #1e293b;">
                    <?php echo esc_html($session->class_name); ?>
                </h3>
                <div style="color: #64748b; font-size: 14px;">
                    <strong>📅 <?php echo $date->format('d.m.Y'); ?></strong> 
                    (<?php echo $days_pl[$date->format('N')]; ?>) • 
                    <?php echo substr($session->time_start, 0, 5); ?>-<?php echo substr($session->time_end, 0, 5); ?><br>
                    📍 <?php echo esc_html($session->facility_name); ?> • 
                    👶 <?php echo esc_html($session->child_name); ?><br>
                    👨‍🏫 <strong><?php echo esc_html($session->instructor_name); ?></strong>
                </div>
            </div>
            
            <?php if ($session->user_rating): ?>
            <div style="text-align: right;">
                <div style="background: #10b981; color: white; padding: 8px 16px; border-radius: 8px; font-weight: 600; margin-bottom: 5px;">
                    ✅ Ocenione
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    <?php echo date('d.m.Y', strtotime($session->rated_at)); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Aktualna ocena (jeśli jest) -->
        <?php if ($session->user_rating): ?>
        <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #10b981;">
            <div style="margin-bottom: 8px;">
                <strong>Twoja ocena:</strong>
                <span style="font-size: 20px; color: #f59e0b; margin-left: 10px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php echo $i <= $session->user_rating ? '⭐' : '☆'; ?>
                    <?php endfor; ?>
                </span>
            </div>
            <?php if ($session->user_comment): ?>
            <div style="color: #64748b; margin-top: 8px;">
                <em>"<?php echo esc_html($session->user_comment); ?>"</em>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Formularz oceny -->
        <details <?php echo !$session->user_rating ? 'open' : ''; ?>>
            <summary style="cursor: pointer; font-weight: 600; color: #667eea; padding: 10px; border-radius: 8px; background: #f0f9ff; user-select: none;">
                <?php echo $session->user_rating ? '✏️ Zmień ocenę' : '⭐ Oceń te zajęcia'; ?>
            </summary>
            
            <form method="post" style="margin-top: 15px; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <?php wp_nonce_field('ssm_rating_form'); ?>
                <input type="hidden" name="session_id" value="<?php echo $session->id; ?>">
                <input type="hidden" name="child_id" value="<?php echo $session->child_id; ?>">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 10px;">
                        Oceń zajęcia (1-5 gwiazdek):
                    </label>
                    <div class="rating-stars" style="font-size: 36px; cursor: pointer;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-rating="<?php echo $i; ?>" 
                              style="color: #d1d5db; transition: color 0.2s;"
                              onmouseover="highlightStars(<?php echo $i; ?>, this.parentElement)" 
                              onmouseout="resetStars(this.parentElement)"
                              onclick="selectRating(<?php echo $i; ?>, this.parentElement)">☆</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="rating-input" value="<?php echo $session->user_rating ?: ''; ?>" required>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 10px;">
                        Komentarz (opcjonalnie):
                    </label>
                    <textarea name="comment" rows="4" 
                              style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit;"
                              placeholder="Podziel się swoją opinią o zajęciach..."><?php echo $session->user_comment ? esc_textarea($session->user_comment) : ''; ?></textarea>
                </div>
                
                <button type="submit" name="ssm_add_rating" 
                        style="padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    💾 <?php echo $session->user_rating ? 'Aktualizuj ocenę' : 'Wyślij ocenę'; ?>
                </button>
            </form>
        </details>
        
    </div>
    <?php endforeach; ?>
    
</div>

<?php else: ?>
<div style="background: white; padding: 60px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
    <div style="font-size: 64px; margin-bottom: 20px;">📚</div>
    <h2 style="color: #64748b; margin: 0 0 10px 0;">Brak przeszłych zajęć</h2>
    <p style="color: #94a3b8; margin: 0;">Historia zajęć z ostatnich 3 miesięcy pojawi się tutaj</p>
</div>
<?php endif; ?>

<script>
let currentRating = <?php echo $past_sessions[0]->user_rating ?? 0; ?>;

function highlightStars(rating, container) {
    const stars = container.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.style.color = '#f59e0b';
            star.textContent = '⭐';
        } else {
            star.style.color = '#d1d5db';
            star.textContent = '☆';
        }
    });
}

function resetStars(container) {
    if (currentRating === 0) {
        const stars = container.querySelectorAll('.star');
        stars.forEach(star => {
            star.style.color = '#d1d5db';
            star.textContent = '☆';
        });
    } else {
        highlightStars(currentRating, container);
    }
}

function selectRating(rating, container) {
    currentRating = rating;
    document.getElementById('rating-input').value = rating;
    highlightStars(rating, container);
}

// Ustaw początkowy stan dla pierwszego formularza
<?php if (!empty($past_sessions) && $past_sessions[0]->user_rating): ?>
document.addEventListener('DOMContentLoaded', function() {
    const firstContainer = document.querySelector('.rating-stars');
    if (firstContainer) {
        currentRating = <?php echo $past_sessions[0]->user_rating; ?>;
        highlightStars(currentRating, firstContainer);
    }
});
<?php endif; ?>
</script>

<style>
.ssm-notice {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 600;
}
.ssm-notice.success {
    background: #d1fae5;
    border-left: 4px solid #10b981;
    color: #064e3b;
}
details summary:hover {
    background: #dbeafe !important;
}
</style>
