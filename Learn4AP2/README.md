# AP2-Karteikarten

Lokale Lern-App zur Vorbereitung auf die IHK-Abschlussprüfung Teil 2 für Fachinformatiker Anwendungsentwicklung. Die App verwendet Laravel, Livewire, SQLite und reines CSS.

## Einrichtung

Voraussetzungen: PHP 8.3 oder neuer, Composer, Node.js und npm.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite | Out-Null }
php artisan migrate
npm ci
```

## Entwicklung und Tests

```powershell
composer run dev
composer test
vendor/bin/pint
```

Projektumfang und Tickets stehen in [backlog.md](./backlog.md); den aktuellen Arbeitsstand dokumentiert [STATUS.md](./STATUS.md).
