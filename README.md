# BranchNet — Network Monitoring Dashboard Multi Cabang

BranchNet adalah central Laravel 13 dashboard untuk memonitor subnet cabang tanpa membuka jaringan internal ke internet. Agent lokal mengirim heartbeat/report melalui HTTPS dengan bearer token ter-hash; pekerjaan scanning diproses queue.

## Docker Compose

Salin `.env.docker.example` menjadi `.env`, isi `APP_KEY`, `DOCKER_NETWORK`, `DB_HOST`, dan kredensial database sesuai database yang sudah berjalan di Docker network Anda, lalu jalankan:

```bash
docker compose up -d --build
```

Aplikasi tersedia di `http://localhost:8001`. Compose hanya menjalankan web app, queue worker, dan scheduler; database tidak dibuat oleh Compose ini. Untuk melihat log gunakan `docker compose logs -f app worker scheduler`.

## Stack

- Laravel 13 / PHP 8.3+
- Blade + Vite/Tailwind-ready CSS, Livewire 4 tersedia
- Chart.js pada halaman detail device
- Laravel Pulse 1.8 dengan custom `App\Pulse\NetworkPulseRecorder`
- Laravel Scheduler + database queue; Redis dapat dipilih melalui `.env`
- Horizon opsional pada Linux/WSL (Windows tidak menyediakan `pcntl`/`posix`)

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Set `DB_CONNECTION=mysql` atau `pgsql` dan kredensial database yang aktif. Untuk deployment production, gunakan HTTPS dan Redis:

```dotenv
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=phpredis
MONITORING_LOG_RETENTION_DAYS=30
```

Jalankan proses aplikasi:

```bash
php artisan serve
php artisan queue:work --queue=network-critical,network-monitoring,default --tries=3 --timeout=120
php artisan schedule:work
```

Pulse tersedia di `/pulse` setelah migration Pulse. Di Linux/WSL, Horizon dapat ditambahkan dengan `composer require laravel/horizon` lalu `php artisan horizon`; package tidak dipaksa di Windows karena ekstensi worker tersebut tidak tersedia.

## Fase implementasi

1. **Project setup** — Laravel 13, Vite, Livewire, Pulse dan konfigurasi queue/cache dasar.
2. **Database & model** — migration `2026_10_01_000003_create_network_monitoring_tables.php`, migration Pulse, serta model Branch, NetworkDevice, MonitoringLog, NetworkAgent, Alert.
3. **Authentication/authorization** — Laravel app siap dipasang Sanctum/Policies; endpoint agent memakai bearer token ter-hash. Role policy dapat ditambahkan pada model User untuk Super Admin, Network Admin, Viewer.
4. **Branch management** — model branch, relasi devices/agents/alerts, health summary, CIDR/gateway menjadi sumber konfigurasi scan.
5. **Agent API** — `POST /api/agent/heartbeat` dan `POST /api/agent/report`, validasi request, rate limit 60/minute, branch scoping, dan audit-ready controller.
6. **Device discovery** — IP baru dibuat sebagai `Unknown Device`, `unknown`, `is_registered=false`; register via `POST /api/devices/{device}/register`.
7. **Monitoring queue** — `ScanBranchNetworkJob`, `CheckDeviceStatusJob`, `ProcessAgentReportJob`, `ResolveNetworkAlertJob`; timeout, retry, queue names, logging hooks, and `WithoutOverlapping`.
8. **Dashboard** — `/dashboard`, filter branch, KPI cards, branch health cards, priority alerts, topology overview, responsive NOC visual design.
9. **Alert system** — `AlertService` memakai `firstOrCreate` untuk dedup active alert, resolve saat device kembali online, critical/gateway offline handling.
10. **Laravel Pulse** — Pulse config + database migration, built-in request/job/exception/query/cache/server recorders dan `/pulse`.
11. **Custom Pulse recorder** — `NetworkPulseRecorder` mengirim `network_scan` metrics: successful/failed scans dan duration avg/max; branch/device/agent/alert metrics disediakan oleh dashboard query service agar Pulse tidak menjadi database device.
12. **Network topology** — simplified topology pada dashboard: Internet → Router → Core Switch → PCs/CCTV/Printers, dengan node status colors.
13. **Optimization & retention** — aggregate query/cache 15 detik, eager loading, indexed logs, pagination, scheduled `network:cleanup-logs` untuk raw logs 30 hari. Hourly/daily rollup dapat ditambahkan tanpa mengubah raw ingestion.

## Routes utama

| Route | Kegunaan |
|---|---|
| `/dashboard` | Central NOC dashboard |
| `/branches/{branch}/devices` | Search, filter, sort, pagination device |
| `/devices/{device}` | Device detail + Chart.js latency/packet loss |
| `/pulse` | Laravel Pulse application observability |
| `/api/agent/heartbeat` | Agent heartbeat |
| `/api/agent/report` | Asynchronous agent report |
| `/api/branches`, `/api/branches/{branch}` | Branch API |
| `/api/branches/{branch}/devices` | Branch device API |
| `/api/devices/{device}`, `/history` | Device API/history |
| `/api/devices/{device}/register` | Register unknown device |
| `/api/alerts` | Active/resolved alerts |

Dokumentasi operasional lengkap tersedia di [docs/OPERATIONS.md](C:\laragon\www\BranchNet\docs\OPERATIONS.md), termasuk cara membuat branch/agent, payload API, queue, scheduler, troubleshooting, dan security checklist.

## CRUD admin

UI CRUD yang tersedia tanpa perlu menulis query manual:

- `/branches` — create, edit, delete cabang; delete memakai cascade data cabang.
- `/branches/{branch}/devices` — list/filter device; tombol create, edit, delete device.
- `/agents` — create, edit, delete monitoring agent; token baru di-generate otomatis dan hanya hash yang disimpan.
- `/alerts` — create, edit, resolve, delete alert.

Semua form memakai CSRF, Laravel validation, flash message, confirmation sebelum delete, dan error validation. Device CRUD menolak IP di luar `network_cidr` cabang.

## Agent security contract

Agent mengirim `Authorization: Bearer <token>`. Token disimpan sebagai hash di `network_agents.token`; server mencocokkan agent dengan `branch_id`, sehingga token tidak dapat digunakan untuk cabang lain. `NetworkScannerService` hanya membentuk host dari `branches.network_cidr` yang dikonfigurasi administrator dan menolak CIDR invalid. Jangan expose endpoint scan arbitrary IP dan selalu gunakan HTTPS.

## Verifikasi lokal

```bash
php artisan route:list
php artisan test
npm run build
```

Migration tidak dapat dijalankan pada environment tanpa PDO SQLite/MySQL/PGSQL driver; jalankan setelah driver/database production tersedia.
