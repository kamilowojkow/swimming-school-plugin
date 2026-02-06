# Przewodnik publikacji aplikacji Szkółka Pływania

## Spis treści
1. [Wymagania wstępne](#wymagania-wstępne)
2. [Konfiguracja kont deweloperskich](#konfiguracja-kont-deweloperskich)
3. [Przygotowanie aplikacji](#przygotowanie-aplikacji)
4. [Budowanie aplikacji](#budowanie-aplikacji)
5. [Publikacja w Google Play](#publikacja-w-google-play)
6. [Publikacja w App Store](#publikacja-w-app-store)
7. [Checklista przed publikacją](#checklista-przed-publikacją)

---

## Wymagania wstępne

### Narzędzia
```bash
# Node.js (v18+)
node --version

# EAS CLI
npm install -g eas-cli

# Zaloguj się do Expo
eas login
```

### Pliki do uzupełnienia
1. `app.json` - uzupełnij:
   - `ios.bundleIdentifier` → np. `com.twojafirma.szkolkaplywania`
   - `android.package` → np. `com.twojafirma.szkolkaplywania`
   - `extra.eas.projectId` → ID projektu z Expo
   - `extra.apiUrl` → URL Twojego API WordPress

2. `eas.json` - uzupełnij:
   - `submit.production.ios.appleId` → Twój Apple ID
   - `submit.production.ios.ascAppId` → App Store Connect App ID
   - `submit.production.ios.appleTeamId` → Team ID

---

## Konfiguracja kont deweloperskich

### Google Play Console

1. **Utwórz konto** na [play.google.com/console](https://play.google.com/console)
   - Opłata jednorazowa: 25 USD
   - Wymagane dane firmy/osoby

2. **Utwórz aplikację**
   - Nazwa: "Szkółka Pływania"
   - Język domyślny: Polski
   - Typ: Aplikacja

3. **Utwórz Service Account dla automatycznego wysyłania**
   ```
   1. Google Cloud Console → IAM → Service Accounts
   2. Utwórz nowe konto serwisowe
   3. Nadaj rolę "Service Account User"
   4. Utwórz klucz JSON
   5. W Play Console → API Access → połącz konto
   6. Zapisz plik jako google-service-account.json
   ```

### Apple Developer Program

1. **Dołącz do programu** na [developer.apple.com](https://developer.apple.com)
   - Opłata roczna: 99 USD
   - Dla firm: wymagany D-U-N-S Number

2. **App Store Connect**
   - Utwórz nową aplikację
   - Bundle ID musi pasować do `app.json`

3. **Certyfikaty (EAS obsługuje automatycznie)**
   ```bash
   # EAS automatycznie zarządza certyfikatami
   eas credentials
   ```

---

## Przygotowanie aplikacji

### 1. Generowanie zasobów graficznych

```bash
# Zainstaluj sharp (do generowania obrazów)
npm install sharp

# Uruchom skrypt generowania
node scripts/generate-assets.js
```

Wymagane pliki:
- `assets/icon.png` (1024x1024)
- `assets/adaptive-icon.png` (1024x1024)
- `assets/splash.png` (1284x2778)
- `assets/notification-icon.png` (96x96)

### 2. Konfiguracja Firebase (powiadomienia push)

```bash
# Android - pobierz z Firebase Console
# Zapisz jako: google-services.json

# iOS - EAS automatycznie konfiguruje APNs
```

### 3. Polityka prywatności

1. Dostosuj plik `store-assets/privacy-policy-pl.html`
2. Wgraj na swój serwer (np. `https://twojadomena.pl/polityka-prywatnosci`)
3. Zaktualizuj URL w opisach sklepowych

---

## Budowanie aplikacji

### Development (testowanie lokalne)
```bash
eas build --profile development --platform all
```

### Preview (testy wewnętrzne)
```bash
eas build --profile preview --platform all

# Tylko Android APK
eas build --profile preview --platform android
```

### Production (do sklepów)
```bash
# Zwiększ wersję w app.json przed każdym buildem!
# version: "1.0.1"
# android.versionCode: 2
# ios.buildNumber: "2"

eas build --profile production --platform all
```

---

## Publikacja w Google Play

### 1. Przygotuj materiały
- [ ] Ikona: 512x512 PNG
- [ ] Feature graphic: 1024x500 PNG
- [ ] Screenshoty: min. 2, format 16:9 lub 9:16
- [ ] Krótki opis: max 80 znaków
- [ ] Pełny opis: max 4000 znaków

### 2. Wyślij build
```bash
eas submit --platform android
```

### 3. W Google Play Console
1. Przejdź do "Production" → "Create new release"
2. Dodaj notki o wydaniu
3. Sprawdź ostrzeżenia pre-launch report
4. Wyślij do przeglądu

### 4. Czas przeglądu
- Nowe aplikacje: 1-7 dni
- Aktualizacje: kilka godzin - 3 dni

---

## Publikacja w App Store

### 1. Przygotuj materiały
- [ ] Ikona: 1024x1024 PNG (bez zaokrągleń, bez przezroczystości)
- [ ] Screenshoty dla każdego rozmiaru urządzenia
- [ ] Opis i słowa kluczowe
- [ ] URL polityki prywatności

### 2. Wyślij build
```bash
eas submit --platform ios
```

### 3. W App Store Connect
1. Wybierz build w "TestFlight" lub "App Store"
2. Wypełnij wszystkie sekcje metadanych
3. Odpowiedz na pytania o eksport kryptografii (wybierz "No")
4. Wyślij do przeglądu

### 4. Czas przeglądu
- Nowe aplikacje: 1-3 dni (czasem dłużej)
- Aktualizacje: 24-48 godzin

---

## Checklista przed publikacją

### Techniczne
- [ ] Wersja zaktualizowana w `app.json`
- [ ] API URL wskazuje na produkcję
- [ ] Powiadomienia push działają
- [ ] Logowanie/wylogowanie działa
- [ ] Wszystkie ekrany działają poprawnie
- [ ] Testowano na prawdziwych urządzeniach
- [ ] Brak console.log w produkcji

### Prawne
- [ ] Polityka prywatności opublikowana i dostępna
- [ ] Regulamin (jeśli wymagany)
- [ ] Zgody RODO w aplikacji

### Google Play
- [ ] Ikona 512x512
- [ ] Feature graphic 1024x500
- [ ] Min. 2 screenshoty
- [ ] Krótki opis (80 znaków)
- [ ] Pełny opis (4000 znaków)
- [ ] Kategoria wybrana
- [ ] Content rating wypełniony
- [ ] Data Safety wypełniona
- [ ] Dane kontaktowe

### App Store
- [ ] Ikona 1024x1024
- [ ] Screenshoty dla wszystkich rozmiarów
- [ ] Opis i słowa kluczowe
- [ ] Podtytuł
- [ ] URL wsparcia
- [ ] URL polityki prywatności
- [ ] Age Rating wypełniony
- [ ] App Privacy wypełnione

---

## Rozwiązywanie problemów

### Build failed
```bash
# Sprawdź logi
eas build:view

# Wyczyść cache
npx expo start --clear
```

### Certyfikaty iOS
```bash
# Zarządzaj certyfikatami
eas credentials

# Zresetuj certyfikaty
eas credentials --platform ios
```

### Rejected przez sklep
1. Przeczytaj dokładnie powód odrzucenia
2. Napraw wskazane problemy
3. Odpowiedz na feedback w konsoli
4. Wyślij ponownie

---

## Aktualizacje OTA (Over-The-Air)

Dla drobnych zmian (bez zmian natywnego kodu):
```bash
eas update --branch production --message "Opis zmian"
```

---

## Kontakt i wsparcie

- Dokumentacja Expo: https://docs.expo.dev
- EAS Build: https://docs.expo.dev/build/introduction/
- EAS Submit: https://docs.expo.dev/submit/introduction/
