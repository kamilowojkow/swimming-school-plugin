<?php
/**
 * Gamification System - Punkty, Odznaczenia, Tiery
 */

if (!defined('ABSPATH')) exit;

/**
 * Definicja tierów (poziomów zaawansowania)
 */
function ssm_get_tiers() {
    return array(
        'beginner' => array(
            'name' => '🐠 Żabka',
            'points_required' => 0,
            'color' => '#94a3b8',
            'description' => 'Początkujący pływak'
        ),
        'intermediate' => array(
            'name' => '🐟 Pływak',
            'points_required' => 100,
            'color' => '#3b82f6',
            'description' => 'Umiejętności podstawowe'
        ),
        'advanced' => array(
            'name' => '🐬 Delfin',
            'points_required' => 300,
            'color' => '#8b5cf6',
            'description' => 'Zaawansowany pływak'
        ),
        'expert' => array(
            'name' => '🦈 Rekin',
            'points_required' => 600,
            'color' => '#f59e0b',
            'description' => 'Ekspert pływania'
        ),
        'master' => array(
            'name' => '👑 Mistrz Basenów',
            'points_required' => 1000,
            'color' => '#ef4444',
            'description' => 'Najwyższy poziom'
        )
    );
}

/**
 * Pobierz lub utwórz profil punktów dziecka
 */
function ssm_get_or_create_child_points($child_id) {
    global $wpdb;
    
    $points = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_child_points WHERE child_id = %d",
        $child_id
    ));
    
    if ($points) {
        return $points;
    }
    
    // Utwórz nowy profil
    $wpdb->insert(
        $wpdb->prefix . 'ssm_child_points',
        array(
            'child_id' => $child_id,
            'points' => 0,
            'level' => 1,
            'tier' => 'beginner',
            'total_earned' => 0,
            'updated_at' => current_time('mysql')
        )
    );
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_child_points WHERE child_id = %d",
        $child_id
    ));
}

/**
 * Dodaj punkty dziecku
 */
function ssm_add_points($child_id, $points, $reason, $description = '', $session_id = null, $achievement_id = null, $awarded_by = null) {
    global $wpdb;
    
    $profile = ssm_get_or_create_child_points($child_id);
    
    // Dodaj do historii
    $wpdb->insert(
        $wpdb->prefix . 'ssm_points_history',
        array(
            'child_id' => $child_id,
            'points' => $points,
            'reason' => $reason,
            'description' => $description,
            'session_id' => $session_id,
            'achievement_id' => $achievement_id,
            'awarded_by' => $awarded_by,
            'created_at' => current_time('mysql')
        )
    );
    
    // Aktualizuj profil
    $new_points = $profile->points + $points;
    $new_total = $profile->total_earned + max(0, $points); // Tylko dodatnie do total
    
    // Oblicz nowy tier
    $new_tier = ssm_calculate_tier($new_points);
    $new_level = ssm_calculate_level($new_points);
    
    $wpdb->update(
        $wpdb->prefix . 'ssm_child_points',
        array(
            'points' => $new_points,
            'total_earned' => $new_total,
            'tier' => $new_tier,
            'level' => $new_level,
            'updated_at' => current_time('mysql')
        ),
        array('child_id' => $child_id)
    );
    
    return true;
}

/**
 * Oblicz tier na podstawie punktów
 */
function ssm_calculate_tier($points) {
    $tiers = ssm_get_tiers();
    $current_tier = 'beginner';
    
    foreach ($tiers as $tier_key => $tier_data) {
        if ($points >= $tier_data['points_required']) {
            $current_tier = $tier_key;
        }
    }
    
    return $current_tier;
}

/**
 * Oblicz poziom na podstawie punktów (każde 50 pkt = 1 poziom)
 */
function ssm_calculate_level($points) {
    return floor($points / 50) + 1;
}

/**
 * Przyznaj odznaczenie dziecku
 */
function ssm_award_achievement($child_id, $achievement_id, $awarded_by = null, $notes = '') {
    global $wpdb;
    
    // Sprawdź czy już ma
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_child_achievements 
         WHERE child_id = %d AND achievement_id = %d",
        $child_id, $achievement_id
    ));
    
    if ($exists) {
        return false; // Już ma
    }
    
    // Pobierz dane odznaczenia
    $achievement = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_achievements WHERE id = %d",
        $achievement_id
    ));
    
    if (!$achievement) {
        return false;
    }
    
    // Przyznaj odznaczenie
    $wpdb->insert(
        $wpdb->prefix . 'ssm_child_achievements',
        array(
            'child_id' => $child_id,
            'achievement_id' => $achievement_id,
            'earned_at' => current_time('mysql'),
            'awarded_by' => $awarded_by,
            'notes' => $notes
        )
    );
    
    // Dodaj punkty za odznaczenie
    if ($achievement->points > 0) {
        ssm_add_points(
            $child_id,
            $achievement->points,
            'achievement',
            'Odznaczenie: ' . $achievement->name,
            null,
            $achievement_id,
            $awarded_by
        );
    }

    // Trigger powiadomienia
    do_action('ssm_child_achievement_awarded', $child_id, $achievement_id, $awarded_by);

    return true;
}

/**
 * Pobierz postępy dziecka
 */
function ssm_get_child_progress($child_id) {
    global $wpdb;
    
    $points = ssm_get_or_create_child_points($child_id);
    $tiers = ssm_get_tiers();
    
    // Obecny tier
    $current_tier = $tiers[$points->tier];
    
    // Następny tier
    $next_tier = null;
    $found_current = false;
    foreach ($tiers as $tier_key => $tier_data) {
        if ($found_current) {
            $next_tier = $tier_data;
            break;
        }
        if ($tier_key == $points->tier) {
            $found_current = true;
        }
    }
    
    // Postęp do następnego tieru
    $progress_percent = 0;
    $points_to_next = 0;
    
    if ($next_tier) {
        $points_in_tier = $points->points - $current_tier['points_required'];
        $tier_range = $next_tier['points_required'] - $current_tier['points_required'];
        $progress_percent = ($points_in_tier / $tier_range) * 100;
        $points_to_next = $next_tier['points_required'] - $points->points;
    }
    
    // Odznaczenia
    $achievements = $wpdb->get_results($wpdb->prepare(
        "SELECT ca.*, a.name, a.description, a.icon, a.type, a.points
         FROM {$wpdb->prefix}ssm_child_achievements ca
         JOIN {$wpdb->prefix}ssm_achievements a ON ca.achievement_id = a.id
         WHERE ca.child_id = %d
         ORDER BY ca.earned_at DESC",
        $child_id
    ));
    
    return array(
        'points' => $points,
        'current_tier' => $current_tier,
        'next_tier' => $next_tier,
        'progress_percent' => round($progress_percent, 1),
        'points_to_next' => $points_to_next,
        'achievements' => $achievements,
        'achievement_count' => count($achievements)
    );
}

/**
 * Inicjalizuj predefiniowane odznaczenia
 */
function ssm_init_default_achievements() {
    global $wpdb;
    
    // Sprawdź czy już są
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_achievements");
    if ($count > 0) {
        return; // Już zainicjalizowane
    }
    
    $achievements = array(
        // Podstawowe
        array(
            'name' => '🏊 Pierwszy rzut',
            'description' => 'Przepłynąłeś pierwszą długość basenu',
            'icon' => '🏊',
            'category' => 'skill',
            'type' => 'bronze',
            'points' => 10
        ),
        array(
            'name' => '🌊 Mistrz baku',
            'description' => 'Opanowałeś technikę pływania grzbietowego',
            'icon' => '🌊',
            'category' => 'skill',
            'type' => 'silver',
            'points' => 25
        ),
        array(
            'name' => '🏁 10 długości',
            'description' => 'Przepłynąłeś 10 długości basenu bez przerwy',
            'icon' => '🏁',
            'category' => 'skill',
            'type' => 'silver',
            'points' => 30
        ),
        array(
            'name' => '🥇 Mistrz motylka',
            'description' => 'Perfekcyjnie opanowałeś najtrudniejszy styl',
            'icon' => '🥇',
            'category' => 'skill',
            'type' => 'gold',
            'points' => 50
        ),
        
        // Frekwencja
        array(
            'name' => '✨ Nigdy nie opuszczam',
            'description' => '10 zajęć z rzędu bez nieobecności',
            'icon' => '✨',
            'category' => 'attendance',
            'type' => 'silver',
            'points' => 20
        ),
        array(
            'name' => '💯 Idealna frekwencja',
            'description' => 'Całe półrocze bez nieobecności',
            'icon' => '💯',
            'category' => 'attendance',
            'type' => 'gold',
            'points' => 75
        ),
        
        // Lojalność
        array(
            'name' => '📅 3 miesiące',
            'description' => 'Uczęszczasz na zajęcia od 3 miesięcy',
            'icon' => '📅',
            'category' => 'loyalty',
            'type' => 'bronze',
            'points' => 15
        ),
        array(
            'name' => '🎂 Rok ze szkółką',
            'description' => 'Uczęszczasz na zajęcia od roku',
            'icon' => '🎂',
            'category' => 'loyalty',
            'type' => 'gold',
            'points' => 100
        ),
        
        // Społeczne
        array(
            'name' => '👥 Pomocna dłoń',
            'description' => 'Pomogłeś koledze nauczyć się nowej techniki',
            'icon' => '👥',
            'category' => 'social',
            'type' => 'silver',
            'points' => 25
        ),
        array(
            'name' => '⭐ Gwiazda grupy',
            'description' => 'Wyróżniłeś się zaangażowaniem na zajęciach',
            'icon' => '⭐',
            'category' => 'social',
            'type' => 'gold',
            'points' => 40
        ),
        
        // Specjalne
        array(
            'name' => '🎯 Pierwsza plomba',
            'description' => 'Ukończyłeś pierwszy kurs',
            'icon' => '🎯',
            'category' => 'special',
            'type' => 'silver',
            'points' => 30
        ),
        array(
            'name' => '🏆 Mistrzostwo',
            'description' => 'Opanowałeś wszystkie podstawowe style',
            'icon' => '🏆',
            'category' => 'special',
            'type' => 'platinum',
            'points' => 150
        )
    );
    
    foreach ($achievements as $ach) {
        $wpdb->insert(
            $wpdb->prefix . 'ssm_achievements',
            array_merge($ach, array(
                'active' => 1,
                'created_at' => current_time('mysql')
            ))
        );
    }
}

// Hook: Inicjalizuj odznaczenia po aktywacji pluginu
add_action('admin_init', 'ssm_init_default_achievements');

/**
 * Hook: Automatyczne punkty za obecność
 */
add_action('ssm_attendance_marked', 'ssm_auto_award_attendance_points', 10, 3);
function ssm_auto_award_attendance_points($child_id, $session_id, $status) {
    if ($status == 'present') {
        ssm_add_points(
            $child_id,
            5,
            'attendance',
            'Obecność na zajęciach',
            $session_id,
            null,
            get_current_user_id()
        );
    }
}
