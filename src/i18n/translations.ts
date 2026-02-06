export type Language = 'pl' | 'en';

export const translations = {
  pl: {
    // Common
    common: {
      loading: 'Ładowanie...',
      error: 'Błąd',
      save: 'Zapisz',
      cancel: 'Anuluj',
      confirm: 'Potwierdź',
      delete: 'Usuń',
      edit: 'Edytuj',
      back: 'Wróć',
      next: 'Dalej',
      done: 'Gotowe',
      search: 'Szukaj',
      noData: 'Brak danych',
      refresh: 'Odśwież',
      yes: 'Tak',
      no: 'Nie',
    },

    // Auth
    auth: {
      login: 'Zaloguj się',
      logout: 'Wyloguj',
      email: 'Email',
      password: 'Hasło',
      forgotPassword: 'Zapomniałem hasła',
      loginError: 'Błąd logowania',
      invalidCredentials: 'Nieprawidłowy login lub hasło',
      enterCredentials: 'Podaj login i hasło',
    },

    // Navigation
    nav: {
      dashboard: 'Panel główny',
      children: 'Dzieci',
      schedule: 'Harmonogram',
      payments: 'Płatności',
      more: 'Więcej',
      substitutions: 'Zastępstwa',
      salary: 'Wynagrodzenie',
      notifications: 'Powiadomienia',
      profile: 'Profil',
      settings: 'Ustawienia',
      attendance: 'Obecność',
    },

    // Role Switcher
    roles: {
      currentView: 'Aktualny widok',
      parent: 'Rodzic',
      instructor: 'Instruktor',
      switchTo: 'Przełącz na',
    },

    // Parent Dashboard
    parentDashboard: {
      greeting: 'Cześć',
      todayLessons: 'Dzisiejsze zajęcia',
      noLessonsToday: 'Brak zajęć na dziś',
      upcomingPayments: 'Nadchodzące płatności',
      viewSchedule: 'Harmonogram',
      viewAll: 'Zobacz wszystkie',
    },

    // Instructor Dashboard
    instructorDashboard: {
      todaySessions: 'Dzisiejsze zajęcia',
      noSessionsToday: 'Brak zajęć na dziś',
      availableSubstitutions: 'Dostępne zastępstwa',
      viewAll: 'Zobacz wszystkie',
      lessons: 'Zajęcia',
      hours: 'Godziny',
      take: 'Weź',
      forInstructor: 'Za',
    },

    // Schedule
    schedule: {
      noLessons: 'Brak zajęć',
      participants: 'uczestników',
      attendanceChecked: 'Obecność sprawdzona',
      checkAttendance: 'Sprawdź obecność',
    },

    // Attendance
    attendance: {
      title: 'Obecność',
      present: 'Obecny',
      absent: 'Nieobecny',
      late: 'Spóźniony',
      excused: 'Usprawiedliwiony',
      unmarked: 'Nieoznaczony',
      saveAttendance: 'Zapisz obecność',
      saving: 'Zapisywanie...',
      saved: 'Zapisano',
      notes: 'Notatki',
      addNote: 'Dodaj notatkę',
      medicalNotes: 'Uwagi medyczne',
      allPresent: 'Wszyscy obecni',
      allAbsent: 'Wszyscy nieobecni',
      summary: 'Podsumowanie',
    },

    // Substitutions
    substitutions: {
      available: 'Dostępne',
      myRequests: 'Moje prośby',
      myTaken: 'Przyjęte',
      requestSubstitution: 'Poproś o zastępstwo',
      takeSubstitution: 'Weź zastępstwo',
      reason: 'Powód',
      noSubstitutions: 'Brak zastępstw',
      selectSession: 'Wybierz zajęcia',
      submit: 'Wyślij',
      substitutionTaken: 'Zastępstwo przyjęte',
      substitutionRequested: 'Prośba wysłana',
    },

    // Salary
    salary: {
      title: 'Wynagrodzenie',
      toPay: 'Do wypłaty',
      sessions: 'Zajęć',
      hoursWorked: 'Godzin',
      hourlyRate: 'Za godzinę',
      conductedSessions: 'Przeprowadzone zajęcia',
      noSessionsThisMonth: 'Brak zajęć w tym miesiącu',
    },

    // Payments
    payments: {
      title: 'Płatności',
      pending: 'Do zapłaty',
      paid: 'Zapłacone',
      overdue: 'Zaległe',
      all: 'Wszystkie',
      dueDate: 'Termin',
      amount: 'Kwota',
      history: 'Historia płatności',
      noPayments: 'Brak płatności',
      payNow: 'Zapłać teraz',
    },

    // Children
    children: {
      title: 'Dzieci',
      level: 'Poziom',
      courses: 'Kursy',
      achievements: 'Osiągnięcia',
      attendance: 'Frekwencja',
      progress: 'Postępy',
      noChildren: 'Brak dzieci',
    },

    // Absences
    absences: {
      title: 'Nieobecności',
      reportAbsence: 'Zgłoś nieobecność',
      upcomingSessions: 'Nadchodzące zajęcia',
      myAbsences: 'Moje nieobecności',
      makeupOptions: 'Możliwości odrabiania',
      scheduleMakeup: 'Zaplanuj odrabianie',
      reason: 'Powód',
      noAbsences: 'Brak nieobecności',
      confirmed: 'Potwierdzone',
      pending: 'Oczekujące',
    },

    // Notifications
    notifications: {
      title: 'Powiadomienia',
      noNotifications: 'Brak powiadomień',
      markAllRead: 'Oznacz wszystkie jako przeczytane',
      today: 'Dzisiaj',
      earlier: 'Wcześniej',
    },

    // Settings
    settings: {
      title: 'Ustawienia',
      language: 'Język',
      theme: 'Motyw',
      lightTheme: 'Jasny',
      darkTheme: 'Ciemny',
      systemTheme: 'Systemowy',
      notifications: 'Powiadomienia',
      pushNotifications: 'Powiadomienia push',
      emailNotifications: 'Powiadomienia email',
      about: 'O aplikacji',
      version: 'Wersja',
      privacyPolicy: 'Polityka prywatności',
      termsOfService: 'Regulamin',
    },

    // Profile
    profile: {
      title: 'Profil',
      firstName: 'Imię',
      lastName: 'Nazwisko',
      email: 'Email',
      phone: 'Telefon',
      address: 'Adres',
      changePassword: 'Zmień hasło',
      currentPassword: 'Aktualne hasło',
      newPassword: 'Nowe hasło',
      confirmPassword: 'Potwierdź hasło',
      saveChanges: 'Zapisz zmiany',
    },

    // Time
    time: {
      today: 'Dzisiaj',
      yesterday: 'Wczoraj',
      tomorrow: 'Jutro',
      hoursAgo: 'godzin temu',
      minutesAgo: 'minut temu',
      daysAgo: 'dni temu',
    },
  },

  en: {
    // Common
    common: {
      loading: 'Loading...',
      error: 'Error',
      save: 'Save',
      cancel: 'Cancel',
      confirm: 'Confirm',
      delete: 'Delete',
      edit: 'Edit',
      back: 'Back',
      next: 'Next',
      done: 'Done',
      search: 'Search',
      noData: 'No data',
      refresh: 'Refresh',
      yes: 'Yes',
      no: 'No',
    },

    // Auth
    auth: {
      login: 'Log in',
      logout: 'Log out',
      email: 'Email',
      password: 'Password',
      forgotPassword: 'Forgot password',
      loginError: 'Login error',
      invalidCredentials: 'Invalid login or password',
      enterCredentials: 'Enter login and password',
    },

    // Navigation
    nav: {
      dashboard: 'Dashboard',
      children: 'Children',
      schedule: 'Schedule',
      payments: 'Payments',
      more: 'More',
      substitutions: 'Substitutions',
      salary: 'Salary',
      notifications: 'Notifications',
      profile: 'Profile',
      settings: 'Settings',
      attendance: 'Attendance',
    },

    // Role Switcher
    roles: {
      currentView: 'Current view',
      parent: 'Parent',
      instructor: 'Instructor',
      switchTo: 'Switch to',
    },

    // Parent Dashboard
    parentDashboard: {
      greeting: 'Hello',
      todayLessons: "Today's lessons",
      noLessonsToday: 'No lessons today',
      upcomingPayments: 'Upcoming payments',
      viewSchedule: 'Schedule',
      viewAll: 'View all',
    },

    // Instructor Dashboard
    instructorDashboard: {
      todaySessions: "Today's sessions",
      noSessionsToday: 'No sessions today',
      availableSubstitutions: 'Available substitutions',
      viewAll: 'View all',
      lessons: 'Lessons',
      hours: 'Hours',
      take: 'Take',
      forInstructor: 'For',
    },

    // Schedule
    schedule: {
      noLessons: 'No lessons',
      participants: 'participants',
      attendanceChecked: 'Attendance checked',
      checkAttendance: 'Check attendance',
    },

    // Attendance
    attendance: {
      title: 'Attendance',
      present: 'Present',
      absent: 'Absent',
      late: 'Late',
      excused: 'Excused',
      unmarked: 'Unmarked',
      saveAttendance: 'Save attendance',
      saving: 'Saving...',
      saved: 'Saved',
      notes: 'Notes',
      addNote: 'Add note',
      medicalNotes: 'Medical notes',
      allPresent: 'All present',
      allAbsent: 'All absent',
      summary: 'Summary',
    },

    // Substitutions
    substitutions: {
      available: 'Available',
      myRequests: 'My requests',
      myTaken: 'Taken',
      requestSubstitution: 'Request substitution',
      takeSubstitution: 'Take substitution',
      reason: 'Reason',
      noSubstitutions: 'No substitutions',
      selectSession: 'Select session',
      submit: 'Submit',
      substitutionTaken: 'Substitution taken',
      substitutionRequested: 'Request sent',
    },

    // Salary
    salary: {
      title: 'Salary',
      toPay: 'To pay',
      sessions: 'Sessions',
      hoursWorked: 'Hours',
      hourlyRate: 'Per hour',
      conductedSessions: 'Conducted sessions',
      noSessionsThisMonth: 'No sessions this month',
    },

    // Payments
    payments: {
      title: 'Payments',
      pending: 'Pending',
      paid: 'Paid',
      overdue: 'Overdue',
      all: 'All',
      dueDate: 'Due date',
      amount: 'Amount',
      history: 'Payment history',
      noPayments: 'No payments',
      payNow: 'Pay now',
    },

    // Children
    children: {
      title: 'Children',
      level: 'Level',
      courses: 'Courses',
      achievements: 'Achievements',
      attendance: 'Attendance',
      progress: 'Progress',
      noChildren: 'No children',
    },

    // Absences
    absences: {
      title: 'Absences',
      reportAbsence: 'Report absence',
      upcomingSessions: 'Upcoming sessions',
      myAbsences: 'My absences',
      makeupOptions: 'Makeup options',
      scheduleMakeup: 'Schedule makeup',
      reason: 'Reason',
      noAbsences: 'No absences',
      confirmed: 'Confirmed',
      pending: 'Pending',
    },

    // Notifications
    notifications: {
      title: 'Notifications',
      noNotifications: 'No notifications',
      markAllRead: 'Mark all as read',
      today: 'Today',
      earlier: 'Earlier',
    },

    // Settings
    settings: {
      title: 'Settings',
      language: 'Language',
      theme: 'Theme',
      lightTheme: 'Light',
      darkTheme: 'Dark',
      systemTheme: 'System',
      notifications: 'Notifications',
      pushNotifications: 'Push notifications',
      emailNotifications: 'Email notifications',
      about: 'About',
      version: 'Version',
      privacyPolicy: 'Privacy policy',
      termsOfService: 'Terms of service',
    },

    // Profile
    profile: {
      title: 'Profile',
      firstName: 'First name',
      lastName: 'Last name',
      email: 'Email',
      phone: 'Phone',
      address: 'Address',
      changePassword: 'Change password',
      currentPassword: 'Current password',
      newPassword: 'New password',
      confirmPassword: 'Confirm password',
      saveChanges: 'Save changes',
    },

    // Time
    time: {
      today: 'Today',
      yesterday: 'Yesterday',
      tomorrow: 'Tomorrow',
      hoursAgo: 'hours ago',
      minutesAgo: 'minutes ago',
      daysAgo: 'days ago',
    },
  },
} as const;

export type TranslationKeys = typeof translations.pl;
