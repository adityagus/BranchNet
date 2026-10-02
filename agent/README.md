# BranchNet Local Agent

Agent ini mengirim heartbeat setiap menit dan melakukan ping ke daftar device yang dikonfigurasi.

## Setup

1. Di dashboard buka **Agents -> Tambah agent**.
2. Pilih branch Bogor dan simpan agent.
3. Salin token yang muncul di pesan sukses. Token hanya ditampilkan saat dibuat/di-rotate.
4. Copy `config.example.json` menjadi `config.json`.
5. Isi `token`, `agent_id`, `branch_id`, `central_url`, dan IP device yang ingin dipantau.

Contoh jika server pusat dan agent berada pada komputer yang sama:

```json
{
  "central_url": "http://127.0.0.1:8000",
  "token": "TOKEN_DARI_DASHBOARD",
  "agent_id": 1,
  "branch_id": 1,
  "hostname": "agent-bogor",
  "ip_address": "192.168.10.10",
  "version": "1.0.0",
  "interval_seconds": 60,
  "devices": [
    {"ip_address": "192.168.10.1", "hostname": "gateway-bogor", "vendor": "Router"}
  ]
}
```

`ip_address` agent adalah IP komputer yang menjalankan script. `gateway_ip` adalah IP router; keduanya tidak perlu sama.

## Menjalankan Windows

Pastikan PHP CLI tersedia, lalu jalankan:

```powershell
php agent.php config.json
```

Atau klik `run-agent.bat`. Biarkan terminal agent tetap hidup. Untuk otomatis saat Windows startup, daftarkan `run-agent.bat` melalui Task Scheduler.

## Menjalankan Linux/WSL

```bash
php agent.php config.json
```

Untuk production, jalankan agent sebagai service Supervisor/systemd. Agent harus dapat mengakses URL central dan perangkat di subnet branch.
