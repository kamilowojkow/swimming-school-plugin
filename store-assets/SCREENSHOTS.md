# Przewodnik tworzenia screenshotów

## Wymagane rozmiary

### iOS App Store

| Urządzenie | Rozmiar (px) | Wymagane |
|------------|--------------|----------|
| iPhone 6.7" (14 Pro Max, 15 Pro Max) | 1290 x 2796 | Tak |
| iPhone 6.5" (11 Pro Max, XS Max) | 1242 x 2688 | Tak |
| iPhone 5.5" (8 Plus, 7 Plus) | 1242 x 2208 | Tak |
| iPad Pro 12.9" (6th gen) | 2048 x 2732 | Opcjonalne |
| iPad Pro 12.9" (2nd gen) | 2048 x 2732 | Opcjonalne |

**Ilość:** 3-10 screenshotów na rozmiar

### Google Play Store

| Typ | Rozmiar (px) | Wymagane |
|-----|--------------|----------|
| Telefon | 1080 x 1920 (min) | Tak (min. 2) |
| Tablet 7" | 1080 x 1920 | Opcjonalne |
| Tablet 10" | 1920 x 1200 | Opcjonalne |

**Ilość:** 2-8 screenshotów na typ

---

## Zalecane ekrany do screenshotów

### Dla rodziców (5-6 screenshotów)

1. **Dashboard** - główny ekran z podsumowaniem
   - Pokaż: powitanie, statystyki, nadchodzące zajęcia

2. **Lista dzieci** - karty dzieci z poziomami
   - Pokaż: 2-3 dzieci, różne poziomy (Żółwik, Delfinek)

3. **Szczegóły dziecka** - zakładka z osiągnięciami
   - Pokaż: odznaki, punkty, poziom pływania

4. **Harmonogram** - widok tygodniowy
   - Pokaż: kilka zaplanowanych zajęć, kolorowe znaczniki dzieci

5. **Płatności** - ekran z oczekującymi płatnościami
   - Pokaż: karta płatności, przycisk "Zapłać online"

6. **Nieobecności** - modal zgłaszania
   - Pokaż: formularz zgłoszenia nieobecności

### Dla instruktorów (4-5 screenshotów)

1. **Dashboard** - panel instruktora
   - Pokaż: statystyki, dzisiejsze zajęcia

2. **Harmonogram** - grafik tygodniowy
   - Pokaż: zajęcia z liczbą uczestników

3. **Obecność** - sprawdzanie obecności
   - Pokaż: lista uczestników z przyciskami statusów

4. **Zastępstwa** - dostępne zastępstwa
   - Pokaż: lista z przyciskiem "Przyjmij"

5. **Wynagrodzenie** - podsumowanie miesięczne
   - Pokaż: suma, liczba godzin, stawka

---

## Jak robić screenshoty

### Metoda 1: Symulator Xcode (iOS)

```bash
# Uruchom aplikację na symulatorze
npx expo start --ios

# W symulatorze:
# Cmd + S = zapisz screenshot
# Lub: Device → Screenshots
```

### Metoda 2: Android Studio Emulator

```bash
# Uruchom aplikację na emulatorze
npx expo start --android

# W emulatorze:
# Kliknij ikonę aparatu w pasku narzędzi
# Lub: Ctrl + S
```

### Metoda 3: Prawdziwe urządzenie

**iOS:**
- Power + Volume Up (iPhone X i nowsze)
- Power + Home (starsze modele)

**Android:**
- Power + Volume Down

---

## Wskazówki dotyczące screenshotów

### ✅ DO
- Użyj przykładowych danych, które wyglądają realistycznie
- Pokaż główne funkcje aplikacji
- Używaj polskich nazw i dat
- Upewnij się, że pasek stanu jest widoczny
- Użyj jasnego motywu dla lepszej czytelności

### ❌ DON'T
- Nie pokazuj pustych ekranów
- Nie używaj prawdziwych danych użytkowników
- Nie pokazuj błędów lub stanów ładowania
- Nie używaj zrzutów z różnych urządzeń w jednej serii

---

## Mockup i ramki

### Narzędzia do dodawania ramek urządzeń

1. **Shots.so** (darmowe online)
   - https://shots.so/

2. **Mockup World** (darmowe)
   - https://www.mockupworld.co/

3. **AppLaunchpad** (płatne)
   - https://theapplaunchpad.com/

4. **Sketch/Figma** z mockupami urządzeń

---

## Teksty na screenshotach

Możesz dodać nakładki tekstowe:

### Ekran 1: Dashboard
**Nagłówek:** "Wszystko w jednym miejscu"
**Podtytuł:** "Harmonogram, płatności i postępy dzieci"

### Ekran 2: Lista dzieci
**Nagłówek:** "Śledź postępy"
**Podtytuł:** "Poziomy, punkty i osiągnięcia"

### Ekran 3: Harmonogram
**Nagłówek:** "Planuj z łatwością"
**Podtytuł:** "Kalendarz wszystkich zajęć"

### Ekran 4: Płatności
**Nagłówek:** "Płać wygodnie"
**Podtytuł:** "Szybkie płatności online"

### Ekran 5: Powiadomienia
**Nagłówek:** "Bądź na bieżąco"
**Podtytuł:** "Powiadomienia o zmianach i terminach"

---

## Struktura katalogów

```
store-assets/
├── screenshots/
│   ├── iphone-6.7/
│   │   ├── 01-dashboard.png
│   │   ├── 02-children.png
│   │   ├── 03-schedule.png
│   │   ├── 04-payments.png
│   │   └── 05-notifications.png
│   ├── iphone-6.5/
│   │   └── ...
│   ├── iphone-5.5/
│   │   └── ...
│   ├── android-phone/
│   │   └── ...
│   └── ipad/
│       └── ...
├── google-play/
│   ├── icon-512.png
│   └── feature-graphic.png
└── app-store/
    └── icon-1024.png
```

---

## Feature Graphic (Google Play)

**Rozmiar:** 1024 x 500 px

### Zawartość:
- Logo/ikona aplikacji
- Nazwa "Szkółka Pływania"
- Hasło reklamowe
- Opcjonalnie: ilustracja pływaka/wody

### Przykładowe hasła:
- "Nauka pływania w Twoich rękach"
- "Zarządzaj zajęciami z łatwością"
- "Harmonogram • Płatności • Postępy"
