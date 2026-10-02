# BranchNet Operations Guide

## 1. Menjalankan aplikasi

Pastikan `.env` memakai database yang sudah aktif:

```dotenv
DB_CONNECTION=mysql
QUEUE_CONNECTION=database
CACHE_STORE=database
APP_TIMEZONE=Asia/Jakarta
```

Jalankan tiga proses berikut pada terminal terpisah:

```bash
php artisan serve
php artisan queue:work --queue=network-critical,network-monitoring,default --tries=3 --timeout=120
php artisan schedule:work
```

Untuk server Linux, scheduler dapat dijalankan otomatis melalui cron setiap menit:

```cron
* * * * * cd /path/to/BranchNet && php artisan schedule:run >> /dev/null 2>&1
```

Template crontab siap pakai tersedia di `deploy/branchnet.crontab.example`.
Template tersebut juga menjalankan queue worker dengan `flock`, sehingga device
tetap diping dan worker cron tidak berjalan tumpang tindih. Ganti path aplikasi,
lalu pasang dengan:

```bash
crontab deploy/branchnet.crontab.example
```

Pada Windows/Laragon, gunakan Task Scheduler untuk menjalankan perintah berikut
setiap 1 menit, atau biarkan `php artisan schedule:work` tetap terbuka:

```text
php C:\laragon\www\BranchNet\artisan schedule:run
```

Untuk queue database, worker wajib tetap hidup:

```text
php artisan queue:work database --queue=network-critical,network-monitoring,default --tries=3 --timeout=120
```

Untuk Redis:

```dotenv
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

Lalu jalankan worker yang sama. Horizon bersifat opsional dan sebaiknya dijalankan pada Linux/WSL karena memerlukan `pcntl` dan `posix`.

## 2. Menu aplikasi

| Menu | URL | Fungsi |
|---|---|---|
| Dashboard | `/dashboard` | KPI, health cabang, alert prioritas |
| Branches | `/branches` | CRUD cabang, CIDR, gateway, status |
| Agents | `/agents` | CRUD agent lokal dan token |
| Devices | `/devices` | Inventory device lintas cabang |
| Network Map | `/network-map` | Topologi branch sederhana |
| Alerts | `/alerts` | CRUD dan resolve alert |
| Monitoring History | `/monitoring-history` | Pencarian raw monitoring logs |
| Laravel Pulse | `/pulse` | Observability aplikasi, queue, query, exception |
| Audit Logs | `/audit-logs` | Aktivitas administrasi |
| Settings | `/settings` | Runtime configuration dan command operasional |

Mode terang dapat diaktifkan melalui tombol matahari di header. Pilihan tema disimpan di `localStorage` browser.

## 2a. Mengetahui printer benar-benar diping

1. Tambahkan printer di **Branches → Devices → + Device**.
2. Pilih `Device type = printer`, masukkan IP printer, dan simpan.
3. Buka detail printer lalu klik **Ping sekarang**.
4. Pastikan worker queue sedang berjalan. Job akan masuk ke queue `network-monitoring`.
5. Refresh detail printer setelah beberapa detik.

Hasil terlihat pada:

- status device: `ONLINE` atau `OFFLINE`;
- `Last checked` dan `Last seen`;
- latency dalam milidetik;
- packet loss dalam persen;
- grafik performance;
- menu **Monitoring History**;
- `storage/logs/laravel.log` dengan event `network.device_checked`.

Perintah worker yang wajib aktif:

```bash
php artisan queue:work --queue=network-critical,network-monitoring,default --tries=3 --timeout=120
```

Penting: ping hanya menguji reachability ICMP. Printer dapat menjawab ping tetapi layanan print tetap bermasalah. Untuk validasi layanan printer, tambahkan TCP check pada agent untuk port 9100, 631, atau 515 sesuai tipe printer.

## 3. Membuat branch

Untuk mengambil identitas cabang dari database Sopiga Pro (`master.branch`), isi
konfigurasi berikut di `.env`:

```dotenv
DB2_CONNECTION=pgsql
DB2_HOST=10.10.10.8
DB2_PORT=5434
DB2_DATABASE=db_gadaimulia
DB2_USERNAME=it_gms
DB2_PASSWORD=your-password
SOPIGA_PG_SSLMODE=prefer
SOPIGA_BRANCH_TABLE=master.branch
SOPIGA_BRANCH_CODE_COLUMN=branch_code
SOPIGA_BRANCH_NAME_COLUMN=branch_name
SOPIGA_BRANCH_CITY_COLUMN=c.city_name
```

Lalu jalankan:

```bash
php artisan config:clear
php artisan sopiga:import-branches
```

Import hanya mengisi kode, nama, dan kota. `network_cidr` serta `gateway_ip`
tetap kosong untuk diisi manual.

1. Buka **Branches → Tambah cabang**.
2. Isi contoh:
   - Code: `PKU`
   - Network: `192.168.10.0/24`
   - Gateway: `192.168.10.1`
3. Simpan.

CIDR yang diterima adalah IPv4 `/16` sampai `/30`. Device di luar CIDR akan ditolak oleh server.

## 4. Membuat agent

1. Buka **Agents → Tambah agent**.
2. Pilih branch dan isi metadata agent.
3. Kosongkan token untuk generate token otomatis, atau masukkan token sendiri minimal 20 karakter.
4. Salin token dari flash message dan simpan hanya di agent lokal.

Server hanya menyimpan hash token. Jika token hilang, edit agent dan isi token baru untuk melakukan rotation.

Request heartbeat:

```http
POST /api/agent/heartbeat
Authorization: Bearer AGENT_TOKEN
Content-Type: application/json

{
  "agent_id": 1,
  "branch_id": 1,
  "hostname": "agent-pku-01",
  "ip_address": "192.168.10.10",
  "version": "1.0.0",
  "timestamp": "2026-10-01T14:00:00+07:00"
}
```

Request report:

```http
POST /api/agent/report
Authorization: Bearer AGENT_TOKEN
Content-Type: application/json

{
  "branch_id": 1,
  "devices": [
    {"ip_address": "192.168.10.1", "online": true, "latency_ms": 1.8, "packet_loss": 0},
    {"ip_address": "192.168.10.55", "online": true, "latency_ms": 4.2, "packet_loss": 0, "mac_address": "AA:BB:CC:DD:EE:FF", "hostname": "printer-pku-01"}
  ]
}
```

Agent melakukan ping/ARP/TCP check di jaringan lokalnya. Report hanya memasukkan job ke queue; central server tidak perlu dan tidak boleh memiliki route langsung ke subnet internal cabang.

## 5. Monitoring lifecycle

- Full subnet scan otomatis dinonaktifkan; hanya device yang ditambahkan manual yang dimonitor.
- Device critical: setiap 1 menit.
- Semua device: setiap 1 menit (critical dan normal).
- Agent heartbeat status: setiap 1 menit.
- Jika branch berstatus `unreachable`, scheduled device checks dilewati agar sistem tidak salah menyimpulkan semua device offline. Tombol **Ping sekarang** tetap dapat memaksa satu pengecekan manual.
- Raw logs cleanup: harian, default 30 hari.
- Scan branch memakai `WithoutOverlapping`, sehingga branch yang sama tidak discan bersamaan.
- Device yang ditemukan agent tetapi belum terdaftar dibuat sebagai `Unknown Device`.
- Alert active dideduplicate berdasarkan branch, device, type, dan status active.
- Device online kembali akan me-resolve alert aktifnya.

## 6. Troubleshooting

### Dashboard kosong

Jalankan:

```bash
php artisan db:seed
php artisan queue:work --queue=network-critical,network-monitoring,default
```

### Branch unreachable

Pastikan agent mengirim heartbeat, bearer token benar, dan `agent_last_seen` tidak lebih lama dari tiga menit.

### Queue tidak memproses

Pastikan worker aktif dan cek:

```bash
php artisan queue:failed
php artisan queue:retry all
```

### Log terlalu besar

Jalankan cleanup manual:

```bash
php artisan network:cleanup-logs
```

### Pulse tidak tampil

Pastikan migration Pulse sudah dijalankan dan akses `/pulse` memakai user yang diizinkan oleh Pulse middleware. BranchNet sudah mengizinkan class cache yang dipakai Pulse (`Collection`, `Eloquent Collection`, `CarbonImmutable`, `CarbonInterval`, dan `stdClass`) karena Laravel default memblokir unserialize object cache.

Jika Pulse sebelumnya pernah error, jalankan:

```bash
php artisan config:clear
php artisan cache:clear
php artisan pulse:clear
php artisan config:cache
```

## 7. Security checklist production

- Wajib gunakan HTTPS.
- Jangan expose jaringan internal branch ke internet.
- Simpan agent token hanya pada agent dan secret manager.
- Batasi akses `/branches`, `/agents`, `/settings`, dan CRUD melalui authentication/policy sebelum production.
- Gunakan Redis untuk queue/cache jika traffic monitoring besar.
- Jalankan Horizon di Linux/WSL jika membutuhkan queue dashboard.
- Backup database dan monitor failed jobs.
