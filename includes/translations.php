<?php
/**
 * Swimming School Manager - Translation System
 * Supported languages: pl, en, uk, ru
 */

if (!defined('ABSPATH')) exit;

class SSM_Translations {

    private static $instance = null;
    private $current_lang = 'pl';
    private $translations = array();

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_translations();
        $this->detect_language();
    }

    private function detect_language() {
        // Check URL parameter
        if (isset($_GET['lang']) && in_array($_GET['lang'], array('pl', 'en', 'uk', 'ru'))) {
            $this->current_lang = sanitize_text_field($_GET['lang']);
            setcookie('ssm_lang', $this->current_lang, time() + (365 * 24 * 60 * 60), '/');
        }
        // Check cookie
        elseif (isset($_COOKIE['ssm_lang']) && in_array($_COOKIE['ssm_lang'], array('pl', 'en', 'uk', 'ru'))) {
            $this->current_lang = $_COOKIE['ssm_lang'];
        }
        // Default to Polish
        else {
            $this->current_lang = 'pl';
        }
    }

    public function get_current_lang() {
        return $this->current_lang;
    }

    public function set_lang($lang) {
        if (in_array($lang, array('pl', 'en', 'uk', 'ru'))) {
            $this->current_lang = $lang;
        }
    }

    public function t($key, $default = '') {
        if (isset($this->translations[$this->current_lang][$key])) {
            return $this->translations[$this->current_lang][$key];
        }
        // Fallback to Polish
        if (isset($this->translations['pl'][$key])) {
            return $this->translations['pl'][$key];
        }
        return $default ?: $key;
    }

    private function load_translations() {
        $this->translations = array(

            // ========================================
            // POLISH (Polski)
            // ========================================
            'pl' => array(
                // General
                'swimming_school' => 'Szkola Plywania',
                'welcome' => 'Witaj',
                'logout' => 'Wyloguj sie',
                'login' => 'Zaloguj sie',
                'search' => 'Szukaj...',
                'save' => 'Zapisz',
                'cancel' => 'Anuluj',
                'delete' => 'Usun',
                'edit' => 'Edytuj',
                'view' => 'Zobacz',
                'close' => 'Zamknij',
                'yes' => 'Tak',
                'no' => 'Nie',
                'loading' => 'Ladowanie...',
                'error' => 'Blad',
                'success' => 'Sukces',

                // Menu sections
                'menu_main' => 'GLOWNE',
                'menu_management' => 'ZARZADZANIE',
                'menu_account' => 'KONTO',

                // Menu items
                'dashboard' => 'Panel glowny',
                'schedule' => 'Harmonogram',
                'history' => 'Historia zajec',
                'makeup' => 'Odrabianie',
                'payments' => 'Platnosci',
                'referrals' => 'Polec znajomych',
                'my_data' => 'Moje dane',
                'children' => 'Dzieci',
                'courses' => 'Kursy',
                'documents' => 'Dokumenty',
                'gallery' => 'Galeria',
                'settings' => 'Ustawienia',

                // Topbar
                'notifications' => 'Powiadomienia',
                'messages' => 'Wiadomosci',
                'profile' => 'Profil',
                'language' => 'Jezyk',
                'dark_mode' => 'Tryb ciemny',

                // Schedule
                'date' => 'Data',
                'time' => 'Godzina',
                'child' => 'Dziecko',
                'course' => 'Kurs',
                'facility' => 'Obiekt',
                'instructor' => 'Instruktor',
                'status' => 'Status',
                'action' => 'Akcja',
                'today' => 'Dzisiaj',
                'upcoming' => 'Nadchodzace',
                'past' => 'Przeszle',
                'absent' => 'Nieobecny',
                'report_absence' => 'Zglos nieobecnosc',
                'reported' => 'Zgłoszono',
                'limit_reached' => 'Limit wyczerpany',
                'too_late' => 'Za pozno',

                // Children
                'add_child' => 'Dodaj dziecko',
                'child_name' => 'Imie dziecka',
                'date_of_birth' => 'Data urodzenia',
                'age' => 'Wiek',
                'years' => 'lat',

                // Payments
                'amount' => 'Kwota',
                'due_date' => 'Termin platnosci',
                'paid' => 'Zaplacono',
                'pending_payment' => 'Oczekuje',
                'overdue' => 'Zalegla',

                // Profile
                'first_name' => 'Imie',
                'last_name' => 'Nazwisko',
                'email' => 'E-mail',
                'phone' => 'Telefon',
                'address' => 'Adres',

                // Messages
                'no_data' => 'Brak danych',
                'no_children' => 'Brak dzieci',
                'no_courses' => 'Brak kursow',
                'no_sessions' => 'Brak zajec',
                'login_required' => 'Musisz byc zalogowany',
                'no_access' => 'Brak dostepu',

                // Children page
                'manage_children_data' => 'Zarzadzaj danymi swoich dzieci',
                'child_updated' => 'Dane dziecka zostaly zaktualizowane',
                'save_error' => 'Nie udalo sie zapisac zmian',
                'edit_child_data' => 'Edytuj dane dziecka',
                'medical_notes' => 'Uwagi medyczne',
                'medical_notes_placeholder' => 'Alergie, schorzenia, uwagi dla instruktora...',
                'swimming_skills' => 'Umiejetnosci plywackie',
                'skills_placeholder' => 'Poziom plywania, ukonczone kursy, style...',
                'save_changes' => 'Zapisz zmiany',
                'back_to_list' => 'Powrot do listy',
                'points' => 'pkt',
                'level' => 'Poziom',
                'achievements' => 'Odznaczenia',
                'progress_to_next_level' => 'Postep do nastepnego poziomu',
                'points_to_next' => 'Punktow do nastepnego poziomu',
                'no_achievements' => 'Brak odznaczen. Zachecamy do aktywnosci na zajeciach!',
                'active_courses' => 'Aktywne kursy',
                'no_active_courses' => 'Brak aktywnych kursow',
                'additional_info' => 'Dodatkowe informacje',
                'view_details' => 'Zobacz szczegoly',
                'contact_admin_to_add' => 'Skontaktuj sie z administracja aby dodac dzieci',

                // Attendance
                'attendance' => 'Obecnosc',
                'present' => 'Obecny',
                'total_classes' => 'Wszystkich zajec',
                'attendance_rate' => 'Frekwencja',
                'day' => 'Dzien',
                'no_class' => 'Brak zajec',

                // Dashboard
                'dashboard_subtitle' => 'Zobacz co slychac w szkole plywania',
                'upcoming_classes' => 'Nadchodzace zajecia',
                'makeups_pending' => 'Odrabianie do zaplanowania',
                'schedule_now' => 'Zaplanuj teraz',
                'payments_pending' => 'Platnosci oczekujace',
                'pay_now' => 'Zaplac teraz',
                'quick_actions' => 'Szybkie akcje',
                'next_class' => 'Nastepne zajecia',
                'tomorrow' => 'Jutro',
                'view_full_schedule' => 'Zobacz pelny harmonogram',
                'no_upcoming_classes' => 'Brak nadchodzacych zajec',
                'your_children' => 'Twoje dzieci',
                'view_all' => 'Zobacz wszystkie',

                // Courses
                'courses_description' => 'Kursy na ktore zapisane sa Twoje dzieci',
                'status_active' => 'Aktywny',
                'completed' => 'Ukonczono',
                'sessions_completed' => 'Zajec ukonczonych',
                'sessions_remaining' => 'Zajec pozostalo',
                'course_price' => 'Cena kursu',
                'course_details' => 'Szczegoly kursu',
                'start_date' => 'Data rozpoczecia',
                'progress' => 'Postep',
                'sessions' => 'zajec',
                'contact_admin_to_enroll' => 'Skontaktuj sie z administracja aby zapisac dziecko',
            ),

            // ========================================
            // ENGLISH
            // ========================================
            'en' => array(
                // General
                'swimming_school' => 'Swimming School',
                'welcome' => 'Welcome',
                'logout' => 'Logout',
                'login' => 'Login',
                'search' => 'Search...',
                'save' => 'Save',
                'cancel' => 'Cancel',
                'delete' => 'Delete',
                'edit' => 'Edit',
                'view' => 'View',
                'close' => 'Close',
                'yes' => 'Yes',
                'no' => 'No',
                'loading' => 'Loading...',
                'error' => 'Error',
                'success' => 'Success',

                // Menu sections
                'menu_main' => 'MAIN',
                'menu_management' => 'MANAGEMENT',
                'menu_account' => 'ACCOUNT',

                // Menu items
                'dashboard' => 'Dashboard',
                'schedule' => 'Schedule',
                'history' => 'Class History',
                'makeup' => 'Make-up Classes',
                'payments' => 'Payments',
                'referrals' => 'Refer Friends',
                'my_data' => 'My Data',
                'children' => 'Children',
                'courses' => 'Courses',
                'documents' => 'Documents',
                'gallery' => 'Gallery',
                'settings' => 'Settings',

                // Topbar
                'notifications' => 'Notifications',
                'messages' => 'Messages',
                'profile' => 'Profile',
                'language' => 'Language',
                'dark_mode' => 'Dark Mode',

                // Schedule
                'date' => 'Date',
                'time' => 'Time',
                'child' => 'Child',
                'course' => 'Course',
                'facility' => 'Facility',
                'instructor' => 'Instructor',
                'status' => 'Status',
                'action' => 'Action',
                'today' => 'Today',
                'upcoming' => 'Upcoming',
                'past' => 'Past',
                'absent' => 'Absent',
                'report_absence' => 'Report Absence',
                'reported' => 'Reported',
                'limit_reached' => 'Limit reached',
                'too_late' => 'Too late',

                // Children
                'add_child' => 'Add Child',
                'child_name' => 'Child Name',
                'date_of_birth' => 'Date of Birth',
                'age' => 'Age',
                'years' => 'years',

                // Payments
                'amount' => 'Amount',
                'due_date' => 'Due Date',
                'paid' => 'Paid',
                'pending_payment' => 'Pending',
                'overdue' => 'Overdue',

                // Profile
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'email' => 'E-mail',
                'phone' => 'Phone',
                'address' => 'Address',

                // Messages
                'no_data' => 'No data',
                'no_children' => 'No children',
                'no_courses' => 'No courses',
                'no_sessions' => 'No sessions',
                'login_required' => 'Login required',
                'no_access' => 'No access',

                // Children page
                'manage_children_data' => 'Manage your children data',
                'child_updated' => 'Child data has been updated',
                'save_error' => 'Failed to save changes',
                'edit_child_data' => 'Edit child data',
                'medical_notes' => 'Medical notes',
                'medical_notes_placeholder' => 'Allergies, conditions, notes for instructor...',
                'swimming_skills' => 'Swimming skills',
                'skills_placeholder' => 'Swimming level, completed courses, styles...',
                'save_changes' => 'Save changes',
                'back_to_list' => 'Back to list',
                'points' => 'pts',
                'level' => 'Level',
                'achievements' => 'Achievements',
                'progress_to_next_level' => 'Progress to next level',
                'points_to_next' => 'Points to next level',
                'no_achievements' => 'No achievements yet. Stay active in classes!',
                'active_courses' => 'Active courses',
                'no_active_courses' => 'No active courses',
                'additional_info' => 'Additional information',
                'view_details' => 'View Details',
                'contact_admin_to_add' => 'Contact administration to add children',

                // Attendance
                'attendance' => 'Attendance',
                'present' => 'Present',
                'total_classes' => 'Total classes',
                'attendance_rate' => 'Attendance rate',
                'day' => 'Day',
                'no_class' => 'No class',

                // Dashboard
                'dashboard_subtitle' => 'See what is happening at the swimming school',
                'upcoming_classes' => 'Upcoming classes',
                'makeups_pending' => 'Makeups to schedule',
                'schedule_now' => 'Schedule now',
                'payments_pending' => 'Payments pending',
                'pay_now' => 'Pay now',
                'quick_actions' => 'Quick actions',
                'next_class' => 'Next class',
                'tomorrow' => 'Tomorrow',
                'view_full_schedule' => 'View full schedule',
                'no_upcoming_classes' => 'No upcoming classes',
                'your_children' => 'Your children',
                'view_all' => 'View all',

                // Courses
                'courses_description' => 'Courses your children are enrolled in',
                'status_active' => 'Active',
                'completed' => 'Completed',
                'sessions_completed' => 'Sessions completed',
                'sessions_remaining' => 'Sessions remaining',
                'course_price' => 'Course price',
                'course_details' => 'Course details',
                'start_date' => 'Start date',
                'progress' => 'Progress',
                'sessions' => 'sessions',
                'contact_admin_to_enroll' => 'Contact administration to enroll your child',
            ),

            // ========================================
            // UKRAINIAN (Українська)
            // ========================================
            'uk' => array(
                // General
                'swimming_school' => 'Школа Плавання',
                'welcome' => 'Ласкаво просимо',
                'logout' => 'Вийти',
                'login' => 'Увійти',
                'search' => 'Пошук...',
                'save' => 'Зберегти',
                'cancel' => 'Скасувати',
                'delete' => 'Видалити',
                'edit' => 'Редагувати',
                'view' => 'Переглянути',
                'close' => 'Закрити',
                'yes' => 'Так',
                'no' => 'Ні',
                'loading' => 'Завантаження...',
                'error' => 'Помилка',
                'success' => 'Успіх',

                // Menu sections
                'menu_main' => 'ГОЛОВНЕ',
                'menu_management' => 'КЕРУВАННЯ',
                'menu_account' => 'ОБЛІКОВИЙ ЗАПИС',

                // Menu items
                'dashboard' => 'Панель',
                'schedule' => 'Розклад',
                'history' => 'Історія занять',
                'makeup' => 'Відпрацювання',
                'payments' => 'Оплати',
                'referrals' => 'Запросити друзів',
                'my_data' => 'Мої дані',
                'children' => 'Діти',
                'courses' => 'Курси',
                'documents' => 'Документи',
                'gallery' => 'Галерея',
                'settings' => 'Налаштування',

                // Topbar
                'notifications' => 'Сповіщення',
                'messages' => 'Повідомлення',
                'profile' => 'Профіль',
                'language' => 'Мова',
                'dark_mode' => 'Темний режим',

                // Schedule
                'date' => 'Дата',
                'time' => 'Час',
                'child' => 'Дитина',
                'course' => 'Курс',
                'facility' => "Об'єкт",
                'instructor' => 'Інструктор',
                'status' => 'Статус',
                'action' => 'Дія',
                'today' => 'Сьогодні',
                'upcoming' => 'Майбутні',
                'past' => 'Минулі',
                'absent' => 'Відсутній',
                'report_absence' => 'Повідомити про відсутність',
                'reported' => 'Повідомлено',
                'limit_reached' => 'Ліміт вичерпано',
                'too_late' => 'Занадто пізно',

                // Children
                'add_child' => 'Додати дитину',
                'child_name' => "Ім'я дитини",
                'date_of_birth' => 'Дата народження',
                'age' => 'Вік',
                'years' => 'років',

                // Payments
                'amount' => 'Сума',
                'due_date' => 'Термін оплати',
                'paid' => 'Оплачено',
                'pending_payment' => 'Очікує',
                'overdue' => 'Прострочено',

                // Profile
                'first_name' => "Ім'я",
                'last_name' => 'Прізвище',
                'email' => 'E-mail',
                'phone' => 'Телефон',
                'address' => 'Адреса',

                // Messages
                'no_data' => 'Немає даних',
                'no_children' => 'Немає дітей',
                'no_courses' => 'Немає курсів',
                'no_sessions' => 'Немає занять',
                'login_required' => 'Потрібен вхід',
                'no_access' => 'Немає доступу',

                // Children page
                'manage_children_data' => 'Керуйте даними своїх дітей',
                'child_updated' => 'Дані дитини оновлено',
                'save_error' => 'Не вдалося зберегти зміни',
                'edit_child_data' => 'Редагувати дані дитини',
                'medical_notes' => 'Медичні примітки',
                'medical_notes_placeholder' => 'Алергії, захворювання, примітки для інструктора...',
                'swimming_skills' => 'Навички плавання',
                'skills_placeholder' => 'Рівень плавання, завершені курси, стилі...',
                'save_changes' => 'Зберегти зміни',
                'back_to_list' => 'Повернутися до списку',
                'points' => 'балів',
                'level' => 'Рівень',
                'achievements' => 'Досягнення',
                'progress_to_next_level' => 'Прогрес до наступного рівня',
                'points_to_next' => 'Балів до наступного рівня',
                'no_achievements' => 'Поки немає досягнень. Будьте активні на заняттях!',
                'active_courses' => 'Активні курси',
                'no_active_courses' => 'Немає активних курсів',
                'additional_info' => 'Додаткова інформація',
                'view_details' => 'Переглянути деталі',
                'contact_admin_to_add' => 'Зверніться до адміністрації, щоб додати дітей',

                // Attendance
                'attendance' => 'Відвідуваність',
                'present' => 'Присутній',
                'total_classes' => 'Всього занять',
                'attendance_rate' => 'Відвідуваність',
                'day' => 'День',
                'no_class' => 'Немає заняття',

                // Dashboard
                'dashboard_subtitle' => 'Дивіться що відбувається в школі плавання',
                'upcoming_classes' => 'Найближчі заняття',
                'makeups_pending' => 'Відпрацювання до планування',
                'schedule_now' => 'Запланувати зараз',
                'payments_pending' => 'Очікуючі оплати',
                'pay_now' => 'Оплатити зараз',
                'quick_actions' => 'Швидкі дії',
                'next_class' => 'Наступне заняття',
                'tomorrow' => 'Завтра',
                'view_full_schedule' => 'Переглянути повний розклад',
                'no_upcoming_classes' => 'Немає найближчих занять',
                'your_children' => 'Ваші діти',
                'view_all' => 'Переглянути всі',

                // Courses
                'courses_description' => 'Курси на які записані ваші діти',
                'status_active' => 'Активний',
                'completed' => 'Завершено',
                'sessions_completed' => 'Занять завершено',
                'sessions_remaining' => 'Занять залишилося',
                'course_price' => 'Ціна курсу',
                'course_details' => 'Деталі курсу',
                'start_date' => 'Дата початку',
                'progress' => 'Прогрес',
                'sessions' => 'занять',
                'contact_admin_to_enroll' => 'Зверніться до адміністрації щоб записати дитину',
            ),

            // ========================================
            // RUSSIAN (Русский)
            // ========================================
            'ru' => array(
                // General
                'swimming_school' => 'Школа Плавания',
                'welcome' => 'Добро пожаловать',
                'logout' => 'Выйти',
                'login' => 'Войти',
                'search' => 'Поиск...',
                'save' => 'Сохранить',
                'cancel' => 'Отмена',
                'delete' => 'Удалить',
                'edit' => 'Редактировать',
                'view' => 'Просмотр',
                'close' => 'Закрыть',
                'yes' => 'Да',
                'no' => 'Нет',
                'loading' => 'Загрузка...',
                'error' => 'Ошибка',
                'success' => 'Успех',

                // Menu sections
                'menu_main' => 'ГЛАВНОЕ',
                'menu_management' => 'УПРАВЛЕНИЕ',
                'menu_account' => 'АККАУНТ',

                // Menu items
                'dashboard' => 'Панель',
                'schedule' => 'Расписание',
                'history' => 'История занятий',
                'makeup' => 'Отработки',
                'payments' => 'Оплаты',
                'referrals' => 'Пригласить друзей',
                'my_data' => 'Мои данные',
                'children' => 'Дети',
                'courses' => 'Курсы',
                'documents' => 'Документы',
                'gallery' => 'Галерея',
                'settings' => 'Настройки',

                // Topbar
                'notifications' => 'Уведомления',
                'messages' => 'Сообщения',
                'profile' => 'Профиль',
                'language' => 'Язык',
                'dark_mode' => 'Темный режим',

                // Schedule
                'date' => 'Дата',
                'time' => 'Время',
                'child' => 'Ребенок',
                'course' => 'Курс',
                'facility' => 'Объект',
                'instructor' => 'Инструктор',
                'status' => 'Статус',
                'action' => 'Действие',
                'today' => 'Сегодня',
                'upcoming' => 'Предстоящие',
                'past' => 'Прошедшие',
                'absent' => 'Отсутствует',
                'report_absence' => 'Сообщить об отсутствии',
                'reported' => 'Сообщено',
                'limit_reached' => 'Лимит исчерпан',
                'too_late' => 'Слишком поздно',

                // Children
                'add_child' => 'Добавить ребенка',
                'child_name' => 'Имя ребенка',
                'date_of_birth' => 'Дата рождения',
                'age' => 'Возраст',
                'years' => 'лет',

                // Payments
                'amount' => 'Сумма',
                'due_date' => 'Срок оплаты',
                'paid' => 'Оплачено',
                'pending_payment' => 'Ожидает',
                'overdue' => 'Просрочено',

                // Profile
                'first_name' => 'Имя',
                'last_name' => 'Фамилия',
                'email' => 'E-mail',
                'phone' => 'Телефон',
                'address' => 'Адрес',

                // Messages
                'no_data' => 'Нет данных',
                'no_children' => 'Нет детей',
                'no_courses' => 'Нет курсов',
                'no_sessions' => 'Нет занятий',
                'login_required' => 'Требуется вход',
                'no_access' => 'Нет доступа',

                // Children page
                'manage_children_data' => 'Управляйте данными своих детей',
                'child_updated' => 'Данные ребенка обновлены',
                'save_error' => 'Не удалось сохранить изменения',
                'edit_child_data' => 'Редактировать данные ребенка',
                'medical_notes' => 'Медицинские заметки',
                'medical_notes_placeholder' => 'Аллергии, заболевания, заметки для инструктора...',
                'swimming_skills' => 'Навыки плавания',
                'skills_placeholder' => 'Уровень плавания, пройденные курсы, стили...',
                'save_changes' => 'Сохранить изменения',
                'back_to_list' => 'Вернуться к списку',
                'points' => 'баллов',
                'level' => 'Уровень',
                'achievements' => 'Достижения',
                'progress_to_next_level' => 'Прогресс до следующего уровня',
                'points_to_next' => 'Баллов до следующего уровня',
                'no_achievements' => 'Пока нет достижений. Будьте активны на занятиях!',
                'active_courses' => 'Активные курсы',
                'no_active_courses' => 'Нет активных курсов',
                'additional_info' => 'Дополнительная информация',
                'view_details' => 'Подробнее',
                'contact_admin_to_add' => 'Свяжитесь с администрацией, чтобы добавить детей',

                // Attendance
                'attendance' => 'Посещаемость',
                'present' => 'Присутствует',
                'total_classes' => 'Всего занятий',
                'attendance_rate' => 'Посещаемость',
                'day' => 'День',
                'no_class' => 'Нет занятия',

                // Dashboard
                'dashboard_subtitle' => 'Смотрите что происходит в школе плавания',
                'upcoming_classes' => 'Ближайшие занятия',
                'makeups_pending' => 'Отработки к планированию',
                'schedule_now' => 'Запланировать сейчас',
                'payments_pending' => 'Ожидающие оплаты',
                'pay_now' => 'Оплатить сейчас',
                'quick_actions' => 'Быстрые действия',
                'next_class' => 'Следующее занятие',
                'tomorrow' => 'Завтра',
                'view_full_schedule' => 'Посмотреть полное расписание',
                'no_upcoming_classes' => 'Нет ближайших занятий',
                'your_children' => 'Ваши дети',
                'view_all' => 'Посмотреть все',

                // Courses
                'courses_description' => 'Курсы на которые записаны ваши дети',
                'status_active' => 'Активный',
                'completed' => 'Завершено',
                'sessions_completed' => 'Занятий завершено',
                'sessions_remaining' => 'Занятий осталось',
                'course_price' => 'Цена курса',
                'course_details' => 'Детали курса',
                'start_date' => 'Дата начала',
                'progress' => 'Прогресс',
                'sessions' => 'занятий',
                'contact_admin_to_enroll' => 'Свяжитесь с администрацией чтобы записать ребенка',
            ),
        );
    }

    public function get_available_languages() {
        return array(
            'pl' => array('name' => 'Polski', 'flag' => '🇵🇱'),
            'en' => array('name' => 'English', 'flag' => '🇬🇧'),
            'uk' => array('name' => 'Українська', 'flag' => '🇺🇦'),
            'ru' => array('name' => 'Русский', 'flag' => '🇷🇺'),
        );
    }
}

// Helper function for easy access
function ssm_t($key, $default = '') {
    return SSM_Translations::get_instance()->t($key, $default);
}

function ssm_lang() {
    return SSM_Translations::get_instance()->get_current_lang();
}

function ssm_languages() {
    return SSM_Translations::get_instance()->get_available_languages();
}
