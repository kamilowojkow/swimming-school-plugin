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
