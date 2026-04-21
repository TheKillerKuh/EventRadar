# EventRadar — Volleyball-Turnier-Plattform (Frontend + PHP API stubs)

Kurzanleitung zur lokalen Entwicklung und Deployment-Hinweise (IONOS/XAMPP).

Voraussetzungen
- Node.js (siehe `package.json` engines)
- PHP + MySQL (XAMPP, MAMP) für die API-Stubs

Entwicklung (Frontend)
1. Installieren:

```bash
npm install
```

2. Development-Server starten:

```bash
npm run dev
```

API-Stubs
- Die einfachen PHP-Endpunkte liegen im `api/`-Ordner:
  - `api/get_tournaments.php` — liest aus einer erwarteten `tournaments`-DB-Tabelle (liefert jetzt `calendar_event_id` zurück)
  - `api/sync_calendar.php` — synchronisiert Turniere mit Google Calendar mittels Service-Account (legt Events an/aktualisiert/löscht)
  - `api/upload_flyer.php` — akzeptiert Datei-Uploads (`flyer`) und erstellt Thumbnails (GD nötig). Dateien landen in `public/uploads/`.

DB-Migration
- Es gibt eine einfache Migration unter `api/migrations/001_add_calendar_event_id.sql` die die Spalte `calendar_event_id` zur Tabelle `tournaments` hinzufügt.

Konfiguration
- Lege eine nicht-versionierte Datei `api/credentials.php` an (oder bearbeite die vorhandene), um die DB-Zugangsdaten und `$GOOGLE_CALENDAR_ID` zu setzen. Beispiel ist bereits vorhanden, kopiere/fülle die Werte und lade `api/service-account.json` (Service-Account) hoch.

DB-Schema (Beispiel)

```sql
CREATE TABLE tournaments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  date DATE,
  time VARCHAR(20),
  mode VARCHAR(64),
  fee DECIMAL(8,2),
  organizer VARCHAR(255),
  location VARCHAR(255),
  registrationInfo TEXT,
  description TEXT
);
```

Google Calendar Integration
- `api/sync_calendar.php` ist aktuell ein Logging-Stub. Für echte Synchronisation implementiere serverseitig OAuth2 und rufe die Google Calendar API (Calendar.insert / events.update / events.delete).

Deployment auf IONOS
- Build-Ordner erzeugen: `npm run build`
- Den Inhalt von `dist/` zusammen mit dem `api/`-Ordner auf den IONOS-Webspace per FTP kopieren. Konfiguriere `db_connect.php` mit den Produktionsdaten.

Tests & Build
- Playwright ist bereits als Dev-Dependency konfiguriert. Verwende `npm run test:e2e` für E2E-Tests.

Was als Nächstes benötigt wird
- Produktions-DB-Zugangsdaten (Host, User, Pass, DB-Name)
- Google Cloud Project + OAuth2 Credentials (Client ID/Secret) falls Kalender-Sync erwünscht
- Optional: Design-Assets / Flyer-Beispiele für Uploads

Wenn du willst, implementiere ich jetzt die Google-API-Integration und die Auth-Endpoints — gib mir dafür bitte die gewünschten Hosting-/Credential-Details oder sag, ob ich ein Setup mit Umgebungsvariablen vorbereiten soll.
# EventRadar

This template should help get you started developing with Vue 3 in Vite.

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
  - [Turn on Custom Object Formatter in Chrome DevTools](http://bit.ly/object-formatters)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)
  - [Turn on Custom Object Formatter in Firefox DevTools](https://fxdx.dev/firefox-devtools-custom-object-formatters/)

## Type Support for `.vue` Imports in TS

TypeScript cannot handle type information for `.vue` imports by default, so we replace the `tsc` CLI with `vue-tsc` for type checking. In editors, we need [Volar](https://marketplace.visualstudio.com/items?itemName=Vue.volar) to make the TypeScript language service aware of `.vue` types.

## Customize configuration

See [Vite Configuration Reference](https://vite.dev/config/).

## Project Setup

```sh
npm install
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

### Type-Check, Compile and Minify for Production

```sh
npm run build
```

### Run End-to-End Tests with [Playwright](https://playwright.dev)

```sh
# Install browsers for the first run
npx playwright install

# When testing on CI, must build the project first
npm run build

# Runs the end-to-end tests
npm run test:e2e
# Runs the tests only on Chromium
npm run test:e2e -- --project=chromium
# Runs the tests of a specific file
npm run test:e2e -- tests/example.spec.ts
# Runs the tests in debug mode
npm run test:e2e -- --debug
```

### Lint with [ESLint](https://eslint.org/)

```sh
npm run lint
```
