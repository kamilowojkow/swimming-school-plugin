<?php
/**
 * System Powiadomień SSM
 * Obsługuje powiadomienia dla rodziców i instruktorów
 * Przygotowany do integracji z push notifications
 */

if (!defined('ABSPATH')) exit;

class SSM_Notification_System {

    private static $instance = null;

    // Typy powiadomień dla rodziców
    const PARENT_TYPES = array(
        'course_starting' => array(
            'icon' => 'ri-calendar-check-line',
            'color' => '#3b82f6',
            'priority' => 'high'
        ),
        'payment_due_soon' => array(
            'icon' => 'ri-wallet-3-line',
            'color' => '#f59e0b',
            'priority' => 'high'
        ),
        'payment_overdue' => array(
            'icon' => 'ri-error-warning-line',
            'color' => '#ef4444',
            'priority' => 'urgent'
        ),
        'child_achievement' => array(
            'icon' => 'ri-trophy-line',
            'color' => '#8b5cf6',
            'priority' => 'normal'
        ),
        'instructor_change' => array(
            'icon' => 'ri-exchange-line',
            'color' => '#06b6d4',
            'priority' => 'normal'
        ),
        'makeup_available' => array(
            'icon' => 'ri-refresh-line',
            'color' => '#10b981',
            'priority' => 'normal'
        ),
        'manual' => array(
            'icon' => 'ri-notification-3-line',
            'color' => '#6366f1',
            'priority' => 'normal'
        )
    );

    // Typy powiadomień dla instruktorów
    const INSTRUCTOR_TYPES = array(
        'substitution_available' => array(
            'icon' => 'ri-hand-heart-line',
            'color' => '#f59e0b',
            'priority' => 'high'
        ),
        'substitution_taken' => array(
            'icon' => 'ri-checkbox-circle-line',
            'color' => '#10b981',
            'priority' => 'normal'
        ),
        'manual' => array(
            'icon' => 'ri-notification-3-line',
            'color' => '#6366f1',
            'priority' => 'normal'
        )
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hooki dla automatycznych powiadomień
        add_action('ssm_child_achievement_awarded', array($this, 'notify_child_achievement'), 10, 3);
        add_action('ssm_substitution_created', array($this, 'notify_substitution_available'), 10, 2);
        add_action('ssm_substitution_taken', array($this, 'notify_substitution_taken'), 10, 2);
        add_action('ssm_instructor_change', array($this, 'notify_instructor_change'), 10, 3);
        add_action('ssm_makeup_slot_available', array($this, 'notify_makeup_available'), 10, 2);

        // Cron dla powiadomień czasowych
        add_action('ssm_check_scheduled_notifications', array($this, 'check_scheduled_notifications'));

        // AJAX handlers
        add_action('wp_ajax_ssm_get_notifications', array($this, 'ajax_get_notifications'));
        add_action('wp_ajax_ssm_mark_notification_read', array($this, 'ajax_mark_notification_read'));
        add_action('wp_ajax_ssm_mark_all_notifications_read', array($this, 'ajax_mark_all_notifications_read'));
        add_action('wp_ajax_ssm_delete_notification', array($this, 'ajax_delete_notification'));
        add_action('wp_ajax_ssm_get_unread_count', array($this, 'ajax_get_unread_count'));

        // Admin AJAX
        add_action('wp_ajax_ssm_send_manual_notification', array($this, 'ajax_send_manual_notification'));
        add_action('wp_ajax_ssm_get_notification_recipients', array($this, 'ajax_get_notification_recipients'));
    }

    /**
     * Tworzenie powiadomienia
     */
    public function create_notification($recipient_type, $recipient_id, $type, $title, $message, $data = array(), $options = array()) {
        global $wpdb;

        $defaults = array(
            'priority' => 'normal',
            'action_url' => '',
            'expires_at' => null,
            'unique_key' => null // Klucz do deduplikacji
        );
        $options = wp_parse_args($options, $defaults);

        // Sprawdź deduplikację
        if ($options['unique_key']) {
            $already_sent = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ssm_notification_sent
                 WHERE notification_key = %s AND recipient_type = %s AND recipient_id = %d",
                $options['unique_key'],
                $recipient_type,
                $recipient_id
            ));

            if ($already_sent) {
                return false; // Już wysłano
            }
        }

        // Pobierz priorytet z typu jeśli nie podano
        if ($options['priority'] === 'normal') {
            $types = ($recipient_type === 'parent') ? self::PARENT_TYPES : self::INSTRUCTOR_TYPES;
            if (isset($types[$type]['priority'])) {
                $options['priority'] = $types[$type]['priority'];
            }
        }

        // Wstaw powiadomienie
        $result = $wpdb->insert(
            $wpdb->prefix . 'ssm_notifications',
            array(
                'recipient_type' => $recipient_type,
                'recipient_id' => $recipient_id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => json_encode($data),
                'priority' => $options['priority'],
                'action_url' => $options['action_url'],
                'expires_at' => $options['expires_at'],
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            $notification_id = $wpdb->insert_id;

            // Zapisz w tabeli deduplikacji
            if ($options['unique_key']) {
                $wpdb->insert(
                    $wpdb->prefix . 'ssm_notification_sent',
                    array(
                        'notification_key' => $options['unique_key'],
                        'recipient_type' => $recipient_type,
                        'recipient_id' => $recipient_id,
                        'sent_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%d', '%s')
                );
            }

            // Hook dla push notifications (do późniejszej implementacji)
            do_action('ssm_notification_created', $notification_id, $recipient_type, $recipient_id, $type, $data);

            return $notification_id;
        }

        return false;
    }

    /**
     * Pobieranie powiadomień dla użytkownika
     */
    public function get_notifications($recipient_type, $recipient_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'unread_only' => false,
            'include_expired' => false
        );
        $args = wp_parse_args($args, $defaults);

        $where = array(
            $wpdb->prepare("recipient_type = %s", $recipient_type),
            $wpdb->prepare("recipient_id = %d", $recipient_id)
        );

        if ($args['unread_only']) {
            $where[] = "is_read = 0";
        }

        if (!$args['include_expired']) {
            $where[] = "(expires_at IS NULL OR expires_at > NOW())";
        }

        $sql = "SELECT * FROM {$wpdb->prefix}ssm_notifications
                WHERE " . implode(' AND ', $where) . "
                ORDER BY
                    CASE priority
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'normal' THEN 3
                        ELSE 4
                    END,
                    created_at DESC
                LIMIT %d OFFSET %d";

        $notifications = $wpdb->get_results($wpdb->prepare($sql, $args['limit'], $args['offset']));

        // Dodaj informacje o typie
        foreach ($notifications as &$notification) {
            $types = ($recipient_type === 'parent') ? self::PARENT_TYPES : self::INSTRUCTOR_TYPES;
            if (isset($types[$notification->type])) {
                $notification->icon = $types[$notification->type]['icon'];
                $notification->color = $types[$notification->type]['color'];
            } else {
                $notification->icon = 'ri-notification-3-line';
                $notification->color = '#6366f1';
            }
            $notification->data = json_decode($notification->data, true);
            $notification->time_ago = $this->time_ago($notification->created_at);
        }

        return $notifications;
    }

    /**
     * Liczba nieprzeczytanych powiadomień
     */
    public function get_unread_count($recipient_type, $recipient_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_notifications
             WHERE recipient_type = %s AND recipient_id = %d AND is_read = 0
             AND (expires_at IS NULL OR expires_at > NOW())",
            $recipient_type,
            $recipient_id
        ));
    }

    /**
     * Oznacz jako przeczytane
     */
    public function mark_as_read($notification_id, $recipient_type = null, $recipient_id = null) {
        global $wpdb;

        $where = array('id' => $notification_id);
        if ($recipient_type && $recipient_id) {
            $where['recipient_type'] = $recipient_type;
            $where['recipient_id'] = $recipient_id;
        }

        return $wpdb->update(
            $wpdb->prefix . 'ssm_notifications',
            array('is_read' => 1, 'read_at' => current_time('mysql')),
            $where
        );
    }

    /**
     * Oznacz wszystkie jako przeczytane
     */
    public function mark_all_as_read($recipient_type, $recipient_id) {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'ssm_notifications',
            array('is_read' => 1, 'read_at' => current_time('mysql')),
            array('recipient_type' => $recipient_type, 'recipient_id' => $recipient_id, 'is_read' => 0)
        );
    }

    /**
     * Usuń powiadomienie
     */
    public function delete_notification($notification_id, $recipient_type = null, $recipient_id = null) {
        global $wpdb;

        $where = array('id' => $notification_id);
        if ($recipient_type && $recipient_id) {
            $where['recipient_type'] = $recipient_type;
            $where['recipient_id'] = $recipient_id;
        }

        return $wpdb->delete($wpdb->prefix . 'ssm_notifications', $where);
    }

    // ========================================
    // TRIGGERY AUTOMATYCZNYCH POWIADOMIEŃ
    // ========================================

    /**
     * Sprawdzanie powiadomień czasowych (wywoływane przez cron)
     */
    public function check_scheduled_notifications() {
        $this->check_course_starting_notifications();
        $this->check_payment_due_notifications();
        $this->check_payment_overdue_notifications();
    }

    /**
     * Powiadomienie: Jutro zaczyna się kurs (24h przed)
     */
    private function check_course_starting_notifications() {
        global $wpdb;

        // Znajdź pierwsze zajęcia kursów które zaczynają się za 24h
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $sessions = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT
                e.id as enrollment_id,
                e.client_id,
                e.child_id,
                c.name as class_name,
                ch.first_name as child_name,
                s.session_date,
                s.time_start,
                f.name as facility_name
            FROM {$wpdb->prefix}ssm_enrollments e
            JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            JOIN {$wpdb->prefix}ssm_sessions s ON s.class_id = c.id
            LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
            WHERE s.session_date = %s
            AND e.status = 'active'
            AND s.id = (
                SELECT MIN(s2.id) FROM {$wpdb->prefix}ssm_sessions s2
                WHERE s2.class_id = c.id AND s2.session_date >= CURDATE()
            )
        ", $tomorrow));

        foreach ($sessions as $session) {
            $unique_key = 'course_starting_' . $session->enrollment_id . '_' . $session->session_date;

            $this->create_notification(
                'parent',
                $session->client_id,
                'course_starting',
                ssm_t('notif_course_starting_title'),
                sprintf(
                    ssm_t('notif_course_starting_msg'),
                    $session->child_name,
                    $session->class_name,
                    date('H:i', strtotime($session->time_start))
                ),
                array(
                    'enrollment_id' => $session->enrollment_id,
                    'child_id' => $session->child_id,
                    'class_name' => $session->class_name,
                    'session_date' => $session->session_date,
                    'time_start' => $session->time_start,
                    'facility' => $session->facility_name
                ),
                array(
                    'unique_key' => $unique_key,
                    'action_url' => '?panel_page=schedule'
                )
            );
        }
    }

    /**
     * Powiadomienie: Zbliża się termin płatności (24h przed)
     */
    private function check_payment_due_notifications() {
        global $wpdb;

        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $payments = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, cl.first_name, cl.last_name
            FROM {$wpdb->prefix}ssm_payments p
            JOIN {$wpdb->prefix}ssm_clients cl ON p.client_id = cl.id
            WHERE p.due_date = %s
            AND p.status IN ('pending', 'partial')
        ", $tomorrow));

        foreach ($payments as $payment) {
            $remaining = $payment->total_amount - $payment->paid_amount;
            $unique_key = 'payment_due_' . $payment->id;

            $this->create_notification(
                'parent',
                $payment->client_id,
                'payment_due_soon',
                ssm_t('notif_payment_due_title'),
                sprintf(
                    ssm_t('notif_payment_due_msg'),
                    number_format($remaining, 2, ',', ' '),
                    ssm_t('currency'),
                    $payment->title
                ),
                array(
                    'payment_id' => $payment->id,
                    'amount' => $remaining,
                    'due_date' => $payment->due_date,
                    'title' => $payment->title
                ),
                array(
                    'unique_key' => $unique_key,
                    'action_url' => '?panel_page=payments'
                )
            );
        }
    }

    /**
     * Powiadomienie: Minął termin płatności (24h po)
     */
    private function check_payment_overdue_notifications() {
        global $wpdb;

        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $payments = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, cl.first_name, cl.last_name
            FROM {$wpdb->prefix}ssm_payments p
            JOIN {$wpdb->prefix}ssm_clients cl ON p.client_id = cl.id
            WHERE p.due_date = %s
            AND p.status IN ('pending', 'partial')
        ", $yesterday));

        foreach ($payments as $payment) {
            $remaining = $payment->total_amount - $payment->paid_amount;
            $unique_key = 'payment_overdue_' . $payment->id;

            $this->create_notification(
                'parent',
                $payment->client_id,
                'payment_overdue',
                ssm_t('notif_payment_overdue_title'),
                sprintf(
                    ssm_t('notif_payment_overdue_msg'),
                    number_format($remaining, 2, ',', ' '),
                    ssm_t('currency'),
                    $payment->title
                ),
                array(
                    'payment_id' => $payment->id,
                    'amount' => $remaining,
                    'due_date' => $payment->due_date,
                    'title' => $payment->title
                ),
                array(
                    'unique_key' => $unique_key,
                    'priority' => 'urgent',
                    'action_url' => '?panel_page=payments'
                )
            );
        }
    }

    /**
     * Powiadomienie: Dziecko otrzymało odznaczenie
     */
    public function notify_child_achievement($child_id, $achievement_id, $awarded_by = null) {
        global $wpdb;

        // Pobierz dane dziecka i rodzica
        $child = $wpdb->get_row($wpdb->prepare(
            "SELECT ch.*, cc.client_id
             FROM {$wpdb->prefix}ssm_children ch
             JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
             WHERE ch.id = %d",
            $child_id
        ));

        if (!$child) return;

        // Pobierz dane odznaczenia
        $achievement = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_achievements WHERE id = %d",
            $achievement_id
        ));

        if (!$achievement) return;

        $this->create_notification(
            'parent',
            $child->client_id,
            'child_achievement',
            ssm_t('notif_achievement_title'),
            sprintf(
                ssm_t('notif_achievement_msg'),
                $child->first_name,
                $achievement->name
            ),
            array(
                'child_id' => $child_id,
                'child_name' => $child->first_name,
                'achievement_id' => $achievement_id,
                'achievement_name' => $achievement->name,
                'achievement_icon' => $achievement->icon,
                'points' => $achievement->points
            ),
            array(
                'action_url' => '?panel_page=children&child_id=' . $child_id
            )
        );
    }

    /**
     * Powiadomienie: Dostępne zastępstwo (dla instruktorów)
     */
    public function notify_substitution_available($unavailability_id, $instructor_id) {
        global $wpdb;

        // Pobierz szczegóły niedyspozycji
        $unavailability = $wpdb->get_row($wpdb->prepare("
            SELECT u.*, s.session_date, s.time_start, s.time_end,
                   c.name as class_name, f.name as facility_name,
                   CONCAT(i.first_name, ' ', i.last_name) as instructor_name
            FROM {$wpdb->prefix}ssm_instructor_unavailability u
            JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
            JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
            LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
            JOIN {$wpdb->prefix}ssm_instructors i ON u.instructor_id = i.id
            WHERE u.id = %d
        ", $unavailability_id));

        if (!$unavailability) return;

        // Pobierz wszystkich innych aktywnych instruktorów
        $instructors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_instructors WHERE id != %d AND active = 1",
            $instructor_id
        ));

        foreach ($instructors as $instructor) {
            $unique_key = 'substitution_available_' . $unavailability_id . '_' . $instructor->id;

            $this->create_notification(
                'instructor',
                $instructor->id,
                'substitution_available',
                ssm_t('notif_substitution_available_title'),
                sprintf(
                    ssm_t('notif_substitution_available_msg'),
                    $unavailability->instructor_name,
                    $unavailability->class_name,
                    date('d.m.Y', strtotime($unavailability->session_date)),
                    substr($unavailability->time_start, 0, 5)
                ),
                array(
                    'unavailability_id' => $unavailability_id,
                    'session_id' => $unavailability->session_id,
                    'class_name' => $unavailability->class_name,
                    'session_date' => $unavailability->session_date,
                    'time_start' => $unavailability->time_start,
                    'instructor_name' => $unavailability->instructor_name,
                    'facility' => $unavailability->facility_name
                ),
                array(
                    'unique_key' => $unique_key,
                    'action_url' => '?instructor_page=substitutions'
                )
            );
        }
    }

    /**
     * Powiadomienie: Ktoś wziął twoje zastępstwo
     */
    public function notify_substitution_taken($unavailability_id, $replacement_instructor_id) {
        global $wpdb;

        // Pobierz szczegóły
        $unavailability = $wpdb->get_row($wpdb->prepare("
            SELECT u.*, s.session_date, s.time_start, s.time_end,
                   c.name as class_name,
                   CONCAT(i.first_name, ' ', i.last_name) as replacement_name
            FROM {$wpdb->prefix}ssm_instructor_unavailability u
            JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
            JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
            JOIN {$wpdb->prefix}ssm_instructors i ON i.id = %d
            WHERE u.id = %d
        ", $replacement_instructor_id, $unavailability_id));

        if (!$unavailability) return;

        $this->create_notification(
            'instructor',
            $unavailability->instructor_id,
            'substitution_taken',
            ssm_t('notif_substitution_taken_title'),
            sprintf(
                ssm_t('notif_substitution_taken_msg'),
                $unavailability->replacement_name,
                $unavailability->class_name,
                date('d.m.Y', strtotime($unavailability->session_date))
            ),
            array(
                'unavailability_id' => $unavailability_id,
                'replacement_instructor_id' => $replacement_instructor_id,
                'replacement_name' => $unavailability->replacement_name,
                'class_name' => $unavailability->class_name,
                'session_date' => $unavailability->session_date
            ),
            array(
                'action_url' => '?instructor_page=substitutions'
            )
        );
    }

    /**
     * Powiadomienie: Zmiana instruktora na zajęciach
     */
    public function notify_instructor_change($session_id, $old_instructor_id, $new_instructor_id) {
        global $wpdb;

        // Pobierz dane sesji
        $session = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, c.name as class_name, f.name as facility_name,
                   CONCAT(i.first_name, ' ', i.last_name) as new_instructor_name
            FROM {$wpdb->prefix}ssm_sessions s
            JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
            LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
            JOIN {$wpdb->prefix}ssm_instructors i ON i.id = %d
            WHERE s.id = %d
        ", $new_instructor_id, $session_id));

        if (!$session) return;

        // Pobierz rodziców dzieci zapisanych na te zajęcia
        $enrollments = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT e.client_id, ch.first_name as child_name
            FROM {$wpdb->prefix}ssm_enrollments e
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            WHERE e.class_id = %d AND e.status = 'active'
        ", $session->class_id));

        foreach ($enrollments as $enrollment) {
            $unique_key = 'instructor_change_' . $session_id . '_' . $enrollment->client_id;

            $this->create_notification(
                'parent',
                $enrollment->client_id,
                'instructor_change',
                ssm_t('notif_instructor_change_title'),
                sprintf(
                    ssm_t('notif_instructor_change_msg'),
                    $session->class_name,
                    date('d.m.Y', strtotime($session->session_date)),
                    $session->new_instructor_name
                ),
                array(
                    'session_id' => $session_id,
                    'class_name' => $session->class_name,
                    'session_date' => $session->session_date,
                    'new_instructor' => $session->new_instructor_name
                ),
                array(
                    'unique_key' => $unique_key,
                    'action_url' => '?panel_page=schedule'
                )
            );
        }
    }

    /**
     * Powiadomienie: Dostępna możliwość odrobienia zajęć
     */
    public function notify_makeup_available($session_id, $class_id) {
        global $wpdb;

        // Pobierz dane sesji
        $session = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, c.name as class_name
            FROM {$wpdb->prefix}ssm_sessions s
            JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
            WHERE s.id = %d
        ", $session_id));

        if (!$session) return;

        // Znajdź rodziców ze zgłoszonymi nieobecnościami w tym samym kursie
        $absences = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT a.id as absence_id, e.client_id, ch.first_name as child_name
            FROM {$wpdb->prefix}ssm_absences a
            JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
            WHERE a.can_makeup = 1
            AND a.makeup_session_id IS NULL
            AND a.status = 'reported'
            AND c.id = %d
        ", $class_id));

        foreach ($absences as $absence) {
            $unique_key = 'makeup_available_' . $session_id . '_' . $absence->absence_id;

            $this->create_notification(
                'parent',
                $absence->client_id,
                'makeup_available',
                ssm_t('notif_makeup_available_title'),
                sprintf(
                    ssm_t('notif_makeup_available_msg'),
                    $absence->child_name,
                    $session->class_name,
                    date('d.m.Y', strtotime($session->session_date)),
                    substr($session->time_start, 0, 5)
                ),
                array(
                    'session_id' => $session_id,
                    'absence_id' => $absence->absence_id,
                    'class_name' => $session->class_name,
                    'session_date' => $session->session_date
                ),
                array(
                    'unique_key' => $unique_key,
                    'action_url' => '?panel_page=makeup'
                )
            );
        }
    }

    /**
     * Ręczne wysyłanie powiadomienia z panelu admina
     */
    public function send_manual_notification($recipient_type, $recipient_ids, $title, $message, $options = array()) {
        $sent_count = 0;

        foreach ($recipient_ids as $recipient_id) {
            $result = $this->create_notification(
                $recipient_type,
                $recipient_id,
                'manual',
                $title,
                $message,
                array('manual' => true),
                $options
            );

            if ($result) {
                $sent_count++;
            }
        }

        return $sent_count;
    }

    // ========================================
    // AJAX HANDLERS
    // ========================================

    public function ajax_get_notifications() {
        check_ajax_referer('ssm_notifications_nonce', 'nonce');

        $recipient_type = sanitize_text_field($_POST['recipient_type']);
        $recipient_id = intval($_POST['recipient_id']);
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        $notifications = $this->get_notifications($recipient_type, $recipient_id, array(
            'limit' => $limit,
            'offset' => $offset
        ));

        wp_send_json_success(array(
            'notifications' => $notifications,
            'unread_count' => $this->get_unread_count($recipient_type, $recipient_id)
        ));
    }

    public function ajax_mark_notification_read() {
        check_ajax_referer('ssm_notifications_nonce', 'nonce');

        $notification_id = intval($_POST['notification_id']);
        $recipient_type = sanitize_text_field($_POST['recipient_type']);
        $recipient_id = intval($_POST['recipient_id']);

        $result = $this->mark_as_read($notification_id, $recipient_type, $recipient_id);

        if ($result !== false) {
            wp_send_json_success(array(
                'unread_count' => $this->get_unread_count($recipient_type, $recipient_id)
            ));
        } else {
            wp_send_json_error('Failed to mark notification as read');
        }
    }

    public function ajax_mark_all_notifications_read() {
        check_ajax_referer('ssm_notifications_nonce', 'nonce');

        $recipient_type = sanitize_text_field($_POST['recipient_type']);
        $recipient_id = intval($_POST['recipient_id']);

        $this->mark_all_as_read($recipient_type, $recipient_id);

        wp_send_json_success(array('unread_count' => 0));
    }

    public function ajax_delete_notification() {
        check_ajax_referer('ssm_notifications_nonce', 'nonce');

        $notification_id = intval($_POST['notification_id']);
        $recipient_type = sanitize_text_field($_POST['recipient_type']);
        $recipient_id = intval($_POST['recipient_id']);

        $result = $this->delete_notification($notification_id, $recipient_type, $recipient_id);

        if ($result) {
            wp_send_json_success(array(
                'unread_count' => $this->get_unread_count($recipient_type, $recipient_id)
            ));
        } else {
            wp_send_json_error('Failed to delete notification');
        }
    }

    public function ajax_get_unread_count() {
        check_ajax_referer('ssm_notifications_nonce', 'nonce');

        $recipient_type = sanitize_text_field($_POST['recipient_type']);
        $recipient_id = intval($_POST['recipient_id']);

        wp_send_json_success(array(
            'unread_count' => $this->get_unread_count($recipient_type, $recipient_id)
        ));
    }

    public function ajax_send_manual_notification() {
        check_ajax_referer('ssm_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $recipient_type = sanitize_text_field($_POST['recipient_type']);
        $recipient_ids = array_map('intval', $_POST['recipient_ids']);
        $title = sanitize_text_field($_POST['title']);
        $message = sanitize_textarea_field($_POST['message']);

        $sent_count = $this->send_manual_notification($recipient_type, $recipient_ids, $title, $message);

        wp_send_json_success(array(
            'sent_count' => $sent_count,
            'message' => sprintf(ssm_t('notif_sent_success'), $sent_count)
        ));
    }

    public function ajax_get_notification_recipients() {
        check_ajax_referer('ssm_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        global $wpdb;

        $type = sanitize_text_field($_POST['type']);
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if ($type === 'parent') {
            $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email
                    FROM {$wpdb->prefix}ssm_clients";
            if ($search) {
                $sql .= $wpdb->prepare(
                    " WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s",
                    '%' . $search . '%',
                    '%' . $search . '%',
                    '%' . $search . '%'
                );
            }
            $sql .= " ORDER BY first_name, last_name LIMIT 100";
        } else {
            $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email
                    FROM {$wpdb->prefix}ssm_instructors WHERE active = 1";
            if ($search) {
                $sql .= $wpdb->prepare(
                    " AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)",
                    '%' . $search . '%',
                    '%' . $search . '%',
                    '%' . $search . '%'
                );
            }
            $sql .= " ORDER BY first_name, last_name LIMIT 100";
        }

        $recipients = $wpdb->get_results($sql);

        wp_send_json_success(array('recipients' => $recipients));
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    private function time_ago($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;

        if ($diff < 60) {
            return ssm_t('just_now');
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return sprintf(ssm_t('minutes_ago'), $mins);
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return sprintf(ssm_t('hours_ago'), $hours);
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return sprintf(ssm_t('days_ago'), $days);
        } else {
            return date('d.m.Y', $time);
        }
    }

    /**
     * Rejestracja crona
     */
    public static function schedule_cron() {
        if (!wp_next_scheduled('ssm_check_scheduled_notifications')) {
            wp_schedule_event(time(), 'hourly', 'ssm_check_scheduled_notifications');
        }
    }

    /**
     * Usunięcie crona
     */
    public static function unschedule_cron() {
        $timestamp = wp_next_scheduled('ssm_check_scheduled_notifications');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ssm_check_scheduled_notifications');
        }
    }
}

// Inicjalizacja
function ssm_notification_system() {
    return SSM_Notification_System::get_instance();
}

// Aktywacja crona przy ładowaniu pluginu
add_action('init', array('SSM_Notification_System', 'schedule_cron'));
register_deactivation_hook(SSM_PLUGIN_DIR . 'swimming-school-manager.php', array('SSM_Notification_System', 'unschedule_cron'));
