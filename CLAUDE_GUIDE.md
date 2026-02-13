# Przewodnik po projekcie Swimming School Plugin

## Opis projektu

System zarządzania szkółką pływania składający się z:
1. **Wtyczka WordPress** (`plugin/`) - backend, REST API, panel administracyjny
2. **Aplikacja mobilna React Native** (`mobile/`) - dla rodziców i instruktorów

## Struktura katalogów

```
swimming-school-plugin/
├── plugin/                              # Wtyczka WordPress
│   ├── swimming-school-manager.php      # Główny plik wtyczki
│   ├── swimming-school-mobile-api-v2.php # REST API dla aplikacji mobilnej
│   └── includes/
│       ├── class-ssm-installer.php      # Tworzenie tabel w bazie danych
│       └── ajax-handlers.php            # Obsługa AJAX w panelu admin
│
├── mobile/                              # Aplikacja React Native (Expo)
│   └── src/
│       ├── api/
│       │   └── client.ts                # Klient API - komunikacja z backendem
│       ├── store/
│       │   ├── authStore.ts             # Zustand store - autoryzacja, role użytkownika
│       │   ├── notificationStore.ts     # Zustand store - powiadomienia
│       │   └── settingsStore.ts         # Zustand store - ustawienia, tłumaczenia
│       ├── screens/
│       │   ├── parent/                  # Ekrany widoku rodzica
│       │   ├── instructor/              # Ekrany widoku instruktora
│       │   └── common/
│       │       └── NotificationsScreen.tsx  # Wspólny ekran powiadomień
│       ├── navigation/
│       │   └── AppNavigator.tsx         # Nawigacja, przełączanie widoków
│       └── components/
│           └── RoleSwitcher.tsx         # Komponent do przełączania ról
```

## Kluczowe koncepcje

### 1. System ról użytkowników

Użytkownicy mogą mieć **wiele ról jednocześnie** (np. być instruktorem I rodzicem):

- **Role WordPress**: `ssm_instructor`, `ssm_parent`, `administrator`
- **Tabele wtyczki**: `ssm_instructors`, `ssm_clients` - przechowują dane szczegółowe

**Ważne**: Rola WordPress decyduje o dostępie, ale tabele wtyczki przechowują ID używane do filtrowania (np. powiadomień).

### 2. Powiadomienia

Tabela `ssm_notifications` zawiera:
- `recipient_type`: `'instructor'`, `'parent'`, `'client'`, `'user'`
- `recipient_id`: ID odbiorcy (z tabeli instructors/clients lub user_id)

Filtrowanie powiadomień:
- Backend (`swimming-school-mobile-api-v2.php`) → funkcja `ssm_api_get_notifications()`
- Frontend przekazuje parametr `role` do API
- Backend używa `ssm_api_get_recipient_info()` do określenia `instructor_id` i `client_id`

### 3. Fallback dla użytkowników bez rekordów

Jeśli użytkownik ma rolę WordPress ale nie ma rekordu w tabeli wtyczki:
- Instruktor bez rekordu w `ssm_instructors` → używany jest `user_id` jako `instructor_id`
- Rodzic bez rekordu w `ssm_clients` → używany jest `user_id` jako `client_id`

## Ostatnio rozwiązane problemy

### Problem: Powiadomienia nie rozdzielają się między role

**Objawy**: Użytkownik z rolami instructor+parent widzi te same powiadomienia w obu widokach.

**Przyczyny i rozwiązania**:

1. **Brak odświeżania przy zmianie roli**
   - Plik: `mobile/src/screens/common/NotificationsScreen.tsx`
   - Rozwiązanie: `useFocusEffect` zamiast `useEffect`

2. **Priorytetyzacja roli instructor**
   - Pliki: `authStore.ts`, `swimming-school-mobile-api-v2.php`
   - Rozwiązanie: Dla multi-role users, domyślnie `parent`, nie priorytetyzuj żadnej roli

3. **Brak `client_id` dla administratorów**
   - Plik: `swimming-school-mobile-api-v2.php` → `ssm_api_get_recipient_info()`
   - Rozwiązanie: Fallback - jeśli ma rolę parent ale brak rekordu, użyj `user_id`

## Debugowanie

### Logi w aplikacji mobilnej

```typescript
// authStore.ts - przy logowaniu
console.log('User roles:', roles, 'Default type:', defaultType);

// authStore.ts - przy przełączaniu roli
console.log('Switching role from', get().activeRole, 'to', role);

// NotificationsScreen.tsx - przy pobieraniu powiadomień
console.log('NotificationsScreen: Screen focused, fetching for role:', activeRole);

// notificationStore.ts - odpowiedź API
console.log('Notifications API response:', JSON.stringify(data, null, 2));
```

### Debug info z API

Endpoint `/notifications` zwraca `_debug`:
```json
{
  "_debug": {
    "user_id": "1",
    "requested_role": "parent",
    "detected_type": "user",
    "has_multiple_roles": true,
    "active_role": "parent",
    "instructor_id": "1",
    "client_id": "1"
  },
  "notifications": [...]
}
```

## Baza danych - ważne tabele

```sql
-- Sprawdź powiadomienia
SELECT id, recipient_type, recipient_id, title FROM wp_ssm_notifications;

-- Sprawdź czy użytkownik ma rekord klienta
SELECT * FROM wp_ssm_clients WHERE user_id = 1 OR email = 'email@example.com';

-- Sprawdź czy użytkownik ma rekord instruktora
SELECT * FROM wp_ssm_instructors WHERE user_id = 1 OR email = 'email@example.com';
```

## Git workflow

- Branch deweloperski: `claude/move-plugin-code-jOzyv`
- Po zmianach: commit + push do tego brancha
- Wtyczka musi być ręcznie zaktualizowana w WordPress po zmianach w `plugin/`

## Częste problemy

1. **Wtyczka nie działa po zmianach** → Trzeba skopiować pliki do WordPress
2. **Aplikacja nie widzi zmian** → Przeładuj Expo (shake → Reload)
3. **Powiadomienia nie filtrują się** → Sprawdź `_debug` w odpowiedzi API
4. **`client_id` jest null** → Użytkownik nie ma rekordu w `ssm_clients` i nie ma roli parent
