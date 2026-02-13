# Instalacja lokalna APK na Androidzie

## Metoda 1: EAS Build (zalecana)

### Wymagania
- Konto Expo (darmowe): https://expo.dev/signup
- EAS CLI zainstalowane globalnie

### Kroki

```bash
# 1. Zaloguj się do Expo
npx eas login

# 2. Skonfiguruj projekt (jednorazowo)
npx eas build:configure

# 3. Zbuduj APK
npm run build:apk
# lub bezpośrednio:
npx eas build --profile local-apk --platform android
```

### Po zakończeniu buildu
1. Otrzymasz link do pobrania APK
2. Otwórz link na telefonie lub pobierz na komputer
3. Prześlij APK na telefon (jeśli pobrano na komputer)
4. Na telefonie:
   - Włącz "Instalacja z nieznanych źródeł" w ustawieniach
   - Otwórz plik APK
   - Zainstaluj aplikację

---

## Metoda 2: Build lokalny (wymaga Android Studio)

### Wymagania
- Android Studio z Android SDK
- Java JDK 17
- Zmienne środowiskowe:
  - `ANDROID_HOME` wskazujący na Android SDK
  - `JAVA_HOME` wskazujący na JDK

### Kroki

```bash
# 1. Wygeneruj projekt natywny
npx expo prebuild --platform android

# 2. Zbuduj APK release
npm run build:apk:local
# lub:
cd android && ./gradlew assembleRelease

# 3. APK znajdziesz w:
# android/app/build/outputs/apk/release/app-release.apk
```

### Instalacja przez ADB
```bash
# Podłącz telefon przez USB (włącz debugowanie USB)
adb install android/app/build/outputs/apk/release/app-release.apk
```

---

## Metoda 3: Expo Go (tylko development)

Do szybkiego testowania bez budowania APK:

```bash
# Uruchom serwer deweloperski
npm start

# Na telefonie:
# 1. Zainstaluj aplikację "Expo Go" z Play Store
# 2. Zeskanuj kod QR wyświetlony w terminalu
```

**Uwaga:** Expo Go ma ograniczenia - nie działa z niektórymi natywnymi modułami.

---

## Rozwiązywanie problemów

### "Instalacja zablokowana"
1. Ustawienia → Bezpieczeństwo
2. Włącz "Nieznane źródła" lub "Instaluj nieznane aplikacje"
3. Zezwól przeglądarce/menedżerowi plików na instalację

### "Aplikacja nie została zainstalowana"
- Odinstaluj poprzednią wersję aplikacji
- Sprawdź czy masz wystarczająco miejsca
- Upewnij się, że APK jest dla właściwej architektury (arm64-v8a)

### Build failed - SDK not found
```bash
# Ustaw zmienną ANDROID_HOME
export ANDROID_HOME=$HOME/Android/Sdk
export PATH=$PATH:$ANDROID_HOME/platform-tools
```

---

## Szybki start (TL;DR)

```bash
# Najszybsza metoda - EAS Build
npx eas login
npm run build:apk
# → Pobierz APK z linku i zainstaluj na telefonie
```
