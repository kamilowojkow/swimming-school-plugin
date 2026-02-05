# Szkółka Pływania - Aplikacja Mobilna

Aplikacja mobilna React Native (Expo) dla systemu zarządzania szkółką pływania.

## Wymagania

- Node.js 18+
- npm lub yarn
- Expo CLI (`npm install -g expo-cli`)
- Expo Go na telefonie (do testowania)

## Instalacja

```bash
cd mobile-app
npm install
```

## Konfiguracja

### 1. Adres API

Edytuj plik `src/api/client.ts` i zmień `API_BASE_URL` na adres swojego WordPressa:

```typescript
const API_BASE_URL = 'https://twoja-domena.pl/wp-json/ssm/v1';
```

### 2. Push Notifications (opcjonalne)

1. Utwórz konto na [Expo](https://expo.dev)
2. Utwórz projekt i skopiuj Project ID
3. Wklej Project ID w `App.tsx` i `app.json`

### 3. Identyfikatory aplikacji

W pliku `app.json` zmień:
- `ios.bundleIdentifier` - unikalny identyfikator dla iOS
- `android.package` - unikalny identyfikator dla Android

## Uruchomienie

```bash
# Tryb deweloperski
npm start

# Na iOS
npm run ios

# Na Android
npm run android
```

## Struktura projektu

```
mobile-app/
├── App.tsx                 # Główny komponent
├── app.json               # Konfiguracja Expo
├── package.json
└── src/
    ├── api/
    │   └── client.ts      # Klient API z obsługą tokenów
    ├── components/        # Współdzielone komponenty
    ├── hooks/             # Custom hooks
    ├── navigation/
    │   └── AppNavigator.tsx  # Nawigacja
    ├── screens/
    │   ├── auth/          # Ekrany logowania
    │   ├── parent/        # Ekrany dla rodziców
    │   ├── instructor/    # Ekrany dla instruktorów
    │   └── common/        # Wspólne ekrany
    ├── store/
    │   ├── authStore.ts   # Stan autentykacji (Zustand)
    │   └── notificationStore.ts
    └── utils/             # Funkcje pomocnicze
```

## Funkcjonalności

### Dla rodziców:
- Dashboard z podsumowaniem
- Lista dzieci i ich szczegóły
- Harmonogram zajęć
- Płatności
- Zgłaszanie nieobecności
- Odrabianie zajęć
- Osiągnięcia dzieci (gamifikacja)
- Powiadomienia push

### Dla instruktorów:
- Dashboard z dzisiejszymi zajęciami
- Harmonogram prowadzonych zajęć
- Sprawdzanie obecności
- Zarządzanie zastępstwami
- Podgląd wynagrodzenia
- Powiadomienia push

## Budowanie produkcyjne

### EAS Build (zalecane)

```bash
# Instalacja EAS CLI
npm install -g eas-cli

# Logowanie
eas login

# Build dla Android
eas build --platform android

# Build dla iOS
eas build --platform ios
```

### Lokalne budowanie

```bash
# Android APK
expo build:android -t apk

# iOS IPA (wymaga konta Apple Developer)
expo build:ios -t archive
```

## API Endpoints

Aplikacja korzysta z REST API pluginu WordPress:

| Endpoint | Opis |
|----------|------|
| `POST /auth/login` | Logowanie |
| `GET /user/me` | Dane użytkownika |
| `GET /parent/children` | Lista dzieci |
| `GET /parent/schedule` | Harmonogram |
| `GET /instructor/schedule` | Harmonogram instruktora |
| `POST /instructor/sessions/{id}/attendance` | Zapisanie obecności |

Pełna dokumentacja API w pliku `/includes/api/rest-api.php`.

## Bezpieczeństwo

- Tokeny są przechowywane w Expo SecureStore (keychain/keystore)
- Automatyczne odświeżanie tokenów (refresh token)
- HTTPS wymagane w produkcji

## Troubleshooting

### "Network Error" przy logowaniu
- Sprawdź czy adres API jest poprawny
- Upewnij się, że WordPress jest dostępny
- W trybie deweloperskim użyj IP zamiast localhost

### Push notifications nie działają
- Sprawdź uprawnienia w ustawieniach telefonu
- Upewnij się, że Project ID jest poprawny
- Push notifications nie działają w symulatorze iOS

## Licencja

Prywatna - tylko do użytku wewnętrznego.
